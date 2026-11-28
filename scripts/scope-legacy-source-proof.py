#!/usr/bin/env python3
"""Limit legacy raw-source diagnostics to relevant *deployed* changes.

Manual source proofs remain available. Automatic first releases/changes still
run, comparing the exact successful deployment to the previous successful
production swap (not to a docs-only or failed main commit). No site HTTP here.
"""
import json
import os
import re
import subprocess
import sys
from pathlib import Path

REPO = "Rastty/na-ryby-git-publisher"


def api(path):
    return json.loads(subprocess.check_output(["gh", "api", path], text=True))


def sha(value):
    if not isinstance(value, str) or not re.fullmatch(r"[a-f0-9]{40}", value):
        raise ValueError("invalid deployment SHA")
    return value


def swapped(run_id):
    jobs = api(f"repos/{REPO}/actions/runs/{int(run_id)}/jobs?per_page=100")["jobs"]
    return any(j["name"] == "deploy" and j["conclusion"] == "success" for j in jobs)


def should_run(paths):
    if os.environ.get("GITHUB_EVENT_NAME") == "workflow_dispatch":
        return True
    if os.environ.get("GITHUB_EVENT_NAME") != "workflow_run":
        raise ValueError("unexpected source proof event")
    event = json.loads(Path(os.environ["GITHUB_EVENT_PATH"]).read_text())
    run = event["workflow_run"]
    if (run["event"] != "push" or run["conclusion"] != "success"
            or run["head_branch"] != "main"
            or run["head_repository"]["full_name"] != REPO):
        raise ValueError("not a successful protected main push")
    current = sha(run["head_sha"])
    if not swapped(run["id"]):
        print("SOURCE_PROOF_NO_PRODUCTION_SWAP")
        return False
    history = api(f"repos/{REPO}/actions/workflows/{int(run['workflow_id'])}/runs"
                  "?event=push&status=success&branch=main&per_page=30")["workflow_runs"]
    prior = next((r for r in history if r["run_number"] < run["run_number"]
                  and r["head_branch"] == "main" and swapped(r["id"])), None)
    if prior is None:
        raise ValueError("previous successful production deployment not established")
    previous = sha(prior["head_sha"])
    if previous == current:
        return False
    diff = api(f"repos/{REPO}/compare/{previous}...{current}")
    files = diff.get("files")
    if diff.get("status") not in {"ahead", "identical"} or not isinstance(files, list) or len(files) >= 300:
        raise ValueError("exact release comparison incomplete")
    changed = {f[k] for f in files for k in ("filename", "previous_filename") if k in f}
    return bool(changed.intersection(paths))


if __name__ == "__main__":
    try:
        paths = sys.argv[1:]
        if not paths or any(p.startswith("/") or ".." in p.split("/") for p in paths):
            raise ValueError("invalid paths")
        run = should_run(paths)
        print("SOURCE_PROOF_RELEVANT" if run else "SOURCE_PROOF_UNCHANGED")
        with open(os.environ["GITHUB_OUTPUT"], "a") as out:
            out.write("run=" + ("true" if run else "false") + "\n")
    except Exception as exc:
        # Scope unknown is a visibly failed diagnostic, not a successful proof.
        # Do not issue speculative public GETs during an incident.
        print("SOURCE_PROOF_SCOPE_UNKNOWN", type(exc).__name__)
        sys.exit(1)
