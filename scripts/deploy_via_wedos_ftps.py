#!/usr/bin/env python3
"""Guarded, opt-in code transport to WEDOS Webhosting over explicit FTPS.

This only swaps the reviewed plugin directory. WordPress content changes remain
exclusively in the existing source-pinned WordPress-local publisher; this tool
does not call a publish endpoint or change database records.
"""

from __future__ import annotations

import hashlib
import io
import os
from pathlib import Path
import re
import secrets
import ssl
import subprocess
import sys
from ftplib import FTP, FTP_TLS, error_perm
from urllib.error import HTTPError
from urllib.request import Request, urlopen


PLUGIN = "na-ryby-git-publisher"
VAULT = ".nry-ftps-vault"
MAX_FILES = 220
MAX_BYTES = 12_000_000
ALLOWED_WP_CONTENT = frozenset({
    "/",  # an existing account already rooted at wp-content
    "/www/wp-content",
    "/www/domains/na-ryby.eu/wp-content",
})
MAIN_ACCOUNT_CANDIDATES = (
    "/www/domains/na-ryby.eu/wp-content",
    "/www/wp-content",
)
REQUIRED = frozenset({
    "na-ryby-git-publisher.php", "guarded-auto-publisher.php",
    "manifest.json", "RELEASE_INTEGRITY.json",
})
VAULT_DENY = (b"<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"
              b"<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n")


class SafeFtpStageError(Exception):
    """Only expose a fixed stage and three-digit FTP reply, never server text."""

    def __init__(self, stage: str, reply_code: str):
        super().__init__(stage + ":" + reply_code)


class SessionReuseFTP_TLS(FTP_TLS):
    """Reuse control-channel TLS on data transfers required by WEDOS vsftpd.

    The stdlib FTP_TLS opens a *new* TLS session for each RETR/STOR. WEDOS
    rejects these data channels with FTP 522, despite valid login and TLS.
    Keep certificate verification and encrypted data enabled; never fall back
    to plaintext FTP if resumption is unavailable.
    """

    def ntransfercmd(self, cmd, rest=None):
        data_socket, size = FTP.ntransfercmd(self, cmd, rest)
        if not self._prot_p:
            data_socket.close()
            raise ValueError("FTPS protected data channel is required")
        try:
            secured_socket = self.context.wrap_socket(
                data_socket, server_hostname=self.host, session=self.sock.session
            )
        except Exception:
            data_socket.close()
            raise
        return secured_socket, size


def ftps_stage(stage: str, operation):
    """Classify permission failures without disclosing FTP reply contents."""
    assert stage in {"connect", "login", "data_tls", "plugin_path"}
    try:
        return operation()
    except error_perm as exc:
        match = re.match(r"^([45][0-9]{2})(?:\s|$)", str(exc))
        raise SafeFtpStageError(stage, match.group(1) if match else "unknown") from None


def validate_sha(value: str) -> str:
    if not re.fullmatch(r"[a-f0-9]{40}", value):
        raise ValueError("GITHUB_SHA must be an exact 40-character commit SHA")
    return value


def validate_wp_content(value: str) -> str:
    if value not in ALLOWED_WP_CONTENT:
        raise ValueError("Unapproved WEDOS wp-content path; only exact known layouts are allowed")
    return value.rstrip("/")


def remote_path(parent: str, child: str) -> str:
    assert child and child[0] != "/" and ".." not in child.split("/")
    return parent + "/" + child


def choose_runtime_files(tracked: list[str], root: Path) -> dict[str, bytes]:
    """Deploy the entire PHP/asset/editorial runtime, not CI or developer files."""
    files: dict[str, bytes] = {}
    for name in sorted(tracked):
        p = Path(name)
        if (len(p.parts) == 1 and p.suffix.lower() in {".php", ".json", ".html"}) or (
            len(p.parts) >= 2 and p.parts[0] in {"assets", "editorial"}
        ):
            source = root / p
            if source.is_symlink() or not source.is_file() or p.is_absolute() or ".." in p.parts:
                raise ValueError("Unsafe runtime source file: " + name)
            payload = source.read_bytes()
            if not payload:
                raise ValueError("Empty runtime source file: " + name)
            files[p.as_posix()] = payload
    missing = REQUIRED - files.keys()
    if missing:
        raise ValueError("Missing essential plugin runtime: " + ", ".join(sorted(missing)))
    if len(files) > MAX_FILES or sum(map(len, files.values())) > MAX_BYTES:
        raise ValueError("Runtime payload exceeds bounded limits")
    loader = files["na-ryby-git-publisher.php"].decode("utf-8")
    modules = re.findall(r"require_once\s+__DIR__\s*\.\s*'/([^']+)'", loader)
    if not modules or any(module not in files for module in modules):
        raise ValueError("Missing required PHP module in runtime payload")
    return files


