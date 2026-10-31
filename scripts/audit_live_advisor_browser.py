#!/usr/bin/env python3
"""Read-only real-browser audit of the existing public Na-Ryby advisor.

No WordPress authentication, writes, merchant clicks or GA4 delivery. Third-party
requests and all non-GET requests are blocked. The receipt contains counts only,
never visitor answers, product URLs, cookies or analytics payloads.
"""
import argparse
import json
from datetime import datetime, timezone
from pathlib import Path
from urllib.parse import urlsplit

PUBLIC_URL = "https://na-ryby.eu/poradce-vybavy/"
EXPECTED_SCENARIOS = 108


def classify(snapshot, now):
    """Fail closed on missing runtime evidence; do not certify future prices."""
    if not isinstance(snapshot, dict):
        return "RUNTIME_UNVERIFIABLE"
    if (snapshot.get("scenario_count") != EXPECTED_SCENARIOS
            or snapshot.get("engine_errors") != 0
            or snapshot.get("form_result_visible") is not True
            or snapshot.get("engine_marker_present") is not True):
        return "RUNTIME_UNVERIFIABLE"
    try:
        expiry = datetime.fromisoformat(snapshot["price_visible_until"].replace("Z", "+00:00"))
        if expiry.tzinfo is None:
            return "RUNTIME_UNVERIFIABLE"
    except (KeyError, ValueError, AttributeError, TypeError):
        return "RUNTIME_UNVERIFIABLE"
    if now < expiry:
        # A new timestamp alone cannot establish current SKU, stock or license.
        return "FUTURE_EVIDENCE_WINDOW_REQUIRES_SOURCE_REVIEW"
    for key in ("scenarios_with_offers", "visible_cards", "visible_clickouts"):
        value = snapshot.get(key)
        if type(value) is not int or value < 0:
            return "RUNTIME_UNVERIFIABLE"
    if (snapshot["scenarios_with_offers"] > 0
            or snapshot["visible_cards"] > 0
            or snapshot["visible_clickouts"] > 0):
        return "EXPIRED_OFFERS_EXPOSED"
    return "EXPIRED_OFFERS_SUPPRESSED"


def inspect_browser():
    from playwright.sync_api import sync_playwright

    blocked = {"third_party": 0, "non_get": 0}
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(headless=True)
        try:
            context = browser.new_context(
                viewport={"width": 390, "height": 844},
                service_workers="block", accept_downloads=False,
            )
            def guard(route):
                request = route.request
                parsed = urlsplit(request.url)
                if request.method != "GET":
                    blocked["non_get"] += 1
                    route.abort()
                elif parsed.scheme != "https" or parsed.hostname != "na-ryby.eu":
                    blocked["third_party"] += 1
                    route.abort()
                else:
                    route.continue_()

            context.route("**/*", guard)
            page = context.new_page()
            response = page.goto(PUBLIC_URL, wait_until="domcontentloaded", timeout=30000)
            if response is None or response.status != 200 or page.url != PUBLIC_URL:
                raise RuntimeError("PUBLIC_PAGE_NOT_EXACT_200")
            page.locator(".nry-setup form").first.wait_for(timeout=15000)
            snapshot = page.evaluate("""() => {
              const scripts = [...document.querySelectorAll('script[data-nry-engine="nry-advisor-live-v1"]')];
              const source = scripts.length === 1 ? scripts[0].textContent : '';
              const match = source.match(/const PRICE_VISIBLE_UNTIL\s*=\s*['"]([^'"]+)['"]/);
              const engine = window.NaRybySetupEngine;
              if (!engine || !engine.OPTIONS || typeof engine.resolve !== 'function') {
                return {engine_marker_present: scripts.length === 1, engine_errors: 1};
              }
              const keys = ['style', 'water', 'travel', 'experience', 'budget'];
              let scenarioCount = 0, withOffers = 0, errors = 0;
              const visit = (index, answers) => {
                if (index === keys.length) {
                  scenarioCount++;
                  try {
                    const result = engine.resolve(answers);
                    if (!result || !Array.isArray(result.offers)) errors++;
                    else if (result.offers.length > 0) withOffers++;
                  } catch (_) { errors++; }
                  return;
                }
                const key = keys[index], values = engine.OPTIONS[key];
                if (!Array.isArray(values) || values.length < 1 || values.length > 12) {
                  errors++; return;
                }
                for (const value of values) visit(index + 1, {...answers, [key]: value});
              };
              visit(0, {});
              return {
                engine_marker_present: scripts.length === 1,
                price_visible_until: match ? match[1] : null,
                scenario_count: scenarioCount,
                scenarios_with_offers: withOffers,
                engine_errors: errors
              };
            }""")
            page.locator(".nry-setup form button[type=submit]").first.click(timeout=12000)
            panel = page.locator(".nry-setup [data-nry-results]").first
            snapshot["form_result_visible"] = panel.is_visible()
            snapshot["visible_cards"] = panel.locator(".nry-offer:visible").count()
            snapshot["visible_clickouts"] = panel.locator(".nry-offer__cta:visible").count()
            context.close()
            return snapshot, blocked
        finally:
            browser.close()


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--output", default="advisor-browser-audit-result.json")
    args = parser.parse_args()
    now = datetime.now(timezone.utc)
    report = {
        "checked_at_utc": now.isoformat(),
        "public_url": PUBLIC_URL,
        "mode": "ANONYMOUS_READ_ONLY_BROWSER",
        "wordpress_writes": 0,
        "merchant_clicks": 0,
        "ga4_delivery": "UNKNOWN_NOT_MEASURED",
        "cj_commissions": "UNKNOWN_NOT_MEASURED",
        "limitations": [
            "A browser result is not a CJ feed or GA4 receipt.",
            "Third-party requests are blocked; no merchant link is clicked.",
            "This verifies current runtime behavior, not future price or stock.",
        ],
    }
    try:
        snapshot, blocked = inspect_browser()
        report["runtime"] = snapshot
        report["blocked_requests"] = blocked
        report["state"] = classify(snapshot, now)
    except Exception as exc:
        # Do not persist page content, cookies, URL query parameters or stack traces.
        report["state"] = "RUNTIME_UNVERIFIABLE"
        report["diagnostic"] = type(exc).__name__
    Path(args.output).write_text(
        json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8"
    )
    print(json.dumps(report, ensure_ascii=False, sort_keys=True))
    return 0 if report["state"] == "EXPIRED_OFFERS_SUPPRESSED" else 1


if __name__ == "__main__":
    raise SystemExit(main())
