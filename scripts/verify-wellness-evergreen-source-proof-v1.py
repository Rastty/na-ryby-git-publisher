#!/usr/bin/env python3
"""Read-only exact WordPress source-digest evidence, never fetch merchants."""
import json
from urllib.request import Request, urlopen

URL='https://na-ryby.eu/poradce-vybavy/?nry_wellness_evergreen_source_v1=1'
EXPECTED=(
'wellness-a-rybolov-v-jednom-luxusni-ubytovani-pro-narocne-rybare',
'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice',
)
def verify(j):
    assert j['schema']==1 and j['site']=='https://na-ryby.eu'
    assert j['publicationAuthorized'] is False
    rows=j['targets']
    assert type(rows) is list and len(rows)==2
    for i,row in enumerate(rows):
        slug=EXPECTED[i]
        assert row['slug']==slug and row['state']=='SOURCE_SHA_AVAILABLE_NO_PUBLICATION'
        assert type(row['postId']) is int and row['postId']>0
        assert row['canonical']=='https://na-ryby.eu/'+slug+'/'
        assert type(row['sourceBytes']) is int and 1000<=row['sourceBytes']<300000
        assert len(row['modifiedGmt'])==19
        digest=row['sourceSha256']
        assert len(digest)==64 and all(c in '0123456789abcdef' for c in digest)
        assert row['wordpressWrites']==row['merchantRequests']==row['syntheticClicks']==0
    return rows

if __name__=='__main__':
    try:
        with urlopen(Request(URL, headers={'User-Agent':'NaRyby-Source-ReadOnly/1.0',
                    'Cache-Control':'no-cache'}), timeout=30) as response:
            assert response.status==200 and response.url==URL
            body=response.read(8193)
            assert len(body)<8193
        result=verify(json.loads(body))
        print(json.dumps({'status':'WELLNESS_EVERGREEN_TWO_SOURCES_VERIFIED',
            'sources':[{'slug':r['slug'],'postId':r['postId'],
                        'modifiedGmt':r['modifiedGmt'],'sourceBytes':r['sourceBytes'],
                        'sourceSha256':r['sourceSha256']} for r in result]},ensure_ascii=False))
    except Exception as exc:
        print(json.dumps({'status':'WELLNESS_EVERGREEN_SOURCE_BLOCKED','error':type(exc).__name__}))
        raise SystemExit(1)
