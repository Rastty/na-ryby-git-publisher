#!/usr/bin/env python3
"""No-network contract for the opt-in WEDOS FTPS transport."""

import io
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest
from unittest.mock import Mock, patch
from urllib.error import HTTPError
from ftplib import FTP, error_perm

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
    "na-ryby-git-publisher.php": (b"<?php require_once __DIR__ . '/guarded-auto-publisher.php'; "
                                   b"class NRY_Git_Publisher {}"),
    "guarded-auto-publisher.php": b"<?php // local guarded writer",
    "manifest.json": b"{}",
    "RELEASE_INTEGRITY.json": b"{}",
    "assets/page.css": b"body{}",
    "editorial/drafts/note.html": b"<p>reviewed</p>",
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

    def test_workflow_never_falls_back_to_legacy_deployer(self):
        workflow = (ROOT / ".github/workflows/guarded-deployer-push.yml").read_text()
        self.assertIn('case "${NRY_DEPLOY_TRANSPORT:-unset}" in', workflow)
        self.assertEqual(workflow.count("if: vars.NRY_DEPLOY_TRANSPORT == 'deployer'"), 2)
        self.assertEqual(workflow.count("if: vars.NRY_DEPLOY_TRANSPORT == 'ftps'"), 2)
        self.assertNotIn("vars.NRY_DEPLOY_TRANSPORT == '' ||", workflow)
        self.assertIn("environment: na-ryby-production", workflow)

    def test_ftp_diagnostic_only_logs_allowlisted_stage_and_reply_code(self):
        with self.assertRaises(ftps.SafeFtpStageError) as caught:
            ftps.ftps_stage("login", lambda: (_ for _ in ()).throw(
                error_perm("530 Wrong password ftp-host=secret.example password=SENSITIVE")))
        self.assertEqual(str(caught.exception), "login:530")
        self.assertNotIn("SENSITIVE", str(caught.exception))
        with self.assertRaises(AssertionError):
            ftps.ftps_stage("arbitrary_host", lambda: None)

    def test_ftp_data_transfer_reuses_authenticated_tls_session(self):
        tls_context = Mock()
        data_socket = Mock()
        control_session = object()
        tls_context.wrap_socket.return_value = Mock()
        client = ftps.SessionReuseFTP_TLS(context=tls_context)
        client.host = "123456.w12.wedos.net"
        client.sock = Mock(session=control_session)
        client._prot_p = True
        with patch.object(FTP, "ntransfercmd", return_value=(data_socket, 42)) as start:
            secured, length = client.ntransfercmd("RETR /www/example.php")
        start.assert_called_once_with(client, "RETR /www/example.php", None)
        tls_context.wrap_socket.assert_called_once_with(
            data_socket, server_hostname=client.host, session=control_session
        )
        self.assertIs(secured, tls_context.wrap_socket.return_value)
        self.assertEqual(length, 42)

    def test_ftp_data_transfer_never_downgrades_to_plaintext(self):
        tls_context = Mock()
        data_socket = Mock()
        client = ftps.SessionReuseFTP_TLS(context=tls_context)
        client._prot_p = False
        with patch.object(FTP, "ntransfercmd", return_value=(data_socket, None)):
            with self.assertRaisesRegex(ValueError, "protected data channel"):
                client.ntransfercmd("RETR /www/example.php")
        data_socket.close.assert_called_once_with()
        tls_context.wrap_socket.assert_not_called()

    def test_ftp_tls_handshake_failure_closes_data_socket(self):
        tls_context = Mock()
        tls_context.wrap_socket.side_effect = OSError("failed handshake")
        data_socket = Mock()
        client = ftps.SessionReuseFTP_TLS(context=tls_context)
        client._prot_p = True
        client.sock = Mock(session=object())
        with patch.object(FTP, "ntransfercmd", return_value=(data_socket, None)):
            with self.assertRaises(OSError):
                client.ntransfercmd("STOR /www/example.php")
        data_socket.close.assert_called_once_with()

    def test_only_exact_sha_manual_dispatch_can_deploy(self):
        workflow = (ROOT / ".github/workflows/guarded-deployer-push.yml").read_text()
        self.assertIn("  workflow_dispatch:\n    inputs:\n      release_sha:", workflow)
        self.assertIn("github.event_name == 'workflow_dispatch' &&", workflow)
        self.assertIn("inputs.release_sha == github.sha", workflow)
        self.assertIn("NRY_EXPECTED_RELEASE_SHA", workflow)
        self.assertIn("=~ ^[0-9a-f]{40}$", workflow)
        self.assertNotIn("vars.NRY_FTPS_RELEASE_ARMED", workflow)
        self.assertIn("python3 scripts/deploy_via_wedos_ftps.py inspect", workflow)
        self.assertIn("needs: [verify, ftps_inspect]", workflow)

    def test_inspect_mode_is_read_only_before_deploy(self):
        source = (ROOT / "scripts/deploy_via_wedos_ftps.py").read_text()
        inspect = source.index('if action == "inspect":')
        first_write = source.index("backup = deploy(ftp, wp_content, sha, files)")
        self.assertLess(inspect, first_write)
        self.assertIn("return 0", source[inspect:first_write])

    def test_exact_sha_and_path_allowlists(self):
        with self.assertRaises(ValueError):
            ftps.validate_sha("main")
        with self.assertRaises(ValueError):
            ftps.validate_wp_content("/www/domains/other.example/wp-content")
        with self.assertRaises(ValueError):
            ftps.validate_wp_content("/www/domains/na-ryby.eu/wp-content/..")
        self.assertEqual(ftps.validate_wp_content("/"), "")
        self.assertEqual(ftps.validate_sha(SHA), SHA)

    def test_existing_wedos_main_account_root_is_discovered(self):
        remote = FakeFTP()
        target = "/www/domains/na-ryby.eu/wp-content"
        remote.dirs.update({"/www", "/www/domains", "/www/domains/na-ryby.eu",
                            target, target + "/plugins", target + "/plugins/" + ftps.PLUGIN})
        remote.files[target + "/plugins/" + ftps.PLUGIN + "/" + ftps.PLUGIN + ".php"] = (
            b"<?php class NRY_Git_Publisher {}")
        self.assertEqual(ftps.resolve_wp_content(remote, ""), target)
        self.assertEqual(ftps.resolve_wp_content(remote, target), target)
        with self.assertRaisesRegex(ValueError, "configured|Configured"):
            ftps.resolve_wp_content(remote, "/www/wp-content")

        second = "/www/wp-content"
        remote.dirs.update({second, second + "/plugins", second + "/plugins/" + ftps.PLUGIN})
        remote.files[second + "/plugins/" + ftps.PLUGIN + "/" + ftps.PLUGIN + ".php"] = (
            b"<?php class NRY_Git_Publisher {}")
        with self.assertRaisesRegex(ValueError, "one exact"):
            ftps.resolve_wp_content(remote, "")

    def test_main_account_releases_only_into_confirmed_site(self):
        remote = FakeFTP()
        target = "/www/domains/na-ryby.eu/wp-content"
        remote.dirs.update({"/www", "/www/domains", "/www/domains/na-ryby.eu",
                            target, target + "/plugins", target + "/plugins/" + ftps.PLUGIN})
        remote.files[target + "/plugins/" + ftps.PLUGIN + "/" + ftps.PLUGIN + ".php"] = (
            b"<?php class NRY_Git_Publisher {}")
        chosen = ftps.resolve_wp_content(remote, "")
        backup = ftps.deploy(remote, chosen, SHA, FILES)
        self.assertEqual(backup, target + "/.nry-ftps-vault/.nry-ftps-backup-" + SHA[:12])
        self.assertIn(target + "/plugins/" + ftps.PLUGIN + "/manifest.json", remote.files)
        self.assertIn("/plugins/na-ryby-git-publisher/na-ryby-git-publisher.php", remote.files)

    def test_staged_files_are_verified_before_first_production_move(self):
        remote = FakeFTP(fail_stage=True)
        with self.assertRaisesRegex(ValueError, "SHA256"):
            ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(remote.moves, [])
        self.assertTrue(any(path.startswith("/.nry-ftps-vault/.nry-ftps-stage-" + SHA[:12])
                            for path in remote.dirs))
        self.assertEqual(remote.files["/.nry-ftps-vault/.htaccess"], ftps.VAULT_DENY)

    def test_partial_stage_retry_uses_new_private_directory(self):
        remote = FakeFTP(fail_stage=True)
        with self.assertRaisesRegex(ValueError, "SHA256"):
            ftps.deploy(remote, "", SHA, FILES)
        prefix = "/.nry-ftps-vault/.nry-ftps-stage-"
        orphaned = [x for x in remote.dirs if x.startswith(prefix) and
                    "/" not in x[len(prefix):]]
        self.assertEqual(len(orphaned), 1)
        remote.fail_stage = False
        backup = ftps.deploy(remote, "", SHA, FILES)
        self.assertIn(backup, remote.dirs)
        self.assertIn(orphaned[0], remote.dirs)
        self.assertIn("/plugins/na-ryby-git-publisher/manifest.json", remote.files)

    def test_repeated_successful_release_blocks_without_new_stage(self):
        remote = FakeFTP()
        ftps.deploy(remote, "", SHA, FILES)
        before = set(remote.dirs)
        with self.assertRaisesRegex(ValueError, "already exists"):
            ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(remote.dirs, before)
        self.assertEqual(len(remote.moves), 2)

    def test_swap_keeps_private_backup(self):
        remote = FakeFTP()
        backup = ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(backup, "/.nry-ftps-vault/.nry-ftps-backup-" + SHA[:12])
        self.assertIn(backup, remote.dirs)
        self.assertEqual(remote.files["/.nry-ftps-vault/.htaccess"], ftps.VAULT_DENY)
        self.assertIn("/plugins/na-ryby-git-publisher", remote.dirs)
        self.assertEqual(remote.files["/plugins/na-ryby-git-publisher/manifest.json"], b"{}")
        self.assertEqual(remote.files["/plugins/na-ryby-git-publisher/editorial/drafts/note.html"],
                         b"<p>reviewed</p>")
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

    def test_vault_transient_status_retries_only_until_real_denial(self):
        remote = FakeFTP()
        def error(code):
            return HTTPError("https://na-ryby.eu/", code, "test", {}, None)
        with patch.object(ftps, "urlopen", side_effect=[error(503), error(429), error(403)]) as get:
            with patch.object(ftps.time, "sleep") as pause:
                ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(get.call_count, 3)
        self.assertEqual(pause.call_count, 2)
        self.assertEqual(len(remote.moves), 2)
        self.assertFalse(any("access-check" in key for key in remote.files))

    def test_vault_outage_never_permits_upload_or_swap(self):
        remote = FakeFTP()
        with patch.object(ftps, "urlopen", side_effect=HTTPError(
                "https://na-ryby.eu/", 503, "test", {}, None)) as get:
            with patch.object(ftps.time, "sleep"):
                with self.assertRaisesRegex(ftps.SafeFtpStageError, "vault_denial:503"):
                    ftps.deploy(remote, "", SHA, FILES)
        self.assertEqual(get.call_count, 3)
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
