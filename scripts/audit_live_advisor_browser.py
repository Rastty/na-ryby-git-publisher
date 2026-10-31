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
            or snapshot.get("visible_guidance") is not True
            or snapshot.get("scenarios_with_guidance") != EXPECTED_SCENARIOS
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
    for key in ("scenarios_with_offers", "visible_cards", "visible_clickouts",
                "visible_external_clickouts", "rendered_cards",
                "rendered_external_clickouts"):
        value = snapshot.get(key)
        if type(value) is not int or value < 0:
            return "RUNTIME_UNVERIFIABLE"
    # Engine-only enumeration cannot certify the rendered visitor experience.
    # All 108 form submissions must also produce useful, offer-free DOM output.
    for key in ("rendered_scenario_count", "rendered_scenarios_with_guidance",
                "rendered_scenarios_matching_engine", "rendered_scenarios_matching_guides",
                "rendered_scenarios_with_unapproved_links", "rendered_scenarios_with_cards",
                "rendered_scenarios_with_external_clickouts",
                "rendered_scenario_errors"):
        value = snapshot.get(key)
        if type(value) is not int or value < 0:
            return "RUNTIME_UNVERIFIABLE"
    if (snapshot["rendered_scenario_count"] != EXPECTED_SCENARIOS
            or snapshot["rendered_scenarios_with_guidance"] != EXPECTED_SCENARIOS
            or snapshot["rendered_scenarios_matching_engine"] != EXPECTED_SCENARIOS
            or snapshot["rendered_scenarios_matching_guides"] != EXPECTED_SCENARIOS
            or snapshot["rendered_scenario_errors"] != 0):
        return "RUNTIME_UNVERIFIABLE"
    if (snapshot["scenarios_with_offers"] > 0
            or snapshot["rendered_scenarios_with_cards"] > 0
            or snapshot["rendered_scenarios_with_external_clickouts"] > 0
            or snapshot["visible_cards"] > 0
            or snapshot["visible_clickouts"] > 0
            or snapshot["visible_external_clickouts"] > 0
            or snapshot["rendered_cards"] > 0
            or snapshot["rendered_external_clickouts"] > 0):
        return "EXPIRED_OFFERS_EXPOSED"
    if snapshot["rendered_scenarios_with_unapproved_links"] > 0:
        # Same-origin redirects can be commercial too. Only exact approved
        # supporting guides are allowed after offer evidence expires.
        return "UNAPPROVED_RESULT_LINKS"
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
              let scenarioCount = 0, withOffers = 0, withGuidance = 0, errors = 0;
              const visit = (index, answers) => {
                if (index === keys.length) {
                  scenarioCount++;
                  try {
                    const result = engine.resolve(answers);
                    if (!result || !Array.isArray(result.offers)) errors++;
                    else {
                      if (result.offers.length > 0) withOffers++;
                      if (typeof result.rod === "string" && result.rod.trim().length > 12
                          && typeof result.reel === "string" && result.reel.trim().length > 12
                          && Array.isArray(result.essentials) && result.essentials.length >= 3
                          && typeof result.guide?.url === "string"
                          && result.guide.url.startsWith("https://na-ryby.eu/")) withGuidance++;
                    }
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
                scenarios_with_guidance: withGuidance,
                engine_errors: errors
              };
            }""")
            # Suppress synthetic funnel events before the FIRST form submission.
            # Third-party/network guards still enforce no GA4 delivery.
            page.evaluate("() => { window.gtag = () => {}; }")
            page.locator(".nry-setup form button[type=submit]").first.click(timeout=12000)
            panel = page.locator(".nry-setup [data-nry-results]").first
            snapshot["form_result_visible"] = panel.is_visible()
            snapshot["visible_cards"] = panel.locator(".nry-offer:visible").count()
            # CSS hiding is not removal: stale product cards and links must not
            # remain in the result DOM for later reveal or assistive tooling.
            snapshot["rendered_cards"] = panel.locator(".nry-offer").count()
            snapshot["visible_clickouts"] = panel.locator(".nry-offer__cta:visible").count()
            # Detect external buy links even if their CSS class changes.
            # Store only a count; never persist destinations or tracking data.
            snapshot["visible_external_clickouts"] = panel.locator("a:visible").evaluate_all(
                """nodes => nodes.filter(a => {
                  try { return new URL(a.href).origin !== location.origin; }
                  catch (_) { return true; }
                }).length"""
            )
            snapshot["rendered_external_clickouts"] = panel.locator("a[href]").evaluate_all(
                """nodes => nodes.filter(a => {
                  try { return new URL(a.href).origin !== location.origin; }
                  catch (_) { return true; }
                }).length"""
            )
            snapshot["visible_guidance"] = len(panel.inner_text().strip()) > 100
            # Exercise the actual visitor renderer for every option combination,
            # not only the deterministic engine. No merchant clicks or WP writes.
            # Suppress synthetic GA4 events so this QA cannot pollute funnels.
            rendered = page.evaluate("""() => {
              window.gtag = () => {};
              const root = document.querySelector('.nry-setup');
              const form = root?.querySelector('form');
              const panel = root?.querySelector('[data-nry-results]');
              const engine = window.NaRybySetupEngine;
              const keys = ['style', 'water', 'travel', 'experience', 'budget'];
              let count = 0, guidance = 0, matching = 0, matchingGuides = 0, unexpectedLinks = 0, cards = 0, external = 0, errors = 0;
              if (!form || !panel || !engine?.OPTIONS) {
                return {rendered_scenario_count: 0, rendered_scenario_errors: 1};
              }
              // Prevent a native navigation if the UI submit handler regresses.
              form.addEventListener('submit', e => e.preventDefault(), {capture: true});
              const visit = (index, answers) => {
                if (index === keys.length) {
                  count++;
                  try {
                    for (const key of keys) {
                      const field = form.elements.namedItem(key);
                      if (!field) throw Error('MISSING_FIELD');
                      field.value = answers[key];
                      if (field.value !== answers[key]) throw Error('INVALID_OPTION');
                    }
                    const expected = engine.resolve(answers);
                    if (!expected || !Array.isArray(expected.essentials)) {
                      errors++; return;
                    }
                    form.dispatchEvent(new Event('change', {bubbles: true}));
                    form.dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
                    if (panel.hidden || !panel.querySelector('.nry-result__title')) {
                      errors++; return;
                    }
                    // A generic/static response for all 108 forms is not useful advice.
                    // Compare the visitor DOM to the engine decision for this selection.
                    // Never persist selected answers or result text.
                    const sections = [...panel.querySelectorAll('.nry-result__section')];
                    const sectionText = title => {
                      const block = sections.find(node =>
                        node.querySelector('h4')?.textContent.trim() === title);
                      return block ? [...block.querySelectorAll('p,li')]
                        .map(node => node.textContent.trim()) : null;
                    };
                    const essentials = sectionText('Nejdřív pořiďte');
                    const rod = sectionText('Doporučený prut');
                    const reel = sectionText('Doporučený naviják');
                    const matches = panel.querySelector('.nry-result__title')?.textContent.trim() === expected.title
                      && panel.querySelector('.nry-result__intro')?.textContent.trim() === expected.subtitle
                      && rod?.length === 1 && rod[0] === expected.rod
                      && reel?.length === 1 && reel[0] === expected.reel
                      && essentials?.length === expected.essentials.length
                      && essentials.every((text, index) => text === expected.essentials[index]);
                    if (matches) matching++;
                    // Supporting guides are part of the actual useful result.
                    // Check every rendered link against the exact decision,
                    // including duplicate suppression and same-origin safety.
                    const expectedGuides = [expected.guide, expected.secondGuide]
                      .filter((item, index, all) => item &&
                        all.findIndex(candidate => candidate?.url === item.url) === index);
                    const actualGuides = [...panel.querySelectorAll('.nry-result__links a[href]')];
                    const guidesMatch = expectedGuides.length > 0
                      && actualGuides.length === expectedGuides.length
                      && expectedGuides.every((item, index) => {
                        try {
                          const target = new URL(item.url);
                          return target.origin === location.origin
                            && !target.search && !target.hash
                            && actualGuides[index].href === target.href
                            && actualGuides[index].textContent.trim() === item.label + ' →';
                        } catch (_) { return false; }
                      });
                    if (guidesMatch) matchingGuides++;
                    // The result may contain a same-origin affiliate redirect
                    // outside the guide section. External-origin checks alone
                    // cannot detect it. All result links must be exact guides.
                    const allResultLinks = [...panel.querySelectorAll("a[href]")];
                    if (allResultLinks.length !== actualGuides.length
                        || allResultLinks.some(link => !actualGuides.includes(link))) unexpectedLinks++;
                    if (matches && guidesMatch && panel.textContent.trim().length > 100) guidance++;
                    if (panel.querySelector('.nry-offer, .nry-offer__cta')) cards++;
                    if ([...panel.querySelectorAll('a[href]')].some(a => {
                      try { return new URL(a.href, location.href).origin !== location.origin; }
                      catch (_) { return true; }
                    })) external++;
                  } catch (_) { errors++; }
                  return;
                }
                const key = keys[index], options = engine.OPTIONS[key];
                if (!Array.isArray(options) || !options.length || options.length > 12) {
                  errors++; return;
                }
                for (const option of options) visit(index + 1, {...answers, [key]: option});
              };
              visit(0, {});
              return {
                rendered_scenario_count: count,
                rendered_scenarios_with_guidance: guidance,
                rendered_scenarios_matching_engine: matching,
                rendered_scenarios_matching_guides: matchingGuides,
                rendered_scenarios_with_unapproved_links: unexpectedLinks,
                rendered_scenarios_with_cards: cards,
                rendered_scenarios_with_external_clickouts: external,
                rendered_scenario_errors: errors
              };
            }""")
            snapshot.update(rendered)
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
