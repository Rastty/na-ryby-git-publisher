#!/usr/bin/env python3
"""Offline public proof contract; no network or third-party calls."""
import runpy
from pathlib import Path

m = runpy.run_path(str(Path(__file__).resolve().parents[1] /
                       "scripts/verify-sonar-outbound-public-v1.py"))
canonical = '<link rel="canonical" href="' + m["ORIGIN"] + m["PAGE"] + '">'
tag = '<script src="' + m["ORIGIN"] + m["ASSET"] + '?' + m["VERSION"] + '"></script>'
dognet = '<a href="https://go.dognet.com/?a=123">Dognet product</a>'
merchant = '<a href="https://www.chytapust.cz/produkt/sample?tracking=redacted">Shop</a>'
source = ('<html><head>' + canonical + '</head><body>' + tag +
          dognet + merchant + '</body></html>').encode()
script = b"/* reviewed exact JS */\n"
assert m["verify"](source, script, script)["status"] == "PUBLIC_ASSET_VERIFIED"
assert m["verify"](source, script, script)["ga4DeliveryProven"] is False

# Tracking-parameter presence is an attribution HINT only, not affiliate proof.
# Four different merchant URLs, deliberately with disjoint query shapes.
plain='<a href="https://www.chytapust.cz/prod/123">Bare</a>'
affiliate='<a href="https://www.chytapust.cz/prod/234?a_box=PRIVATE_AFFILIATE_TAG">Affiliate hint</a>'
campaign='<a href="https://www.chytapust.cz/prod/345?utm_source=site">Campaign</a>'
other='<a href="https://www.chytapust.cz/prod/456?variant=blue">Unknown query</a>'
classified=source.replace(merchant.encode(),
    (plain+affiliate+campaign+other).encode())
status=m["verify"](classified,script,script)
assert status["merchant_cta_count"] == 4
assert status["merchant_distinct_destination_count"] == 4
assert status["merchant_bare_url_count"] == 1
assert status["merchant_affiliate_parameter_hint_count"] == 1
assert status["merchant_campaign_only_url_count"] == 1
assert status["merchant_other_query_url_count"] == 1
assert status["attribution_qualification"] == "URL_HINTS_ONLY_NOT_VERIFIED_AFFILIATE_CREDIT"
assert "PRIVATE_AFFILIATE_TAG" not in str(status)
assert "chytapust.cz/prod" not in str(status)
duplicate=source.replace(merchant.encode(),(plain+plain).encode())
assert m["verify"](duplicate,script,script)["merchant_distinct_destination_count"] == 1
assert m["verify"](duplicate,script,script)["merchant_cta_count"] == 2

def should_block(name, html=source, installed=script, expected=script):
    try:
        m["verify"](html, installed, expected)
    except AssertionError:
        return
    raise AssertionError(name + " must fail closed")
should_block("missing Dognet", source.replace(dognet.encode(), b""))
should_block("missing merchant", source.replace(merchant.encode(), b""))
should_block("wrong canonical", source.replace(canonical.encode(), b""))
should_block("script version drift", source.replace(b"ver=1.2.0", b"ver=1.1.0"))
should_block("duplicate script", source.replace(tag.encode(), tag.encode()*2))
should_block("unexpected installed JS", installed=b"/* stale JS */")
should_block("untrusted click-out merchant",
    source.replace(merchant.encode(), b'<a href="https://evilchytapust.cz/offer">Wrong</a>'))
should_block("untrusted redirect",
    source.replace(dognet.encode(), b'<a href="http://go.dognet.com/offer">HTTP</a>'))
assert m["get"].__name__ == "get"
print("PASS sonar public readback offline: exact browser JS, version, page, 2 CTA types, fail-closed")
