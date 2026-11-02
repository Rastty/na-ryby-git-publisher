#!/usr/bin/env python3
"""Read-only live public REST inspection for four existing buyer-guide posts.

No passwords, cookies, WP writes, post_content exports, affiliate link rewrites
or private source claim. This evidence is *not* an authenticated WP source SHA
and never independently grants publication authority.
"""
import html
import json
import re
import sys
import time
from datetime import datetime, timezone
from pathlib import Path
from urllib.request import Request, urlopen

ORIGIN = "https://na-ryby.eu"
CANDIDATES = (
    (34858, "detske-rybarske-kreslo"),
    (35003, "rybarske-kreslo-s-vysokym-operadlem"),
    (34953, "rybarska-kresla-v-akci"),
    (34969, "levne-rybarske-kreslo"),
)
PROTECTED = frozenset((38064, 42671, 44102))
FIELDS = "id,slug,type,status,link,modified_gmt,content,title"


def url_for(post_id):
    if type(post_id) is not int or post_id < 1:
        raise ValueError("INVALID_ID")
    return f"{ORIGIN}/wp-json/wp/v2/posts/{post_id}?_fields={FIELDS}"


def inspect(post_id, expected_hint, payload):
    base = {
        "id": post_id, "expected_slug_hint": expected_hint,
        "source": "ANONYMOUS_PUBLIC_REST_RENDERED_NOT_PRIVATE_POST_CONTENT",
        "authenticated_source_sha256": "UNKNOWN",
        "gsc_protection_state": "UNKNOWN_NOT_CHECKED",
        "wordpress_writes": 0, "publication_authorized": False,
    }
    if post_id in PROTECTED:
        return dict(base, state="PROTECTED_SKIP")
    if not isinstance(payload, dict) or payload.get("id") != post_id:
        return dict(base, state="PUBLIC_REST_MISMATCH")
    slug = payload.get("slug")
    link = payload.get("link")
    gmt = payload.get("modified_gmt")
    typ = payload.get("type")
    status = payload.get("status")
    content = payload.get("content")
    text = content.get("rendered") if isinstance(content, dict) else None
    if not isinstance(slug, str) or not re.fullmatch(r"[a-z0-9-]{8,160}", slug) \
       or not isinstance(link, str) or link != f"{ORIGIN}/{slug}/" \
       or typ != "post" or status != "publish" \
       or not isinstance(gmt, str) or not re.fullmatch(r"20\d\d-\d\d-\d\dT\d\d:\d\d:\d\d", gmt) \
       or not isinstance(text, str) or len(text) < 1000:
        return dict(base, state="PUBLIC_REST_IDENTITY_OR_SIZE_BLOCKED")
    try:
        datetime.fromisoformat(gmt)
    except ValueError:
        return dict(base, state="PUBLIC_REST_GMT_INVALID")
    marker_ids = sorted(set(re.findall(r'data-nry-editorial-edit="([a-z0-9-]{8,80})"', text)))
    heading_text = re.sub(r"<[^>]+>", " ", (re.search(r"<h[12][^>]*>(.*?)</h[12]>", text, flags=re.I|re.S) or ["",""])[1])
    heading_text = html.unescape(re.sub(r"\s+", " ", heading_text)).strip()[:140]
    return dict(base, state="PUBLIC_REST_FOUND_NEEDS_PRIVATE_SOURCE_GSC_CHECK",
                slug=slug, public_url=link, modified_gmt=gmt.replace("T", " "),
                rendered_bytes=len(text.encode("utf-8")),
                existing_editorial_markers=marker_ids,
                first_heading=heading_text,
                rendered_heading_count=len(re.findall(r"<h[1-6]\b", text, flags=re.I)),
                rendered_image_count=len(re.findall(r"<img\b", text, flags=re.I)),
                rendered_link_count=len(re.findall(r"<a\b", text, flags=re.I)))


def fetch_public(post_id, retries=2):
    url = url_for(post_id)
    req = Request(url, headers={"User-Agent": "NaRybyBuyerReadOnlyAudit/1.0",
                                "Accept": "application/json", "Cache-Control": "no-cache"})
    for n in range(retries):
        try:
            with urlopen(req, timeout=22) as response:
                if response.status != 200 or response.headers.get("Content-Type","").split(";")[0] != "application/json":
                    raise ValueError("INVALID_PUBLIC_REST_RESPONSE")
                raw = response.read(650001)
            if len(raw) > 650000:
                raise ValueError("PUBLIC_REST_TOO_LARGE")
            return json.loads(raw)
        except Exception:
            if n + 1 == retries:
                raise
            time.sleep(2)


def collect():
    checked=datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    rows=[]
    for post_id,hint in CANDIDATES:
        try:
            rows.append(inspect(post_id,hint,fetch_public(post_id)))
        except Exception as exc:
            # Never log response bodies or request headers/cookies.
            rows.append({"id":post_id,"state":"PUBLIC_REST_UNAVAILABLE",
                         "diagnostic":type(exc).__name__,
                         "publication_authorized":False,"wordpress_writes":0})
    return {"site":ORIGIN, "checked_utc":checked, "read_only":True,
            "private_source_sha_verified":False,"wordPress_writes":0,
            "eligible_for_automatic_publication":0, "rows":rows}


def main():
    report=collect()
    target=Path(sys.argv[1] if len(sys.argv)>1 else "nry-four-buyer-public-preflight.json")
    target.write_text(json.dumps(report,ensure_ascii=False,indent=2)+"\n",encoding="utf-8")
    print(json.dumps(report,ensure_ascii=False,sort_keys=True))
    # If the site is inaccessible the read-only diagnostic still records an
    # honest BLOCKED state. Never mark write-ready on partial public evidence.
    return 0


if __name__=="__main__":
    raise SystemExit(main())
