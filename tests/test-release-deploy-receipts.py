#!/usr/bin/env python3
"""Offline negative fixtures for automatic post-deploy release receipts."""
import importlib.util
from pathlib import Path

p = Path(__file__).resolve().parents[1] / "scripts/report-guarded-deploy-status.py"
spec = importlib.util.spec_from_file_location("release_report", p)
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)

SHA = "a" * 40
MAIN = "b" * 40
run = {
    "id": 12345, "name": m.WORKFLOW, "event": "push",
    "head_branch": "main", "head_repository": {"full_name": m.REPO},
    "head_sha": SHA, "status": "completed", "conclusion": "success",
}
def jobs(verify="success", inspect="success", deploy="success", ftps="success", wordpress="success"):
    return [
        {"name": "verify", "conclusion": verify},
        {"name": "ftps_inspect", "conclusion": inspect},
        {"name": "deploy", "conclusion": deploy, "steps": [
            {"name": m.FTPS_STAGE, "conclusion": ftps},
            {"name": "Confirm active WordPress publisher and one-shot SHA-bound update", "conclusion": wordpress},
        ]},
    ]
def check(status, jc, r=run):
    got = m.conclude(r, jc)
    assert got["status"] == status, (status, got)
    return got
good = check("CODE_TRANSPORT_SUCCESS", jobs())
assert good["transport"] == "FTPS" and good["live_proof_allowed"]
check("DEPLOY_SKIPPED", jobs(deploy="skipped", ftps="skipped"))
check("VERIFICATION_BLOCKED", jobs(verify="failure", deploy="skipped", ftps="skipped"))
check("FTPS_INSPECTION_BLOCKED", jobs(inspect="failure", deploy="skipped"))
check("DEPLOY_FAILED", jobs(deploy="failure", ftps="failure"))
check("DEPLOY_NOT_CONFIRMED", jobs(ftps="skipped"))
check("DEPLOY_NOT_CONFIRMED", jobs(deploy="success", ftps="failure"))
# Overall workflow success must not be confused with a skipped deploy.
assert not m.conclude(run, jobs(deploy="skipped", ftps="skipped"))["live_proof_allowed"]
for bad in (
    dict(run, event="pull_request"),
    dict(run, head_branch="feature"),
    dict(run, head_repository={"full_name": "attacker/fork"}),
    dict(run, status="in_progress"),
    dict(run, head_sha="x"),
    dict(run, name="Different CI workflow"),
    dict(run, id=-1),
):
    assert not m.valid_run(bad)
# No current-main proof if older run has successful code transport.
assert "older run" in m.render_comment(run, MAIN, good, {"map": "PUBLIC_VERIFIED"})
assert "LIVE_PUBLIC_VERIFIED" not in m.render_comment(run, MAIN, good, {
    "map": "PUBLIC_VERIFIED", "stays": "PUBLIC_VERIFIED",
    "private_waters": "PUBLIC_VERIFIED"})
assert "LIVE_PUBLIC_VERIFIED" in m.render_comment(run, SHA, good, {
    "map": "PUBLIC_VERIFIED", "stays": "PUBLIC_VERIFIED",
    "private_waters": "PUBLIC_VERIFIED"})
assert "NOT_LIVE_VERIFIED" in m.render_comment(run, SHA,
    m.conclude(run, jobs(deploy="skipped", ftps="skipped")), {})
for secret in ("GITHUB_TOKEN", "CHYTRYRYBAR_WEDOS", "NRY_FTPS_PASSWORD"):
    assert secret not in m.render_comment(run, MAIN, good, {"map": "HTTP_401"})
assert m.REPO == "Rastty/na-ryby-git-publisher" and m.ISSUE == 349
assert len(m.EXPECTED) == 3
assert all("https://na-ryby.eu/" in (p.parent.parent / "scripts" / p.name).as_posix()
           for p in [])  # no network fixture; GET URLs are read from fixed verifiers.
print("NRY_RELEASE_LEDGER_OFFLINE_VERIFIED")
