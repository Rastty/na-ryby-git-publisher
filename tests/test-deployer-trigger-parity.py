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
# #60354 remains permanently pinned. The ONLY temporary transition is
# the exact newly reviewed #118455 one-post batch, which must include
# native WordPress backup and local candidate receipt proofs.
assert "NRY_60354_POST_WRITE_SOURCE_REPIN_REQUIRED" not in workflow
assert "current['edits'][0].get('post_id')==118455" in workflow
assert "current.get('batch_id')=='echolot-budget-118455-three-offers-fix-20261108-v1'" in workflow
assert "health.get('candidate_matches') is True" in workflow
assert "health.get('backup_present') is True" in workflow
# A prior draft inserted the transient 118455 logic into the permanent
# 60354 rank-proof step and failed before any public confirmation. Guard
# stage order and the live row variable so the mix-up cannot recur.
rank_start=workflow.index("          print('NRY_60354_SOURCE_CLAIM_COUNTS'")
budget_start=workflow.index("          raw=r.get('historical_offer_raw_counts')")
budget_transition=workflow.index("          # Fresh independent preflight: run 42460178190")
budget_receipt=workflow.index("          print('NRY_118455_HISTORIC_OFFERS_COUNTS'")
assert rank_start<budget_start<budget_transition<budget_receipt
assert "old_sha='f0ba0b4dcd29646d470d084af077623b12fa687935347e626203a13107e7be79'" not in workflow[rank_start:budget_start]
assert "r['source_sha256']!=old_sha" in workflow[budget_start:budget_receipt]
print("PASS: all live publisher modules trigger PR review and guarded main deploy")
