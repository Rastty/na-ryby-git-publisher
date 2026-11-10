#!/usr/bin/env python3
"""Read-only verification of public sonar #118455 trust cleanup.

No WordPress writes, merchant requests, user events, external links or identifiers.
Only after guarded deploy; a green commit is not public proof.
"""
from html.parser import HTMLParser
from urllib.request import Request, urlopen

URL = ("https://na-ryby.eu/"
       "nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/")
MARKER = "<!-- nry-sonar-budget-trust-118455-v1 -->"
REQUIRED = (
    "Srovnání níže obsahuje starší příklady modelů a obchodů",
    "Garmin Striker Vivid a Lowrance Hook Reveal 5 tu slouží jako příklady",
    "Lowrance Hook Reveal 5 je zde starší příklad sestavy",
    "U vybraných variant Hook Reveal 5 stojí za prověření",
    "Větší displej může zlepšit čitelnost",
    "Jednoduchý začátek pro rybáře, kteří potřebují hlavně hloubku",
    "Modely Lowrance v tomto starším přehledu slouží jako příklady",
    "Tři praktické příklady podle způsobu použití",
    "Garmin Striker Vivid může být jednou z možností",
    "příslušenství v balení ověřte u prodejce",
)
# Earlier source-bound editorial cleanup already made case five safe on live
# WordPress. Preserve it: do not overwrite an editor's safer current statement.
SAFE_EXISTING_CASE_5 = ("Pokud chceš vytěžit maximum z limitu do 15 tisíc, "
                        "tady už dává větší smysl připlatit hlavně za displej. "
                        "Před koupí ověřte aktuální cenu a zda jsou baterie "
                        "a nabíječka součástí přesné varianty balení.")
FORBIDDEN = (
    "Zadání sice zmiňuje",
    "kde aktuálně dává větší smysl nabídka pro čtenáře",
    "lepší aktuální nabídku ze dvou mých preferovaných obchodů",
    "Hook Reveal 5 je v této chvíli z těchto dvou obchodů",
    "Chyť a pusť tu má rozumnou aktuální nabídku",
    "Oba obchody patří mezi moje ověřené top volby",
    "mi v danou chvíli vychází lépe reálně dostupné nabídky Lowrance",
    "Za mě jsou dnes v rámci dvou preferovaných obchodů nejlepší tři scénáře",
    "hlavní doporučení stavím jen na aktuálně dohledatelných nabídkách",
    "bonus baterie a nabíječka",
)


class Document(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals = []
        self.parts = []
        self.visible_parts = []
        self.script_parts = []
        self.hidden_parts = []
        self.script_depth = 0
        self.hidden_depth = 0

    def handle_starttag(self, tag, attrs):
        if tag == "link":
            data = dict(attrs)
            if data.get("rel") == "canonical":
                self.canonicals.append(data.get("href"))
        if tag == "script":
            self.script_depth += 1
        elif tag in ("style", "template", "noscript"):
            self.hidden_depth += 1

    def handle_endtag(self, tag):
        if tag == "script":
            self.script_depth = max(0, self.script_depth - 1)
        elif tag in ("style", "template", "noscript"):
            self.hidden_depth = max(0, self.hidden_depth - 1)

    def handle_data(self, text):
        self.parts.append(text)
        if self.script_depth:
            self.script_parts.append(text)
        elif self.hidden_depth:
            self.hidden_parts.append(text)
        else:
            self.visible_parts.append(text)


def verify(html):
    assert html.count(MARKER) == 1, "PUBLIC_MARKER_MISSING_OR_DUPLICATE"
    doc = Document()
    doc.feed(html)
    assert doc.canonicals == [URL], "CANONICAL_DRIFT"
    text = " ".join(doc.parts)
    # Boolean-only evidence: avoid writing page bodies, visitors or merchant hrefs
    # to public Actions logs. Keep the original exact public assertions.
    case5_existing_safe = SAFE_EXISTING_CASE_5 in text
    expected_present = [i + 1 for i, signal in enumerate(REQUIRED)
                        if signal in text or (i == 4 and case5_existing_safe)]
    old_claims_present = [i + 1 for i, bad in enumerate(FORBIDDEN)
                          if bad in text]
    # Case 9 still occurs somewhere despite corrected reader-facing copy.
    # Identify whether the stale assertion is from visible DOM or embedded SEO
    # script / hidden markup, without publishing a single page URL/href/body.
    old_faq_claim = FORBIDDEN[8]
    context = {
        "case9_in_visible_text": old_faq_claim in " ".join(doc.visible_parts),
        "case9_in_script": old_faq_claim in " ".join(doc.script_parts),
        "case9_in_hidden": old_faq_claim in " ".join(doc.hidden_parts),
        "case9_total_match_count": text.count(old_faq_claim),
    }
    print({"case9_origin_diagnostic": context}, flush=True)

    print({"expected_correction_indices": expected_present,
           "old_claim_indices": old_claims_present,
           "missing_correction_indices":
               [i for i in range(1, len(REQUIRED) + 1)
                if i not in expected_present],
           "case5_preexisting_safe_variant": case5_existing_safe,
           "marker_verified": True, "canonical_verified": True},
          flush=True)
    for i, signal in enumerate(REQUIRED):
        assert signal in text or (i == 4 and case5_existing_safe), (
            "EXPECTED_CORRECTION_OR_SOURCE_VERIFIED_SAFE_VARIANT_NOT_PUBLIC")
    for bad in FORBIDDEN:
        assert bad not in text, "EDITORIAL_ARTIFACT_OR_STALE_CURRENT_CLAIM"
    return {"status": "PUBLIC_VERIFIED", "safe_cases": len(REQUIRED),
            "newly_corrected_cases": 9 if case5_existing_safe else 10,
            "already_safe_cases": 1 if case5_existing_safe else 0,
            "old_claims": 0, "merchant_requests": 0, "wordpress_writes": 0}


if __name__ == "__main__":
    req = Request(URL, headers={"User-Agent": "NaRyby-TrustPublicReadback/1.0",
                                "Cache-Control": "no-cache"})
    with urlopen(req, timeout=22) as response:
        assert response.status == 200, "PUBLIC_HTTP_ERROR"
        page = response.read(1000000).decode("utf-8", "replace")
    print(verify(page))
