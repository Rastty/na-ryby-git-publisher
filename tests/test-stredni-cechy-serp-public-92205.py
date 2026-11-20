"""Strict offline tests for exact GET-only SEO readback parser."""
import importlib.util
from html import escape
from pathlib import Path

source = Path(__file__).resolve().parents[1] / 'scripts/verify-stredni-cechy-serp-public-92205.py'
spec = importlib.util.spec_from_file_location('nry_stredni_serp', source)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


def fixture(desc):
    return ('<html><head><title>' + module.TITLE + '</title>'
            + '<link rel="canonical" href="' + module.URL + '">'
            + '<meta name="description" content="' + escape(desc, quote=True) + '">'
            + '<meta property="og:description" content="' + escape(desc, quote=True) + '">'
            + '<meta name="robots" content="index, follow"></head><body>untouched</body></html>')


def reject(action):
    try:
        action()
    except AssertionError:
        return
    raise AssertionError('fail-closed guard did not reject tampering')


assert module.validate_page(fixture(module.BASELINE), False)['state'] == 'SEO_BASELINE_VERIFIED'
assert module.validate_page(fixture(module.CANDIDATE), True)['state'] == 'SEO_PUBLIC_VERIFIED'
reject(lambda: module.validate_page(fixture(module.CANDIDATE), False))
reject(lambda: module.validate_page(fixture(module.BASELINE), True))
reject(lambda: module.validate_page(fixture(module.BASELINE).replace('rel="canonical"', 'rel="alternate"'), False))
reject(lambda: module.validate_page(fixture(module.BASELINE).replace('content="index, follow"', 'content="noindex"'), False))
reject(lambda: module.validate_page(fixture(module.BASELINE).replace('og:description', 'og:title'), False))
proof = {'state': 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'postId': 92205,
         'canonical': module.URL, 'sourceSha256': module.SOURCE_SHA,
         'sourceBytes': 5881, 'modifiedGmt': '2024-10-07 12:15:48'}
module.validate_source(proof)
reject(lambda: module.validate_source({**proof, 'sourceSha256': '0' * 64}))
reject(lambda: module.validate_source({**proof, 'postId': 92206}))
print('PASS Stredni Cechy SERP exact public baseline, candidate and source-proof guards')
