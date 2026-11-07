#!/usr/bin/env python3
"""Bounded, same-origin, GET-only transport retry for guarded WordPress readback.

Only transient network failures are retried. No content/hash/identity assertion
is weakened, no redirect is followed, and no POST/auth/merchant request uses
this helper. A successful transport is NOT a verified production release.
"""
import ssl
import time
import urllib.error
import urllib.request
from urllib.parse import urlsplit

_TRANSIENT_HTTP = frozenset((429, 500, 502, 503, 504))


class _NeverFollowRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


_OPENER = urllib.request.build_opener(_NeverFollowRedirect()).open


def guarded_urlopen(request, timeout=25, attempts=3, *, open_impl=None, sleep_impl=None):
    """Return an HTTP 200 response only, with transport-only bounded retries.

    Callers still validate exact source/version/hash inside their own `with`
    block. Do not retry body parsing, semantic assertions, or authorization.
    """
    if not isinstance(request, urllib.request.Request) or request.get_method() != "GET":
        raise ValueError("READONLY_GET_REQUEST_REQUIRED")
    target = urlsplit(request.full_url)
    if (target.scheme != "https" or target.hostname != "na-ryby.eu"
            or target.port is not None or target.username or target.password
            or target.fragment or not target.path.startswith("/")):
        raise ValueError("READONLY_EXACT_SITE_REQUIRED")
    if (type(attempts) is not int or not 1 <= attempts <= 3
            or type(timeout) not in (int, float) or not 0 < timeout <= 45):
        raise ValueError("READONLY_RETRY_BOUNDS_INVALID")

    opener = open_impl if open_impl is not None else _OPENER
    sleeper = sleep_impl if sleep_impl is not None else time.sleep
    for attempt in range(attempts):
        try:
            response = opener(request, timeout=timeout)
        except urllib.error.HTTPError as exc:
            # 401/403/404 and all unexpected statuses are permanent blockers.
            transient = exc.code in _TRANSIENT_HTTP
            exc.close()
            if not transient or attempt == attempts - 1:
                raise
        except (urllib.error.URLError, TimeoutError, ConnectionError) as exc:
            reason = getattr(exc, "reason", None)
            # Invalid TLS credentials must not be hidden by an automatic retry.
            if isinstance(reason, ssl.SSLCertVerificationError) or attempt == attempts - 1:
                raise
        else:
            try:
                if response.geturl() != request.full_url:
                    raise ValueError("READONLY_REDIRECT_OR_ORIGIN_DRIFT")
                if response.status != 200:
                    raise ValueError("READONLY_HTTP_NON_200")
                return response
            except Exception:
                response.close()
                raise
        # No URL, tokens, response body, merchant path or visitor information.
        print("READONLY_TRANSPORT_RETRY", attempt + 1, "of", attempts)
        sleeper(min(attempt + 1, 2))
    raise RuntimeError("READONLY_TRANSPORT_RETRY_EXHAUSTED")
