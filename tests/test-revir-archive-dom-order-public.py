#!/usr/bin/env python3
"""Pure parser contracts: do not confuse header metadata with rendered archive DOM."""
import sys
from pathlib import Path
sys.path.insert(0,str(Path(__file__).resolve().parents[1]/'scripts'))
import importlib.util
path=Path(__file__).resolve().parents[1]/'scripts'/'verify-revir-archive-entry-public.py'
spec=importlib.util.spec_from_file_location('archive_verify',path)
module=importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
check=module.check
DESTS=module.DESTS

def document(*, early_result=False, duplicate=False, broken_link=False):
    # The title text precedes all body elements on every real page.
    before='<title>Sportovní rybolov Rašelina | Revíry</title>'
    header='<h1>Soukromé rybářské revíry</h1>'
    result='<h2><a href="/sportovni-rybolov-raselina/">Sportovní rybolov <em>Rašelina</em></a></h2>'
    links=''.join('<a href="'+(url+'/wrong' if broken_link and i==0 else url)+'">Route</a>'
                  for i,url in enumerate(DESTS))
    section='<section data-nry-revir-archive-entry="v1"><h2>Najděte vhodnou vodu</h2>'+links+'</section>'
    css='<link rel="stylesheet" href="/wp-content/plugins/na-ryby-git-publisher/assets/nry-revir-archive-entry-v1.css">'
    body=result+section if early_result else section+result
    if duplicate:body=section+body
    return before+css+header+body

assert check(document(),True)=='NRY_REVIR_ARCHIVE_ENTRY_PUBLIC_VERIFIED'
for kwargs in ({'early_result':True},{'duplicate':True},{'broken_link':True}):
    try:
        check(document(**kwargs),True)
    except AssertionError:
        pass
    else:
        raise AssertionError('negative fixture unexpectedly passed: '+str(kwargs))
print('REVIR_ARCHIVE_DOM_ORDER_FIXTURES_VERIFIED')
