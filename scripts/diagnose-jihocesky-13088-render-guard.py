#!/usr/bin/env python3
"""GET-only exact-post render-guard probe. Bounded digests/flags, no WP writes."""
import json
import re
from urllib.request import Request, HTTPRedirectHandler, build_opener

URL = 'https://na-ryby.eu/rybareni-s-ubytovanim-jihocesky-kraj/?nry_jc_guard_probe=source_v1'


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, request, fp, code, message, headers, newurl):
        return None


def inspect(html):
    matches = re.findall(r'<!-- NRY_JC13088_PROBE_V1 (\{[^<>]{0,2400}\}) -->', html)
    assert len(matches) == 1, 'GUARD_PROBE_MISSING_OR_DUPLICATE'
    data = json.loads(matches[0])
    assert data['status'] == 'JIHOCESKY_13088_GUARD_PROBE_V1', 'BAD_PROBE_STATUS'
    assert data['postId'] == 13088 and data['eligibleWithoutLoop'] is True, 'BAD_POST_IDENTITY'
    assert isinstance(data['renderCalls'], int) and 0 <= data['renderCalls'] <= 100000, 'BAD_CALL_COUNT'
    assert data['wordpressWrites'] == data['merchantRequests'] == data['syntheticClicks'] == 0, 'UNSAFE_PROBE'
    for field in ('first', 'best'):
        row = data[field]
        if row is None:
            continue
        assert isinstance(row['bytes'], int) and 0 <= row['bytes'] <= 2000000
        assert re.fullmatch(r'[a-f0-9]{64}', row['prefixSha256'])
        assert isinstance(row['anchorMask'], int) and 0 <= row['anchorMask'] <= 127
        for key in ('prefixAllowed', 'mainQuery', 'inLoop', 'eligible',
                    'alreadyContainsBlock', 'alreadyContainsStaysLink'):
            assert isinstance(row[key], bool), 'INVALID_GUARD_FLAG'
    return data


def main():
    request = Request(URL, headers={
        'Cache-Control': 'no-cache,no-store',
        'User-Agent': 'NaRyby-JC13088-ReadOnlyRuntimeProbe/1.0',
    })
    with build_opener(NoRedirect).open(request, timeout=25) as response:
        assert response.status == 200 and response.geturl() == URL, 'HTTP_OR_REDIRECT_DRIFT'
        html = response.read(2000001)
    assert len(html) <= 2000000, 'RESPONSE_TOO_LARGE'
    print(json.dumps(inspect(html.decode('utf-8', 'replace')), ensure_ascii=False, sort_keys=True))


if __name__ == '__main__':
    try:
        main()
    except (AssertionError, OSError, ValueError, KeyError) as exc:
        print(json.dumps({'status':'JIHOCESKY_13088_GUARD_PROBE_BLOCKED','reason':str(exc)[:150]}))
        raise SystemExit(1)
