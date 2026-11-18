#!/usr/bin/env python3
"""Offline public-fingerprint proof shape tests. No network or stored data."""
import runpy
from pathlib import Path

module = runpy.run_path(str(Path(__file__).resolve().parents[1]
                            / 'scripts/verify-sport-stays-source-proof-85384.py'))
sha = 'b' * 64
proof = {'schema': 1, 'state': 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
         'postId': 85384, 'canonical': module['SITE'] + module['PATH'],
         'modifiedGmt': module['MODIFIED'], 'sourceSha256': sha,
         'sourceBytes': 15000, 'literalCounts': {k: 0 for k in module['COUNT_KEYS']},
         'publicationAuthorized': False, 'wordpressWrites': 0,
         'syntheticClicks': 0}
post = {'id': 85384, 'slug': module['SLUG'], 'status': 'publish',
        'modified_gmt': '2026-04-12T12:38:45',
        'link': module['SITE'] + module['PATH']}
verify = module['verify']
assert verify(proof, post)['status'] == 'SPORT_STAYS_SOURCE_SHA_VERIFIED'


def rejects(p=proof, q=post):
    try:
        verify(p, q)
    except (AssertionError, KeyError):
        return
    raise AssertionError('tampered source identity was accepted')


rejects({**proof, 'sourceSha256': 'wrong'})
rejects({**proof, 'state': 'BLOCKED_SOURCE_DRIFT'})
rejects({**proof, 'publicationAuthorized': True})
rejects({**proof, 'wordpressWrites': 1})
rejects({**proof, 'literalCounts': {'trackingSecret': 1}})
rejects(q={**post, 'id': 92205})
rejects(q={**post, 'modified_gmt': '2026-04-12T12:39:00'})
rejects(q={**post, 'link': 'https://other.example/stays/'})
print('PASS: #85384 source SHA public proof shape fails closed')
