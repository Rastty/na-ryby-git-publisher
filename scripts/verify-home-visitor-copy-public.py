#!/usr/bin/env python3
"""Independent first-party GET-only proof of six source-locked homepage copy edits.

PR preflight: the OLD six process/editorial sentences still exist on production,
with no replacement marker. Post-deploy: NEW visitor sentences and one marker.
No merchant traffic, synthetic clicks, WordPress content writes or analytics.
"""
import json
import sys
from html.parser import HTMLParser
from urllib.error import HTTPError, URLError
from urllib.request import HTTPRedirectHandler, Request, build_opener

HOME = "https://na-ryby.eu/"
SOURCE = 'data-nry-home-trust="source-bound-118641-20261109-v1"'
MARKER = 'data-nry-home-visitor-copy="v1"'
SENTENCES = (
    ("Místo slabého archivního výpisu dostane návštěvník přehledný rozcestník podle záměru: výběr vybavení, nejlepší srovnání, praktické návody, začátky s rybařením i prostor pro budoucí revíry a pobyty.",
     "Vyberte si podle toho, co potřebujete: rybářskou výbavu, návod, revír nebo pobyt u vody. U konkrétních nabídek a pravidel lovu si vždy ověřte aktuální podmínky."),
    ("Každá hlavní sekce je řešená jako samostatná vizuální karta s obrázkem, krátkým úvodem a top odkazy.",
     "Vyberte oblast, která vás zajímá, a začněte praktickým návodem nebo srovnáním."),
    ("Top výběry, souboje značek a ručně vybrané články s vysokou klikatelností.",
     "Srovnání podle způsobu lovu, rozpočtu a důležitých vlastností výbavy."),
    ("Silné vstupní průvodce, které pomůžou pochopit parametry, rozpočet i rozdíly mezi variantami.",
     "Průvodci, kteří vysvětlují parametry, rozpočet a rozdíly mezi variantami."),
    ("Praktické průvodce, výběr vybavení a rybářský obsah stavěný tak, aby pomohl rychleji se zorientovat, vybrat správné řešení a nestrácet čas ve slabém obsahu.",
     "Praktické návody pro výběr výbavy, plánování výpravy a hledání vody. U konkrétních produktů a pobytů rozlišujeme rady od ověřovaných nabídek."),
    ("Vedle hlavních průvodců tu postupně vzniká i prostor pro revíry, ubytování a další inspiraci k vodě.",
     "Najdete tu také rybářské revíry a tipy na pobyty u vody.")
)
ROUTE_STAYS = 'href="https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/"'
ROUTE_WATERS = 'href="https://na-ryby.eu/category/soukrome-rybarske-reviry/"'


class Homepage(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.source_markers = 0
        self.copy_markers = 0
        self.words = []
        self.in_article = False
        self.article_depth = 0

    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        if tag == "link" and "canonical" in (a.get("rel") or "").lower().split():
            self.canonicals.append(a.get("href"))
        if a.get("data-nry-home-trust") == "source-bound-118641-20261109-v1":
            self.source_markers += 1
        if a.get("data-nry-home-visitor-copy") == "v1":
            self.copy_markers += 1

    def handle_data(self, data):
        if data.strip():
            self.words.append(data)

    def text(self):
        return " ".join(" ".join(self.words).split())


def verify(html, preflight=False):
    if not isinstance(html, str) or len(html) < 3000 or len(html) > 1_300_000:
        raise ValueError("BOUNDED_HOME_HTML_REQUIRED")
    tree = Homepage()
    tree.feed(html)
    if tree.canonicals != [HOME]:
        raise ValueError("HOME_CANONICAL_MISMATCH")
    if tree.source_markers != 1 or html.count(SOURCE) != 1:
        raise ValueError("SOURCE_PROOF_MISSING_OR_DUPLICATE")
    if (tree.copy_markers != (0 if preflight else 1) or
            html.count(MARKER) != (0 if preflight else 1)):
        raise ValueError("HOME_COPY_MARKER_NOT_IN_EXPECTED_STATE")
    plain = tree.text()
    expected = [pair[0 if preflight else 1] for pair in SENTENCES]
    disallowed = [pair[1 if preflight else 0] for pair in SENTENCES]
    for i, phrase in enumerate(expected):
        if plain.count(phrase) != 1:
            raise ValueError("HOME_COPY_MISSING_OR_DUPLICATE_" + str(i + 1))
    for i, phrase in enumerate(disallowed):
        if phrase in plain:
            raise ValueError("HOME_OLD_AND_NEW_COPY_BOTH_PRESENT_" + str(i + 1))
    # All verified visitor shortcuts stay first-party. The existing stricter
    # homepage entry proof checks the eight-link order and asset bytes.
    if ROUTE_STAYS not in html or ROUTE_WATERS not in html:
        raise ValueError("VERIFIED_STAY_OR_WATER_PATH_MISSING")
    if "DOPLNIT-URL" in html or "Vizuální placeholder" in html:
        raise ValueError("UNFINISHED_EDITORIAL_NOTE_REAPPEARED")
    return {
        "status": "HOME_VISITOR_COPY_PREFLIGHT_VERIFIED" if preflight
                  else "HOME_VISITOR_COPY_PUBLIC_VERIFIED",
        "canonical": HOME,
        "reviewed_sentences": len(SENTENCES),
        "copy_marker_count": tree.copy_markers,
        "private_waters_and_stays_present": True,
        "wordpress_writes": 0,
        "synthetic_clicks": 0,
        "affiliate_requests": 0,
        "traffic_or_commission_uplift": "NOT_PROVEN",
    }


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, message, headers, newurl):
        return None


def fetch_public():
    req = Request(HOME, headers={
        "User-Agent": "NaRybyHomeVisitorCopyPublicReadOnly/1.0",
        "Accept": "text/html", "Cache-Control": "no-cache",
    })
    with build_opener(NoRedirect).open(req, timeout=26) as response:
        if (response.status != 200 or response.geturl() != HOME
                or "text/html" not in response.headers.get("Content-Type", "").lower()):
            raise ValueError("UNTRUSTED_PUBLIC_RESPONSE")
        body = response.read(1_300_001)
    if len(body) > 1_300_000:
        raise ValueError("PUBLIC_RESPONSE_TOO_LARGE")
    return body.decode("utf-8", "replace")


if __name__ == "__main__":
    try:
        print(json.dumps(verify(fetch_public(), "--preflight" in sys.argv),
                         ensure_ascii=False, sort_keys=True))
    except (ValueError, HTTPError, URLError, TimeoutError, OSError) as exc:
        print(json.dumps({"status": "HOME_VISITOR_COPY_PUBLIC_NOT_VERIFIED",
                          "reason": type(exc).__name__ + ": " + str(exc),
                          "wordpress_writes": 0, "affiliate_requests": 0},
                         ensure_ascii=False))
        sys.exit(1)