def checked_out_files(root: Path, sha: str) -> dict[str, bytes]:
    head = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=root, text=True).strip()
    if head != sha:
        raise ValueError("Checkout SHA differs from the exact reviewed main commit")
    tracked = subprocess.check_output(["git", "ls-files", "-z"], cwd=root).decode().split("\0")
    return choose_runtime_files([x for x in tracked if x], root)


def existing_dir(ftp: FTP_TLS, path: str) -> bool:
    original = ftp.pwd()
    try:
        ftp.cwd(path)
        return True
    except Exception as exc:
        # Only an ordinary FTP 550 missing-directory response means absence.
        if not str(exc).startswith("550"):
            raise
        return False
    finally:
        ftp.cwd(original)


def confirmed_plugin_root(ftp: FTP_TLS, wp_content: str) -> bool:
    """Only accept a WordPress root containing our precise installed plugin."""
    plugin_dir = remote_path(wp_content, "plugins/" + PLUGIN)
    if not existing_dir(ftp, plugin_dir):
        return False
    previous = bytearray()
    try:
        ftp.retrbinary("RETR " + remote_path(plugin_dir, PLUGIN + ".php"), previous.extend)
    except Exception as exc:
        if str(exc).startswith("550"):
            return False
        raise
    return b"NRY_Git_Publisher" in previous


def resolve_wp_content(ftp: FTP_TLS, configured: str) -> str:
    """Discover a single exact WEDOS target without asking for an extra account."""
    if configured:
        target = validate_wp_content(configured)
        if not confirmed_plugin_root(ftp, target):
            raise ValueError("Configured WEDOS path lacks the exact expected plugin")
        return target
    matches = [candidate for candidate in MAIN_ACCOUNT_CANDIDATES
               if confirmed_plugin_root(ftp, candidate)]
    if len(matches) != 1:
        raise ValueError("Unable to determine one exact WEDOS WordPress plugin root")
    return matches[0]


def make_remote_dir(ftp: FTP_TLS, path: str) -> None:
    if existing_dir(ftp, path):
        raise ValueError("Stage or backup directory already exists; recovery required")
    ftp.mkd(path)


def verify_remote_bytes(ftp: FTP_TLS, path: str, expected: bytes) -> None:
    hasher = hashlib.sha256()
    size = 0

    def chunk(data: bytes) -> None:
        nonlocal size
        size += len(data)
        hasher.update(data)

    ftp.retrbinary("RETR " + path, chunk)
    if size != len(expected) or hasher.digest() != hashlib.sha256(expected).digest():
        raise ValueError("Staged file SHA256 verification failed")


def private_vault(ftp: FTP_TLS, wp_content: str) -> str:
    """Protect the parent *before* transferring any source or moving old code."""
    vault = remote_path(wp_content, VAULT)
    deny = remote_path(vault, ".htaccess")
    if not existing_dir(ftp, vault):
        ftp.mkd(vault)
        ftp.storbinary("STOR " + deny, io.BytesIO(VAULT_DENY))
    verify_remote_bytes(ftp, deny, VAULT_DENY)
    return vault


def verify_vault_is_not_public(ftp: FTP_TLS, vault: str) -> None:
    """Require a live anonymous web denial for an existing FTPS probe file."""
    nonce = secrets.token_hex(12)
    marker = ".ftps-access-check-" + nonce + ".txt"
    probe_path = remote_path(vault, marker)
    payload = ("nry-private-probe-" + nonce).encode("ascii")
    ftp.storbinary("STOR " + probe_path, io.BytesIO(payload))
    try:
        verify_remote_bytes(ftp, probe_path, payload)
        req = Request("https://na-ryby.eu/wp-content/" + VAULT + "/" + marker,
                      headers={"Cache-Control": "no-cache", "User-Agent": "NaRybyFtpsVaultCheck/1.0"})
        try:
            with urlopen(req, timeout=20):
                raise ValueError("Private FTPS vault is publicly served or redirected")
        except HTTPError as exc:
            if exc.code not in {403, 404}:
                raise ValueError("Private FTPS vault denial was not confirmed") from exc
    finally:
        ftp.delete(probe_path)


def stage(ftp: FTP_TLS, vault: str, sha: str, files: dict[str, bytes]) -> str:
    # A fresh protected directory allows a safe rerun after a partial upload;
    # abandoned stages are kept for inspection, never silently overwritten.
    stage_dir = remote_path(vault, ".nry-ftps-stage-" + sha[:12] + "-" + secrets.token_hex(6))
    make_remote_dir(ftp, stage_dir)
    parents = set()
    for name in files:
        folder = Path(name).parent
        while folder != Path("."):
            parents.add(folder.as_posix())
            folder = folder.parent
    folders = sorted(parents, key=lambda item: (item.count("/"), item))
    for folder in folders:
        ftp.mkd(remote_path(stage_dir, folder))
    for name, data in files.items():
        path = remote_path(stage_dir, name)
        ftp.storbinary("STOR " + path, io.BytesIO(data))
        verify_remote_bytes(ftp, path, data)
    return stage_dir


