"""Offline positive/negative public checker contract, no HTTP."""
from importlib.machinery import SourceFileLoader
from pathlib import Path
mod=SourceFileLoader('nry_wellness_public',str(Path(__file__).resolve().parents[1]/'scripts/verify-wellness-srub-hauzi-public-17536.py')).load_module()
hero='<section data-nry-wellness-srub-offer="v1"><p>nižší hladina Vranovské přehrady povolenku</p><a href="'+mod.AFFILIATE+'" rel="sponsored nofollow noopener">Zjistit termín Wellness srubu na Hauzi</a><a href="'+mod.OWNER+'">Provozovatel</a></section>'
footer='<aside><p>jejich aktuální dostupnost ale není ověřena</p><a href="'+mod.AFFILIATE+'" rel="sponsored nofollow noopener">Ověřit konkrétní srub na Hauzi</a></aside>'
src='<!doctype html><html><head><link rel="canonical" href="'+mod.URL+'"><link rel="stylesheet" href="https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/assets/'+mod.CSS+'?ver=1.0.0"></head><body>'+hero+footer+'<p>Historický popis ubytování.</p>'*250+'</body></html>'
assert mod.verify(src)['status']=='WELLNESS_SRUB_HAUZI_OFFER_PUBLIC_VERIFIED'
def reject(html):
    try:mod.verify(html)
    except ValueError:return
    raise AssertionError('Fail-open accepted')
reject(src.replace(mod.MARKER,'wrong'))
reject(src+hero)
reject(src.replace(mod.AFFILIATE,'https://www.hauzi.cz/rybarsky-srub',1))
reject(src.replace('rel="sponsored nofollow noopener"','rel="noopener"',1))
reject(src.replace(mod.OWNER,'https://other.example/'))
reject(src.replace(mod.CSS,'other.css'))
reject(src.replace(mod.URL,'https://other.example/',1))
reject(src+mod.LEGACY[0])
reject(src+mod.LEGACY[1])
reject(src+mod.LEGACY[2])
reject(src.replace('nižší hladina Vranovské přehrady','bez omezení',1))
reject(src[:2000])
print('PASS WELLNESS_SRUB_HAUZI_PUBLIC_OFFLINE_NEGATIVE_FIXTURES')
