#!/usr/bin/env python3
"""Independent, read-only public audit of the EXISTING Na-Ryby equipment advisor.

This checks public structure and CJ route SHAPE, not actual GA4 delivery,
redirect destinations, product availability, commissions or browser behaviour.
It never authenticates, follows redirects or writes to WordPress.
"""
import argparse
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request
from datetime import datetime, timezone
from pathlib import Path

PUBLIC_URL = "https://na-ryby.eu/poradce-vybavy/"
REST_URL = ("https://na-ryby.eu/wp-json/wp/v2/pages/119865"
            "?_fields=id,slug,status,link,modified_gmt")
MAX_HTML_BYTES = 2_000_000
MAX_REST_BYTES = 30_000
EXPECTED_EVENTS = (
    "nry_advisor_view", "nry_advisor_start", "nry_advisor_result",
    "nry_advisor_offer_view", "nry_advisor_product_click", "nry_advisor_copy",
)
CJ_HOSTS = frozenset((
    "www.kqzyfj.com", "www.tkqlhce.com", "www.jdoqocy.com",
    "www.dpbolvw.net", "www.anrdoezrs.net",
))
PRICE_EXPIRY = "const PRICE_VISIBLE_UNTIL='2026-10-21T00:00:00Z'"
MAP_PATTERN = re.compile(
    r"const AFFILIATE_LINKS\s*=\s*Object\.freeze\((\{[^\n]+\})\);"
)


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def fetch_public(url, limit):
    req = urllib.request.Request(
        url, method="GET",
        headers={"User-Agent": "NaRyby-Advisor-PublicAudit/1.0",
                 "Accept": "text/html, application/json",
                 "Cache-Control": "no-cache"},
    )
    try:
        with urllib.request.build_opener(NoRedirect()).open(req, timeout=15) as response:
            return {"status": response.status, "url": response.geturl(),
                    "content_type": response.headers.get("Content-Type", ""),
                    "body": response.read(limit + 1)}
    except urllib.error.HTTPError as exc:
        return {"status": exc.code, "url": url, "content_type": "",
                "body": b""}
    except (urllib.error.URLError, TimeoutError, OSError):
        return {"status": 0, "url": url, "content_type": "", "body": b""}


def readable(response, url, mime, limit):
    return (response.get("status") == 200 and response.get("url") == url
            and response.get("content_type", "").lower().startswith(mime)
            and len(response.get("body", b"")) <= limit)


def audit_rest(response):
    result = {"http_status": response.get("status", 0),
              "state": "READER_UNAVAILABLE", "modified_gmt": None}
    if not readable(response, REST_URL, "application/json", MAX_REST_BYTES):
        return result
    try:
        page = json.loads(response["body"].decode("utf-8"))
        if not isinstance(page, dict):
            raise ValueError("not an object")
    except (ValueError, UnicodeDecodeError):
        return result
    result["modified_gmt"] = page.get("modified_gmt")
    if (page.get("id") == 119865 and page.get("slug") == "poradce-vybavy"
            and page.get("status") == "publish"
            and page.get("link") == PUBLIC_URL):
        result["state"] = "PUBLIC_IDENTITY_PRESENT"
    else:
        result["state"] = "IDENTITY_DRIFT"
    return result


def valid_cj_route(value):
    if not isinstance(value, str):
        return False
    try:
        route = urllib.parse.urlsplit(value)
        if (route.scheme != "https" or route.hostname not in CJ_HOSTS
                or route.username or route.password or route.port
                or route.path != "/click-8287231-15734253"
                or route.fragment):
            return False
        params = urllib.parse.parse_qsl(route.query, keep_blank_values=True)
        if len(params) != 1 or params[0][0] != "url":
            return False
        merchant = urllib.parse.urlsplit(params[0][1])
        return (merchant.scheme == "https"
                and merchant.netloc == "www.chytapust.cz"
                and bool(merchant.path.strip("/"))
                and not merchant.username and not merchant.password
                and not merchant.fragment)
    except ValueError:
        return False


def audit_html(response):
    result = {
        "http_status": response.get("status", 0),
        "state": "READER_UNAVAILABLE", "marker_present": False,
        "missing_event_tokens": list(EXPECTED_EVENTS),
        "cj_route_count": None, "cj_route_shape_ok": False,
        "price_expiry_policy_present": False,
        "runtime_price_visibility": "UNKNOWN_REQUIRES_BROWSER",
        "ga4_delivery": "UNKNOWN_REQUIRES_ANALYTICS",
        "cj_redirects_and_commissions": "UNKNOWN_REQUIRES_CJ",
    }
    if not readable(response, PUBLIC_URL, "text/html", MAX_HTML_BYTES):
        return result
    html = response["body"].decode("utf-8", "replace")
    result["marker_present"] = (
        "<!-- nry-advisor-live-v1 -->" in html
        and 'data-nry-engine="nry-advisor-live-v1"' in html
    )
    result["missing_event_tokens"] = [name for name in EXPECTED_EVENTS
                                      if name not in html]
    result["price_expiry_policy_present"] = PRICE_EXPIRY in html
    match = MAP_PATTERN.search(html)
    if match:
        try:
            mapping = json.loads(match.group(1))
            if isinstance(mapping, dict):
                routes = list(mapping.values())
                result["cj_route_count"] = len(routes)
                result["cj_route_shape_ok"] = (
                    len(routes) == 14 and len(set(routes)) == 14
                    and all(valid_cj_route(route) for route in routes)
                )
        except (ValueError, TypeError):
            pass
    result["state"] = (
        "STRUCTURE_PRESENT_RUNTIME_UNVERIFIED"
        if result["marker_present"] and not result["missing_event_tokens"]
        and result["price_expiry_policy_present"] and result["cj_route_shape_ok"]
        else "PUBLIC_STRUCTURE_DRIFT"
    )
    return result


def build_report(rest_response, html_response, checked_at):
    rest, html = audit_rest(rest_response), audit_html(html_response)
    if "READER_UNAVAILABLE" in (rest["state"], html["state"]):
        state = "READER_UNAVAILABLE"
    elif rest["state"] != "PUBLIC_IDENTITY_PRESENT":
        state = "IDENTITY_DRIFT"
    elif html["state"] != "STRUCTURE_PRESENT_RUNTIME_UNVERIFIED":
        state = "PUBLIC_STRUCTURE_DRIFT"
    else:
        state = "PUBLIC_STRUCTURE_PRESENT_RUNTIME_UNVERIFIED"
    return {
        "checked_at_utc": checked_at,
        "url": PUBLIC_URL,
        "state": state,
        "wordpress_writes": 0,
        "rest": rest,
        "html": html,
        "limitations": [
            "Public HTTP structure is not a browser interaction test.",
            "Event tokens do not prove GA4/GTM receipt or consent.",
            "CJ URL shape does not prove final merchant redirect, stock or commission.",
            "Expired 2026-10-21 price snapshot needs runtime browser check.",
            "A 401/503 reader failure is not evidence that the page is unpublished.",
        ],
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", default="advisor-live-audit-result.json")
    args = parser.parse_args()
    checked_at = datetime.now(timezone.utc).isoformat()
    report = build_report(
        fetch_public(REST_URL, MAX_REST_BYTES),
        fetch_public(PUBLIC_URL, MAX_HTML_BYTES),
        checked_at,
    )
    Path(args.output).write_text(
        json.dumps(report, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    return 0 if report["state"] == "PUBLIC_STRUCTURE_PRESENT_RUNTIME_UNVERIFIED" else 1


if __name__ == "__main__":
    sys.exit(main())
