#!/usr/bin/env python3
"""GET-only production readback of the raw-source fingerprint for post 85384.

Hashes and limited counts only; no stored content, affiliate URL requests,
WordPress writes, synthetic clicks, or publication authorization.
"""
import json
import sys
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
PATH = '/nejlepsi-mista-pro-sportovni-rybolov-s-ubytovanim-v-ceske-republice/'
PROOF = SITE + '/poradce-vybavy/?nry_sport_stays_source_proof=1'
REST = SITE + '/wp-json/wp/v2/posts/85384?_fields=id,slug,status,modified_gmt,link'
SLUG = PATH.strip('/')
MODIFIED = '2026-04-12 12:38:45'
COUNT_KEYS = {'legacyBookingLinks', 'stayGuideMentions',
              'affiliateDognetUrls', 'affiliateHauziUrls'}


def verify(proof, public_post):
    assert public_post['id'] == 85384 and public_post['status'] == 'publish'
    assert public_post['slug'] == SLUG and public_post['link'] == SITE + PATH
    assert public_post['modified_gmt'] == MODIFIED.replace(' ', 'T'), 'PUBLIC_POST_CHANGED'
    assert proof['schema'] == 1 and proof['state'] == 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION'
    assert proof['postId'] == 85384 and proof['canonical'] == SITE + PATH
    assert proof['modifiedGmt'] == MODIFIED
    sha = proof['sourceSha256']
    assert len(sha) == 64 and all(c in '0123456789abcdef' for c in sha)
    assert type(proof['sourceBytes']) is int and proof['sourceBytes'] > 4000
    counts = proof['literalCounts']
    assert set(counts) == COUNT_KEYS
    assert all(type(v) is int and 0 <= v < 100 for v in counts.values())
    assert proof['publicationAuthorized'] is False and proof['wordpressWrites'] == 0
    assert proof['syntheticClicks'] == 0
    return {'status': 'SPORT_STAYS_SOURCE_SHA_VERIFIED', 'postId': 85384,
            'sourceSha256': sha, 'sourceBytes': proof['sourceBytes'],
            'modifiedGmt': MODIFIED, 'literalCounts': counts,
            'publicationAuthorized': False, 'wordpressWrites': 0,
            'merchantRequests': 0, 'syntheticClicks': 0}


def get_json(url):
    assert url in (PROOF, REST), 'UNAPPROVED_PUBLIC_READ'
    request = Request(url, headers={'User-Agent': 'NaRyby-SportStays-Source-ReadOnly/1.0',
                                    'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(request, timeout=25) as response:
        raw = response.read(12000)
        assert len(raw) < 12000, 'RESPONSE_TOO_LARGE'
        return json.loads(raw)


def main():
    assert sys.argv[1:] == ['--verify'], 'VERIFY_PHASE_REQUIRED'
    print(json.dumps(verify(get_json(PROOF), get_json(REST)), ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (ValueError, AssertionError, OSError, KeyError, TypeError) as exc:
        print(json.dumps({'status': 'SPORT_STAYS_SOURCE_PROOF_BLOCKED',
                          'error': type(exc).__name__}))
        raise SystemExit(1)
