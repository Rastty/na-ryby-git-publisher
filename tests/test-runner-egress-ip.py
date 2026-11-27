#!/usr/bin/env python3
"""Offline invariants for the guarded-deploy public egress IP hint."""

import json
import unittest
from importlib.machinery import SourceFileLoader
from pathlib import Path


module = SourceFileLoader(
    'runner_egress_ip',
    str(Path(__file__).resolve().parents[1] /
        'scripts/print-runner-egress-ip.py')
).load_module()


class FakeResponse:
    def __init__(self, payload, status=200, url=module.ENDPOINT):
        self.payload = payload
        self.status = status
        self.url = url

    def __enter__(self):
        return self

    def __exit__(self, exc_type, exc_value, traceback):
        return False

    def geturl(self):
        return self.url

    def read(self, size):
        return self.payload[:size]


class FakeOpener:
    def __init__(self, response=None, exception=None):
        self.response = response
        self.exception = exception
        self.calls = []

    def open(self, request, timeout):
        self.calls.append((request.full_url, request.get_method(), timeout))
        if self.exception:
            raise self.exception
        return self.response


class EgressIPv4Tests(unittest.TestCase):
    def test_success_without_deployer_request(self):
        opener = FakeOpener(FakeResponse(b'8.8.8.8\n'))
        result = module.diagnostic(opener)
        self.assertEqual(result['ipv4'], '8.8.8.8')
        self.assertEqual(result['deployerRequests'], 0)
        self.assertFalse(result['secretsLogged'])
        self.assertEqual(opener.calls, [(module.ENDPOINT, 'GET', 6)])

    def test_invalid_untrusted_or_oversized_responses_fail_closed(self):
        for response in (
            FakeResponse(b'127.0.0.1'),
            FakeResponse(b'192.168.1.1'),
            FakeResponse(b'::1'),
            FakeResponse(b'8.8.8.8 ' * 20),
            FakeResponse(b'8.8.8.8', status=302),
            FakeResponse(b'8.8.8.8', url='https://wrong.example/'),
            FakeResponse(b'PRIVATE_TOKEN=xyz'),
        ):
            with self.subTest(response=response.payload):
                result = module.diagnostic(FakeOpener(response))
                self.assertIsNone(result['ipv4'])
                self.assertNotIn('PRIVATE_TOKEN', json.dumps(result))

    def test_network_failure_never_leaks_exception(self):
        opener = FakeOpener(exception=OSError('secret=PRIVATE_ABC'))
        result = module.diagnostic(opener)
        self.assertIsNone(result['ipv4'])
        self.assertNotIn('PRIVATE_ABC', json.dumps(result))
        self.assertEqual(result['wordpressWrites'], 0)


if __name__ == '__main__':
    unittest.main(verbosity=2)
