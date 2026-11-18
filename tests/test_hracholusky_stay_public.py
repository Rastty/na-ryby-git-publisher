#!/usr/bin/env python3
"""Offline proof that the public monitor fails closed on replaced or stale content."""

import importlib.util
from pathlib import Path
import unittest


source = Path(__file__).resolve().parents[1] / "scripts/verify-hracholusky-stay-trust-public.py"
spec = importlib.util.spec_from_file_location("hrach_public", source)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class PublicTests(unittest.TestCase):
    def setUp(self):
        self.html = (
            '<div>Bungalov U přehrady Hracholusky</div>'
            '<section class="nry-hracholusky-stay-trust" '+module.MARKER+'>'
            '<h2>Pobyt</h2><p>Pension U Vlčků</p><p>Současnou cenu jsme nepotvrdili.</p>'
            '<p>Nabídka nezaručuje povolenku k rybolovu.</p>'
            '<a href="'+module.STAYS+'">Pobyty</a></section>'
        )

    def test_safe_public_document(self):
        self.assertTrue(module.verify(self.html))

    def test_missing_marker_and_unsafe_old_cta_fail(self):
        self.assertFalse(module.verify(self.html.replace(module.MARKER, "")))
        self.assertFalse(module.verify(self.html+self.html))
        self.assertFalse(module.verify(self.html+'<div class="agoda-booking-box"></div>'))
        self.assertFalse(module.verify(self.html+'Garantovaná nejnižší cena'))

    def test_no_internal_route_or_disclaimer_fails(self):
        self.assertFalse(module.verify(self.html.replace(module.STAYS, "https://other.example")))
        self.assertFalse(module.verify(self.html.replace("nezaručuje povolenku", "")))


if __name__ == "__main__":
    unittest.main()
