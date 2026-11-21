#!/usr/bin/env python3
"""Anonymous GET-only source proof for exact old 'fishing by car' article.

Reports identity and hashes only; never fetches merchant tracking URLs.
"""
import hashlib
import json
import re
import sys
from pathlib import Path
from urllib.parse import urlencode
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

BASE = "https://na-ryby.eu"
SLUG = "1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem"
PAGE = BASE + "/" + SLUG + "/"
PROOF = BASE + "/poradce-vybavy/?nry_reviry_autem_source_v1=1"
REST = BASE + "/wp-json/wp/v2/posts?" + urlencode({
    "slug": SLUG, "_fields": "id,slug,status,type,modified_gmt,link,content"})
ANCHORS = {
    "orlíkWrongGeo": "Orlická přehrada se nachází východně od Hradce Králové",
    "podyjiGeneric": "Rybářský revír Podyjí",
    "sumavaGeneric": "Rybářský revír Šumava",
    "lipnoGeneric": "Rybářský revír Lipno",
    "originalH2": "Nejlepší rybářské revíry v Česku, kam se dostanete autem",
}


def parse(rest_rows, html):
    if not isinstance(rest_rows, list) or len(rest_rows) != 1:
        raise ValueError("EXACT_SLUG_RESULT_NOT_UNIQUE")
    p = rest_rows[0]
    if p["slug"] != SLUG or p["link"] != PAGE or p["status"] != "publish" or p["type"] != "post":
        raise ValueError("IDENTITY_DRIFT")
    rendered = p["content"]["rendered"]
    if not isinstance(rendered, str) or len(rendered.encode("utf-8")) < 2000:
        raise ValueError("CONTENT_MISSING")
    if "postid-" + str(p["id"]) not in html or "rel=\"canonical\" href=\"" + PAGE + "\"" not in html:
        raise ValueError("FRONTEND_IDENTITY_DRIFT")
    counts = {key: rendered.count(value) for key, value in ANCHORS.items()}
    if any(v < 1 for v in counts.values()):
        raise ValueError("EXPECTED_ARTICLE_ANCHOR_MISSING")
    blob = rendered.encode("utf-8")
    return {
        "status": "NRY_REVIRY_AUTEM_SOURCE_OBSERVED",
        "id": p["id"], "slug": p["slug"], "modifiedGmt": p["modified_gmt"],
        "renderedBytes": len(blob),
        "renderedPrefix512Sha256": hashlib.sha256(blob[:512]).hexdigest(),
        "anchorCounts": counts,
        "frontendHasIncorrectGeo": ANCHORS["orlíkWrongGeo"] in html,
        "wordpressWrites": 0, "merchantRequests": 0, "syntheticClicks": 0,
    }


def verify_raw(proof, observed):
    if not isinstance(proof, dict) or proof.get("schema") != 1 \
            or proof.get("state") != "SOURCE_SHA_AVAILABLE_NO_PUBLICATION":
        raise ValueError("RAW_PROOF_NOT_AVAILABLE")
    if proof.get("postId") != observed["id"] or proof.get("slug") != SLUG \
            or proof.get("canonical") != PAGE:
        raise ValueError("RAW_PROOF_POST_DRIFT")
    if proof.get("modifiedGmt") != observed["modifiedGmt"].replace("T", " "):
        raise ValueError("RAW_MODIFIED_DRIFT")
    sha = proof.get("sourceSha256", "")
    if not isinstance(sha, str) or not re.fullmatch("[0-9a-f]{64}", sha):
        raise ValueError("RAW_SHA_INVALID")
    if type(proof.get("sourceBytes")) is not int or proof["sourceBytes"] < 2000:
        raise ValueError("RAW_LENGTH_INVALID")
    counts = proof.get("literalCounts")
    if not isinstance(counts, dict) or set(counts) != {"wrongGeo", "podyji", "sumava", "lipno"} \
            or any(type(v) is not int or not 0 <= v < 100 for v in counts.values()):
        raise ValueError("RAW_COUNTS_INVALID")
    if any(proof.get(x) != 0 for x in ("wordpressWrites", "merchantRequests", "syntheticClicks")):
        raise ValueError("UNEXPECTED_SIDE_EFFECT")
    return {"status": "NRY_REVIRY_AUTEM_RAW_SOURCE_SHA_VERIFIED",
            "id": observed["id"], "slug": SLUG, "modifiedGmt": observed["modifiedGmt"],
            "sourceSha256": sha, "sourceBytes": proof["sourceBytes"],
            "literalCounts": counts, "wordpressWrites": 0, "merchantRequests": 0,
            "syntheticClicks": 0}


def get(url, ceiling):
    if url not in (PAGE, REST, PROOF):
        raise ValueError("UNAPPROVED_GET")
    request = Request(url, headers={"User-Agent": "NaRyby-ReviryAutem-ReadOnly/1.0"})
    with guarded_urlopen(request, timeout=25) as response:
        if response.status != 200 or response.geturl() != url:
            raise ValueError("STATUS_OR_REDIRECT_DRIFT")
        raw = response.read(ceiling + 1)
        if len(raw) > ceiling:
            raise ValueError("RESPONSE_TOO_LARGE")
        return raw


def main():
    if sys.argv[1:] not in (["--verify"], ["--raw"]):
        raise ValueError("ONLY_READONLY_MODES")
    data = json.loads(get(REST, 1_000_000))
    html = get(PAGE, 2_000_000).decode("utf-8", "replace")
    observed = parse(data, html)
    if sys.argv[1:] == ["--raw"]:
        proof = json.loads(get(PROOF, 12000))
        print(json.dumps(verify_raw(proof, observed), ensure_ascii=False, sort_keys=True))
    else:
        print(json.dumps(observed, ensure_ascii=False, sort_keys=True))


if __name__ == "__main__":
    try:
        main()
    except (ValueError, KeyError, TypeError, OSError):
        print(json.dumps({"status": "NRY_REVIRY_AUTEM_SOURCE_BLOCKED"}))
        raise SystemExit(1)
