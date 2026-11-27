#!/usr/bin/env python3
"""Offline negative fixtures for four canonical gear archive paths."""
import importlib.util
from pathlib import Path
src=Path(__file__).resolve().parents[1]/'scripts/verify-gear-archive-public-v1.py'
spec=importlib.util.spec_from_file_location('nry_gear_archive_proof',src)
m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)

def fixture(slug='navijaky',links=None,heading=None,role=None,css=None,js=None):
    cfg=m.ROUTES[slug]
    use=cfg['links'] if links is None else links
    anchors=''.join('<a'+(' data-nry-gear-guide="'+(role if role is not None else kind)+'"' if (role is not None or kind is not None) else '')+
        ' href="'+href+'">Průvodce</a>' for kind,href in use)
    h1=cfg['title'] if heading is None else heading
    intro='Vybírejte prakticky a nenechte se zmást starymi recenzemi. '
    if slug=='zavazeci-lodky': intro+='Dálkově ovládaná zakrmovací loďka, nikoli nafukovací člun pro lidi.'
    return ('<html><head><link rel="stylesheet" href="'+(css or m.CSS)+'"></head><body>'
        + '<h1>'+h1+'</h1>'
        + '<section data-nry-gear-archive="v1" data-nry-gear-category="'+slug+'">'
        + '<h2>Najděte správnou výbavu</h2><p>'+intro+'</p><nav>'+anchors+'</nav>'
        + '</section>'
        + 'Historický archiv ' + ('X'*5100)
        + '<script src="'+(js or m.JS)+'"></script></body></html>')

def reject(body,slug):
    try:m.check(body,slug)
    except AssertionError:return
    raise AssertionError('unsafe invalid release proof accepted: '+slug)

for slug,cfg in m.ROUTES.items():
    expected=len(cfg['links'])
    assert m.check(fixture(slug),slug)['exactFirstPartyCTA']==expected
    home=[(None,m.ORIGIN+'/')]+cfg['links']
    assert m.check(fixture(slug,links=home),slug)['themeContextHome']==1
    reject(fixture(slug,links=home+[home[0]]),slug)
    reject(fixture(slug,links=cfg['links'][:-1]),slug)
    reject(fixture(slug,links=cfg['links']+[cfg['links'][0]]),slug)
    reject(fixture(slug,links=list(reversed(cfg['links']))),slug)
    reject(fixture(slug,links=[('foreign','https://merchant.example/click')]+cfg['links'][1:]),slug)
    reject(fixture(slug,links=[(cfg['links'][0][0],cfg['links'][0][1]+'?utm=wrong')]+cfg['links'][1:]),slug)
    reject(fixture(slug,role='wrong_role'),slug)
    reject(fixture(slug,heading='Obecný archiv'),slug)
    reject(fixture(slug,css=m.CSS.replace('?ver=1.0.0','?ver=2.0.0')),slug)
    reject(fixture(slug,js=m.JS.replace('https://na-ryby.eu/','https://evil.example/')),slug)
    reject(fixture(slug).replace('data-nry-gear-category="'+slug+'"','data-nry-gear-category="other"'),slug)
    reject(fixture(slug)+fixture(slug),slug)
    reject(fixture(slug).replace('</section>','</section><h1>Another</h1>'),slug)
reject('<a href="'+m.ROUTES['navijaky']['links'][0][1]+'">Koupit</a>','navijaky')
assert sum(len(v['links']) for v in m.ROUTES.values())==10
print('GEAR_ARCHIVE_FOUR_PUBLIC_NEGATIVE_CONTRACTS_VERIFIED')
