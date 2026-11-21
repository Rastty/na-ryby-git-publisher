#!/usr/bin/env python3
"""No-network safety contract: an automated catalog run can ONLY read CJ/Woo."""
from pathlib import Path

wf=(Path(__file__).resolve().parents[1] / '.github/workflows/nry-cj-woo-catalog-pilot.yml').read_text(encoding='utf-8')
assert wf.count("cron: '23 5 * * 2,5'")==1,'scheduled cadence must be explicit'
assert wf.count("vars.NRY_CJ_WOO_READONLY_ENABLED == 'YES'")==2,'both schedule jobs must be opt-in'
assert wf.count("github.event_name == 'schedule'")==2,'only contract and preview may run on schedule'
assert wf.count("python3 scripts/cj_woo_catalog_sync.py --limit 20")==1,'read-only preview once'
assert wf.count("python3 scripts/cj_woo_catalog_sync.py --apply --limit 20")==1,'separate manual draft step'
assert wf.count("python3 tests/test-cj-woo-scheduled-preview-contract.py")==1
assert wf.count("    if: github.event_name == 'workflow_dispatch' && inputs.mode == 'draft-pilot'")==1,'only manual draft pilot'
a=wf.index("\n  preview:\n")
b=wf.index("\n  drafts:\n",a)
preview=wf[a:b];drafts=wf[b:]
assert "inputs.mode == 'dry-run'" in preview
assert "github.event_name == 'schedule'" in preview
assert "vars.NRY_CJ_WOO_READONLY_ENABLED == 'YES'" in preview
assert "NRY_CJ_WOO_APPLY" not in preview,'scheduled preview must not expose write flag'
assert "--apply" not in preview.split("          # --apply is deliberately absent:")[0]
assert "--limit 20" in preview,'bound output scale'
for name in ('NRY_CJ_FEED_URL','NRY_CJ_FEED_USER','NRY_CJ_FEED_PASSWORD','NRY_WC_CONSUMER_KEY','NRY_WC_CONSUMER_SECRET'):
    assert name in preview and f"secrets.{name}" in preview,'complete five-secret guard '+name
assert "set -euo pipefail" in preview and "if [ -z" in preview
assert "github.event_name == 'schedule'" not in drafts,'scheduled run must never reach drafts'
assert "inputs.mode == 'draft-pilot'" in drafts
assert "environment: na-ryby-production" in drafts
assert "vars.NRY_CJ_WOO_APPLY" in drafts
print("CJ_WOO_SCHEDULE_ONLY_READONLY_CONTRACT_VERIFIED")
