#!/usr/bin/env python3
"""Anonymous GET-only exact source-hash receipt for carp-rod pillar #118541."""
import json, sys, time, urllib.error, urllib.request
URL='https://na-ryby.eu/poradce-vybavy/?nry_carp_pillar_source_proof=1'
def fetch():
    req=urllib.request.Request(URL,headers={'Accept':'application/json',
        'Cache-Control':'no-cache','User-Agent':'NRY-CarpPillar-SourceProof/1.0'})
    with urllib.request.urlopen(req,timeout=20) as r:
        if r.status!=200 or r.geturl()!=URL: raise RuntimeError('WRONG_STATUS_OR_REDIRECT')
        data=json.load(r)
    if data.get('schema')!=1 or data.get('postId')!=118541 or data.get('publicationAuthorized')!=False or data.get('wordpressWrites')!=0:
        raise RuntimeError('INVALID_SCOPE_OR_WRITE_CLAIM')
    if data.get('state')!='SOURCE_ONLY_NO_PUBLICATION':raise RuntimeError(str(data.get('state','UNKNOWN')))
    if len(data.get('sourceSha256',''))!=64 or int(data.get('sourceBytes',0))<1000:
        raise RuntimeError('MISSING_EXACT_SOURCE_EVIDENCE')
    # Only bounded hashes and counters; never output text/html, URLs or credentials.
    return {k:data.get(k) for k in ('state','postId','sourceSha256','sourceBytes','modifiedGmt','literalCounts','wordpressWrites','publicationAuthorized')}
if __name__=='__main__':
    for attempt in range(3):
        try:
            receipt=fetch()
            print('NRY_CARP_PILLAR_SOURCE_PROOF '+json.dumps(receipt,ensure_ascii=False,sort_keys=True))
            sys.exit(0)
        except (urllib.error.URLError,ValueError,RuntimeError,TimeoutError) as e:
            print('NRY_CARP_PILLAR_SOURCE_PROOF_RETRY '+type(e).__name__,file=sys.stderr)
            if attempt==2:sys.exit(1)
            time.sleep(5)
