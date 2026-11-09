#!/usr/bin/env python3
"""Independent GET-only visitor proof of exact winter safety repair #45070."""
import re
import urllib.request
from html.parser import HTMLParser

URL = "https://na-ryby.eu/jak-si-vybavit-vybavu-pro-zimni-privlac-na-ryby/"
MARKER = 'data-nry-winter-ice-safety="45070-20261109-v1"'
DANGER = "nerespektovat varovné informace o nebezpečnosti"
ORIGINAL_ICE = "Pamatujte, že zimní přívlač na ryby se provádí na zamrzlých jezerech a rybnících."
ORIGINAL_DEF = "Toto speciální rybaření probíhá na zamrzlých vodách, které jsou kryty ledem."

class ArticleHTML(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonicals = []
        self.parts = []
        self.suppressed = 0
    def handle_starttag(self, tag, attrs):
        data = dict(attrs)
        if tag == "link" and "canonical" in data.get("rel", "").lower().split():
            self.canonicals.append(data.get("href", ""))
        if tag in ("script", "style", "noscript"):
            self.suppressed += 1
    def handle_endtag(self, tag):
        if tag in ("script", "style", "noscript") and self.suppressed:
            self.suppressed -= 1
    def handle_data(self, data):
        if not self.suppressed:
            self.parts.append(data)

def prove(html):
    if not isinstance(html, str) or len(html) < 2500 or len(html) > 2_000_000:
        raise AssertionError("PUBLIC_HTML_INVALID")
    if html.count(MARKER) != 1:
        raise AssertionError("SAFETY_BANNER_NOT_UNIQUELY_PUBLIC")
    p = ArticleHTML()
    p.feed(html)
    if p.canonicals != [URL]:
        raise AssertionError("CANONICAL_CHANGED_OR_UNKNOWN")
    visible = re.sub(r"\s+", " ", " ".join(p.parts))
    for bad in (DANGER, ORIGINAL_ICE, ORIGINAL_DEF):
        if bad in visible:
            raise AssertionError("DANGEROUS_OR_FALSE_ICE_TEXT_STILL_VISIBLE")
    if "Nevstupujte kvůli rybolovu na zamrzlé plochy" not in visible:
        raise AssertionError("PUBLIC_WARNING_MISSING")
    if "Zimní přívlač na otevřené vodě není lov pod ledem" not in visible:
        raise AssertionError("DIFFERENT_FISHING_METHOD_NOT_EXPLAINED")
    if "respektovat všechna varování před nebezpečným ledem" not in visible:
        raise AssertionError("OLD_NEGATED_WARNING_NOT_REPAIRED")
    return "PUBLIC_VERIFIED_WINTER_ICE_SAFETY_45070"

def check_site():
    req = urllib.request.Request(URL, headers={
        "User-Agent": "NaRyby-Winter-45070-ReadOnly/1.0",
        "Accept": "text/html", "Cache-Control": "no-cache"})
    with urllib.request.urlopen(req, timeout=20) as res:
        if res.status != 200 or res.geturl() != URL:
            raise AssertionError("PUBLIC_HTTP_OR_REDIRECT_UNVERIFIABLE")
        if "text/html" not in (res.headers.get("content-type") or ""):
            raise AssertionError("NON_HTML_RESPONSE")
        raw = res.read(2_000_001)
        if len(raw) > 2_000_000:
            raise AssertionError("PUBLIC_HTML_OVERSIZE")
    return prove(raw.decode("utf-8"))

if __name__ == "__main__":
    import sys
    if "--selftest" in sys.argv:
        template = ('<html><head><link rel="canonical" href="'+URL+
          '"></head><body><article><aside '+MARKER+
          '><p>Nevstupujte kvůli rybolovu na zamrzlé plochy. Zimní přívlač na otevřené vodě není lov pod ledem. '
          'Je proto nutné respektovat všechna varování před nebezpečným ledem.</p></aside>'+
          '<p>'+'Rybářské rady ze břehu. '*140+'</p></article></body></html>')
        assert prove(template) == "PUBLIC_VERIFIED_WINTER_ICE_SAFETY_45070"
        for malformed in (template.replace(MARKER, ''),
            template.replace('Nevstupujte kvůli rybolovu na zamrzlé plochy', DANGER),
            template.replace(URL, "https://other.example/", 1)):
            try:
                prove(malformed)
            except AssertionError:
                continue
            raise AssertionError("SELFTEST_FALSE_PASS")
        print("PASS: visitor warning, canonical, exact marker, dangerous text absence")
    else:
        print(check_site())
