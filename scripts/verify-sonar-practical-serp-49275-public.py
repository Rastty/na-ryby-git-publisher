#!/usr/bin/env python3
"""Independent anonymous GET-only live proof for sonar 49275 SEO description.

Never requests WordPress credentials, calls a merchant or emits GA4/CJ clicks.
A public Yoast REST description can be verified; actual Google SERP and CTR
remain outside this proof. Fails closed on identity, title or content drift.
"""
import json
import sys
from html import unescape
from urllib.request import Request, urlopen
from urllib.parse import quote
import re

ROOT='https://na-ryby.eu'
SLUG='jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun'
URL=ROOT+'/'+SLUG+'/'
POST_ID=49275
ENDPOINT=(ROOT+'/wp-json/wp/v2/posts?slug='+SLUG+
    '&_fields=id,slug,status,link,title,modified_gmt,content,yoast_head_json')
BEFORE_TITLE='Jak efektivně používat echolot a sonar na loď nebo člun?'
NEW_META=('Jak používat echolot na lodi: nastavení citlivosti, umístění sondy, '
    'čtení dna a časté chyby při lovu z člunu. Praktické rady a navazující výběr sonaru.')
OLD_META=('Získejte lepší přehled o podvodním světě s tím, jak správně používat '
    'echolot a sonar na vaší lodi nebo člunu. Naučte se efektivněji lovit ryby '
    'a vyhnout se nebezpečným překážkám.✔️')
BOAT=ROOT+'/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/'

def verify(rows):
    if not isinstance(rows,list) or len(rows)!=1:
        raise AssertionError('ONE_EXACT_PUBLIC_POST_REQUIRED')
    p=rows[0]
    if (type(p.get('id')) is not int or p['id']!=POST_ID
        or p.get('slug')!=SLUG or p.get('status')!='publish'
        or p.get('link')!=URL
        or p.get('modified_gmt')!='2026-10-16T11:38:12'):
        raise AssertionError('POST_ID_CANONICAL_GMT_DRIFT')
    if unescape(p.get('title',{}).get('rendered',''))!=BEFORE_TITLE:
        raise AssertionError('POST_TITLE_CHANGED')
    html=p.get('content',{}).get('rendered')
    if not isinstance(html,str) or len(html.encode('utf-8'))!=13396:
        raise AssertionError('PUBLIC_BODY_DRIFT')
    links=re.findall(r'<a\b[^>]*\bhref\s*=\s*["\']([^"\']+)["\']',html,re.I)
    if sum(unescape(x)==BOAT for x in links)!=1:
        raise AssertionError('EXISTING_BOAT_BUYER_HANDOFF_DRIFT')
    yoast=p.get('yoast_head_json')
    if (not isinstance(yoast,dict)
        or yoast.get('canonical')!=URL
        or yoast.get('title')!=BEFORE_TITLE):
        raise AssertionError('YOAST_CANONICAL_OR_TITLE_DRIFT')
    desc=yoast.get('description')
    if desc==OLD_META:
        raise AssertionError('OLD_GENERIC_META_STILL_PUBLIC')
    if desc!=NEW_META:
        raise AssertionError('YOAST_METADESCRIPTION_NOT_EXACT_CANDIDATE')
    return 'PUBLIC_VERIFIED_SONAR_49275_EXACT_SERP_META_AND_BODY'

def read():
    req=Request(ENDPOINT,headers={
        'Accept':'application/json','Cache-Control':'no-cache',
        'User-Agent':'NaRybyExactSonarSerpPublicVerifier/1.0'
    })
    with urlopen(req,timeout=25) as response:
        if response.status!=200 or response.geturl()!=ENDPOINT:
            raise AssertionError('PUBLIC_REST_NOT_EXACT_200')
        body=response.read(400000)
        if len(body)>=400000:
            raise AssertionError('PUBLIC_REST_BODY_OVERSIZE')
    return json.loads(body.decode('utf-8'))

if __name__=='__main__':
    if '--selftest' in sys.argv:
        row={'id':POST_ID,'slug':SLUG,'status':'publish','link':URL,
             'modified_gmt':'2026-10-16T11:38:12',
             'title':{'rendered':BEFORE_TITLE},
             'content':{'rendered':'<a href="'+BOAT+'">Navazující průvodce</a>'+
                ('X'*(13396-len('<a href="'+BOAT+'">Navazující průvodce</a>')))},
             'yoast_head_json':{'canonical':URL,'title':BEFORE_TITLE,'description':NEW_META}}
        assert verify([row])=='PUBLIC_VERIFIED_SONAR_49275_EXACT_SERP_META_AND_BODY'
        for wrong in [
            {**row,'id':19999},
            {**row,'yoast_head_json':{**row['yoast_head_json'],'description':OLD_META}},
            {**row,'yoast_head_json':{**row['yoast_head_json'],'canonical':'https://wrong.example/'}},
            {**row,'content':{'rendered':'SHORT'}}
        ]:
            try: verify([wrong])
            except AssertionError: pass
            else: raise AssertionError('UNSAFE_PUBLIC_PROOF_FALSE_POSITIVE')
        print('PASS: exact public SERP/body/identity guard and old-meta rejection')
    else:
        print(verify(read()))
        print('Real Google SERP selection, visitor click-through and CJ sales remain UNKNOWN.')
