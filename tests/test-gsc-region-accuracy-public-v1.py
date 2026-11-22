"""Offline public parser fixtures for GSC regional accuracy proof; no HTTP."""
from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path

p=Path(__file__).resolve().parents[1]/'scripts/verify-gsc-region-accuracy-public-v1.py'
spec=spec_from_file_location('nry_region_accuracy',p)
m=module_from_spec(spec)
spec.loader.exec_module(m)

def fixture(role,released=False):
    c=m.CONFIG[role]
    section=(
        '<section class="nry-ris-choices" data-nry-region-accuracy="v1" data-nry-region="'+role+'">'
        +'<h2>'+c['new'][0]+'</h2><p>'+c['new'][1]+'</p>'
        +''.join('<a href="'+url+'">Ověřit</a>' for url in c['links'])
        +'</section>'
    ) if released else ''
    css=(
      '<link rel="stylesheet" href="https://na-ryby.eu/wp-content/plugins/'
      'na-ryby-git-publisher/assets/nry-gsc-revir-ris-paths-v1.css?ver=1.0.0">'
    ) if released else ''
    return ('<!doctype html><html><head><link rel="canonical" href="'+c['url']+'">'
        +css+'</head><body><main>'+section
        +'<h2>'+c['legacy'][0]+'</h2><p>'+' '.join(c['legacy'])+'</p>'
        +'<p>Původní redakční obsah i všechny odkazy zůstávají.</p>'*105
        +'</main></body></html>')

def reject(content,role,preflight=False):
    try:m.verify(content,role,preflight=preflight)
    except ValueError:return
    raise AssertionError('Must fail closed: '+role)

for role,c in m.CONFIG.items():
    old=fixture(role)
    new=fixture(role,True)
    assert m.verify(old,role,preflight=True)['status']=='NRY_REGION_LEGACY_SOURCE_PUBLIC_PREFLIGHT'
    assert m.verify(new,role)['status']=='NRY_REGION_GSC_ACCURACY_PUBLIC_VERIFIED'
    reject(old,role)
    reject(new,role,preflight=True)
    reject(new.replace('rel="canonical"','rel="alternate"'),role)
    reject(new.replace('nry-gsc-revir-ris-paths-v1.css?ver=1.0.0',
                       'nry-gsc-revir-ris-paths-v1.css?ver=1.0.1'),role)
    reject(new.replace('data-nry-region="'+role+'"','data-nry-region="wrong"'),role)
    reject(new.replace('data-nry-region-accuracy="v1"',
                       'data-nry-region-accuracy="v1" data-nry-region-accuracy="v1"'),role)
    reject(new.replace(c['links'][0],'https://attacker.invalid/'),role)
    reject(new.replace(c['new'][0],'Zkreslená nabídka'),role)
    reject(new.replace(c['legacy'][0],'Původní článek zmizel'),role)
    reject(new[:1400],role)
    reject(new.replace('</section>','</section><section data-nry-region-accuracy="v1" data-nry-region="'+role+'"></section>'),role)
print('NRY_REGION_GSC_PUBLIC_PARSER_OFFLINE_VERIFIED: 3 real roles and fail-closed permutations')
