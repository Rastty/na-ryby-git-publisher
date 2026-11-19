#!/usr/bin/env python3
"""Offline negative tests of public proof; never make merchant requests."""
import runpy
from pathlib import Path

root = Path(__file__).resolve().parents[1]
m = runpy.run_path(str(root / 'scripts/verify-private-fishing-stay-public-59113.py'))
source = (root / 'tests/fixtures/post-59113-20261119-rendered.html').read_text(encoding='utf-8')
metadata = {
    'id': 59113, 'type': 'post', 'status': 'publish', 'slug': m['PAGE'].strip('/'),
    'link': m['SITE'] + m['PAGE'], 'modified_gmt': '2023-08-18T07:08:57',
    'content': {'rendered': source},
}
assert m['verify_rest'](metadata)
old = ('<html><head><link rel="canonical" href="' + m['SITE'] + m['PAGE'] + '"></head>'
       '<body class="single-post postid-59113"><div>Naše privátní revíry. '
       'Rezervace je velmi jednoduchá.</div></body></html>')
assert m['verify_public'](old, deployed=False)['status'] == 'PRIVATE_STAY_59113_PREFLIGHT_READY'
new = ('<html><head><link rel="canonical" href="' + m['SITE'] + m['PAGE'] + '">'
       '<link rel="stylesheet" href="' + m['SITE'] + m['STYLE'] + '?ver=1.0.0"></head>'
       '<body class="single-post postid-59113"><div class="td-post-content">'
       '<section data-nry-private-stay="v1"><h2>Soukromý revír s ubytováním: '
       'co si ověřit před rezervací</h2><p>Neprovozujeme vlastní soukromé revíry</p>'
       '<article data-nry-private-example="jedraz"><p>V popisu konkrétní varianty Slevomatu</p>'
       '<a href="' + m['STAYS'] + '">Pobyty</a></article>'
       '<a href="' + m['GEAR'] + '">Vybavení</a></section></div></body></html>')
css = (root / 'assets/nry-private-fishing-stay-trust-59113-v1.css').read_bytes()
assert m['verify_public'](new, deployed=True, css_public=css, css_reviewed=css)['cssByteIdentical']
assert m['verify_public'](new, deployed=False, css_public=css, css_reviewed=css)['cssByteIdentical']

def blocked(html, *, css_read=css, deployed=True):
    try:
        m['verify_public'](html, deployed=deployed, css_public=css_read, css_reviewed=css)
    except AssertionError:
        return
    raise AssertionError('Tampered page accepted')

blocked(old)
blocked(new.replace('postid-59113', 'postid-59114'))
blocked(new.replace('data-nry-private-stay="v1"', 'data-nry-private-stay="v2"'))
blocked(new.replace('data-nry-private-example="jedraz"', 'data-nry-private-example="other"'))
blocked(new.replace(m['STAYS'], 'https://external.example/stays'))
blocked(new.replace('?ver=1.0.0', '?ver=1.0.1'))
blocked(new.replace('</article>', '<a href="https://external.example">extra</a></article>'))
blocked(new.replace('</section>', '<p>Naše privátní revíry se nachází</p></section>'))
blocked(new, css_read=css+b'changed')
blocked(old.replace('Rezervace je velmi jednoduchá', 'jiný text'), deployed=False)
print('PASS: private fishing stay 59113 public readback, exact two links and tamper rejection')
