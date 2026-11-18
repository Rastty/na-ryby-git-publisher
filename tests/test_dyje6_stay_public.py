"""Public verifier fixtures without any HTTP or affiliate click."""
import importlib.util
from pathlib import Path
import unittest

path = Path(__file__).parents[1] / "scripts/verify-dyje6-stay-public.py"
spec = importlib.util.spec_from_file_location("dyje6_stay_public", path)
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)


class PublicVerification(unittest.TestCase):
    def setUp(self):
        self.good = (
            '<section class="nry-dyje6-facts" ' + m.FACTS + '>'
            '<h3>Lov jen na určeném úseku jižní hráze</h3>'
            '<p>přírodní rezervace, státní rybářský lístek, Rybníkářstvím Pohořelice</p>'
            '<p>denní 350 Kč, týdenní 1 380 Kč</p>'
            '<a href="https://www.rybnikarstvipohorelice.cz/dyje-6/">Pravidla</a></section>'
            '<section class="nry-dyje6-offers" ' + m.OFFERS + '>'
            '<article data-nry-offer-id="2464086">Hotel Termal Mušov'
            '<a href="' + m.SLEVOMAT + '?utm_source=affiliate&amp;utm_medium=cpc&amp;utm_term=j3d7tsgc&amp;a_box=j3d7tsgc">Slevomat</a></article>'
            '<article data-nry-offer-id="hportal-penzion-pasohlavky">Penzion Pasohlávky'
            '<a href="' + m.HPORTAL + '">Hportal</a></article>'
            '<article data-nry-offer-id="hauzi-chalupa-dolni-vestonice">Chalupa Dolní Věstonice'
            '<a href="' + m.HAUZI + '">Hauzi</a></article>'
            '<a href="https://na-ryby.eu/booking-cesko">Další pobyty</a></section>'
        )

    def test_all_readback_conditions(self):
        self.assertTrue(m.verify(self.good))

    def test_missing_one_partner_or_source(self):
        self.assertFalse(m.verify(self.good.replace('Hotel Termal Mušov', 'Cizí hotel')))
        self.assertFalse(m.verify(self.good.replace('Rybníkářstvím Pohořelice', 'bez povolenky')))
        self.assertFalse(m.verify(self.good.replace(m.HPORTAL, 'https://evil.test/')))
        self.assertFalse(m.verify(self.good.replace(m.HAUZI, 'https://evil.test/')))

    def test_old_price_or_duplicate_fail(self):
        self.assertFalse(m.verify(self.good + 'denní povolenka: 200 korun'))
        self.assertFalse(m.verify(self.good + self.good))

    def test_production_js_proof_needs_exact_bytes_and_version(self):
        doc = ('<html><head><link rel="canonical" href="' + m.PAGE + '">'
               '<script src="https://na-ryby.eu' + m.ASSET + '?' + m.VERSION + '"></script>'
               '</head><body>' + self.good + '</body></html>')
        js = b'/* reviewed local browser asset */' + b'x' * 1300
        self.assertTrue(m.verify(doc, deployed=True, public_js=js, reviewed_js=js))
        self.assertFalse(m.verify(doc, deployed=True, public_js=js + b'unsafe', reviewed_js=js))
        self.assertFalse(m.verify(doc.replace('ver=1.11.2', 'ver=1.11.1'),
                                  deployed=True, public_js=js, reviewed_js=js))


if __name__ == "__main__":
    unittest.main()
