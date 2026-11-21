#!/usr/bin/env python3
"""GET-only public visitor readback, only AFTER verified source + real deploy."""
import json
import sys
from pathlib import Path
from urllib.request import Request

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

SITE = 'https://na-ryby.eu'
PAGE = SITE + '/soukromy-revir-sterkac-zajeci/'
REST = SITE + '/wp-json/wp/v2/posts/12040?_fields=id,slug,status,modified_gmt,link'
MARKER = 'data-nry-sterkac-rules="v1"'
ARTICLE_HEAD = '<h1>Soukromý revír Štěrkáč Zaječí</h1>'
ARTICLE_END = '<div style="border-top:1px solid #e5e5e5;margin-top:28px;'


def verify(html, post):
    assert post['id'] == 12040 and post['status'] == 'publish'
    assert post['slug'] == 'soukromy-revir-sterkac-zajeci'
    assert post['modified_gmt'] == '2026-04-14T10:33:15'
    assert post['link'] == PAGE
    assert html.count(MARKER) == 1, 'MISSING_OR_DUPLICATE_PANEL'
    assert html.count(ARTICLE_HEAD) == 1 and html.count(ARTICLE_END) == 1
    start = html.index(ARTICLE_HEAD)
    end = html.index(ARTICLE_END, start)
    article = html[start:end]
    for required in ('500 Kč', 'Noční povolenku nelze koupit samostatně',
                     'Lov dravců je celoročně zakázán',
                     'https://vinarstviukaplicky.cz/rybarina/',
                     'tel:+420725924195', 'platný český rybářský lístek'):
        assert required in article, 'MISSING_VISITOR_FACT'
    for outdated in ('300 Kč', 'listopad', 'bez protihrotu',
                     'si rybu i ponechat', 'booking-cesko'):
        assert outdated not in article, 'STALE_CLAIM_REMAINS'
    return {'status': 'STERKAC_RULES_LIVE_VERIFIED', 'postId': 12040,
            'markerCount': 1, 'wordpressWrites': 0,
            'merchantRequests': 0, 'syntheticClicks': 0}


def fetch(url, limit):
    assert url in (PAGE, REST), 'UNAPPROVED_READ'
    req = Request(url, headers={'User-Agent': 'NaRyby-Sterkac-RulesReadback/1.0',
                                'Cache-Control': 'no-cache,no-store'})
    with guarded_urlopen(req, timeout=25) as response:
        assert response.status == 200
        data = response.read(limit + 1)
        assert len(data) <= limit, 'OVERSIZE_RESPONSE'
        return data.decode('utf-8')


def main():
    assert sys.argv[1:] == ['--verify'], 'VERIFY_PHASE_REQUIRED'
    print(json.dumps(verify(fetch(PAGE, 1700000), json.loads(fetch(REST, 12000))),
                     ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, ValueError, KeyError, OSError, TypeError) as exc:
        print(json.dumps({'status': 'STERKAC_RULES_LIVE_BLOCKED',
                          'error': type(exc).__name__}))
        raise SystemExit(1)
