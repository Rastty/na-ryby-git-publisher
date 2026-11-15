#!/usr/bin/env python3
"""Fixture-level public verifier safety tests: zero network requests."""
import importlib.util
import unittest
from pathlib import Path

source = Path(__file__).resolve().parents[1] / 'scripts/verify-chair-hub-historical-ratings-60341-public.py'
spec = importlib.util.spec_from_file_location('chair_hub_verifier', source)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


def fixture():
    return ('<html><head><link rel="canonical" href="' + module.URL + '"></head><body>'
            + module.MARKER
            + '<section data-nry-editorial-edit="chair-hub-trust-60341-v1">'
            + ''.join('<h2>' + h + '</h2>' for h in module.HEADINGS)
            + 'Historická zmínka bez ověřeného bodového hodnocení' * 3
            + '<a href="https://na-ryby.eu/kresla">Nabídka</a>' * 4
            + '</section></body></html>').encode('utf-8')


class PublicTests(unittest.TestCase):
    def test_verifies_expected_live_contract(self):
        proof = module.verify(fixture())
        self.assertEqual(proof['product_comparison_headings_repaired'], 3)
        self.assertEqual(proof['original_chair_link_routes_observed'], 4)

    def test_fails_missing_marker(self):
        with self.assertRaisesRegex(ValueError, 'NOT_DEPLOYED'):
            module.verify(fixture().replace(module.MARKER.encode(), b''))

    def test_fails_unreviewed_star_return(self):
        with self.assertRaisesRegex(ValueError, 'KEYWORD_LIST_OR_STARS'):
            module.verify(fixture() + '⭐⭐⭐⭐'.encode())

    def test_fails_lost_existing_buyer_links(self):
        with self.assertRaisesRegex(ValueError, 'BUYER_LINKS_LOST'):
            module.verify(fixture().replace(b'https://na-ryby.eu/kresla', b'/'))


if __name__ == '__main__':
    unittest.main()
