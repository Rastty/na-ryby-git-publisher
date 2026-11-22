#!/usr/bin/env python3
"""Offline schema/identity contract only."""
import runpy
from pathlib import Path

m = runpy.run_path(str(Path(__file__).resolve().parents[1]
                      / 'scripts/verify-cyklochata-source-proof-11983.py'))
proof = {'schema': 1, 'state': 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
         'postId': 11983, 'canonical': m['SITE'] + m['PATH'],
         'modifiedGmt': m['MODIFIED'], 'sourceSha256': 'f' * 64,
         'sourceBytes': 2000, 'literalCounts': {k: 1 for k in m['COUNT_KEYS']},
         'publicationAuthorized': False, 'wordpressWrites': 0,
         'merchantRequests': 0, 'syntheticClicks': 0}
post = {'id': 11983, 'slug': m['PATH'].strip('/'), 'status': 'publish',
        'modified_gmt': m['MODIFIED'].replace(' ', 'T'),
        'link': m['SITE'] + m['PATH']}
verify = m['verify']
assert verify(proof, post)['status'] == 'CYKLOCHATA_SOURCE_SHA_VERIFIED'


def reject(p=proof, q=post):
    try:
        verify(p, q)
    except (AssertionError, KeyError, TypeError):
        return
    raise AssertionError('unsafe source proof accepted')


reject({**proof, 'sourceSha256': 'invalid'})
reject({**proof, 'publicationAuthorized': True})
reject({**proof, 'literalCounts': {'unknown': 1}})
reject({**proof, 'merchantRequests': 1})
reject(q={**post, 'status': 'draft'})
reject(q={**post, 'modified_gmt': '2023-04-12T00:00:00'})
reject(q={**post, 'link': 'https://example.org/'})
print('PASS: Cyklochata source proof offline fail-closed')
