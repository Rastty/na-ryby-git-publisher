#!/usr/bin/env python3
"""Best-effort public GitHub runner egress IP hint; never inspect deployment secrets.

Run only immediately before an already-authorized guarded deploy. A single GET
to the official ipify IPv4 echo endpoint gives WEDOS a potential /32 to search
around the deployment timestamp; the origin's access log remains authoritative.
Failures are deliberately nonfatal and never emit exception text or raw HTTP.
"""

import ipaddress
import json
import urllib.request


ENDPOINT = 'https://api.ipify.org'
MAX_RESPONSE_BYTES = 64


class DenyRedirects(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def get_public_ipv4(opener):
    request = urllib.request.Request(ENDPOINT, headers={
        'User-Agent': 'NaRyby-Guarded-Deploy-Public-IP-Diagnostic/1',
        'Accept': 'text/plain',
    }, method='GET')
    with opener.open(request, timeout=6) as response:
        if response.status != 200 or response.geturl() != ENDPOINT:
            raise ValueError('untrusted echo response')
        data = response.read(MAX_RESPONSE_BYTES + 1)
    if len(data) > MAX_RESPONSE_BYTES:
        raise ValueError('oversized echo response')
    candidate = data.decode('ascii').strip()
    address = ipaddress.ip_address(candidate)
    if not isinstance(address, ipaddress.IPv4Address) or not address.is_global:
        raise ValueError('not a global IPv4')
    return str(address)


def diagnostic(opener):
    try:
        ipv4 = get_public_ipv4(opener)
    except Exception:
        # No traceback, URL, response, token or exception content in CI logs.
        ipv4 = None
    return {
        'diagnostic': 'RUNNER_EGRESS_IPV4_HINT_V1',
        'ipv4': ipv4,
        'source': 'official_ipify_ipv4_echo_get',
        'correlation': 'hint_only_origin_logs_authoritative',
        'deployerRequests': 0,
        'wordpressWrites': 0,
        'secretsLogged': False,
    }


def main():
    opener = urllib.request.build_opener(DenyRedirects())
    print(json.dumps(diagnostic(opener), sort_keys=True))


if __name__ == '__main__':
    main()
