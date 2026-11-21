from importlib.util import module_from_spec, spec_from_file_location
from pathlib import Path
from html import escape

script=Path(__file__).resolve().parents[1]/'scripts/verify-gsc-revir-ris-public-v1.py'
spec=spec_from_file_location('nry_ris_public',script)
m=module_from_spec(spec)
spec.loader.exec_module(m)

def fixture(route,release):
    assert route in m.DEST
    old=' '.join(m.OLD[route])
    expected=m.CANDAT_LINKS if route=='candat' else m.BASE_LINKS
    section=(
      '<section data-nry-gsc-revir-ris-paths="v1" data-nry-ris-scope="'+route+'">'
      +'<h2>'+' '.join(m.NEW[route])+'</h2>'
      +''.join('<a href="'+url+'">Zkontrolovat</a>' for url in expected)
      +'</section>') if release else ''
    css=('<link rel="stylesheet" href="https://na-ryby.eu/wp-content/plugins/'
         'na-ryby-git-publisher/assets/nry-gsc-revir-ris-paths-v1.css?ver=1.0.0">') if release else ''
    return ('<!doctype html><html><head><link rel="canonical" href="'+m.DEST[route]+'">'+css+
            '</head><body><main><article>'+section+
            '<p>'+old+'</p>'+'<p>Ostatní původní seznamy a rady zůstaly.</p>'*105+
            '</article></main></body></html>')

for role in m.DEST:
    old=fixture(role,False)
    new=fixture(role,True)
    assert m.verify(old,role,True)['status']=='NRY_GSC_REVIR_RIS_PREFLIGHT_VERIFIED'
    result=m.verify(new,role,False)
    assert result['status']=='NRY_GSC_REVIR_RIS_PUBLIC_VERIFIED'
    assert result['original_preserved']
    assert result['wordpress_writes']==0
    assert result['affiliate_clicks']==0
    def fail(html,preflight=False):
        try:m.verify(html,role,preflight)
        except ValueError:return
        raise AssertionError('must fail closed '+role)
    fail(old)
    fail(new,True)
    fail(new.replace('rel="canonical"','rel="somethingelse"'))
    fail(new.replace('nry-gsc-revir-ris-paths-v1.css?ver=1.0.0',
                     'nry-gsc-revir-ris-paths-v1.css?ver=0.9.0'))
    fail(new.replace('data-nry-ris-scope="'+role+'"','data-nry-ris-scope="other"'))
    fail(new.replace('data-nry-gsc-revir-ris-paths="v1"',
                    'data-nry-gsc-revir-ris-paths="v1" data-nry-gsc-revir-ris-paths="v1"'))
    fail(new.replace('nry-ris-choices', 'nry-ris-choices')+'<p>DOPLNIT-URL</p>')
    fail(new.replace('https://ris.rybsvaz.cz/seznam-reviru',
                     'https://untrusted.example/reviry'))
    fail(new.replace(m.OLD[role][0],'Původní záznam byl odstraněn'))
    fail(new[:1000])
print('NRY_GSC_REVIR_RIS_PUBLIC_PARSER_OFFLINE_VERIFIED: three canaries and fail-closed states')
