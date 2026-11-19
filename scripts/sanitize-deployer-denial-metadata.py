#!/usr/bin/env python3
"""Safe non-200 Deployer response classifier; never prints headers, body, URL or token.

Run ONLY after an otherwise authorized Deployer call fails. The only output is
fixed-label metadata useful to distinguish WAF HTML from WordPress JSON. This
script itself has no network operations, secrets or deployment capabilities.
"""
import argparse
import json
from pathlib import Path

MAX_BODY_READ = 65536


def classify(headers, body, status):
    assert isinstance(headers, bytes) and isinstance(body, bytes)
    assert isinstance(status, str) and len(status) == 3 and status.isdigit()
    assert status != '200', 'DIAGNOSTIC_ONLY_ON_DENIAL'
    # curl --dump-header may contain a proxy CONNECT block before the final
    # origin response. Only the LAST HTTP status block is relevant.
    fields = {}
    for line in headers[:32768].decode('latin-1', 'replace').splitlines():
        if line.startswith('HTTP/'):
            fields = {}
        elif ':' in line:
            key, value = line.split(':', 1)
            fields[key.strip().lower()] = value.strip()[:1000]
    content_type = fields.get('content-type', '').split(';', 1)[0].strip().lower()
    mime = ('json' if content_type in ('application/json', 'application/problem+json')
            or content_type.endswith('+json') else
            'html' if content_type in ('text/html', 'application/xhtml+xml') else
            'text' if content_type == 'text/plain' else
            'other' if content_type else 'missing')
    server = fields.get('server', '').lower()
    server_family = ('envoy' if 'envoy' in server else
                     'nginx' if 'nginx' in server else
                     'apache' if 'apache' in server else
                     'other' if server else 'missing')
    edge = any('wedos' in fields.get(name, '').lower()
               for name in ('x-provider', 'x-protected-by', 'x-cdn-provider'))
    cache = fields.get('x-cdn-cache-status', '').upper()
    cache_class = cache if cache in ('HIT', 'MISS', 'BYPASS', 'EXPIRED') else (
        'other' if cache else 'missing')
    rest_code_type = 'absent'
    if mime == 'json' and len(body) <= MAX_BODY_READ:
        try:
            payload = json.loads(body)
            code = payload.get('code', '') if isinstance(payload, dict) else ''
            if isinstance(code, str) and code:
                rest_code_type = ('dfg_prefix' if code.lower().startswith('dfg_') else
                                  'rest_prefix' if code.lower().startswith('rest_') else
                                  'other_code')
        except (ValueError, UnicodeDecodeError):
            rest_code_type = 'invalid_json'
    return {
        'diagnostic': 'DEPLOYER_DENIAL_SANITIZED_V1',
        'httpStatus': status,
        'mimeFamily': mime,
        'serverFamily': server_family,
        'wedosRelatedHeadersPresent': edge,
        'cdnCacheClass': cache_class,
        'wpRestCodeType': rest_code_type,
        'responseBodySizeClass': ('zero' if not body else
                                  'small' if len(body) <= 4096 else
                                  'medium' if len(body) <= MAX_BODY_READ else 'large'),
        'secretsOrRawResponseLogged': False,
        'newNetworkRequests': 0,
        'wordpressWrites': 0,
    }


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument('--headers', type=Path, required=True)
    parser.add_argument('--body', type=Path, required=True)
    parser.add_argument('--status', required=True)
    args = parser.parse_args()
    headers = args.headers.read_bytes()[:32768]
    with args.body.open('rb') as response:
        body = response.read(MAX_BODY_READ + 1)
    print(json.dumps(classify(headers, body, args.status), sort_keys=True))


if __name__ == '__main__':
    main()
