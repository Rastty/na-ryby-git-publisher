#!/usr/bin/env python3
"""Ensure every live publisher payload change gets reviewed and triggers guarded deploy.

The PR contract may additionally monitor tests. The push contract must cover
every root PHP runtime module, the manifest and all assets. No WordPress writes.
"""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[1]
workflow = (ROOT / ".github/workflows/guarded-deployer-push.yml").read_text(encoding="utf-8")

def paths(event, next_event):
    start = workflow.index("  " + event + ":\n")
    end = workflow.index("  " + next_event + ":\n", start)
    text = workflow[start:end]
    if "    paths:\n" not in text:
        raise AssertionError(event + " is missing its path filter")
    values = re.findall(r"(?m)^      - '([^']+)'$", text)
    if len(values) != len(set(values)):
        raise AssertionError(event + " has duplicate paths")
    return set(values)

pr = paths("pull_request", "push")
push = paths("push", "workflow_dispatch")
runtime = {p.name for p in ROOT.glob("*.php")}
runtime.update({"manifest.json", "assets/**"})

for name, selected in (("pull_request", pr), ("push", push)):
    missing = runtime - selected
    if missing:
        raise AssertionError(name + " misses runtime files: " + ", ".join(sorted(missing)))

assert ".github/workflows/guarded-deployer-push.yml" in pr
assert "tests/test-deployer-trigger-parity.py" in pr
assert "seo-pipaky-beginner-35755-v1.php" in pr & push
assert "checklist-release-v1.php" in pr & push
assert "seo-snippet-pilot.php" in pr & push
assert "assets/**" in pr & push
# Preserve the original production-only release gate.
assert "github.event_name != 'pull_request'" in workflow
assert "vars.NRY_AUTO_DEPLOY_ENABLED == 'true'" in workflow
print("PASS: all live publisher modules trigger PR review and guarded main deploy")
