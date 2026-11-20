#!/usr/bin/env python3
"""No-network negative tests; no synthetic browser events reach GA4."""
import runpy
from pathlib import Path

root = Path(__file__).resolve().parents[1]
m = runpy.run_path(str(root / 'scripts/verify-stredni-cechy-stay-click-public-92205.py'))
approved = m['PARTNER'].replace('&', '&amp;')
hauzi = m['HAUZI'].replace('&', '&amp;')
source_old = ('<html><head><link rel="canonical" href="' + m['ORIGIN'] + m['PAGE'] + '"></head>'
    '<body class="single-post postid-92205"><section data-nry-sc-stay="v1">'
    '<article data-nry-sc-offer="slapy-janata"><a href="' + approved + '" '
    'rel="sponsored nofollow noopener" target="_blank">Ubytování</a></article>'
    '<a href="' + m['ORIGIN'] + '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/">Pobyty</a>'
    '</section></body></html>').encode()
source_new = source_old.replace(
    b'</article>',
    ('</article><article data-nry-sc-offer="hauzi-slapy-skalice"><a href="' + hauzi
     + '" rel="sponsored nofollow noopener" target="_blank">Chata Slapy</a></article>').encode(),
    1,
)
def with_script(ver, html=source_new):
    return html.replace(b'</head>', ('<script src="' + m['ORIGIN'] + m['ASSET'] + '?ver=' + ver + '"></script></head>').encode())
assert m['verify'](source_old, deployed=False)['affiliateOffers'] == 1
assert m['verify'](source_new, deployed=False)['affiliateOffers'] == 2
assert m['verify'](with_script('1.11.4', source_old), deployed=False)['status'] == 'STREDNI_CECHY_CLICK_PREFLIGHT_READY'
assert m['verify'](with_script('1.11.5', source_new), deployed=False)['status'] == 'STREDNI_CECHY_CLICK_PREFLIGHT_READY'
js = (root / 'assets/nry-buyer-clicks-v1.js').read_bytes()
verified = m['verify'](with_script('1.11.5'), deployed=True, public_js=js, reviewed_js=js)
assert verified['scriptByteIdentical'] and verified['affiliateOffers'] == 2
assert verified['clickObservationEnabledFor'] == ['slapy-janata']
assert verified['hauziClickObservationEnabled'] is False
offer = runpy.run_path(str(root / 'scripts/verify-stredni-cechy-stay-offer-public-92205.py'))
old_page = with_script('1.11.5', source_old).decode().replace(
    '</head>', '<link rel="stylesheet" href="' + m['ORIGIN'] + offer['CSS'] + '?ver=1.0.0"></head>')
assert offer['public_preflight'](old_page)['plannedAffiliateOffers'] == 2
new_page = with_script('1.11.5').decode().replace(
    '</head>', '<link rel="stylesheet" href="' + m['ORIGIN'] + offer['CSS'] + '?ver=1.1.0"></head>')
new_page = new_page.replace('</section>', '</section><h2>Zážitkové rybaření ve středních Čechách</h2>')
css = (root / 'assets/nry-stredni-cechy-stay-offer-v1.css').read_bytes()
assert offer['public_verify'](new_page, css, css)['exactSponsoredOffers'] == 2
try:
    offer['public_verify'](old_page, css, css)
except AssertionError:
    pass
else:
    raise AssertionError('Old single-offer page accepted as newly deployed')
def blocked(html, *, j=js):
    try:
        m['verify'](html, deployed=True, public_js=j, reviewed_js=js)
    except AssertionError:
        return
    raise AssertionError('Unexpected acceptance of tampered source/event')
blocked(source_old)
blocked(with_script('1.11.5', source_old))
blocked(with_script('1.11.4'))
blocked(with_script('1.11.5').replace(b'postid-92205', b'postid-59271'))
blocked(with_script('1.11.5').replace(b'data-nry-sc-offer="slapy-janata"',b'data-nry-sc-offer="other"'))
blocked(with_script('1.11.5').replace(b'data-nry-sc-offer="hauzi-slapy-skalice"', b'data-nry-sc-offer="other"'))
blocked(with_script('1.11.5').replace(b'chata-slapy-skalice', b'chata-jina'))
blocked(with_script('1.11.5').replace(b'chid=5QoXiSqx', b'chid=other'))
blocked(with_script('1.11.5').replace(b'rel="sponsored nofollow noopener"',b'rel="nofollow"'))
blocked(with_script('1.11.5'), j=js+b'tampered')

# A successful push build is never a protected production FTPS release.
for guarded_proof in (
    '.github/workflows/stredni-cechy-stay-offer-public-92205.yml',
    '.github/workflows/stredni-cechy-stay-click-92205.yml',
    '.github/workflows/stredni-cechy-stay-source-proof-92205.yml',
):
    proof_yaml = (root / guarded_proof).read_text(encoding='utf-8')
    assert "github.event.workflow_run.event == 'workflow_dispatch'" in proof_yaml
    assert "github.event.workflow_run.event == 'push'" not in proof_yaml
print('PASS: Stredni Cechy 92205 exact public click-script verification, no synthetic clicks')
