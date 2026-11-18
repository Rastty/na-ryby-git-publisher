#!/usr/bin/env python3
"""Anonymous GET-only test; NEVER opens partner/redirect/permit source URLs."""
import datetime
import hashlib
import json
import sys
import urllib.request
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit

PAGE = "https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/"
FACTS = 'data-nry-dyje6-facts="v1"'
OFFERS = 'data-nry-dyje6-offers="v1"'
SLEVOMAT = "https://www.slevomat.cz/akce/2464086-dovolena-na-palave-polopenze-i-vstup-do-wellness"
HPORTAL = "https://go.dognet.com/?chid=5QoXiSqx&amp;url=https%3A%2F%2Fhportal.cz%2Fcs%2Fpasohlavky%2Fpenzion-pasohlavky%2F"
HAUZI = "https://tracking.affiliateport.eu/click?o=1990&amp;a=1736&amp;sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fchalupa-dolni-vestonice"
ASSET = "/wp-content/plugins/na-ryby-git-publisher/assets/nry-buyer-clicks-v1.js"
VERSION = "ver=1.11.1"
ROOT = Path(__file__).resolve().parents[1]


class ReadOnlyPage(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.script_versions = []

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if tag == "link" and "canonical" in (attrs.get("rel") or "").split():
            self.canonicals.append(attrs.get("href"))
        if tag == "script":
            src = urlsplit(attrs.get("src") or "")
            if src.path == ASSET and not src.fragment and src.scheme in ("", "https") \
                    and src.netloc in ("", "na-ryby.eu"):
                self.script_versions.append(src.query)


def section(html, marker):
    at = html.find(marker)
    start = html.rfind("<section", 0, at) if at >= 0 else -1
    end = html.find("</section>", start) if start >= 0 else -1
    return html[start:end] if end > start else ""


def verify(html, *, deployed=False, public_js=b"", reviewed_js=b""):
    if not isinstance(html, str):
        return False
    facts, offers = section(html, FACTS), section(html, OFFERS)
    valid = all((
        html.count(FACTS) == 1,
        html.count(OFFERS) == 1,
        FACTS in facts and OFFERS in offers,
        'denní 350 Kč, týdenní 1 380 Kč' in facts,
        'státní rybářský lístek' in facts,
        'Rybníkářstvím Pohořelice' in facts,
        'přírodní rezervace' in facts,
        'Lov jen na určeném úseku jižní hráze' in facts,
        'www.rybnikarstvipohorelice.cz/dyje-6/' in facts,
        'Hotel Termal Mušov' in offers,
        'Penzion Pasohlávky' in offers,
        'Chalupa Dolní Věstonice' in offers,
        offers.count('data-nry-offer-id=') == 3,
        offers.count(SLEVOMAT) == 1,
        offers.count(HPORTAL) == 1,
        offers.count(HAUZI) == 1,
        'data-nry-offer-id="hauzi-chalupa-dolni-vestonice"' in offers,
        'utm_term=j3d7tsgc&amp;a_box=j3d7tsgc' in offers,
        'https://na-ryby.eu/booking-cesko' in offers,
        'denní povolenka: 200 korun' not in html,
        'týdenní povolenka: 800 korun' not in html,
        'prostor pro <a' not in html,
    ))
    if not valid or not deployed:
        return valid
    page = ReadOnlyPage()
    page.feed(html)
    return all((
        page.canonicals == [PAGE],
        page.script_versions == [VERSION],
        len(public_js) > 1000,
        public_js == reviewed_js,
    ))


def main():
    if datetime.date.today() > datetime.date(2027, 3, 31):
        raise ValueError("OFFER_EXPIRED_REVIEW_REQUIRED")
    req = urllib.request.Request(PAGE, headers={
        "Cache-Control": "no-cache", "Pragma": "no-cache",
        "User-Agent": "NaRyby-Dyje6-Stay-GET-Only/1.0",
    })
    with urllib.request.urlopen(req, timeout=25) as response:
        final = urlsplit(response.geturl())
        if response.status != 200 or final.scheme != "https" or final.netloc != "na-ryby.eu":
            raise ValueError("unsafe final response")
        raw = response.read(1_600_001)
        if len(raw) > 1_600_000:
            raise ValueError("unexpectedly long response")
    html = raw.decode("utf-8", "replace")
    css_deployed = 'nry-dyje6-stay-trust-v1.css?ver=1.0.0' in html
    script_url = "https://na-ryby.eu" + ASSET + "?" + VERSION
    script_req = urllib.request.Request(script_url, headers={
        "Cache-Control": "no-cache", "User-Agent": "NaRyby-Dyje6-Stay-GET-Only/1.0",
    })
    with urllib.request.urlopen(script_req, timeout=25) as response:
        final = urlsplit(response.geturl())
        if response.status != 200 or final.scheme != "https" or final.netloc != "na-ryby.eu":
            raise ValueError("unsafe script response")
        public_js = response.read(50_001)
        if len(public_js) > 50_000:
            raise ValueError("unexpectedly large script")
    reviewed_js = (ROOT / "assets/nry-buyer-clicks-v1.js").read_bytes()
    ok = verify(html, deployed=True, public_js=public_js, reviewed_js=reviewed_js)
    print(json.dumps({"url": PAGE, "status": "PUBLIC_VERIFIED" if ok else "PUBLIC_NOT_VERIFIED",
                      "facts_count": html.count(FACTS), "offer_count": html.count(OFFERS),
                      "css_deployed": css_deployed,
                      "stale_price_visible": "denní povolenka: 200 korun" in html,
                      "buyer_js_sha256": hashlib.sha256(public_js).hexdigest(),
                      "no_write": True, "no_partner_requests": True}, ensure_ascii=False))
    return 0 if ok else 1


if __name__ == "__main__":
    try:
        sys.exit(main())
    except (OSError, ValueError) as exc:
        print(json.dumps({"status": "READBACK_BLOCKED", "error_type": type(exc).__name__}))
        sys.exit(1)
