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
assert "expected_source='94c3683902bee004fc136c12004ecde138509bc3a9346df0b095286f781e10cd'" in workflow
assert "expected_bytes=13069" in workflow
assert "expected_modified_gmt='2026-11-07 13:19:54'" in workflow
assert "approved_price_fix" not in workflow
assert "assert all(v==0 for v in row['legacy_price_raw_literal_counts'].values())" in workflow
assert (ROOT/"tests/v8-pipaky-36702-five-prices-20261107-frozen.json").exists()
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
# Post-write 42401 source is permanently pinned after actual run 42379244280.
# This prevents a future unrelated batch from silently treating the old raw
# post as current. Keep source, GMT and exact byte length, not just a marker.
assert "expected_source='a61c57d0838632d17d9aca33593fca4903d1603953c0ef0534d37dbfcee0bfff'" in workflow
assert "expected_bytes=16590" in workflow
assert "expected_modified_gmt='2026-11-07 13:51:49'" in workflow
assert "assert row['source_sha256']==expected_source" in workflow
assert "assert row['source_bytes']==expected_bytes" in workflow
assert "assert row['source_modified_gmt']==expected_modified_gmt" in workflow
assert "PIPAKY_MAIN_EXACT_POST_WRITE_SOURCE_REPIN_REQUIRED" not in workflow
assert "reviewed_transition=(" not in workflow
# The 60354 historic-rank trust correction is already real WordPress content.
# Every subsequent unrelated release must preserve this exact current source.
assert "expected_source='36a361eb839948cb41b81490b2969a6b0e12540862cf7c572ffef0893ec39005'" in workflow
assert "expected_bytes=20528" in workflow
assert "expected_modified_gmt='2026-11-07 17:30:55'" in workflow
assert "assert row['source_sha256']==expected_source" in workflow
assert "assert row['source_bytes']==expected_bytes" in workflow
assert "assert row['source_modified_gmt']==expected_modified_gmt" in workflow
assert "assert all(raw[k]==0 and safe[k]==0 for k in keys)" in workflow
assert "NRY_60354_POST_WRITE_SOURCE_REPIN_REQUIRED" not in workflow
# Two independent source-locked public interventions are permanently pinned.
# A future new batch must never reopen a stale #118455 or #60354 source
# transition just because a manifest is reused or the page cache is stale.
assert "NRY_60354_POST_WRITE_SOURCE_REPIN_REQUIRED" not in workflow
assert "NRY_118455_NEW_SOURCE_PIN_REQUIRED" not in workflow
assert "old_sha='f0ba0b4dcd29646d470d084af077623b12fa687935347e626203a13107e7be79'" not in workflow
assert "current_sha='6d8736d5d2f62a5f6170871a7630d9e097f6dc6839a461e97da53d95cb166f7e'" in workflow
assert "current_bytes=39897" in workflow
assert "current_gmt='2026-11-08 07:56:27'" in workflow
assert "assert r['source_sha256']==current_sha" in workflow
assert "assert r['source_bytes']==current_bytes" in workflow
assert "assert r['source_modified_gmt']==current_gmt" in workflow
assert "assert all(raw[key]==0 and safe[key]==0 for key in keys)" in workflow
import re
import textwrap
heredoc_starts=list(re.finditer(r"(?m)^[ \t]*python3 - <<'PY'[ \t]*$",workflow))
assert len(heredoc_starts)>=8, 'unexpected missing inline Python checks'
for index,m in enumerate(heredoc_starts):
    tail=workflow[m.end():]
    close=re.search(r"(?m)^[ \t]*PY[ \t]*$",tail)
    assert close, f"unclosed workflow Python heredoc {index}"
    compile(textwrap.dedent(tail[:close.start()]),f'guarded-deployer-inline-{index}.py','exec')
rank_step=workflow.split(
    '      - name: Exact 60354 legacy-ranking source and safe visible-claim counts (GET only)'
)[1].split('      - name: Read-only exact live source and Yoast hashes for broad pípáky guide #42401')[0]
budget_step=workflow.split(
    '      - name: Prove exact read-only WordPress source of 15k echolot buyer guide'
)[1].split('      - name: Read-only exact echolot pillar source proof from live WordPress')[0]
assert 'NRY_60354_SOURCE_CLAIM_COUNTS' in rank_step
assert "expected_source='36a361eb839948cb41b81490b2969a6b0e12540862cf7c572ffef0893ec39005'" in rank_step
assert "current_sha='6d8736d5d2f62a5f6170871a7630d9e097f6dc6839a461e97da53d95cb166f7e'" in budget_step
assert "current_bytes=39897" in budget_step
assert "current_gmt='2026-11-08 07:56:27'" in budget_step
assert "assert all(raw[key]==0 and safe[key]==0 for key in keys)" in budget_step
assert 'NRY_118455_HISTORIC_OFFERS_COUNTS' in budget_step
print("PASS: all live publisher modules trigger PR review and guarded main deploy")
