#!/usr/bin/env python3
"""GET-only independent raw-source SHA; public site only, never merchants."""
import json
import sys
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
PATH = '/soukromy-revir-sterkac-zajeci/'
PROOF = SITE + '/poradce-vybavy/?nry_sterkac_source_proof=1'
REST = SITE + '/wp-json/wp/v2/posts/12040?_fields=id,slug,status,modified_gmt,link'
SLUG = PATH.strip('/')
MODIFIED = '2026-04-14 10:33:15'
COUNT_KEYS = {'oldPermit300Mentions', 'novemberMentions',
              'oldBookingCategoryLinks', 'officialOperatorLinks'}


def verify(proof, post):
    assert post['id'] == 12040 and post['status'] == 'publish'
    assert post['slug'] == SLUG and post['link'] == SITE + PATH
    assert post['modified_gmt'] == MODIFIED.replace(' ', 'T'), 'PUBLIC_POST_CHANGED'
    assert proof['schema'] == 1 and proof['state'] == 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION'
    assert proof['postId'] == 12040 and proof['canonical'] == SITE + PATH
    assert proof['modifiedGmt'] == MODIFIED
    sha = proof['sourceSha256']
    assert len(sha) == 64 and all(c in '0123456789abcdef' for c in sha)
    assert type(proof['sourceBytes']) is int and proof['sourceBytes'] > 5000
    assert set(proof['literalCounts']) == COUNT_KEYS
    assert all(type(v) is int and 0 <= v < 100 for v in proof['literalCounts'].values())
    assert proof['publicationAuthorized'] is False and proof['wordpressWrites'] == 0
    assert proof['merchantRequests'] == 0 and proof['syntheticClicks'] == 0
    return {'status': 'STERKAC_SOURCE_SHA_VERIFIED', 'postId': 12040,
            'sourceSha256': sha, 'sourceBytes': proof['sourceBytes'],
            'literalCounts': proof['literalCounts'], 'publicationAuthorized': False,
            'wordpressWrites': 0, 'merchantRequests': 0, 'syntheticClicks': 0}


def get_json(url):
    assert url in (PROOF, REST), 'UNAPPROVED_READ'
    req = Request(url, headers={'User-Agent': 'NaRyby-Sterkac-SourceProof/1.0',
                                'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        raw = response.read(12000)
        assert len(raw) < 12000, 'RESPONSE_TOO_LARGE'
        return json.loads(raw)


def main():
    assert sys.argv[1:] == ['--verify'], 'VERIFY_PHASE_REQUIRED'
    print(json.dumps(verify(get_json(PROOF), get_json(REST)), ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, KeyError, OSError, TypeError) as exc:
        print(json.dumps({'status': 'STERKAC_SOURCE_PROOF_BLOCKED',
                          'error': type(exc).__name__}))
        raise SystemExit(1)
