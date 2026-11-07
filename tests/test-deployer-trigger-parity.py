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

assert ".github/workflows/guarded-deployer-push.yml" in pr & push
assert "tests/test-deployer-trigger-parity.py" in pr
assert "seo-pipaky-beginner-35755-v1.php" in pr & push
assert "checklist-release-v1.php" in pr & push
assert "seo-snippet-pilot.php" in pr & push
assert "assets/**" in pr & push
# Preserve the original production-only release gate.
assert "github.event_name != 'pull_request'" in workflow
assert "vars.NRY_AUTO_DEPLOY_ENABLED == 'true'" in workflow
# A successful already-published 35755 content update is the CURRENT
# protected source for future batches. Do not let a new manifest regress
# back to the pre-edit 14984-byte content or require its historical batch.
assert "expected_source='d4a35b7772708fe47dec76c6d023f1f6b39d0acc443fa160d60ab9dafd390016'" in workflow
assert "expected_bytes=17459" in workflow
assert "expected_modified_gmt='2026-11-07 10:51:04'" in workflow
assert "assert row['source_sha256']==expected_source" in workflow
assert "assert row['source_bytes']==expected_bytes" in workflow
assert "assert row['source_modified_gmt']==expected_modified_gmt" in workflow
assert "reviewed_diagnostic" not in workflow
assert "expected_source='2ae4c0adb81a0fc7b667e95bb23d9b1e3f4c590b173c98b8e125c48c41c2763f'" not in workflow
assert (ROOT/"tests/v8-pipaky-diagnostics-35755-20261107-frozen.json").exists()
# Post-36702 advanced buyer answer is now real public content, not a
# temporary manifest exception. Exact current SHA/bytes/GMT must be checked
# even when next release edits an unrelated WordPress article.
assert "expected_source='b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735'" in workflow
assert "expected_bytes=12938" in workflow
assert "expected_modified_gmt='2026-11-07 11:23:04'" in workflow
assert "approved_batch" not in workflow
assert "assert row['source_sha256']==expected_source" in workflow
assert (ROOT/"tests/v8-pipaky-advanced-36702-20261107-frozen.json").exists()
assert "seo-pipaky-advanced-description-36702-v1.php" in pr & push
assert "tests/test-pipaky-advanced-description-36702-v1.php" in pr
assert "php tests/test-pipaky-advanced-description-36702-v1.php" in workflow
assert "ADVANCED_PIPAKY_SEO_PROD_VERIFIED" in workflow
assert "assert unescape(html.description or '')==expected_meta" in workflow
assert "assert unescape(html.canonical or '')==canonical" in workflow
assert "assert row['raw_yoast_title_exists'] is False" in workflow
print("PASS: all live publisher modules trigger PR review and guarded main deploy")
