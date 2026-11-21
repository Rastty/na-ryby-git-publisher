#!/usr/bin/env python3
"""GET-only, exact-main release receipts to GitHub issue #349. Never deploys."""
from __future__ import annotations

import importlib.util
import json
import os
import re
import sys
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.request import Request, urlopen

REPO = "Rastty/na-ryby-git-publisher"
WORKFLOW = "Na-Ryby Guarded Deployer Push-to-Deploy"
ISSUE = 349
VERSION = "NRY_RELEASE_LEDGER_V1"
EXPECTED = {
    "map": ("verify_legacy_map_choice_public.py", "check", 700000),
    "stays": ("verify-legacy-stays-archive-entry-public.py", "check", 1200000),
    "private_waters": ("verify-private-waters-concrete-choice-public.py", "verify", 1200000),
}
KNOWN_JOBS = ("verify", "ftps_inspect", "deploy")
FTPS_STAGE = "Stage, verify and swap exact reviewed plugin over WEDOS explicit FTPS"
DEPLOYER_STAGE = "Request WordPress Deployer pull of reviewed main"

def valid_run(run):
    return (
        isinstance(run, dict)
        and run.get("name") == WORKFLOW
        and run.get("event") == "push"
        and run.get("head_branch") == "main"
        and (run.get("head_repository") or {}).get("full_name") == REPO
        and isinstance(run.get("id"), int) and run["id"] > 0
        and re.fullmatch(r"[0-9a-f]{40}", run.get("head_sha") or "") is not None
        and run.get("status") == "completed"
    )

def conclude(run, jobs):
    """Successful PR tests or an overall green run with skipped deploy are NOT live."""
    if not valid_run(run):
        return {"status": "INVALID_RUN", "jobs": {}, "transport": None, "live_proof_allowed": False}
    matching = {n: next((j for j in jobs if j.get("name") == n), {}) for n in KNOWN_JOBS}
    conclusions = {n: matching[n].get("conclusion") or "unknown" for n in KNOWN_JOBS}
    steps = {s.get("name"): s.get("conclusion") for s in matching["deploy"].get("steps", [])}
    transport = "FTPS" if steps.get(FTPS_STAGE) == "success" else (
        "DEPLOYER" if steps.get(DEPLOYER_STAGE) == "success" else None)
    if conclusions["verify"] != "success":
        code = "VERIFICATION_BLOCKED"
    elif conclusions["deploy"] == "skipped":
        code = "DEPLOY_SKIPPED"
    elif conclusions["ftps_inspect"] not in ("success", "skipped"):
        code = "FTPS_INSPECTION_BLOCKED"
    elif conclusions["deploy"] == "failure":
        code = "DEPLOY_FAILED"
    elif conclusions["deploy"] == "success" and transport and run.get("conclusion") == "success":
        code = "CODE_TRANSPORT_SUCCESS"
    else:
        code = "DEPLOY_NOT_CONFIRMED"
    return {"status": code, "jobs": conclusions, "transport": transport,
            "live_proof_allowed": code == "CODE_TRANSPORT_SUCCESS"}

def github_api(path, method="GET", data=None):
    """Only GitHub API on fixed origin; no secrets included in comments."""
    assert path.startswith("/repos/" + REPO + "/")
    token = os.environ["GITHUB_TOKEN"]
    headers = {"Accept": "application/vnd.github+json",
               "Authorization": "Bearer " + token,
               "X-GitHub-Api-Version": "2022-11-28",
               "User-Agent": "NaRybyReleaseLedger/1.0",
               "Content-Type": "application/json"}
    payload = json.dumps(data).encode("utf-8") if data is not None else None
    with urlopen(Request("https://api.github.com" + path, method=method,
                         headers=headers, data=payload), timeout=20) as response:
        return json.load(response)

def get_main_sha():
    sha = github_api("/repos/" + REPO + "/git/ref/heads/main").get("object", {}).get("sha", "")
    if not re.fullmatch(r"[0-9a-f]{40}", sha):
        raise ValueError("MAIN_SHA_NOT_VALID")
    return sha

def get_latest_guarded_push_run():
    p = "/repos/" + REPO + "/actions/workflows/guarded-deployer-push.yml/runs?branch=main&event=push&per_page=20"
    data = github_api(p)
    return next((r for r in data.get("workflow_runs", []) if valid_run(r)), None)

def get_jobs(run_id):
    assert isinstance(run_id, int) and run_id > 0
    p = "/repos/" + REPO + "/actions/runs/" + str(run_id) + "/jobs?per_page=100&filter=latest"
    jobs = github_api(p).get("jobs", [])
    if not isinstance(jobs, list) or len(jobs) > 100:
        raise ValueError("JOB_RESULT_BOUNDS")
    return jobs

def import_verifier(filename):
    root = Path(__file__).resolve().parent
    if str(root) not in sys.path:
        sys.path.insert(0, str(root))
    name = "nry_" + filename.replace(".", "_").replace("-", "_")
    spec = importlib.util.spec_from_file_location(name, root / filename)
    if spec is None or spec.loader is None:
        raise ValueError("MISSING_VERIFIER")
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    return module

