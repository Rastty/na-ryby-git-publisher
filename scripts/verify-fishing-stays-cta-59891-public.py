#!/usr/bin/env python3
"""Anonymous GET-only confirmation of the scoped stay CTA style on post 59891.

No WordPress writes, affiliate URL requests or synthetic click events.
"""
import json
import runpy
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

ROOT = Path(__file__).resolve().parents[1]
BASE = runpy.run_path(str(ROOT / 'scripts/verify-fishing-stays-click-public-v1.py'))
SITE = BASE['ORIGIN']
POST = BASE['POST']
CSS = '/wp-content/plugins/na-ryby-git-publisher/assets/nry-fishing-stays-cta-59891-v1.css'
VERSION = 'ver=1.0.0'


class Styles(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.styles = []

    def handle_starttag(self, tag, attrs):
        if tag != 'link':
            return
        a = dict(attrs)
        url = urlsplit(a.get('href') or '')
        if url.path == CSS:
            self.styles.append((url.scheme, url.netloc, url.query,
                                (a.get('rel') or '').lower()))


def verify(page, *, deployed=False, public_css=b'', reviewed_css=b''):
    # Reuse existing production-v9 offer identity/link/canonical validation.
    release = BASE['verify'](page, deployed=False)
    parser = Styles()
    parser.feed(page.decode('utf-8', 'replace'))
    parser.close()
    if deployed:
        assert parser.styles == [('', '', VERSION, 'stylesheet')] or parser.styles == [
            ('https', 'na-ryby.eu', VERSION, 'stylesheet')
        ], 'STAY_CTA_CSS_NOT_EXACTLY_ONCE'
        assert len(public_css) > 800 and public_css == reviewed_css, 'STAY_CTA_CSS_BYTE_DRIFT'
    else:
        assert parser.styles == [], 'STAY_CTA_ALREADY_STYLED_OR_UNREVIEWED'
    return {
        'status': 'STAY_CTA_PUBLIC_VERIFIED' if deployed else 'STAY_CTA_PREFLIGHT_READY',
        'postId': 59891, 'exactSponsoredOffers': release['exactSponsoredOffers'],
        'styleByteIdentical': deployed, 'wordPressWrites': 0,
        'merchantRequests': 0, 'syntheticClicks': 0,
        'realClicks': None, 'paidCommissions': None,
    }


def get(url, max_bytes):
    assert url in (SITE + POST, SITE + CSS + '?' + VERSION), 'UNAPPROVED_STAY_CTA_GET'
    request = Request(url, method='GET', headers={'User-Agent': 'NaRyby-StayStyle-GETOnly/1.0',
                                                 'Cache-Control': 'no-cache, no-store'})
    with guarded_urlopen(request, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'STAY_CTA_GET_REDIRECT'
        result = response.read(max_bytes + 1)
    assert len(result) <= max_bytes, 'STAY_CTA_MAX_BYTES'
    return result


if __name__ == '__main__':
    if len(sys.argv) != 2 or sys.argv[1] not in ('--preflight', '--verify'):
        raise SystemExit('Usage: --preflight or --verify')
    deployed = sys.argv[1] == '--verify'
    try:
        result = verify(
            get(SITE + POST, 1500000), deployed=deployed,
            public_css=get(SITE + CSS + '?' + VERSION, 30000) if deployed else b'',
            reviewed_css=(ROOT / 'assets/nry-fishing-stays-cta-59891-v1.css').read_bytes()
            if deployed else b'')
        print(json.dumps(result, ensure_ascii=False))
    except (AssertionError, ValueError, OSError) as exc:
        print(json.dumps({'status': 'STAY_CTA_PUBLIC_BLOCKED', 'reason': str(exc)[:150]}))
        raise SystemExit(1)
