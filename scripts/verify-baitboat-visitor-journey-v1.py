#!/usr/bin/env python3
"""Read-only real-browser proof of the live V5/V6 baitboat buyer journey.

Changes only local <select> state. It NEVER opens a CJ redirect, merchant URL,
analytics endpoint or WordPress admin endpoint. All off-site requests are blocked.
The result proves functioning recommendations, not purchases or commissions.
"""
import json
import sys
from urllib.parse import parse_qs, urlsplit


PAGE = 'https://na-ryby.eu/jak-vybrat-zavazeci-lodku/'
CJ_HOST = 'www.anrdoezrs.net'
CJ_PATH = '/click-8287231-15734253'
MERCHANT_HOST = 'www.chytapust.cz'
CATEGORY = '/Zavazeci-lodky-2165/'

# All test options are declared in the live form. None has to visit a seller.
CASES = (
    ('basic', {'budget': '10000', 'range': '150', 'sonar': 'no', 'gps': 'no', 'load': '1'},
     'Boatman Actor Basic', 'Boatman-Zavazeci-lodicka-Actor-Basic'),
    ('spark', {'budget': '35000', 'range': '150', 'sonar': 'yes', 'gps': 'yes', 'load': '1'},
     'Deeper SPARK', 'Deeper-Zavazeci-lod-SPARK'),
    ('actor_pro', {'budget': '35000', 'range': '500', 'sonar': 'yes', 'gps': 'yes', 'load': '1.5'},
     'Boatman Actor PRO', 'Boatman-Zavazeci-lodicka-Actor-PRO'),
    ('mini_unavailable', {'budget': '35000', 'range': '500', 'sonar': 'yes', 'gps': 'yes', 'load': '2'},
     'Vhodná loďka je u prodejce nedostupná', CATEGORY),
    ('vulcan_unavailable', {'budget': '35000', 'range': '500', 'sonar': 'yes', 'gps': 'yes', 'load': '2.5'},
     'Vhodná loďka je u prodejce nedostupná', CATEGORY),
    ('gps_unavailable', {'budget': '20000', 'range': '500', 'sonar': 'no', 'gps': 'yes', 'load': '2'},
     'Vhodná loďka je u prodejce nedostupná', CATEGORY),
)


def check_cj(href, expected_target_fragment):
    """Check exact owned creative and target without ever requesting it."""
    url = urlsplit(href)
    if url.scheme != 'https' or url.hostname != CJ_HOST or url.port or url.username \
            or url.password or url.path != CJ_PATH or url.fragment:
        raise ValueError('NOT_OWNED_CJ_DESTINATION')
    query = parse_qs(url.query, strict_parsing=True)
    if set(query) != {'url'} or len(query['url']) != 1:
        raise ValueError('INVALID_CJ_QUERY')
    target = urlsplit(query['url'][0])
    if target.scheme != 'https' or target.hostname != MERCHANT_HOST \
            or target.port or target.query or target.fragment or target.username or target.password:
        raise ValueError('INVALID_CJ_MERCHANT')
    if expected_target_fragment not in target.path:
        raise ValueError('MISMATCHED_PRODUCT_RECOMMENDATION')
    if expected_target_fragment == CATEGORY and target.path != CATEGORY:
        raise ValueError('UNAVAILABLE_PRODUCT_DID_NOT_FALL_BACK_TO_CATEGORY')
    return target.path


def check_read_request(url, method):
    """Exact own-page GET allowlist; block all other network destinations."""
    p = urlsplit(url)
    return method == 'GET' and p.scheme == 'https' and p.netloc == 'na-ryby.eu' \
        and p.path in ('/jak-vybrat-zavazeci-lodku/', '/jak-vybrat-zavazeci-lodku') \
        and not p.query and not p.fragment