def public_checks():
    """Only three hard-coded first-party GETs; never follows affiliate links."""
    verdicts = {}
    for label, (file, method, max_bytes) in EXPECTED.items():
        try:
            module = import_verifier(file)
            url = module.URL
            if not url.startswith("https://na-ryby.eu/") or "?" in url:
                raise ValueError("UNAPPROVED_URL")
            req = Request(url, method="GET", headers={
                "User-Agent": "NaRybyReleaseLedger/1.0", "Cache-Control": "no-cache"})
            with module.guarded_urlopen(req, timeout=15) as response:
                raw = response.read(max_bytes + 1)
            if len(raw) > max_bytes:
                verdicts[label] = "BOUNDED_RESPONSE_EXCEEDED"
                continue
            result = getattr(module, method)(raw.decode("utf-8", "replace"))
            verdicts[label] = "PUBLIC_VERIFIED" if result.endswith("PUBLIC_VERIFIED") else "SEMANTIC_UNVERIFIED"
        except AssertionError as exc:
            code = str(exc)
            verdicts[label] = code if re.fullmatch(r"[A-Z0-9_]{4,90}", code) else "SEMANTIC_UNVERIFIED"
        except HTTPError as exc:
            verdicts[label] = "HTTP_" + str(exc.code) if 400 <= exc.code <= 599 else "HTTP_UNVERIFIABLE"
        except (OSError, URLError, TimeoutError):
            verdicts[label] = "NETWORK_UNVERIFIABLE"
        except (ImportError, ValueError, AttributeError):
            verdicts[label] = "VERIFIER_UNAVAILABLE"
    return verdicts

def render_comment(run, main_sha, data, checks):
    """Whitelisted status strings only: no logs, remote HTML, secrets or tokens."""
    rid = run["id"]
    sha = run["head_sha"]
    exact = sha == main_sha
    lines = [
        "<!-- " + VERSION + " run=" + str(rid) + " -->",
        "### Na-Ryby protected release evidence — run " + str(rid),
        "- [Guarded Actions run](https://github.com/" + REPO + "/actions/runs/" + str(rid) + "), SHA " + sha[:12] + ".",
        "- Run classification: **" + data["status"] + "**. Main SHA " + main_sha[:12] + "; same SHA: **" + ("yes" if exact else "NO - older run") + "**.",
        "- Jobs: " + ", ".join(n + "=" + data["jobs"].get(n, "unknown") for n in KNOWN_JOBS) + ".",
        "- Protected transport stage: " + (data["transport"] or "NOT_CONFIRMED") + ".",
    ]
    if data["live_proof_allowed"] and checks:
        lines += ["- Independent public GET checks: " + ", ".join(n + "=" + checks.get(n, "NOT_RUN") for n in EXPECTED) + "."]
        live = exact and all(checks.get(n) == "PUBLIC_VERIFIED" for n in EXPECTED)
        lines += ["- Release conclusion: **" + ("LIVE_PUBLIC_VERIFIED" if live else "TRANSPORT_SUCCESS_PUBLIC_NOT_ALL_VERIFIED") + "**."]
    else:
        lines += ["- Release conclusion: **NOT_LIVE_VERIFIED**. Public proofs withheld without successful exact current-main transport."]
    lines += ["_This receipt is not evidence of Google traffic growth, bookings or approved/paid commissions. No WordPress, merchant or affiliate writes/clicks._"]
    return "\n".join(lines)

def is_already_reported(run_id):
    path = "/repos/" + REPO + "/issues/" + str(ISSUE) + "/comments?per_page=100&sort=created&direction=desc"
    token = "<!-- " + VERSION + " run=" + str(run_id) + " -->"
    return any(token in (c.get("body") or "") for c in github_api(path))

def run():
    if os.environ.get("GITHUB_REPOSITORY") != REPO:
        raise ValueError("REPO_IDENTITY")
    event_name = os.environ.get("GITHUB_EVENT_NAME")
    if event_name == "workflow_run":
        event = json.loads(Path(os.environ["GITHUB_EVENT_PATH"]).read_text())
        candidate = event.get("workflow_run")
        selected = candidate if valid_run(candidate) else None
    elif event_name == "push":
        selected = get_latest_guarded_push_run()
    else:
        raise ValueError("UNAPPROVED_EVENT")
    if selected is None:
        print("NO_COMPLETED_APPROVED_MAIN_PUSH_RUN")
        return
    rid = selected["id"]
    if is_already_reported(rid):
        print("RECEIPT_ALREADY_RECORDED run=" + str(rid))
        return
    main_sha = get_main_sha()
    data = conclude(selected, get_jobs(rid))
    checks = public_checks() if data["live_proof_allowed"] and selected["head_sha"] == main_sha else {}
    comment = render_comment(selected, main_sha, data, checks)
    github_api("/repos/" + REPO + "/issues/" + str(ISSUE) + "/comments", method="POST", data={"body": comment})
    print("RECEIPT_WRITTEN run=" + str(rid) + " stage=" + data["status"])
    for key, val in checks.items():
        print(key + "=" + val)

if __name__ == "__main__":
    run()
