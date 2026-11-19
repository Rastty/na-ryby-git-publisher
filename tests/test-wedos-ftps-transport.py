#!/usr/bin/env python3
"""No-network contract for the opt-in WEDOS FTPS transport."""

import io
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import patch
from urllib.error import HTTPError

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT / "scripts"))
import deploy_via_wedos_ftps as ftps


SHA = "a" * 40


class FakeFTP:
    def __init__(self, fail_stage=False, fail_swap=False):
        self.dirs = {"/", "/plugins", "/plugins/na-ryby-git-publisher"}
        self.files = {"/plugins/na-ryby-git-publisher/na-ryby-git-publisher.php":
                      b"<?php class NRY_Git_Publisher {}"}
        self.here = "/"
        self.fail_stage = fail_stage
        self.fail_swap = fail_swap
        self.moves = []

    def pwd(self):
        return self.here

    def cwd(self, path):
        if path not in self.dirs:
            raise ValueError("550 missing directory")
        self.here = path

    def mkd(self, path):
        if path in self.dirs or (path.rsplit("/", 1)[0] or "/") not in self.dirs:
            raise ValueError("550 bad directory")
        self.dirs.add(path)

    def storbinary(self, command, handle):
        assert command.startswith("STOR ")
        path = command[5:]
        if (path.rsplit("/", 1)[0] or "/") not in self.dirs:
            raise ValueError("550 bad parent")
        data = handle.read()
        self.files[path] = data + (b"tampered" if self.fail_stage and "ftps-stage" in path else b"")

    def retrbinary(self, command, callback):
        assert command.startswith("RETR ")
        path = command[5:]
        if path not in self.files:
            raise ValueError("550 missing file")
        callback(self.files[path])

    def delete(self, path):
        if path not in self.files:
            raise ValueError("550 missing file")
        del self.files[path]

    def rename(self, before, after):
        if self.fail_swap and "ftps-stage" in before and after.endswith(ftps.PLUGIN):
            raise ValueError("550 simulated swap error")
        if before not in self.dirs or after in self.dirs:
            raise ValueError("550 invalid rename")
        self.moves.append((before, after))
        self.dirs = {after + x[len(before):] if x == before or x.startswith(before + "/")
                     else x for x in self.dirs}
        self.files = {after + x[len(before):] if x.startswith(before + "/") else x: v
                      for x, v in self.files.items()}


FILES = {
    "na-ryby-git-publisher.php": b"<?php require_once __DIR__ . '/guarded-auto-publisher.php';",
    "guarded-auto-publisher.php": b"<?php // local guarded writer",
    "manifest.json": b"{}",
    "RELEASE_INTEGRITY.json": b"{}",
    "assets/page.css": b"body{}",
}


class TransportContract(unittest.TestCase):
    def setUp(self):
        # The real deployment does a mandatory HTTPS GET of a temporary file
        # and accepts only an anonymous 403/404. Never contact production here.
        mocked = patch.object(ftps, "urlopen", side_effect=HTTPError(
            "https://na-ryby.eu/", 403, "Forbidden", {}, None))
        mocked.start()
        self.addCleanup(mocked.stop)

    def test_reviewed_checkout_payload_is_bounded(self):
        sha = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=ROOT, text=True).strip()
        files = ftps.checked_out_files(ROOT, sha)
        self.assertIn("manifest.json", files)
        self.assertIn("editorial/receiver-46573-decision-guide-20261109.html", files)
        self.assertNotIn(".github/workflows/guarded-deployer-push.yml", files)
        self.assertNotIn("tests/test-wedos-ftps-transport.py", files)
        self.assertLess(len(files), ftps.MAX_FILES)

    def test_exact_sha_and_path_allowlists(self):
        with self.assertRaises(ValueError):
            ftps.validate_sha("main")
        with self.assertRaises(ValueError):
            ftps.validate_wp_content("/www/domains/other.example/wp-content")
        with self.assertRaises(ValueError):
            ftps.validate_wp_content("/www/domains/na-ryby.eu/wp-content/..")
        self.assertEqual(ftps.validate_wp_content("/"), "")
        self.assertEqual(ftps.validate_sha(SHA), SHA)

    def test_staged_files_are_verified_before_first_production_move(self):
        remote = FakeFTP(fail_stage=True)
        with self.assertRaisesRegex(ValueError, "SHA256"):
            ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(remote.moves, [])
        self.assertIn("/.nry-ftps-vault/.nry-ftps-stage-" + SHA[:12], remote.dirs)
        self.assertEqual(remote.files["/.nry-ftps-vault/.htaccess"], ftps.VAULT_DENY)

    def test_swap_keeps_private_backup(self):
        remote = FakeFTP()
        backup = ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(backup, "/.nry-ftps-vault/.nry-ftps-backup-" + SHA[:12])
        self.assertIn(backup, remote.dirs)
        self.assertEqual(remote.files["/.nry-ftps-vault/.htaccess"], ftps.VAULT_DENY)
        self.assertIn("/plugins/na-ryby-git-publisher", remote.dirs)
        self.assertEqual(remote.files["/plugins/na-ryby-git-publisher/manifest.json"], b"{}")
        self.assertEqual(len(remote.moves), 2)

    def test_swap_failure_restores_previous_plugin(self):
        remote = FakeFTP(fail_swap=True)
        with self.assertRaisesRegex(ValueError, "simulated"):
            ftps.deploy(remote, "", SHA, FILES)
        self.assertIn("/plugins/na-ryby-git-publisher", remote.dirs)
        self.assertNotIn("/.nry-ftps-vault/.nry-ftps-backup-" + SHA[:12], remote.dirs)
        self.assertEqual(remote.files["/.nry-ftps-vault/.htaccess"], ftps.VAULT_DENY)
        self.assertEqual(len(remote.moves), 2)  # previous→backup, backup→previous

    def test_existing_vault_must_retain_exact_access_deny(self):
        remote = FakeFTP()
        remote.dirs.add("/.nry-ftps-vault")
        remote.files["/.nry-ftps-vault/.htaccess"] = b"Require all granted\n"
        with self.assertRaisesRegex(ValueError, "SHA256"):
            ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(remote.moves, [])
        self.assertFalse(any("ftps-stage" in key for key in remote.files))

    def test_publicly_served_vault_blocks_before_upload_or_swap(self):
        remote = FakeFTP()
        class Response:
            def __enter__(self):
                return self
            def __exit__(self, *_):
                return False
        with patch.object(ftps, "urlopen", return_value=Response()):
            with self.assertRaisesRegex(ValueError, "publicly served"):
                ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(remote.moves, [])
        self.assertFalse(any("ftps-stage" in key for key in remote.files))
        self.assertFalse(any("access-check" in key for key in remote.files))

    def test_wrong_remote_plugin_stops_without_moves(self):
        remote = FakeFTP()
        remote.files["/plugins/na-ryby-git-publisher/na-ryby-git-publisher.php"] = b"unknown-plugin"
        with self.assertRaisesRegex(ValueError, "identity mismatch"):
            ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(remote.moves, [])

    def test_missing_required_module_fails_before_network(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            for name, data in FILES.items():
                path = root / name
                path.parent.mkdir(parents=True, exist_ok=True)
                path.write_bytes(data)
            with self.assertRaisesRegex(ValueError, "Missing essential plugin runtime"):
                ftps.choose_runtime_files([name for name in FILES if name != "guarded-auto-publisher.php"], root)


if __name__ == "__main__":
    unittest.main(verbosity=2)