def main():
    from playwright.sync_api import sync_playwright

    blocked = [0]
    results = []
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        context = browser.new_context(locale='cs-CZ', accept_downloads=False,
                                      service_workers='block')
        page = context.new_page()

        def on_request(route):
            req = route.request
            if check_read_request(req.url, req.method):
                route.continue_()
            else:
                blocked[0] += 1
                route.abort()

        page.route('**/*', on_request)
        response = page.goto(PAGE, wait_until='domcontentloaded', timeout=30000)
        if response is None or response.status != 200 or page.url != PAGE:
            raise ValueError('PUBLIC_200_CANONICAL_PAGE_REQUIRED')
        is_v5 = page.locator('[data-nry-baitboat-rc="v5-stock-guard"]').count() == 1
        is_v6 = page.locator('[data-nry-baitboat-rc="v6-compromise-recovery"]').count() == 1
        if is_v5 == is_v6:
            raise ValueError('LIVE_EXACT_V5_OR_V6_REQUIRED')
        page.wait_for_function('document.getElementById("result-link").hidden === true',
                               timeout=8000)
        page.locator('#budget').select_option('35000')
        if not page.locator('#result-link').is_hidden():
            raise ValueError('PREMATURE_CTA_ON_PARTIAL_ANSWERS')
        for name, values, expected_name, expected_target in CASES:
            for field, value in values.items():
                page.locator('#' + field).select_option(value)
            title = page.locator('#result-name').inner_text()
            if expected_name not in title:
                raise ValueError(name + ':UNEXPECTED_RECOMMENDATION:' + title)
            cta = page.locator('#result-link')
            if not cta.is_visible():
                raise ValueError(name + ':CTA_NOT_VISIBLE')
            href = cta.get_attribute('href') or ''
            merchant_path = check_cj(href, expected_target)
            rel = (cta.get_attribute('rel') or '').split()
            if not {'sponsored', 'nofollow', 'noopener'}.issubset(set(rel)):
                raise ValueError(name + ':SPONSORED_REL_MISSING')
            if name.endswith('unavailable') and 'nebyl u prodejce dostupný' not in \
                    page.locator('#result-description').inner_text():
                raise ValueError(name + ':AVAILABILITY_EXPLANATION_MISSING')
            results.append({'case': name, 'result': 'PASS', 'merchant_target_path': merchant_path})
        if is_v6:
            # Real selection only; no merchant click or synthetic conversion.
            for field, value in {'budget': '35000', 'range': '500', 'sonar': 'yes',
                                 'gps': 'yes', 'load': '2.5'}.items():
                page.locator('#' + field).select_option(value)
            alternative = page.locator('#result-addon-links button')
            if alternative.count() != 1 or '1,5 kg' not in alternative.inner_text():
                raise ValueError('V6_OPT_IN_ALTERNATIVE_MISSING')
            if 'Vulcan-Pro' in (page.locator('#result-link').get_attribute('href') or ''):
                raise ValueError('V6_UNAVAILABLE_MODEL_CTA_PRESENT')
            alternative.click()
            if page.locator('#load').input_value() != '1.5' or \
                    'Boatman Actor PRO' not in page.locator('#result-name').inner_text():
                raise ValueError('V6_OPT_IN_ALTERNATIVE_NOT_APPLIED')
            check_cj(page.locator('#result-link').get_attribute('href') or '',
                     'Boatman-Zavazeci-lodicka-Actor-PRO')
            results.append({'case': 'opt_in_lower_capacity', 'result': 'PASS'})
        context.close()
        browser.close()

    print(json.dumps({'state': 'LIVE_' + ('V6' if is_v6 else 'V5') + '_REAL_BROWSER_BUYER_PATH_VERIFIED',
                      'page': PAGE, 'scenarios_passed': len(results),
                      'cases': [case['case'] for case in results],
                      'external_requests_blocked': blocked[0],
                      'merchant_requests_completed': 0, 'synthetic_affiliate_clicks': 0,
                      'wordpress_writes': 0, 'live_stock_feed': False,
                      'first_party_cta_click_telemetry': 'NOT_VERIFIED',
                      'commission_attribution': 'NOT_VERIFIED'},
                     sort_keys=True, ensure_ascii=False))


if __name__ == '__main__':
    try:
        main()
    except Exception as exc:
        # Includes Playwright/browser-launch failures, which otherwise emit an
        # unstructured traceback and obscure the fail-closed readback state.
        print(json.dumps({'state': 'LIVE_V5_OR_V6_VISITOR_JOURNEY_UNVERIFIED',
                          'reason': str(exc), 'merchant_requests_completed': 0,
                          'synthetic_affiliate_clicks': 0, 'wordpress_writes': 0},
                         ensure_ascii=False, sort_keys=True))
        sys.exit(1)
