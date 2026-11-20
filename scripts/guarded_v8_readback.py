"""Classify an exact V8 release receipt without authorizing WordPress writes.

The historical 54272 V8 manifest remains checked in while its automatic
publisher is deliberately disabled by the WordPress administrator. Unrelated
code-only deployments must not claim that old batch was published. For pushes,
both ends must contain the exact immutable manifest. Manual dispatches have
no `before` SHA, so only the pinned, byte-exact frozen manifest plus the exact
disabled live receipt can be deferred. Any new/changed content fails closed.
"""

import hashlib
import re
import subprocess


FROZEN_MANIFEST_SHA256 = "a00dcf7c46680fb5b19d4177e4984b86cc4b71b4ca0002096db1a1898e38d7de"
FROZEN_BATCH_ID = "carp-rods-3m-54272-source-trust-20261109-v1"


def previous_manifest_bytes(event_name, before_sha):
    """Read the actual push's prior manifest from Git, or deny the waiver."""
    if event_name != "push" or not re.fullmatch(r"[a-f0-9]{40}", before_sha or ""):
        return None
    result = subprocess.run(
        ["git", "show", before_sha + ":manifest.json"],
        capture_output=True, check=False, timeout=15,
    )
    return result.stdout if result.returncode == 0 else None


def classify_receipt(manifest, manifest_bytes, health, before_bytes, event_name):
    """Return verified, historical_disabled, or blocked (never infer writes)."""
    if manifest.get("schema_version") != 8:
        return "not_v8"
    expected = manifest.get("batch_id")
    if (health.get("publisher_version") == "0.16.0"
            and health.get("last_auto_batch_id") == expected
            and health.get("last_auto_receipt_status") == "applied_and_verified_local"):
        return "verified"
    # A manually triggered run has no github.event.before. The independently
    # pinned manifest hash proves that this is the existing frozen batch, not
    # newly approved content. Pushes additionally prove no manifest change.
    frozen_event_safe = (
        (event_name == "push" and before_bytes is not None
         and before_bytes == manifest_bytes)
        or event_name == "workflow_dispatch"
    )
    if (frozen_event_safe
            and expected == FROZEN_BATCH_ID
            and hashlib.sha256(manifest_bytes).hexdigest() == FROZEN_MANIFEST_SHA256
            and health.get("publisher_version") == "0.16.0"
            and health.get("last_auto_batch_id") == expected
            and health.get("last_auto_receipt_status") == "disabled_by_admin"
            and health.get("guarded_publisher_enabled") is False
            and health.get("candidate_matches") is True
            and health.get("backup_present") is True
            and health.get("auto_status") == "recovered_local_verified_pending_public_browser"
            and health.get("last_auto_stop_reason") == "none"):
        return "historical_disabled"
    return "blocked"
