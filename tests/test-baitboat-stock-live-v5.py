#!/usr/bin/env python3
"""Offline contract: independent V5 public readback is safe and fail-closed."""
import hashlib
import importlib.util
import unittest
from pathlib import Path

script = Path(__file__).resolve().parents[1] / 'scripts/verify-baitboat-stock-live-v5.py'
spec = importlib.util.spec_from_file_location('live_v5', script)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


def fixture(version='v5-stock-guard'):
    links = ''.join(
        '<a href="https://www.anrdoezrs.net/click-8287231-15734253?url='
        'https%3A%2F%2Fwww.chytapust.cz%2FZavazeci-lodky-2165%2F'
        'Boatman-Zavazeci-lodicka-Actor-PRO-s-GPS-Echolotem-10Ah-baterie-Carbon"'
        ' rel="nofollow sponsored noopener">Ověřit dostupnost</a>'
        for _ in range(12)
    )
    v6 = version == 'v6-compromise-recovery'
    html = (f'<html data-nry-baitboat-rc="{version}"><head>'
            '<link rel="canonical" href="https://na-ryby.eu/jak-vybrat-zavazeci-lodku/">'
            '</head><body>'
            + ''.join(f'<div id="{x}"></div>' for x in
                      ['budget', 'range', 'gps', 'sonar', 'load', 'result-link', 'result-availability'])
            + '<script>unavailableProducts=new Set(["vulgps","mini","vulcan"]);'
            'products.filter(p=>!unavailableProducts.has(p.id)&&);'
            'Skladová dostupnost není ověřena živým feedem.</script>'
            + ('lowerCapacityAlternatives=products.filter(p=>!unavailableProducts.has(p.id);'
               'change.addEventListener("click",()=>{fields.load.value=' if v6 else '')
            + 'Hledat dostupnou alternativu ↗' * 3
            + '<a href="https://na-ryby.eu/">Domů</a>' + links + '</body></html>')
    body = html.encode()
    headers = {'content-type': 'text/html; charset=UTF-8', 'cache-control': 'no-store',
               (module.V6_HEADER if v6 else module.HEADER).lower(): hashlib.sha256(body).hexdigest()}
    return body, headers


class LiveV5Tests(unittest.TestCase):
    def test_good_public_body(self):
        body, headers = fixture()
        evidence = module.verify(body, headers)
        self.assertEqual(evidence['excluded_known_unavailable_models'], 3)
        self.assertEqual(evidence['tracked_external_anchors'], 12)

    def test_v6_approved_body(self):
        body, headers = fixture('v6-compromise-recovery')
        evidence = module.verify(body, headers)
        self.assertEqual(evidence['observed_version'], 'v6-compromise-recovery')
        self.assertEqual(evidence['excluded_known_unavailable_models'], 3)

    def test_v6_without_consent_action_fails(self):
        body, _ = fixture('v6-compromise-recovery')
        body = body.replace(b'change.addEventListener("click"', b'change.addEventListener("change"')
        headers = {'content-type': 'text/html', 'cache-control': 'no-store',
                   module.V6_HEADER.lower(): hashlib.sha256(body).hexdigest()}
        with self.assertRaisesRegex(ValueError, 'V6_EXPLICIT_VISITOR_CHOICE_MISSING'):
            module.verify(body, headers)

    def test_v4_only_cannot_pass(self):
        body, headers = fixture('v4-cj')
        with self.assertRaisesRegex(ValueError, 'V5_OR_V6_NOT_LIVE'):
            module.verify(body, headers)

    def test_sha_must_match_public_body(self):
        body, headers = fixture()
        headers[module.HEADER.lower()] = 'bad'
        with self.assertRaisesRegex(ValueError, 'SHA256'):
            module.verify(body, headers)

    def test_known_unavailable_destination_fails(self):
        body, _ = fixture()
        body = body.replace(b'</body>', b'Boatman-Zavazeci-lodicka-Vulcan-Pro-s-GPS-a-Echolotem-Cerna</body>')
        headers = {'content-type': 'text/html', 'cache-control': 'no-store',
                   module.HEADER.lower(): hashlib.sha256(body).hexdigest()}
        with self.assertRaisesRegex(ValueError, 'UNAVAILABLE_PRODUCT_DESTINATION'):
            module.verify(body, headers)

    def test_untracked_link_fails(self):
        body, _ = fixture()
        body = body.replace(b'https://www.anrdoezrs.net/click-8287231-15734253?url=',
                            b'https://www.chytapust.cz/Zavazeci-lodky-2165/')
        headers = {'content-type': 'text/html', 'cache-control': 'no-store',
                   module.HEADER.lower(): hashlib.sha256(body).hexdigest()}
        with self.assertRaisesRegex(ValueError, 'DIRECT_OR_UNEXPECTED'):
            module.verify(body, headers)


if __name__ == '__main__':
    unittest.main()
