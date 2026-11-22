#!/usr/bin/env python3
"""Offline read-only source SHA schema; no external WordPress or merchant GET."""
import sys
from importlib.machinery import SourceFileLoader
from pathlib import Path

script = Path(__file__).resolve().parents[1] / 'scripts/verify-wellness-srub-source-proof-17536.py'
mod = SourceFileLoader('wellness_srub_source_verify', str(script)).load_module()
base = dict(schema=1, state='SOURCE_SHA_AVAILABLE_NO_PUBLICATION', postId=17536,
            canonical=mod.SITE + mod.PATH, modifiedGmt=mod.MODIFIED,
            sourceSha256='a' * 64, sourceBytes=3800,
            literalCounts={k: 1 for k in mod.COUNT_KEYS},
            publicationAuthorized=False, wordpressWrites=0,
            merchantRequests=0, syntheticClicks=0)
rest = dict(id=17536, status='publish', slug=mod.PATH.strip('/'),
            link=mod.SITE + mod.PATH, modified_gmt=mod.MODIFIED.replace(' ', 'T'))
assert mod.verify(base, rest)['status'] == 'WELLNESS_SRUB_SOURCE_SHA_VERIFIED'
for bad in (
    dict(base, sourceSha256=''),
    dict(base, state='BLOCKED_SOURCE_DRIFT'),
    dict(base, publicationAuthorized=True),
    dict(base, literalCounts={}),
    dict(base, merchantRequests=1),
):
    try:
        mod.verify(bad, rest)
        raise RuntimeError('FAIL_OPEN')
    except AssertionError:
        pass
for bad in (dict(rest, id=17537), dict(rest, modified_gmt='2026-11-19T00:00:00')):
    try:
        mod.verify(base, bad)
        raise RuntimeError('FAIL_OPEN')
    except AssertionError:
        pass
assert mod.PROOF.startswith('https://na-ryby.eu/poradce-vybavy/')
assert 'tracking.affiliateport.eu' not in (mod.PROOF + mod.REST)
print('PASS: wellness srub source SHA offline fail-closed')