def swap(ftp: FTP_TLS, wp_content: str, vault: str, stage_dir: str, sha: str) -> str:
    plugin_dir = remote_path(wp_content, "plugins/" + PLUGIN)
    backup_dir = remote_path(vault, ".nry-ftps-backup-" + sha[:12])
    if not existing_dir(ftp, plugin_dir):
        raise ValueError("Expected installed plugin directory does not exist; first install is out of scope")
    if existing_dir(ftp, backup_dir):
        raise ValueError("Unique remote rollback backup already exists")
    # The existing directory must be the correct plugin, not a nearby site.
    previous = bytearray()
    ftp.retrbinary("RETR " + remote_path(plugin_dir, PLUGIN + ".php"), previous.extend)
    if b"NRY_Git_Publisher" not in previous:
        raise ValueError("Remote plugin identity mismatch; refusing replacement")
    ftp.rename(plugin_dir, backup_dir)
    try:
        # The parent vault was already denied and byte-verified *before* any
        # content was uploaded or the previous plugin was moved.
        ftp.rename(stage_dir, plugin_dir)
    except Exception:
        # Before the first public GET/content trigger, recover exact prior code.
        ftp.rename(backup_dir, plugin_dir)
        raise
    return backup_dir


def deploy(ftp: FTP_TLS, wp_content: str, sha: str, files: dict[str, bytes]) -> str:
    if not confirmed_plugin_root(ftp, wp_content):
        raise ValueError("Remote plugin identity mismatch or wrong path; refusing release")
    vault = private_vault(ftp, wp_content)
    if existing_dir(ftp, remote_path(vault, ".nry-ftps-backup-" + sha[:12])):
        raise ValueError("Release backup already exists; repeat release requires operator review")
    verify_vault_is_not_public(ftp, vault)
    # No production file is moved until *every* upload has been retrieved and
    # verified byte-for-byte over encrypted FTPS.
    stage_dir = stage(ftp, vault, sha, files)
    return swap(ftp, wp_content, vault, stage_dir, sha)


def main() -> int:
    if len(sys.argv) != 2 or sys.argv[1] not in {"preflight", "inspect", "deploy"}:
        raise ValueError("Usage: deploy_via_wedos_ftps.py preflight|inspect|deploy")
    action = sys.argv[1]
    sha = validate_sha(os.environ.get("GITHUB_SHA", ""))
    if os.environ.get("GITHUB_REF") != "refs/heads/main":
        raise ValueError("FTPS production is permitted only for reviewed main")
    root = Path(__file__).resolve().parents[1]
    files = checked_out_files(root, sha)
    print("FTPS_RUNTIME_PREFLIGHT_OK sha=" + sha[:12] + " files=" + str(len(files)))
    if action == "preflight":
        return 0
    host = os.environ.get("NRY_FTPS_HOST", "").strip()
    user = os.environ.get("NRY_FTPS_USER", "")
    password = os.environ.get("NRY_FTPS_PASSWORD", "")
    configured_wp_content = os.environ.get("NRY_FTPS_WP_CONTENT", "")
    if not re.fullmatch(r"[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*\.wedos\.net", host):
        raise ValueError("FTPS host must be the assigned WEDOS hostname, never an arbitrary URL")
    if not user or not password or any(c in user for c in "\r\n\0"):
        raise ValueError("WEDOS FTPS credential fields are missing or invalid")
    tls = ssl.create_default_context()
    tls.minimum_version = ssl.TLSVersion.TLSv1_2
    with SessionReuseFTP_TLS(context=tls, timeout=30) as ftp:
        ftps_stage("connect", lambda: ftp.connect(host, 21, timeout=30))
        ftps_stage("login", lambda: ftp.login(user, password))
        ftps_stage("data_tls", ftp.prot_p)  # mandatory encrypted data
        ftp.set_pasv(True)
        wp_content = ftps_stage("plugin_path", lambda: resolve_wp_content(ftp, configured_wp_content))
        if action == "inspect":
            # No uploads, directory creation, or renames: only authenticate,
            # read the expected installed PHP loader and close the session.
            print("FTPS_REMOTE_INSPECT_OK sha=" + sha[:12] + " target=confirmed")
            return 0
        backup = deploy(ftp, wp_content, sha, files)
    print("FTPS_CODE_SWAPPED sha=" + sha[:12] + " rollback=" + backup)
    print("CONTENT_NOT_YET_VERIFIED: preserve prior code until protected public receipts pass")
    return 0


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as error:
        # Never print server URLs, credentials or exception messages that might
        # reflect FTP replies. The private runner retains its Python traceback
        # only if an operator explicitly reproduces the error locally.
        if isinstance(error, SafeFtpStageError):
            print("FTPS_BLOCKED: " + str(error))
        else:
            print("FTPS_BLOCKED: " + type(error).__name__)
        sys.exit(1)
