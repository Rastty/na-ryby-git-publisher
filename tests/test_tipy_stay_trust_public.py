"""No networking in tests; public script uses a separate GET-only run."""
import importlib.util
import pathlib
import unittest

SCRIPT = pathlib.Path(__file__).parents[1] / "scripts/verify-tipy-stay-trust-public.py"
spec = importlib.util.spec_from_file_location("tipy_stay_public", SCRIPT)
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)


class TipyStayPublicTest(unittest.TestCase):
    def setUp(self):
        self.valid = (
            '<section class="nry-tipy-stay-trust" ' + mod.MARKER + '>'
            '<p>Pension U Kohoutů, Pension U Vlčků a Pension Janata, partnerské nabídky</p>'
            '<a href="' + mod.STAYS + '">Konkrétní pobyty</a></section>'
            '<h3>Vranovská přehrada &#8211; Jihomoravský kraj</h3>'
            '<p>rybářský lístek a příslušná povolenka</p>'
            '<p>Bez přesného názvu nelze doporučit lokalitu.</p>'
            '<a href="https://www.lipno.info/">Lipno</a>'
        )

    def test_expected_readback(self):
        self.assertTrue(mod.verify(self.valid))

    def test_marker_or_destination_missing(self):
        self.assertFalse(mod.verify(self.valid.replace(mod.MARKER, "")))
        self.assertFalse(mod.verify(self.valid.replace(mod.STAYS, "https://bad.example/")))

    def test_old_dangerous_claims_fail(self):
        for injected in (
            'Moravské rybářské jezero, které leží',
            'Rybaření na pobřeží s ubytováním',
            'Náš tým je sám vášnivým rybářem',
        ):
            self.assertFalse(mod.verify(self.valid + injected))

    def test_partial_repairs_fail(self):
        self.assertFalse(mod.verify(self.valid.replace('rybářský lístek', 'rybářská licence')))
        self.assertFalse(mod.verify(self.valid.replace('Jihomoravský kraj', 'Zlínský kraj')))
        self.assertFalse(mod.verify(self.valid + self.valid))


if __name__ == "__main__":
    unittest.main()
