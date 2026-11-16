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
should_block("script version drift", source.replace(m["VERSION"].encode(), b"ver=1.1.0"))
should_block("duplicate script", source.replace(tag.encode(), tag.encode()*2))
should_block("unexpected installed JS", installed=b"/* stale JS */")
should_block("untrusted click-out merchant",
    source.replace(merchant.encode(), b'<a href="https://evilchytapust.cz/offer">Wrong</a>'))
should_block("untrusted redirect",
    source.replace(dognet.encode(), b'<a href="http://go.dognet.com/offer">HTTP</a>'))

# The prior intervention is live at 1.7.0. Preflight must prove the
# installed old asset; new after-deploy readback must prove 1.8.0 bytes.
pillar_canonical='<link rel="canonical" href="'+m["ORIGIN"]+m["PILLAR"]+'">'
pillar_merchant='<a href="https://www.chytapust.cz/Echoloty/?utm_source=site">Merchant</a>'
pillar_practical='<a href="'+m["ORIGIN"]+m["PRACTICAL"]+'">Guide</a>'
baseline_tag='<script src="'+m["ORIGIN"]+m["ASSET"]+'?'+m["BASELINE_VERSION"]+'"></script>'
pillar_current=('<html><head>'+pillar_canonical+baseline_tag+'</head><body>'+
            pillar_merchant+pillar_practical+'</body></html>').encode()
pillar_next=pillar_current.replace(m["BASELINE_VERSION"].encode(),m["VERSION"].encode())
pillar_old=pillar_current.replace(m["BASELINE_VERSION"].encode(),b"ver=1.4.0")
assert m["verify_pillar"](pillar_current,b"",b"",False)["status"]=="PUBLIC_PILLAR_PREFLIGHT_VERIFIED"
assert m["verify_pillar"](pillar_next,script,script,True)["status"]=="PUBLIC_PILLAR_MEASUREMENT_VERIFIED"
def pillar_block(name,body=pillar_next,asset=script,after=True):
 try:m["verify_pillar"](body,asset,script,after)
 except AssertionError:return
 raise AssertionError(name+" must fail closed")
pillar_block("missing practical",pillar_next.replace(pillar_practical.encode(),b""))
pillar_block("missing merchant",pillar_next.replace(pillar_merchant.encode(),b""))
pillar_block("wrong canonical",pillar_next.replace(pillar_canonical.encode(),b""))
pillar_block("stale installed script",asset=b"not reviewed")
pillar_block("duplicate script",pillar_next.replace(tag.encode(), tag.encode()*2))
pillar_block("stale prior version cannot be certified",pillar_old,after=True)
pillar_block("stale version cannot pass current preflight",pillar_old,after=False)
pillar_block("missing deployed baseline",pillar_current.replace(baseline_tag.encode(),b""),after=False)

# No merchant requests are needed to prove the three other existing sonar
# buyer-guide handoffs. Each page must serve the exact reviewed shared JS.
for role, path, expected in (
    ('echolot_budget', m['PAGE'], (m['PILLAR'],m['BOAT'],m['ADVISOR'])),
    ('sonar_boat', m['BOAT'], (m['PILLAR'],m['PAGE'],m['ADVISOR'])),
    ('sonar_historic', m['HISTORIC'], (m['PILLAR'],m['BOAT'],m['PAGE'],m['ADVISOR']))
):
    canonical_tag='<link rel="canonical" href="'+m['ORIGIN']+path+'">'
    links=''.join('<a href="'+m['ORIGIN']+target+'">Guide</a>' for target in expected)
    fixture=('<html><head>'+canonical_tag+tag+'</head><body>'+links+'</body></html>').encode()
    verified=m['verify_guide_paths'](role,fixture,script,script)
    assert verified['required_internal_routes_observed']==len(expected)
    assert verified['ga4DeliveryProven'] is False
    for damaged in (
        fixture.replace(canonical_tag.encode(),b''),
        fixture.replace(tag.encode(),b''),
        fixture.replace(m['ORIGIN'].encode(),b'https://other.example',1),
    ):
        try:m['verify_guide_paths'](role,damaged,script,script)
        except AssertionError:pass
        else:raise AssertionError('UNSAFE_CROSS_GUIDE_PUBLIC_PROOF_ACCEPTED')
    for destination in expected:
        poisoned=fixture.replace((m['ORIGIN']+destination).encode(),
                                  (m['ORIGIN']+destination+'?unknown=1').encode(),1)
        try:m['verify_guide_paths'](role,poisoned,script,script)
        except AssertionError:pass
        else:raise AssertionError('UNREVIEWED_LINK_VARIANT_ACCEPTED')
    try:m['verify_guide_paths'](role,fixture,b'old script',script)
    except AssertionError:pass
    else:raise AssertionError('ASSET_DRIFT_ACCEPTED')

assert m["get"].__name__ == "get"
print("PASS sonar public readback offline: exact browser JS, version, page, 2 CTA types, fail-closed")
