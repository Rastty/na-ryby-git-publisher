#!/usr/bin/env python3
"""No-network fail-closed public offer and click observer tests."""
import runpy
from pathlib import Path

root = Path(__file__).resolve().parents[1]
m = runpy.run_path(str(root / 'scripts/verify-sport-stays-offers-public-85384.py'))
src = '<html><head><link rel="canonical" href="' + m['ORIGIN'] + m['PAGE'] + '"></head><body class="single-post postid-85384">'
old = ('<p><a href="' + m['LEGACY'] + '">Starý odkaz</a></p>') * 3
banner = '<aside class="mtsnb-button"><a href="' + m['LEGACY'] + '">Globální navigace</a></aside>'
base = (src + old + banner + '</body></html>').encode()
assert m['verify'](base, deployed=False)['status'] == 'SPORT_STAYS_OFFERS_PREFLIGHT_READY'
assert m['verify_current'](base)['status'] == 'SPORT_STAYS_OFFERS_PREFLIGHT_READY'
style = '<link href="' + m['ORIGIN'] + m['CSS'] + '?' + m['CSS_VERSION'] + '" rel="stylesheet">'
script = '<script src="' + m['ORIGIN'] + m['JS'] + '?' + m['JS_VERSION'] + '"></script>'
ctas = ''.join('<article data-nry-sport-offer="' + offer + '"><a href="' + link.replace('&','&amp;') +
               '" rel="sponsored nofollow noopener" target="_blank">Pobyt</a></article>'
               for offer, link in m['OFFERS'])
block = '<section data-nry-sport-stays="v1">' + ctas + '<a href="' + m['STAYS'] + '">Další</a></section>'
ready = (src.replace('</head>', style + script + '</head>') +
         '<div class="td-post-content">' + block +
         ('<p><a href="' + m['STAYS'] + '">Nová cesta</a></p>') * 3 +
         '</div>' + banner + '</body></html>').encode()
css = (root / 'assets/nry-sport-stays-85384-v1.css').read_bytes()
js = (root / 'assets/nry-buyer-clicks-v1.js').read_bytes()
def deployed(doc, *, css_content=css, js_content=js):
    return m['verify'](doc, deployed=True, css_bytes=css_content, js_bytes=js_content,
                       reviewed_css=css, reviewed_js=js)
assert deployed(ready)['exactSponsoredOffers'] == 3
counts = m['diagnostic_counts'](ready)
assert counts['status'] == 'SPORT_STAYS_LINK_DISTRIBUTION_OBSERVED_NOT_VERIFIED'
assert counts['publicPageNewStayLinks'] == 4
assert counts['publicPageOldBookingLinks'] == 1
assert counts['panelInternalStayLinks'] == 1 and counts['offerCardCount'] == 3
assert counts['postContentRegionCount'] == 1
assert counts['articleNewStayLinks'] == 4 and counts['articleOldBookingLinks'] == 0
# Additional unrelated internal navigation must not change the article proof.
extra = ('<nav>' + ('<a href="' + m['STAYS'] + '">Region</a>') * 3 + '</nav>').encode()
ready_with_nav = ready.replace(b'</body>', extra + b'</body>')
nav_counts = m['diagnostic_counts'](ready_with_nav)
assert nav_counts['publicPageNewStayLinks'] == 7
assert nav_counts['articleNewStayLinks'] == 4
assert nav_counts['articleOldBookingLinks'] == 0
# Public confirmation requires four routes in the article; extra nav is okay.
assert deployed(ready_with_nav)['articleNewStayLinks'] == 4
assert deployed(ready_with_nav)['otherFirstPartyStayLinks'] == 3
assert deployed(ready_with_nav)['articleOldBookingLinks'] == 0
assert counts['merchantRequests'] == counts['wordpressWrites'] == counts['syntheticClicks'] == 0
assert all('https://' not in str(val) for val in counts.values())
assert m['verify_current'](ready, css_bytes=css, js_bytes=js,
                           reviewed_css=css, reviewed_js=js)['status'] == 'SPORT_STAYS_OFFERS_PUBLIC_VERIFIED'
def blocked(doc, *, css_content=css, js_content=js):
    try:
        deployed(doc, css_content=css_content, js_content=js_content)
    except AssertionError:
        return
    raise AssertionError('public source or affiliate drift wrongly accepted')
# A stale source link inside the article must still be rejected, even if the
# sitewide banner is swapped to preserve global old/new link counts.
article_link = ('<p><a href="' + m['STAYS'] + '">Nová cesta</a></p>').encode()
stale_link = ('<p><a href="' + m['LEGACY'] + '">Nová cesta</a></p>').encode()
other_link = ('<a href="' + m['LEGACY'] + '">Globální navigace</a>').encode()
replacement = ('<a href="' + m['STAYS'] + '">Globální navigace</a>').encode()
balanced_wrong = ready_with_nav.replace(article_link, stale_link, 1).replace(other_link, replacement, 1)
blocked(balanced_wrong)
blocked(ready_with_nav.replace(article_link, b'', 1))
blocked(base)
blocked(ready.replace(b'postid-85384',b'postid-92205'))
blocked(ready.replace(m['PAGE'].encode(), b'/wrong-page/'))
blocked(ready.replace(b'data-nry-sport-stays="v1"', b'data-nry-sport-stays="v2"'))
blocked(ready.replace(b'data-nry-sport-offer="palava-vestonice"', b'data-nry-sport-offer="other"'))
blocked(ready.replace(b'chid=5QoXiSqx', b'chid=other'))
blocked(ready.replace(b'rel="sponsored nofollow noopener"', b'rel="nofollow"'))
blocked(ready.replace(b'ver=1.11.7', b'ver=1.11.6'))
blocked(ready.replace(b'ver=1.0.0', b'ver=1.0.1'))
blocked(ready + b'<a href="https://na-ryby.eu/booking-cesko">legacy</a>')
blocked(ready.replace(b'</body>', block.encode() + b'</body>'))
blocked(ready, css_content=css + b'drift')
blocked(ready, js_content=js + b'drift')
def current_blocked(doc, *, css_content=css, js_content=js):
    try:
        m['verify_current'](doc, css_bytes=css_content, js_bytes=js_content,
                            reviewed_css=css, reviewed_js=js)
    except AssertionError:
        return
    raise AssertionError('current live page drift wrongly accepted')
current_blocked(ready.replace(b'chid=5QoXiSqx', b'chid=other'))
current_blocked(ready.replace(b'rel="sponsored nofollow noopener"', b'rel="nofollow"'))
current_blocked(ready.replace(b'ver=1.11.7', b'ver=1.11.6'))
current_blocked(ready.replace(b'data-nry-sport-stays="v1"', b'data-nry-sport-stays="v2"'))
current_blocked(ready.replace(b'</body>', block.encode() + b'</body>'))
current_blocked(ready, css_content=css + b'drift')
current_blocked(ready, js_content=js + b'drift')
print('PASS: sport-stays 85384 public links, style and genuine click script fail closed')
