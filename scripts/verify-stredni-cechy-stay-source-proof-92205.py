#!/usr/bin/env python3
"""Anonymous GET-only public SHA proof; source remains private on WordPress.

Never follows merchant URLs, creates WP writes or synthesizes buyer clicks.
Only run --verify after the guarded main deployment has completed.
"""
import argparse
import json
from urllib.request import Request, urlopen

SITE = 'https://na-ryby.eu'
CANONICAL = SITE + '/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/'
PROOF = SITE + '/poradce-vybavy/?nry_stredni_cechy_stay_source_proof=1'
REST = SITE + '/wp-json/wp/v2/posts/92205?_fields=id,slug,status,modified_gmt,link'


def read_json(url):
    with urlopen(Request(url, headers={'User-Agent': 'Na-Ryby-Source-Proof/1.0'}), timeout=25) as response:
        assert response.status == 200
        return json.load(response)


def validate(proof, post):
    assert post['id'] == 92205 and post['status'] == 'publish'
    assert post['slug'] == 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach'
    assert post['link'] == CANONICAL
    assert post['modified_gmt'] == '2024-10-07T12:15:48'
    assert proof['schema'] == 1 and proof['state'] == 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION'
    assert proof['postId'] == 92205 and proof['canonical'] == CANONICAL
    assert proof['modifiedGmt'] == '2024-10-07 12:15:48'
    assert proof['sourceBytes'] > 1200
    assert len(proof['sourceSha256']) == 64
    assert all(c in '0123456789abcdef' for c in proof['sourceSha256'])
    assert proof['publicationAuthorized'] is False and proof['wordpressWrites'] == 0
    assert proof['syntheticClicks'] == 0
    assert set(proof['literalCounts']) == {'weOfferExperience', 'jindrichovice', 'stredniCechyFishingSource'}
    assert all(isinstance(v, int) and 0 <= v < 100 for v in proof['literalCounts'].values())
    return {
        'status': 'STREDNI_CECHY_SOURCE_SHA_VERIFIED',
        'postId': 92205,
        'modifiedGmt': proof['modifiedGmt'],
        'sourceSha256': proof['sourceSha256'],
        'sourceBytes': proof['sourceBytes'],
        'literalCounts': proof['literalCounts'],
        'publicationAuthorized': False,
        'wordpressWrites': 0,
        'merchantRequests': 0,
        'syntheticClicks': 0,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--verify', action='store_true', required=True)
    parser.parse_args()
    print(json.dumps(validate(read_json(PROOF), read_json(REST)), ensure_ascii=False))


if __name__ == '__main__':
    main()
