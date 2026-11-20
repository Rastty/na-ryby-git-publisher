#!/usr/bin/env python3
"""Offline public parser proofs; no network, affiliate clicks or WP writes."""
import runpy
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
m = runpy.run_path(str(ROOT / 'scripts/verify-jihocesky-lipno-stays-public-13088.py'))
verify_public = m['verify_public']
HPORTAL, HAUZI, STAYS = m['HPORTAL'], m['HAUZI'], m['STAYS']
STYLE = m['STYLE']
SITE, PAGE = m['SITE'], m['PAGE']
prefix = ('<link rel="canonical" href="' + SITE + PAGE + '">'
          '<body class="single postid-13088">')
historical = '<div class="su-table su-table-alternate"><h2>Sportovní rybolov Rašelina</h2></div>'
ready = verify_public(prefix + historical, deployed=False)
assert ready['status'] == 'JIHOCESKY_LIPNO_13088_PREFLIGHT_READY'
assert ready['plannedMerchantLinks'] == 2 and ready['syntheticClicks'] == 0
block = ('<section data-nry-jihocesky-stays="v1"><h2>Na ryby k Lipnu a kde přespat?</h2>'
         '<article data-nry-jc-offer="kohoutu"><h3>Pension U Kohoutů</h3>'
         '<a href="' + HPORTAL.replace('&', '&amp;') + '" target="_blank"'
         ' rel="sponsored nofollow noopener">Hportal</a></article>'
         '<article data-nry-jc-offer="vyhlidka"><h3>Pension Vyhlídka</h3>'
         '<a href="' + HAUZI.replace('&', '&amp;') + '" target="_blank"'
         ' rel="sponsored nofollow noopener">Hauzi</a></article>'
         '<a href="' + STAYS + '">Pobyty</a>'
         '<p>Tabulka níže je starší rozcestník</p></section>')
css = (ROOT / 'assets/nry-jihocesky-lipno-stays-13088-v1.css').read_bytes()
css_only_html = prefix + '<link rel="stylesheet" href="' + SITE + STYLE + '?ver=1.0.0">' + historical
css_only = verify_public(css_only_html, deployed=False, css_public=css, css_reviewed=css)
assert css_only['status'] == 'JIHOCESKY_LIPNO_13088_PARTIAL_STYLES_ONLY'
assert css_only['existingOffers'] is False
try:
    verify_public(css_only_html, deployed=False, css_public=css + b'drift', css_reviewed=css)
except AssertionError:
    pass
else:
    raise AssertionError('PARTIAL_CSS_DRIFT_ACCEPTED')
try:
    verify_public(css_only_html.replace('?ver=1.0.0', '?ver=2.0.0'), deployed=False,
                  css_public=css, css_reviewed=css)
except AssertionError:
    pass
else:
    raise AssertionError('UNKNOWN_PARTIAL_CSS_VERSION_ACCEPTED')
html = prefix + '<link rel="stylesheet" href="' + SITE + STYLE + '?ver=1.0.0">' + block + historical
done = verify_public(html, deployed=True, css_public=css, css_reviewed=css)
assert done['status'] == 'JIHOCESKY_LIPNO_13088_PUBLIC_VERIFIED'
assert done['visibleReviewedOffers'] == 2 and done['completedReservations'] is None


def blocked(src):
    try:
        verify_public(src, deployed=True, css_public=css, css_reviewed=css)
    except AssertionError:
        return
    raise AssertionError('DRIFT_NOT_BLOCKED')


blocked(html.replace('rel="sponsored nofollow noopener"', 'rel="nofollow"', 1))
blocked(html.replace('data-nry-jc-offer="vyhlidka"', 'data-nry-jc-offer="unknown"'))
blocked(html.replace(HAUZI.replace('&', '&amp;'), STAYS))
blocked(html + block)
blocked(html.replace('Tabulka níže je starší rozcestník', 'bez poznamky'))
blocked(html.replace('postid-13088', 'postid-13089'))
blocked(html.replace(STYLE + '?ver=1.0.0', STYLE + '?ver=2.0.0'))
print('JIHOCESKY_LIPNO_13088_PUBLIC_PARSER_OK')

# Cards must precede the old 2017/18 index, never be hidden below it.
blocked(prefix + historical + '<link rel="stylesheet" href="' + SITE + STYLE + '?ver=1.0.0">' + block)
workflow = (ROOT / '.github/workflows/jihocesky-lipno-stays-13088.yml').read_text()
assert "github.event.workflow_run.event == 'workflow_dispatch'" in workflow
assert "github.event.workflow_run.event == 'push'" in workflow
assert "steps.upstream.outputs.deployed == 'true'" in workflow
assert "select(.name==\"deploy\") | .conclusion" in workflow
assert "github.event.workflow_run.conclusion == 'success'" in workflow
