#!/usr/bin/env python3
"""Offline negative fixtures for the public Vranov two-stays readback."""
from pathlib import Path
import runpy
import unittest

VERIFY = runpy.run_path(str(Path(__file__).resolve().parents[1] / 'scripts' / 'verify-stays-vranov-public-59891.py'))['verify']
CYCLO = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/'
WELLNESS = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/'
MARKER = 'data-nry-vranov-stays-59891="v1"'
A = '<a href="' + CYCLO + '">Cyklochata</a>'
B = '<a href="' + WELLNESS + '">Wellness</a>'


def fixture(inside=A+B):
    return (
        '<header><nav>' + A+B + '</nav><nav>' + A+B + '</nav>'
        '<nav>' + A+B + '</nav></header>'
        '<main><h4>Statek Jedraž – Jižní Čechy</h4>'
        '<a href="https://www.slevomat.cz/akce/2462030-priklad">Pobyt</a>'
        '<nav ' + MARKER + '><h3>Vranov: dvě chaty</h3>' + inside + '</nav>'
        '<h3>Další možnosti ubytování v rybářských lokalitách</h3></main>'
    )


class StayBlockContract(unittest.TestCase):
    def test_sitewide_header_duplicates_are_not_duplicate_ctas(self):
        VERIFY(fixture())

    def test_no_sitewide_header_also_passes(self):
        VERIFY(fixture().replace('<header><nav>' + A+B + '</nav><nav>' + A+B + '</nav><nav>' + A+B + '</nav></header>', ''))

    def test_rejects_missing_extra_reordered_or_duplicate_panel_link(self):
        for inside in (A, B, B+A, A+A+B, A+B+B, A+B+'<a href="/other/">Elsewhere</a>'):
            with self.subTest(inside=inside), self.assertRaises(ValueError):
                VERIFY(fixture(inside))

    def test_rejects_missing_or_duplicate_marker_and_non_nav_marker(self):
        bad = (fixture().replace(MARKER, ''),
               fixture().replace('<nav ' + MARKER, '<nav ' + MARKER + ' ' + MARKER),
               fixture().replace('<nav ' + MARKER, '<div ' + MARKER).replace('</nav><h3>Další', '</div><h3>Další'))
        for html in bad:
            with self.subTest(case=html[-100:]), self.assertRaises(ValueError):
                VERIFY(html)

    def test_rejects_missing_editorial_anchor_or_original_offer_and_wrong_position(self):
        bad = (fixture().replace('Statek Jedraž', 'Statek archiv'),
               fixture().replace('Další možnosti ubytování', 'Další nabídky'),
               fixture().replace('www.slevomat.cz/akce/2462030-', 'www.slevomat.cz/akce/999-'),
               fixture().replace('<h4>Statek Jedraž', '<h4>Statek Jedraž').replace(
                   '<nav ' + MARKER + '><h3>Vranov', '<h3>Další možnosti ubytování v rybářských lokalitách</h3><nav ' + MARKER + '><h3>Vranov'))
        for html in bad:
            with self.subTest(case=html[-100:]), self.assertRaises(ValueError):
                VERIFY(html)


if __name__ == '__main__':
    unittest.main()
