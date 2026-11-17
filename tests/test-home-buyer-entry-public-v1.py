"""Independent offline public-markup parser regression."""
import importlib.util
from pathlib import Path

source = Path(__file__).resolve().parents[1] / 'scripts/verify-home-buyer-entry-public-v1.py'
spec = importlib.util.spec_from_file_location('home_buyer_proof', source)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


def check_reject(body, preflight, expected):
    try:
        module.verify_home(body, preflight)
    except ValueError as e:
        assert str(e) == expected, (e, expected)
    else:
        raise AssertionError('Expected fail closed: ' + expected)


nav = '<nav data-nry-home-problem-shortcuts="v4">' + ''.join(
    '<a href="https://na-ryby.eu' + p + '">Guide</a>' for p in module.BUYER_PATHS
) + '</nav>'
advisor = '<p data-nry-home-advisor-entry="v1"><a href="https://na-ryby.eu/poradce-vybavy/">Poradce</a></p>'
script = '<script id="nry-home-buyer-entry-v1-js" src="https://na-ryby.eu' + \
    module.SCRIPT_PATH + '?ver=1.1.0"></script>'
base = '<html><head><link rel="canonical" href="https://na-ryby.eu/"/></head><body>' + nav + advisor + script + '</body></html>'
assert module.verify_home(base, True)['routes_observed'] == 6
assert module.verify_home(base)['state'] == 'HOMEPAGE_MEASUREMENT_SCRIPT_DECLARED'
baseline = base.replace('data-nry-home-problem-shortcuts="v4"', 'data-nry-home-problem-shortcuts="v3"')
baseline = baseline.replace('<a href="https://na-ryby.eu' + module.BUYER_PATHS[-1] + '">Guide</a>', '')
assert module.verify_home(baseline, True)['routes_observed'] == 5
check_reject(baseline, False, 'SIX_HOMEPAGE_BUYER_ROUTES_NOT_LIVE')
check_reject(base.replace('/jak-vybrat-zavazeci-lodku/', '/broken/'), True, 'SIX_HOMEPAGE_BUYER_ROUTES_NOT_LIVE')
check_reject(base.replace('data-nry-home-problem-shortcuts="v4"', 'data-nry-home-problem-shortcuts="v3"'), True, 'SIX_HOMEPAGE_BUYER_ROUTES_NOT_LIVE')
check_reject(base.replace('/poradce-vybavy/', '/wrong/'), True, 'EXACT_HOMEPAGE_ADVISOR_LINK_MISSING')
check_reject(base.replace(script, ''), False, 'ONE_HOMEPAGE_MEASUREMENT_SCRIPT_REQUIRED')
check_reject(base.replace('?ver=1.1.0', '?ver=0.9.0'), False, 'HOMEPAGE_SCRIPT_IDENTITY_MISMATCH')
check_reject(base.replace('https://na-ryby.eu/"', 'https://other.example/"', 1), True, 'HOMEPAGE_CANONICAL_MISMATCH')
check_reject(base.replace(script, script+script), False, 'ONE_HOMEPAGE_MEASUREMENT_SCRIPT_REQUIRED')
check_reject(base.replace(nav, nav+nav), True, 'SIX_HOMEPAGE_BUYER_ROUTES_NOT_LIVE')
print('PASS homepage route parser, exact script identity, malformed-input rejection; no network')
