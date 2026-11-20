#!/usr/bin/env python3
"""Prove a private secret never appears in safe deployment failure metadata."""
import json
from importlib.machinery import SourceFileLoader
from pathlib import Path

module = SourceFileLoader(
    'deployer_deny',
    str(Path(__file__).resolve().parents[1] /
        'scripts/sanitize-deployer-denial-metadata.py')
).load_module()
secret = 'PRIVATE_QUERY_SECRET_SHOULD_NEVER_APPEAR_6D2EB6C3'
header = (
    'HTTP/1.1 200 Connection established\r\n\r\n'
    'HTTP/2 401\r\n'
    'Content-Type: application/json; charset=UTF-8\r\n'
    'Server: envoy\r\n'
    'X-Provider: WEDOS - WE DO Security\r\n'
    'X-CDN-Cache-Status: BYPASS\r\n'
    'Location: https://na-ryby.eu/?secret=' + secret + '\r\n'
    'Set-Cookie: session=' + secret + '\r\n'
    'X-Untrusted-Secret: ' + secret + '\r\n\r\n'
).encode('utf8')
body = json.dumps({'code': 'rest_forbidden', 'message': secret}).encode('utf8')
result = module.classify(header, body, '401')
assert result == {
    'diagnostic': 'DEPLOYER_DENIAL_SANITIZED_V1',
    'httpStatus': '401', 'mimeFamily': 'json',
    'serverFamily': 'envoy', 'wedosRelatedHeadersPresent': True,
    'cdnCacheClass': 'BYPASS', 'wpRestCodeType': 'rest_prefix',
    'responseBodySizeClass': 'small',
    'secretsOrRawResponseLogged': False, 'newNetworkRequests': 0,
    'wordpressWrites': 0,
}
assert secret not in json.dumps(result)
assert 'rest_forbidden' not in json.dumps(result)
assert module.classify(b'HTTP/2 401\r\nContent-Type: text/html\r\n', b'<html>no access</html>', '401')['mimeFamily'] == 'html'
assert module.classify(b'HTTP/2 403\r\nContent-Type: application/json\r\n',
                       b'{"code":"dfg_invalid_secret","message":"denied"}', '403')['wpRestCodeType'] == 'dfg_prefix'
assert module.classify(b'HTTP/2 401\r\nServer: '+secret.encode('ascii')+b'\r\n',
                       secret.encode('ascii'), '401')['serverFamily'] == 'other'
assert secret not in json.dumps(module.classify(b'HTTP/2 401\r\nServer: '+secret.encode('ascii')+b'\r\n',
                                                   secret.encode('ascii'), '401'))
try:
    module.classify(header, body, '200')
    raise RuntimeError('Diagnostic must fail closed on HTTP 200')
except AssertionError:
    pass
print('PASS: Deployer non-200 diagnostic classifies origin hints without leaking secrets')
