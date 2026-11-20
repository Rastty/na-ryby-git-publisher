"""Offline bounded guard probe parser tests; network is never opened."""
import json
import runpy
from pathlib import Path

path = Path(__file__).resolve().parents[1] / 'scripts/diagnose-jihocesky-13088-render-guard.py'
inspect = runpy.run_path(str(path))['inspect']
row = {
    'bytes': 16000, 'prefixSha256': 'a' * 64, 'prefixAllowed': False,
    'mainQuery': True, 'inLoop': False, 'eligible': False,
    'anchorMask': 127, 'alreadyContainsBlock': False,
    'alreadyContainsStaysLink': False,
}
payload = {
    'status': 'JIHOCESKY_13088_GUARD_PROBE_V1',
    'postId': 13088, 'eligibleWithoutLoop': True, 'renderCalls': 1,
    'first': row, 'best': row, 'wordpressWrites': 0,
    'merchantRequests': 0, 'syntheticClicks': 0,
}
def fixture(data):
    return '<html><!-- NRY_JC13088_PROBE_V1 ' + json.dumps(data) + ' --></html>'
assert inspect(fixture(payload))['best']['anchorMask'] == 127
assert inspect(fixture(dict(payload, renderCalls=0, first=None, best=None)))['renderCalls'] == 0
for bad in (
    '<html></html>',
    fixture(dict(payload, postId=13089)),
    fixture(dict(payload, merchantRequests=1)),
    fixture(dict(payload, best=dict(row, prefixSha256='bad'))),
    fixture(payload) + fixture(payload),
):
    try:
        inspect(bad)
    except (AssertionError, KeyError, ValueError):
        continue
    raise AssertionError('UNSAFE_OR_UNBOUNDED_PROBE_ACCEPTED')
print('JIHOCESKY_13088_GUARD_PROBE_OFFLINE_OK')
