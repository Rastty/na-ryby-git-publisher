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
ALLOWED = (5, 6, 7, 8)


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
        if edit.get("placement") == "historic_sonar_offers_only":
            if (data.get("schema_version") != 8
                    or data.get("batch_id") != "echolot-budget-118455-three-offers-fix-20261108-v1"
                    or post_id != 118455
                    or marker != "echolot-budget-118455-three-offers-20261108-v1"
                    or len(edits) != 1):
                raise ValueError("UNAUTHORIZED_SONAR_OFFER_CORRECTION")
        if edit.get("placement") == "price_corrections_only":
            if (data.get("schema_version") != 8 or
                    data.get("batch_id") != "pipaky-pokrocile-five-price-fix-36702-20261107-v1" or
                    post_id != 36702 or
                    marker != "pipaky-pokrocile-36702-20261107-v1" or
                    len(edits) != 1):
                raise ValueError("UNAUTHORIZED_PIPAKY_PRICE_ONLY_RELEASE")
        if edit.get("placement") == "corrections_only":
            if (data.get("schema_version") != 8 or
                    data.get("batch_id") != "alarm-buyer-score-method-118218-20261104-v2" or
                    post_id != 118218 or
                    marker != "alarm-buyer-prices-trust-118218-v1" or
                    len(edits) != 1):
                raise ValueError("UNAUTHORIZED_CORRECTION_ONLY_RELEASE")
        ids.add(post_id)
        targets.append({"post_id": post_id, "slug": slug,
                        "edit_id": marker, "placement": edit.get("placement"),
                        "url": BASE + "/" + slug + "/"})
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
    # The protected trust-repair canary must also remove verified deceptive
    # source copy. An inserted banner alone does not pass this semantic gate.
    semantic_ok = True
    if target["post_id"] == 42401 and target["edit_id"] == "pipaky-navod-42401-quick-choice-20261107-v1":
        # Never pass an inserted marker alone as useful visitor advice.
        # Require all four problem-first choices and exact internal routes.
        begin = html.find('<section ' + marker)
        finish = html.find('</section>', begin) if begin >= 0 else -1
        panel = html[begin:finish] if finish > begin else ""
        routes = (
            'href="https://na-ryby.eu/proc-mi-signalizator-pipa-bez-zaberu-5-nejcastejsich-pricin-a-jak-je-vyresit/"',
            'href="https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/"',
            'href="https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/"',
            'href="https://na-ryby.eu/baterie-do-signalizatoru-ktere-vydrzi-nejdele-a-proc-nekupovat-levne/"',
        )
        semantic_ok = (
            panel.startswith('<section ' + marker)
            and panel.count('<li>') == 4
            and panel.count('<a href=') == 4
            and '<h2>Pípák na ryby: koupit nový, nebo nejdřív opravit nastavení?</h2>' in panel
            and all(phrase in panel for phrase in (
                'Pípá i bez záběru?',
                'Začínáte s jedním prutem?',
                'Chcete sadu s příposlechem?',
                'Pípák mlčí nebo je zvuk slabý?',
                'není důvod automaticky kupovat nový',
                'Ceny, sklad a kompatibilitu konkrétní varianty',
            ))
            and all(panel.count(route) == 1 for route in routes)
        )
    elif target["post_id"] == 60354 and target.get("placement") == "ranking_claims_only":
        # One immutable historic-score correction with NO new section marker.
        # Semantic text proof is mandatory; marker-only check cannot apply.
        # Decode HTML entities because old star symbols may be entity-encoded.
        from html import unescape
        text = unescape(html)
        obsolete = (
            "⭐⭐⭐⭐⭐", "⭐⭐⭐⭐",
            "Pokud jste vášnivý rybář, určitě oceníte naše produkty.",
            "Nejlepší poměr cena/výkon",
            "Top produkt na trhu",
            "100% vodotěsné",
            "Voděodolnost zaručena",
            "Pojďme se společně podívat na ty nejlepší signalizátory, kterou jsou momentálně na našem trhu.",
        )
        replacements = (
            "Jde o historický redakční přehled, nikoli nabídku našeho obchodu",
            "historické redakční označení bez ověřeného srovnání cen",
            "historicky uváděná prémiová sada",
            "Stupeň ochrany před vodou ověřte v návodu konkrétního modelu",
            "Odolnost vůči vodě ověřte podle specifikace výrobce",
            "Následuje historický přehled vybraných modelů, nikoli současný žebříček trhu.",
        )
        semantic_ok = (
            all(phrase not in text for phrase in obsolete)
            and text.count("bez zveřejněného testovacího skóre") == 6
            and all(text.count(phrase) == 1 for phrase in replacements)
            and all(token in text for token in (
                "Nejlepší rybářské hlásiče a signalizátory",
                "Zfish", "DAM", "Nash", "Hlásiče v AKCI",
            ))
        )
    elif target["post_id"] == 35755 and target["edit_id"] == "pipaky-diagnostika-35755-20261107-v1":
        # An HTML marker without the concrete four-symptom diagnostic or
        # preserved, safe same-site next steps is not a user-facing improvement.
        start = html.find('<section ' + marker)
        end = html.find('</section>', start) if start >= 0 else -1
        panel = html[start:end] if end > start else ""
        required = (
            "Nepípne ani při posunutí vlasce",
            "Pípá opakovaně, ale ryba nezabírá",
            "Na jemné záběry reaguje pozdě",
            "příposlech však mlčí",
            "Ověření před prvním nahozením",
        )
        routes = (
            'href="https://na-ryby.eu/baterie-do-signalizatoru-ktere-vydrzi-nejdele-a-proc-nekupovat-levne/"',
            'href="https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/"',
            'href="https://na-ryby.eu/poradce-vybavy/"',
        )
        semantic_ok = (
            panel.startswith('<section ' + marker)
            and '<h2>Pípák pípá bez záběru, nebo naopak mlčí?' in panel
            and panel.count('<li>') == 4
            and all(phrase in panel for phrase in required)
            and all(panel.count(route) == 1 for route in routes)
            and panel.count('<a href=') == 3
        )
    elif target["post_id"] == 36702 and target.get("placement") == "price_corrections_only":
        # The existing advanced-buyer section is deliberately unchanged.
        # This release is successful only when ALL five old prices are gone
        # and exactly five visible honest noncurrent-price labels remain.
        labels=("Cena: od 1000 Kč","Cena: od 800 Kč","Cena: od 3000 Kč",
                "Cena: od 1200 Kč","Cena: od 2000 Kč")
        semantic_ok=(
            count==1
            and all(label not in html for label in labels)
            and html.count("Historická cena – aktuálně neověřeno")==5
            and "Pípáky pro pokročilé: kdy má dražší sada opravdu smysl?" in html
            and "není nezávislý srovnávací test" in html
            and 'href="https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/"' in html
            and "rybarske-potreby-na-zaclony.cz" in html
            and "rybarske-potreby-na-lukavecu.cz" in html
        )
    elif target["post_id"] == 36702 and target["edit_id"] == "pipaky-pokrocile-36702-20261107-v1":
        # No marker-only success: verify four distinct buyer decisions, a
        # clearly disclosed non-tested historical brand comparison, and
        # three exact same-site next steps in the newly inserted section.
        start = html.find('<section ' + marker)
        end = html.find('</section>', start) if start >= 0 else -1
        panel = html[start:end] if end > start else ""
        routes = (
            'href="https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/"',
            'href="https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/"',
            'href="https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/"',
        )
        requirements = (
            "Pípáky pro pokročilé: kdy má dražší sada opravdu smysl?",
            "Lovíte blízko prutů",
            "Potřebujete slyšitelný přenos",
            "Rybaříte dlouho za deště",
            "Chcete rozšířit nebo nahradit",
            "není nezávislý srovnávací test",
        )
        semantic_ok = (
            panel.startswith('<section ' + marker)
            and panel.count('<li>') == 4
            and all(phrase in panel for phrase in requirements)
            and all(panel.count(route) == 1 for route in routes)
            and panel.count('<a href=') == 3
        )
    elif target["post_id"] == 60341:
        semantic_ok = (
            "Naše společnost se specializuje na výrobu a prodej" not in html
            and "Jsme si jisti, že si u nás vyberete židličku na ryby" not in html
            and "Na-ryby.eu je rybářský informační magazín" in html
        )
    elif target["post_id"] == 118455 and target.get("placement") == "historic_sonar_offers_only":
        # No invented marker. The actual three exact unsafe historical
        # price/stock claims must be absent, replaced by accurate warnings.
        old = (
            "v době přípravy článku skladem za 4 509 Kč",
            "aktuálně 10 542 Kč a skladem 1 ks",
            "v době přípravy článku skladem za 13 889 Kč",
        )
        expected = (
            "historická nabídka Eagle 4X – aktuální cenu a sklad ověřte u prodejce",
            "historická nabídka Hook Reveal 5 – aktuální cenu a sklad ověřte u prodejce",
            "historická nabídka Eagle 7 – aktuální cenu a sklad ověřte u prodejce",
        )
        # Keep the previous independently verified practical buying
        # guidance and full three internal routes, not just three substitutions.
        semantic_ok = (
            all(x not in html for x in old)
            and all(html.count(x) == 1 for x in expected)
            and "nepředstavují dnes ověřený ceník" in html
            and "nemusíte kupovat nové" in html
            and 'href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/"' in html
            and 'href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/"' in html
            and 'href="https://na-ryby.eu/poradce-vybavy/"' in html
        )
    elif target["post_id"] == 118455:
        # An inserted marker alone is not a buyer-journey improvement:
        # independently require the price warning and the three intended routes.
        semantic_ok = (
            "nepředstavují dnes ověřený ceník" in html
            and "nemusíte kupovat nové" in html
            and 'href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/"' in html
            and 'href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/"' in html
            and 'href="https://na-ryby.eu/poradce-vybavy/"' in html
        )
    elif target["post_id"] == 60637 and target["edit_id"] == "echolot-boat-decision-60637-20261108-v1":
        # Buyer help on a boat: no evidence for an added affiliate link.
        # Marker alone never verifies an actual useful decision path.
        start = html.find('<section ' + marker)
        end = html.find('</section>', start) if start >= 0 else -1
        panel = html[start:end] if end > start else ""
        routes = (
            'href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/"',
            'href="https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/"',
            'href="https://na-ryby.eu/poradce-vybavy/"',
        )
        semantic_ok = (
            panel.startswith('<section ' + marker)
            and '<h2>Echolot na loď: vyberte funkční sestavu, ne jen displej</h2>' in panel
            and '<h3>Čtyři kontroly před objednávkou</h3>' in panel
            and panel.count('<ul>') == 1 and panel.count('<ol>') == 1
            and panel.count('<li>') == 8
            and all(phrase in panel for phrase in (
                'Malý člun na známé vodě',
                'Kajak nebo přenosná sestava',
                'Hledáte hrany a překážky vedle člunu',
                'Chcete se vracet na vybraná místa',
                'kompatibilní sondy',
                'nemusíte kupovat novou',
                'Starší ceny a tvrzení o skladovosti nejsou důkazem aktuální nabídky.',
            ))
            and all(panel.count(route) == 1 for route in routes)
            and panel.count('<a href=') == 3
        )
    elif target["post_id"] == 36518 and target["edit_id"] == "echolot-historic-decision-36518-20261108-v1":
        # Historic comparison must not masquerade as current test, price
        # or stock data; ensure the actual four route decision guide exists.
        start = html.find('<section ' + marker)
        end = html.find('</section>', start) if start >= 0 else -1
        panel = html[start:end] if end > start else ""
        routes = (
            'href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/"',
            'href="https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/"',
            'href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/"',
            'href="https://na-ryby.eu/poradce-vybavy/"',
        )
        semantic_ok = (
            panel.startswith('<section ' + marker)
            and '<h2>Historické srovnání echolotů: co z něj využít při dnešním výběru</h2>' in panel
            and '<h3>Jak pokračovat bez zbytečného nákupu</h3>' in panel
            and panel.count('<ul>') == 1
            and panel.count('<li>') == 4
            and all(phrase in panel for phrase in (
                'Nejde o aktuální žebříček',
                'Chytáte převážně ze břehu',
                'Máte malý člun',
                'Vracíte se na stejné lovné body',
                'Chcete zkoumat prostor vedle člunu',
                'kompatibilitu a typ sondy',
                'nový echolot nepotřebujete',
            ))
            and all(panel.count(route) == 1 for route in routes)
            and panel.count('<a href=') == 4
        )
    elif target["post_id"] == 118553:
        # Require actual answer-first decision section, not a marker elsewhere
        # or duplicated existing links in the long historical article.
        begin = html.find('<section ' + marker)
        finish = html.find('</section>', begin) if begin >= 0 else -1
        panel = html[begin:finish] if finish > begin else ""
        paths = (
            'href="https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/"',
            'href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/"',
            'href="https://na-ryby.eu/poradce-vybavy/"',
        )
        semantic_ok = (
            panel.startswith('<section ' + marker)
            and '<h2>Jak vybrat vhodný typ echolotu</h2>' in panel
            and panel.count('<li>') == 4
            and 'další nákup není nutný' in panel
            and 'aktuální konečnou cenu kompletu' in panel
            and all(route in panel for route in paths)
        )
    elif target["post_id"] == 118218 and target.get("placement") == "corrections_only":
        # Exactly six unsubstantiated table ratings must be removed. The
        # previously verified buyer guide, warning section and affiliate links
        # remain on the same canonical page; no new front-end panel required.
        original_scores=("8,1 / 10","8,6 / 10","8,9 / 10",
                         "9,2 / 10","9,1 / 10","9,4 / 10")
        new_warning=(
            "Uvedené ceny v Kč a akce 1+1 jsou orientační, nikoli právě "
            "ověřené skladové nabídky. Bodová hodnocení jsme z tabulky "
            "odstranili, protože pro ně není zveřejněná srovnávací "
            "testovací metodika."
        )
        semantic_ok = (
            count == 1 and html.count("Bez doloženého skóre") == 6
            and all(score not in html for score in original_scores)
            and html.count(new_warning) == 1
            and 'href="https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/"' in html
            and 'href="https://na-ryby.eu/baterie-do-signalizatoru-ktere-vydrzi-nejdele-a-proc-nekupovat-levne/"' in html
            and all(name in html for name in (
                "NGT", "DAM", "Delphin", "Helios", "Zx8", "Flajzar"))
            and "Zjistit cenu" in html
        )
    elif target["post_id"] == 118218:
        # Marker-only pass is not enough: honest price/ratings warning must
        # exist ahead of the unchanged commercial table and affiliate links.
        start = html.find('<section ' + marker)
        end = html.find('</section>', start) if start >= 0 else -1
        section = html[start:end] if end > start else ""
        allowed = (
            'href="https://na-ryby.eu/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/"',
            'href="https://na-ryby.eu/baterie-do-signalizatoru-ktere-vydrzi-nejdele-a-proc-nekupovat-levne/"',
        )
        semantic_ok = (
            section.startswith('<section ' + marker)
            and '<h2>Signalizátor záběru: podle čeho vybrat' in section
            and section.count('<li>') == 4
            and 'číselná hodnocení typu 8,9/10' in section
            and 'nezávisle ověřenými cenami' in section
            and 'historické orientační údaje' in section
            and 'akce 1+1 může skončit' in section
            and all(route in section for route in allowed)
        )
    semantic_only = (
        target["post_id"] == 60354 and target.get("placement") == "ranking_claims_only"
    ) or (
        target["post_id"] == 118455 and target.get("placement") == "historic_sonar_offers_only"
    )
    passed = (response.get("status") == "READ_OK" and response.get("http") == 200
              and (count == 1 or (semantic_only and semantic_ok)) and semantic_ok)
    return {"post_id": target["post_id"], "url": target["url"],
            "http": response.get("http"), "marker_count": count,
            "semantic_trust_verified": semantic_ok if target["post_id"] in (60354,42401,35755,36702,60341,118455,118553,118218,60637,36518) else None,
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
