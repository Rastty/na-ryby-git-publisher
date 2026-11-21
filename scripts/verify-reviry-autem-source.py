#!/usr/bin/env python3
"""Anonymous GET-only source proof for exact old 'fishing by car' article.

Reports identity and hashes only; never fetches merchant tracking URLs.
"""
import hashlib
import json
import re
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlencode
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

BASE = "https://na-ryby.eu"
SLUG = "1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem"
PAGE = BASE + "/" + SLUG + "/"
PROOF = BASE + "/poradce-vybavy/?nry_reviry_autem_source_v1=1"
STYLE_PATH = "/wp-content/plugins/na-ryby-git-publisher/assets/nry-reviry-autem-trust-56493-v1.css"
STYLE_URL = BASE + STYLE_PATH + "?ver=1.0.0"
ROOT = Path(__file__).resolve().parents[1]
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


class Readback(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.styles = []
        self.panels = 0
        self.links = []
        self.in_panel = False

    def handle_starttag(self, tag, attrs):
        d = dict(attrs)
        if tag == "link":
            if "canonical" in (d.get("rel") or "").split():
                self.canonicals.append(d.get("href"))
            if (d.get("href") or "").split("?")[0] in (STYLE_PATH, BASE + STYLE_PATH):
                self.styles.append(d.get("href"))
        if tag == "section" and d.get("data-nry-reviry-autem-trust") == "v1":
            self.panels += 1
            self.in_panel = True
        if tag == "a" and self.in_panel:
            self.links.append(d.get("href"))

    def handle_endtag(self, tag):
        if tag == "section" and self.in_panel:
            self.in_panel = False


def verify_public(html, css, source, css_reviewed):
    check = Readback()
    check.feed(html)
    check.close()
    if "postid-56493" not in html or check.canonicals != [PAGE]:
        raise ValueError("PUBLIC_IDENTITY_DRIFT")
    if check.panels != 1 or html.count('data-nry-reviry-autem-trust="v1"') != 1:
        raise ValueError("PANEL_MISSING_OR_DUPLICATE")
    if source["sourceSha256"] != EXPECTED_RAW_SHA256:
        raise ValueError("POST_RAW_SOURCE_DRIFT")
    expected_links = [
        "https://na-ryby.eu/rybareni-s-ubytovanim-jihocesky-kraj/",
        "https://na-ryby.eu/kde-prespat-u-orliku-na-rybarsky-vikend/",
        "https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/",
        "https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/",
    ]
    if check.links != expected_links:
        raise ValueError("EXPECTED_EXACT_INTERNAL_ROUTES_MISSING")
    if any(t in html for t in ANCHORS.values() if t in (
            ANCHORS["orlíkWrongGeo"], ANCHORS["podyjiGeneric"],
            ANCHORS["sumavaGeneric"])):
        raise ValueError("PUBLIC_INACCURATE_COPY_STILL_PRESENT")
    for good in ("Orlická přehrada leží na Vltavě.",
                 "Podyjí: lov jen na konkrétně povolených úsecích",
                 "Šumava: nejprve ověřte konkrétní revír",
                 "Ubytování není automaticky povolenkou k rybolovu.",
                 "Podyjí a Šumava jsou chráněná území"):
        if good not in html:
            raise ValueError("PUBLIC_TRUST_COPY_MISSING")
    if check.styles != [STYLE_URL] and check.styles != [STYLE_PATH + "?ver=1.0.0"]:
        raise ValueError("STYLESHEET_MISSING")
    if len(css) < 600 or css != css_reviewed:
        raise ValueError("STYLESHEET_BYTES_MISMATCH")
    if html.index('data-nry-reviry-autem-trust="v1"') > html.index("Podyjí: lov jen na"):
        raise ValueError("NEW_GUIDANCE_NOT_EARLY")
    return {"status": "NRY_REVIRY_AUTEM_56493_PUBLIC_VERIFIED",
            "postId": 56493, "internalRoutes": len(check.links),
            "badGeoRemoved": True, "protectedAreaGuardAdded": True,
            "sourceSha256": source["sourceSha256"], "wordpressWrites": 0,
            "merchantRequests": 0, "syntheticClicks": 0,
            "realClicks": None, "completedBookings": None, "paidCommissions": None}


# Must equal the exact approved raw-source proof. A pending value is disarmed.
EXPECTED_RAW_SHA256 = "71372809934bc056f73f3e918be4e4c45d238c360d1e5155252c508cc9cd2491"


def get(url, ceiling):
    if url not in (PAGE, REST, PROOF, STYLE_URL):
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
    if sys.argv[1:] not in (["--verify"], ["--raw"], ["--public"]):
        raise ValueError("ONLY_READONLY_MODES")
    data = json.loads(get(REST, 1_000_000))
    html = get(PAGE, 2_000_000).decode("utf-8", "replace")
    observed = parse(data, html)
    if sys.argv[1:] in (["--raw"], ["--public"]):
        proof = json.loads(get(PROOF, 12000))
        raw = verify_raw(proof, observed)
        if sys.argv[1:] == ["--public"]:
            css = get(STYLE_URL, 100000)
            reviewed = (ROOT / "assets/nry-reviry-autem-trust-56493-v1.css").read_bytes()
            result = verify_public(html, css, raw, reviewed)
        else:
            result = raw
        print(json.dumps(result, ensure_ascii=False, sort_keys=True))
    else:
        print(json.dumps(observed, ensure_ascii=False, sort_keys=True))


if __name__ == "__main__":
    try:
        main()
    except (ValueError, KeyError, TypeError, OSError) as exc:
        # Never log a request URL, URL query, response content, or credentials.
        # HTTP status/error type separate network denial from content drift.
        import urllib.error
        status_code = exc.code if isinstance(exc, urllib.error.HTTPError) else None
        print(json.dumps({"status": "NRY_REVIRY_AUTEM_SOURCE_BLOCKED",
                          "errorType": type(exc).__name__,
                          "httpStatus": status_code}))
        raise SystemExit(1)
