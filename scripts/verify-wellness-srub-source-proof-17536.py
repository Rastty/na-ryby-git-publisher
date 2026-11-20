#!/usr/bin/env python3
"""Only exact anonymous WP identity and GET-only source SHA; no merchant reads."""
import json
import sys
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
PATH = '/chata-rybarsky-wellness-srub/'
PROOF = SITE + '/poradce-vybavy/?nry_wellness_srub_source_proof=1'
REST = SITE + '/wp-json/wp/v2/posts/17536?_fields=id,slug,status,modified_gmt,link'
MODIFIED = '2023-04-11 13:48:48'
COUNT_KEYS = {'oldAgodaCjLinks', 'oldAgodaBoxes', 'lowestPriceClaims',
              'oldBoatFeedingClaims', 'verifiedHauziListingLinks'}


def verify(proof, post):
    assert post['id'] == 17536 and post['status'] == 'publish'
    assert post['slug'] == PATH.strip('/') and post['link'] == SITE + PATH
    assert post['modified_gmt'] == MODIFIED.replace(' ', 'T')
    assert proof['schema'] == 1 and proof['state'] == 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION'
    assert proof['postId'] == 17536 and proof['canonical'] == SITE + PATH
    assert proof['modifiedGmt'] == MODIFIED
    sha = proof['sourceSha256']
    assert len(sha) == 64 and all(c in '0123456789abcdef' for c in sha)
    assert type(proof['sourceBytes']) is int and proof['sourceBytes'] > 1000
    assert set(proof['literalCounts']) == COUNT_KEYS
    assert all(type(v) is int and 0 <= v < 50 for v in proof['literalCounts'].values())
    assert proof['publicationAuthorized'] is False and proof['wordpressWrites'] == 0
    assert proof['merchantRequests'] == 0 and proof['syntheticClicks'] == 0
    return {'status': 'WELLNESS_SRUB_SOURCE_SHA_VERIFIED', 'postId': 17536,
            'sourceSha256': sha, 'sourceBytes': proof['sourceBytes'],
            'literalCounts': proof['literalCounts'], 'wordpressWrites': 0,
            'merchantRequests': 0, 'syntheticClicks': 0}


def get_json(url):
    assert url in (PROOF, REST), 'UNAPPROVED_READ'
    req = Request(url, headers={'User-Agent': 'NaRyby-Wellness-Srub-Source/1.0',
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
        print(json.dumps({'status': 'WELLNESS_SRUB_SOURCE_PROOF_BLOCKED',
                          'error': type(exc).__name__}))
        raise SystemExit(1)
