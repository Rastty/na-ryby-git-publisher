#!/usr/bin/env python3
"""Offline guard against unsafe network access and misrouted CJ destinations."""
import importlib.util
import unittest
from pathlib import Path
from urllib.parse import quote

path = Path(__file__).resolve().parents[1] / 'scripts/verify-baitboat-visitor-journey-v1.py'
spec = importlib.util.spec_from_file_location('visitor_journey', path)
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)


def cj(dest):
    return 'https://www.anrdoezrs.net/click-8287231-15734253?url=' + quote(dest, safe='')


class Contract(unittest.TestCase):
    def test_only_exact_own_page_get_passes(self):
        self.assertTrue(mod.check_read_request(mod.PAGE, 'GET'))
        self.assertFalse(mod.check_read_request(mod.PAGE, 'POST'))
        self.assertFalse(mod.check_read_request(mod.PAGE + '?x=y', 'GET'))
        self.assertFalse(mod.check_read_request('https://na-ryby.eu/wp-admin/', 'GET'))
        self.assertFalse(mod.check_read_request('https://www.anrdoezrs.net/', 'GET'))
        self.assertFalse(mod.check_read_request('https://i00.eu/img/photo.jpg', 'GET'))
        self.assertFalse(mod.check_read_request('http://na-ryby.eu/jak-vybrat-zavazeci-lodku/', 'GET'))

    def test_owned_affiliate_product(self):
        product = 'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Actor-Basic'
        self.assertIn('Actor-Basic', mod.check_cj(cj(product), 'Actor-Basic'))

    def test_unavailable_must_be_category(self):
        self.assertEqual(mod.check_cj(cj('https://www.chytapust.cz' + mod.CATEGORY),
                                      mod.CATEGORY), mod.CATEGORY)
        with self.assertRaisesRegex(ValueError, 'UNAVAILABLE_PRODUCT'):
            mod.check_cj(cj('https://www.chytapust.cz' + mod.CATEGORY + 'product'),
                         mod.CATEGORY)

    def test_fake_property_and_foreign_target_fail(self):
        link = cj('https://www.chytapust.cz' + mod.CATEGORY)
        with self.assertRaisesRegex(ValueError, 'NOT_OWNED'):
            mod.check_cj(link.replace('8287231', '12345'), mod.CATEGORY)
        with self.assertRaisesRegex(ValueError, 'INVALID_CJ_MERCHANT'):
            mod.check_cj(cj('https://foreign.example' + mod.CATEGORY), mod.CATEGORY)

    def test_no_tracking_parameter_or_old_product(self):
        link = cj('https://www.chytapust.cz' + mod.CATEGORY)
        with self.assertRaisesRegex(ValueError, 'INVALID_CJ_QUERY'):
            mod.check_cj(link + '&sid=synthetic-click', mod.CATEGORY)
        with self.assertRaisesRegex(ValueError, 'MISMATCHED_PRODUCT'):
            mod.check_cj(link, 'Boatman-Zavazeci-lodicka-Actor-Basic')


if __name__ == '__main__':
    unittest.main()
