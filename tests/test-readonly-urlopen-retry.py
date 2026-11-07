#!/usr/bin/env python3
"""Offline regression of the exact-origin GET-only WordPress transport retry."""
import ssl
import sys
import unittest
import urllib.error
import urllib.request
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from scripts.readonly_urlopen_retry import guarded_urlopen


URL = "https://na-ryby.eu/poradce-vybavy/?nry_evergreen_source_proof=1"
REQUEST = urllib.request.Request(URL, headers={"Cache-Control": "no-cache"})


class FakeResponse:
    status = 200

    def __init__(self, url=URL, status=200):
        self.url = url
        self.status = status
        self.closed = False

    def geturl(self):
        return self.url

    def close(self):
        self.closed = True

    def __enter__(self):
        return self

    def __exit__(self, *_):
        self.close()


class ReadonlyTransportContract(unittest.TestCase):
    def test_ssl_handshake_timeout_once_then_success(self):
        calls, sleeps = [], []
        response = FakeResponse()

        def open_once_then_ok(req, timeout):
            calls.append((req.full_url, timeout))
            if len(calls) == 1:
                raise urllib.error.URLError(TimeoutError("TLS handshake timeout"))
            return response

        with guarded_urlopen(REQUEST, timeout=30, open_impl=open_once_then_ok,
                              sleep_impl=sleeps.append) as got:
            self.assertIs(got, response)
        self.assertEqual(calls, [(URL, 30), (URL, 30)])
        self.assertEqual(sleeps, [1])
        self.assertTrue(response.closed)

    def test_three_transient_failures_stop(self):
        calls, sleeps = [], []
        def always_fail(req, timeout):
            calls.append(1)
            raise urllib.error.URLError("temporary network")
        with self.assertRaises(urllib.error.URLError):
            guarded_urlopen(REQUEST, open_impl=always_fail, sleep_impl=sleeps.append)
        self.assertEqual(len(calls), 3)
        self.assertEqual(sleeps, [1, 2])

    def test_permanent_auth_http401_never_retries(self):
        calls = []
        def forbidden(req, timeout):
            calls.append(1)
            raise urllib.error.HTTPError(URL, 401, "Unauthorized", None, None)
        with self.assertRaises(urllib.error.HTTPError):
            guarded_urlopen(REQUEST, open_impl=forbidden,
                            sleep_impl=lambda _: self.fail("must not sleep"))
        self.assertEqual(len(calls), 1)

    def test_temporary_http503_then_success(self):
        calls, sleeps = [], []
        def temporary(req, timeout):
            calls.append(1)
            if len(calls) == 1:
                raise urllib.error.HTTPError(URL, 503, "Unavail", None, None)
            return FakeResponse()
        with guarded_urlopen(REQUEST, open_impl=temporary, sleep_impl=sleeps.append):
            pass
        self.assertEqual(len(calls), 2)
        self.assertEqual(sleeps, [1])

    def test_tls_certificate_verification_is_permanent(self):
        calls = []
        def invalid_tls(req, timeout):
            calls.append(1)
            raise urllib.error.URLError(ssl.SSLCertVerificationError("certificate"))
        with self.assertRaises(urllib.error.URLError):
            guarded_urlopen(REQUEST, open_impl=invalid_tls,
                            sleep_impl=lambda _: self.fail("no retry"))
        self.assertEqual(len(calls), 1)

    def test_redirect_or_non200_never_certifies(self):
        for response in (FakeResponse("https://elsewhere.test/"),
                         FakeResponse("https://na-ryby.eu/wrong/"),
                         FakeResponse(status=204)):
            with self.assertRaises(ValueError):
                guarded_urlopen(REQUEST, open_impl=lambda _req, timeout: response,
                                sleep_impl=lambda _: self.fail("no retry"))
            self.assertTrue(response.closed)

    def test_reject_wrong_origin_post_or_missing_request(self):
        for req in (None,
                    urllib.request.Request("https://other.example/"),
                    urllib.request.Request("http://na-ryby.eu/"),
                    urllib.request.Request("https://na-ryby.eu:8443/"),
                    urllib.request.Request(URL, method="POST")):
            with self.assertRaises(ValueError):
                guarded_urlopen(req, open_impl=lambda *_args, **_kw:
                                self.fail("network should not be used"))

    def test_bounds_fail_closed(self):
        for n in (0, 4, True, -1):
            with self.assertRaises(ValueError):
                guarded_urlopen(REQUEST, attempts=n, open_impl=lambda *_args, **_kw:
                                self.fail("network should not be used"))
        for t in (0, -1, 999, "30"):
            with self.assertRaises(ValueError):
                guarded_urlopen(REQUEST, timeout=t, open_impl=lambda *_args, **_kw:
                                self.fail("network should not be used"))


if __name__ == "__main__":
    unittest.main()
