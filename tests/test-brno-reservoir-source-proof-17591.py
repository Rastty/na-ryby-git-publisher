#!/usr/bin/env python3
"""Offline schema and drift checks, no network."""
import runpy
from pathlib import Path

module = runpy.run_path(str(Path(__file__).resolve().parents[1]
                            / 'scripts/verify-brno-reservoir-source-proof-17591.py'))
proof = {'schema': 1, 'state': 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
         'postId': 17591, 'canonical': module['SITE'] + module['PATH'],
         'modifiedGmt': module['MODIFIED'], 'sourceSha256': 'b' * 64,
         'sourceBytes': 7000, 'literalCounts': {k: 1 for k in module['COUNT_KEYS']},
         'publicationAuthorized': False, 'wordpressWrites': 0,
         'merchantRequests': 0, 'syntheticClicks': 0}
post = {'id': 17591, 'slug': module['SLUG'], 'status': 'publish',
        'modified_gmt': '2018-01-22T19:53:18', 'link': module['SITE'] + module['PATH']}
verify = module['verify']
assert verify(proof, post)['status'] == 'BRNO_RESERVOIR_SOURCE_SHA_VERIFIED'


def reject(p=proof, q=post):
    try:
        verify(p, q)
    except (AssertionError, KeyError):
        return
    raise AssertionError('source identity drift accepted')


reject({**proof, 'sourceSha256': 'wrong'})
reject({**proof, 'publicationAuthorized': True})
reject({**proof, 'literalCounts': {'unexpected': 1}})
reject({**proof, 'merchantRequests': 1})
reject(q={**post, 'status': 'draft'})
reject(q={**post, 'modified_gmt': '2018-01-23T00:00:00'})
reject(q={**post, 'link': 'https://other.example/'})
print('PASS: Brno reservoir source proof fail-closed')
