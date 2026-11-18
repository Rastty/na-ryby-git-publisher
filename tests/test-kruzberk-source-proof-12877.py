#!/usr/bin/env python3
"""Offline schema/drift checks only; no public requests."""
import runpy
from pathlib import Path

module = runpy.run_path(str(Path(__file__).resolve().parents[1]
                            / 'scripts/verify-kruzberk-source-proof-12877.py'))
proof = {'schema': 1, 'state': 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
         'postId': 12877, 'canonical': module['SITE'] + module['PATH'],
         'modifiedGmt': module['MODIFIED'], 'sourceSha256': 'a' * 64,
         'sourceBytes': 8500, 'literalCounts': {k: 1 for k in module['COUNT_KEYS']},
         'publicationAuthorized': False, 'wordpressWrites': 0, 'syntheticClicks': 0}
post = {'id': 12877, 'slug': module['SLUG'], 'status': 'publish',
        'modified_gmt': '2026-04-14T23:33:21',
        'link': module['SITE'] + module['PATH']}
verify = module['verify']
assert verify(proof, post)['status'] == 'KRUZBERK_SOURCE_SHA_VERIFIED'


def reject(p=proof, q=post):
    try:
        verify(p, q)
    except (AssertionError, KeyError):
        return
    raise AssertionError('source identity drift was accepted')


reject({**proof, 'sourceSha256': 'wrong'})
reject({**proof, 'publicationAuthorized': True})
reject({**proof, 'literalCounts': {'unexpected': 1}})
reject({**proof, 'wordpressWrites': 1})
reject(q={**post, 'status': 'draft'})
reject(q={**post, 'modified_gmt': '2026-04-15T00:00:00'})
reject(q={**post, 'link': 'https://other.example/old/'})
print('PASS: Kružberk source proof fail-closed')
