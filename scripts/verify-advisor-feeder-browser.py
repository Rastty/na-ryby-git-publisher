#!/usr/bin/env python3
"""Anonymous real-browser check of feeder advisor handoff on public WordPress.

Only approved same-origin static GET requests are allowed. The test never
clicks outbound guides or merchant links, never writes to WordPress and cannot
send GA4/CJ events; it uses real browser form interaction only.
"""
import json
import sys
from audit_live_advisor_browser import PUBLIC_URL, request_policy

FEEDER = "https://na-ryby.eu/nejlepsi-feederove-navijaky-srovnani/"
GENERAL = "https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/"
CARP = "https://na-ryby.eu/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/"

def inspect_links(panel):
    return panel.locator(".nry-result__links a[href]").evaluate_all(
        """nodes => nodes.map(a => ({href:a.href, text:a.textContent.trim(),
           unsafe:!!(a.ping || a.download || a.getAttribute('onclick') ||
                     a.getAttribute('target') || a.getAttribute('data-affiliate'))}))"""
    )

def validate(feeder, carp, hidden_on_change, cards, unsafe_count):
    assert hidden_on_change is True, "CHANGED_FORM_MUST_HIDE_STALE_RESULT"
    assert len(feeder) == 2, "FEEDER_MUST_HAVE_TWO_EXACT_GUIDES"
    assert [a.get("href") for a in feeder] == [FEEDER, GENERAL], "FEEDER_GUIDE_ORDER_OR_URL_DRIFT"
    assert [a.get("text") for a in feeder] == [
        "Feederové navijáky: praktické srovnání →",
        "Obecný průvodce výběrem navijáku →"
    ], "FEEDER_TEXT_DRIFT"
    assert len(carp) == 2 and [a.get("href") for a in carp] == [
        CARP, GENERAL
    ], "OTHER_METHOD_GUIDES_CHANGED"
    assert cards == 0, "EXPIRED_MERCHANT_OFFERS_EXPOSED"
    assert unsafe_count == 0 and all(not a.get("unsafe") for a in feeder+carp), "UNAPPROVED_LINK_OR_ACTION"
    return {"state": "PUBLIC_FEEDER_BUYER_ROUTE_BROWSER_VERIFIED",
            "feeder_guides": 2, "carp_guides": 2,
            "expired_product_cards": 0, "merchant_clicks": 0,
            "analytics_delivery_proven": False, "wordpress_writes": 0}

def main():
    from playwright.sync_api import sync_playwright
    counters = {"third_party":0, "non_get":0, "dynamic_get":0}
    with sync_playwright() as playwright:
        browser = playwright.chromium.launch(headless=True)
        try:
            context = browser.new_context(locale="cs-CZ", service_workers="block")
            page = context.new_page()
            def guard(route):
                policy = request_policy(route.request.url, route.request.method)
                if policy == "allow":
                    route.continue_()
                else:
                    counters[policy] = counters.get(policy, 0) + 1
                    route.abort()
            page.route("**/*", guard)
            response = page.goto(PUBLIC_URL, wait_until="domcontentloaded", timeout=30000)
            assert response and response.status == 200 and page.url == PUBLIC_URL, "ADVISOR_PAGE_UNAVAILABLE"
            page.wait_for_function("window.__nryAdvisorGuideClickV1 === true", timeout=15000)
            # All network destinations beyond the verified document/assets
            # remain blocked. Suppress local telemetry before visitor-like QA.
            page.evaluate("""() => {
                window.gtag = () => {};
                window.dataLayer = [];
            }""")
            selector = ".nry-setup form select[name='style']"
            submit = ".nry-setup form button[type=submit]"
            panel = page.locator(".nry-setup [data-nry-results]").first
            page.locator(selector).select_option("feeder")
            page.locator(submit).first.click(timeout=12000)
            assert panel.is_visible(), "FEEDER_RESULT_NOT_VISIBLE"
            feeder = inspect_links(panel)
            cards = panel.locator(".nry-offer, .nry-offer__cta").count()
            page.locator(selector).select_option("kapr")
            change_state = page.evaluate("""() => {
                const panel = document.querySelector('.nry-setup [data-nry-results]');
                const form = document.querySelector('.nry-setup form');
                return {
                  style_is_carp: form?.elements?.style?.value === 'kapr',
                  panel_hidden_property: panel?.hidden === true,
                  panel_hidden_attribute: panel?.hasAttribute('hidden') === true,
                  panel_link_count: panel?.querySelectorAll('a[href]').length ?? -1
                };
            }""")
            stale_hidden = panel.is_hidden() or panel.get_attribute("hidden") is not None
            print(json.dumps({
                "phase":"form_change_diagnostics",
                "feeder_routes_match_before_change": [a.get("href") for a in feeder] == [FEEDER, GENERAL],
                **change_state,
                "playwright_is_hidden": panel.is_hidden(),
            }, sort_keys=True))
            page.locator(submit).first.click(timeout=12000)
            assert panel.is_visible(), "CARP_RESULT_NOT_VISIBLE"
            carp = inspect_links(panel)
            cards += panel.locator(".nry-offer, .nry-offer__cta").count()
            unsafe_count = panel.locator("a[ping], a[download], a[onclick], a[target], a[data-affiliate]").count()
            report = validate(feeder, carp, stale_hidden, cards, unsafe_count)
            report["blocked_dependency_counts"] = counters
            print(json.dumps(report, sort_keys=True, ensure_ascii=False))
            context.close()
        finally:
            browser.close()

if __name__ == "__main__":
    if "--selftest" in sys.argv:
        a = [{"href":FEEDER, "text":"Feederové navijáky: praktické srovnání →", "unsafe":False},
             {"href":GENERAL, "text":"Obecný průvodce výběrem navijáku →", "unsafe":False}]
        b = [{"href":CARP, "text":"Jak vybrat délku a akci kaprového prutu →", "unsafe":False},
             {"href":GENERAL, "text":"Jak vybrat rybářský naviják →", "unsafe":False}]
        assert validate(a,b,True,0,0)["state"] == "PUBLIC_FEEDER_BUYER_ROUTE_BROWSER_VERIFIED"
        for broken in (
            (a[::-1],b,True,0,0),(a[:1],b,True,0,0),(a,b,False,0,0),
            (a,b,True,1,0),(a,b,True,0,1),
            ([dict(a[0],unsafe=True),a[1]],b,True,0,0)
        ):
            try: validate(*broken)
            except AssertionError: pass
            else: raise AssertionError("FALSE_GREEN_FEEDER_CONTRACT")
        print("PASS: strict feeder browser readback contract")
    else:
        main()
