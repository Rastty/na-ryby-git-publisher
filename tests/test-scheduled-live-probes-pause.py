#!/usr/bin/env python3
"""Offline guard: pause recurring live probes during Na-Ryby incident #265.

Manual and successful-release readbacks are deliberately not disabled.
Re-enabling schedules requires an explicit, reviewed change to this contract.
"""

from pathlib import Path
import re


WORKFLOWS = {
    "advisor-public-readonly.yml": False,
    "advisor-browser-readonly.yml": True,
    "baitboat-v5-stock-affiliate-public.yml": False,
    "baitboat-v5-visitor-journey.yml": False,
    "chair-candidates-readonly.yml": False,
    "cj-property-identity-readonly.yml": False,
    "dyje6-stay-public-v1.yml": True,
    "echolot-cluster-readonly.yml": True,
    "hracholusky-stay-public-v1.yml": True,
    "guarded-auto-public-readback.yml": False,
    "fishing-stays-click-public-v1.yml": True,
    "reel-dashboard-navigation-public.yml": True,
    "tipy-stay-trust-public-v1.yml": True,
}


def check(name: str, require_release: bool) -> None:
    path = Path(__file__).resolve().parents[1] / ".github" / "workflows" / name
    source = path.read_text(encoding="utf-8")
    assert source.startswith("name:"), f"{name}: invalid workflow heading"
    assert "\non:\n" in source, f"{name}: missing event block"
    event_block = source.split("\non:\n", 1)[1].split("\npermissions:", 1)[0]

    # No cron-triggered public traffic; no scope creep into source or deploy.
    assert not re.search(r"(?m)^\s{2}schedule\s*:", event_block), name
    assert not re.search(r"(?m)^\s+- cron\s*:", event_block), name
    assert re.search(r"(?m)^\s{2}workflow_dispatch\s*:", event_block), name
    assert re.search(r"(?m)^\s{2}pull_request\s*:", event_block), name
    assert re.search(r"(?m)^\s{4}paths\s*:", event_block), name
    assert re.search(r"(?m)^\s{6}- ['\"](?:scripts|tests|assets)/", event_block) or re.search(
        r"(?m)^\s{6}- ['\"][^'\"]+\.php['\"]", event_block
    ), f"{name}: feature-specific PR checks disappeared"

    # Workflow-file-only edits must not fan out into unnecessary live checks.
    own_trigger = f"      - '.github/workflows/{name}'"
    assert own_trigger not in event_block, f"{name}: own-file PR/push fan-out"
    if require_release:
        assert re.search(r"(?m)^\s{2}workflow_run\s*:", event_block), name
        assert "Na-Ryby Guarded Deployer Push-to-Deploy" in event_block, name
    assert "workflow_dispatch" in source, name


if __name__ == "__main__":
    for filename, preserve_release in WORKFLOWS.items():
        check(filename, preserve_release)
    print(f"PASS: {len(WORKFLOWS)} recurring probes paused; manual/release paths preserved")
