#!/usr/bin/env python3
"""Fail-closed, offline receipt-state fixtures for the deploy workflow."""

import json
import unittest
from pathlib import Path
from unittest.mock import patch

from scripts.guarded_v8_readback import (
    FROZEN_MANIFEST_SHA256,
    classify_receipt,
    previous_manifest_bytes,
)
import hashlib


ROOT = Path(__file__).resolve().parents[1]
FROZEN_BYTES = (ROOT / "manifest.json").read_bytes()
FROZEN_MANIFEST = json.loads(FROZEN_BYTES)


class V8GuardTests(unittest.TestCase):
    def setUp(self):
        self.health = {
            "publisher_version": "0.16.0",
            "last_auto_batch_id": FROZEN_MANIFEST["batch_id"],
            "last_auto_receipt_status": "disabled_by_admin",
            "guarded_publisher_enabled": False,
            "candidate_matches": True,
            "backup_present": True,
            "auto_status": "recovered_local_verified_pending_public_browser",
            "last_auto_stop_reason": "none",
        }

    def mode(self, before=FROZEN_BYTES, event="push", manifest=None, raw=None, health=None):
        return classify_receipt(manifest or FROZEN_MANIFEST, raw or FROZEN_BYTES,
                                self.health if health is None else health, before, event)

    def test_exact_frozen_manifest_is_checked_in(self):
        self.assertEqual(hashlib.sha256(FROZEN_BYTES).hexdigest(), FROZEN_MANIFEST_SHA256)

    def test_deliberately_disabled_historical_batch_is_not_reported_published(self):
        self.assertEqual(self.mode(), "historical_disabled")

    def test_actual_applied_receipt_is_still_verified(self):
        self.health["last_auto_receipt_status"] = "applied_and_verified_local"
        self.assertEqual(self.mode(before=None, event="workflow_dispatch"), "verified")

    def test_manifest_changed_on_this_push_blocks_waiver(self):
        self.assertEqual(self.mode(before=b"an older manifest"), "blocked")
        self.assertEqual(self.mode(raw=FROZEN_BYTES + b"\n"), "blocked")

    def test_dispatch_never_grants_disabled_waiver(self):
        self.assertEqual(self.mode(event="workflow_dispatch"), "blocked")
        self.assertEqual(self.mode(before=None), "blocked")

    def test_new_batch_cannot_reuse_the_waiver(self):
        self.assertEqual(self.mode(manifest={**FROZEN_MANIFEST, "batch_id": "new-batch"}), "blocked")

    def test_new_or_unexpected_admin_state_blocks(self):
        for field, value in (
            ("guarded_publisher_enabled", True),
            ("last_auto_receipt_status", "not_applied"),
            ("last_auto_batch_id", "another-batch"),
            ("candidate_matches", False),
            ("backup_present", False),
            ("auto_status", "source_drift"),
            ("last_auto_stop_reason", "error"),
            ("publisher_version", "0.17.0"),
        ):
            with self.subTest(field=field):
                self.assertEqual(self.mode(health={**self.health, field: value}), "blocked")

    def test_non_v8_manifest_does_not_inherit_v8_receipt_gate(self):
        self.assertEqual(self.mode(manifest={"schema_version": 9}), "not_v8")

    @patch("scripts.guarded_v8_readback.subprocess.run")
    def test_prior_manifest_requires_exact_push_commit(self, run):
        run.return_value.returncode = 0
        run.return_value.stdout = FROZEN_BYTES
        sha = "a" * 40
        self.assertEqual(previous_manifest_bytes("push", sha), FROZEN_BYTES)
        run.assert_called_once_with(
            ["git", "show", sha + ":manifest.json"],
            capture_output=True, check=False, timeout=15,
        )
        run.reset_mock()
        self.assertIsNone(previous_manifest_bytes("workflow_dispatch", sha))
        self.assertIsNone(previous_manifest_bytes("push", "HEAD~1"))
        run.assert_not_called()


if __name__ == "__main__":
    unittest.main()
