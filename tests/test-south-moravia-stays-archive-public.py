#!/usr/bin/env python3
"""Offline negative public parser checks. No outbound network."""
import importlib.util
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'scripts/verify-south-moravia-stays-archive-public.py'
spec=importlib.util.spec_from_file_location('nry_jm_public',p)
m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)

def doc(links=m.EXPECTED,canonical=m.URL,old=True,marker=True):
    piece='<section '+m.MARKER+'>' if marker else '<section>'
    piece+=''.join('<a href="'+u+'">Volba</a>' for u in links)+'</section>'
    suffix='<main><h2>Zukalův rybník</h2> Rybník Popůvky Havelka ORCA Rybník Božice</main>' if old else '<main>Jiné články</main>'
    return '<html><head><link rel="canonical" href="'+canonical+'"/></head><body>' + piece + suffix + 'x'*9000+'</body></html>'

assert m.verify(doc())['exactFirstPartyLinks']==3
assert m.verify(doc(links=(m.HOME,)+m.EXPECTED))['exactContextHomeLink'] is True
assert m.verify(doc())['exactContextHomeLink'] is False
for b in (doc(links=(m.CYCLO,m.SRUB)),
          doc(links=(m.HOME,m.HOME)+m.EXPECTED),
          doc(links=m.EXPECTED+(m.HOME,)),
          doc(links=(m.HOME+'?utm_source=abc',)+m.EXPECTED),
          doc(links=('https://na-ryby.eu/jiny-clanek/',)+m.EXPECTED),
          doc(links=('https://evil.example/',)+m.EXPECTED),
          doc(links=(m.CYCLO,'https://evil.example/x',m.HUB)),
          doc(canonical='https://na-ryby.eu/random/'),
          doc(old=False),
          doc(marker=False),
          doc().replace(m.MARKER, m.MARKER+' '+m.MARKER),
          doc().replace('Zukalův rybník','Other list'),
          doc().replace('<main>','<main><section '+m.MARKER+'></section>')):
    try:m.verify(b)
    except ValueError:pass
    else:raise AssertionError('unsafe public document accepted')
print('SOUTH_MORAVIA_STAYS_PUBLIC_NEGATIVE_CONTRACT_PASS')
