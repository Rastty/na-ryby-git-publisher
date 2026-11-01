#!/usr/bin/env python3
"""Independent public verification of the current, bounded Git editorial manifest.

No WordPress credentials, no API mutations and no private post source access.
A green GitHub workflow means only that the expected markers were visible to
an external HTTP reader at the measurement time, not that indexing succeeded.
"""
import hashlib
import json
import re
import sys
import urllib.error
import urllib.request
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urlparse

BASE = "https://na-ryby.eu"
ALLOWED = (5, 6, 7)


def verify_manifest(data):
    if not isinstance(data, dict) or data.get("schema_version") not in ALLOWED or data.get("site") != BASE:
        raise ValueError("UNSUPPORTED_MANIFEST")
    edits = data.get("edits")
    if not isinstance(edits, list) or not 1 <= len(edits) <= 4:
        raise ValueError("UNSUPPORTED_BATCH_SIZE")
    ids = set()
    targets = []
    for edit in edits:
        if not isinstance(edit, dict):
            raise ValueError("INVALID_EDIT")
        post_id, slug, marker = edit.get("post_id"), edit.get("slug"), edit.get("edit_id")
        if (type(post_id) is not int or post_id < 1 or post_id in ids
                or not isinstance(slug, str) or not re.fullmatch(r"[a-z0-9-]{10,140}", slug)
                or not isinstance(marker, str) or not re.fullmatch(r"[a-z0-9][a-z0-9-]{7,63}", marker)):
            raise ValueError("INVALID_EDIT_IDENTITY")
        ids.add(post_id)
        targets.append({"post_id": post_id, "slug": slug,
                        "edit_id": marker, "url": BASE + "/" + slug + "/"})
    return targets


def read_public(url):
    req = urllib.request.Request(url, headers={
        "User-Agent": "NaRyby-AutoPublisher-ExternalReadback/1.0",
        "Cache-Control": "no-cache", "Pragma": "no-cache",
    })
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            final = urlparse(resp.geturl())
            if final.scheme != "https" or final.netloc != "na-ryby.eu":
                return {"status": "REDIRECT_OUTSIDE_ORIGIN", "http": resp.status}
            return {"status": "READ_OK", "http": resp.status,
                    "html": resp.read(1200000).decode("utf-8", "replace")}
    except urllib.error.HTTPError as exc:
        return {"status": "HTTP_ERROR", "http": exc.code}
    except (urllib.error.URLError, TimeoutError, OSError) as exc:
        return {"status": "READER_ERROR", "error_type": type(exc).__name__}


def check_target(target, fetch=read_public):
    response = fetch(target["url"])
    html = response.get("html", "")
    marker = 'data-nry-editorial-edit="' + target["edit_id"] + '"'
    count = html.count(marker)
    passed = response.get("status") == "READ_OK" and response.get("http") == 200 and count == 1
    return {"post_id": target["post_id"], "url": target["url"],
            "http": response.get("http"), "marker_count": count,
            "status": "PUBLIC_MARKER_VERIFIED" if passed else
                      ("NOT_PUBLIC_OR_CACHE_STALE" if response.get("status") == "READ_OK"
                       else response.get("status", "READER_ERROR"))}


def audit(data, fetch=read_public):
    targets = verify_manifest(data)
    with ThreadPoolExecutor(max_workers=4) as pool:
        rows = list(pool.map(lambda target: check_target(target, fetch), targets))
    positive = sum(row["status"] == "PUBLIC_MARKER_VERIFIED" for row in rows)
    return {"checked_at_utc": datetime.now(timezone.utc).isoformat(),
            "batch_id": data.get("batch_id"), "schema_version": data["schema_version"],
            "expected": len(rows), "public_verified": positive,
            "status": "PUBLIC_VERIFIED_ALL" if positive == len(rows) else "PUBLIC_NOT_VERIFIED",
            "rows": rows}


if __name__ == "__main__":
    path = Path(sys.argv[1] if len(sys.argv) > 1 else "manifest.json")
    try:
        raw = path.read_bytes()
        result = audit(json.loads(raw))
        result["manifest_sha256"] = hashlib.sha256(raw).hexdigest()
    except (ValueError, OSError, json.JSONDecodeError) as err:
        result = {"status": "AUDIT_BLOCKED",
                  "reason": str(err).split("\n", 1)[0][:90]}
    Path("auto-publisher-public-readback.json").write_text(
        json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(result, ensure_ascii=False))
    sys.exit(0 if result.get("status") == "PUBLIC_VERIFIED_ALL" else 1)
