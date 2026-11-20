"""Offline contract for the independent, GET-only public power/solar guide audit."""

import importlib.util
from pathlib import Path
import unittest


ROOT = Path(__file__).resolve().parents[1]
SCRIPT = ROOT / "scripts" / "verify-powerbank-solar-fishing-public-v1.py"
SPEC = importlib.util.spec_from_file_location("nry_power_guide_public", SCRIPT)
assert SPEC and SPEC.loader
AUDIT = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(AUDIT)
GUIDE = (ROOT / "assets" / "nry-powerbank-solar-fishing-v1.html").read_text(encoding="utf-8")
ENTRY = (
    '<!doctype html><html><body>'
    '<aside data-nry-power-energy-entry="v1">'
    '<a href="https://na-ryby.eu/powerbanka-nebo-solarni-panel-na-ryby/">Přepočítat Wh</a>'
    '</aside></body></html>'
)


class PowerGuidePublicAuditContract(unittest.TestCase):
    def test_approved_asset_and_one_discoverable_entry_pass(self):
        checks = AUDIT.validate(GUIDE, ENTRY, AUDIT.SHA)
        self.assertTrue(all(checks.values()), checks)

    def test_changed_bytes_or_http_header_fail_closed(self):
        self.assertFalse(AUDIT.validate(GUIDE + " ", ENTRY, AUDIT.SHA)["html_sha_match"])
        self.assertFalse(AUDIT.validate(GUIDE, ENTRY, "bad")["header_sha_match"])

    def test_duplicate_link_or_marker_fails_closed(self):
        duplicate_link = ENTRY.replace('</aside>', '<a href="' + AUDIT.GUIDE + '">dup</a></aside>')
        self.assertFalse(AUDIT.validate(GUIDE, duplicate_link, AUDIT.SHA)["entry_discoverability"])
        duplicate_marker = ENTRY.replace('</body>', '<aside data-nry-power-energy-entry="v1"></aside></body>')
        self.assertFalse(AUDIT.validate(GUIDE, duplicate_marker, AUDIT.SHA)["entry_exact_one_marker"])

    def test_affiliate_host_and_missing_calculator_fail_closed(self):
        changed = GUIDE.replace('href="https://www.foxint.com/',
                                'href="https://www.dpbolvw.net/click-000?u=https://www.foxint.com/', 1)
        self.assertFalse(AUDIT.validate(changed, ENTRY, AUDIT.SHA)["guide_has_no_affiliate_click_host"])
        self.assertFalse(AUDIT.validate(GUIDE.replace('data-nry-whcalc="v1"', ''), ENTRY, AUDIT.SHA)["calculator"])


if __name__ == "__main__":
    unittest.main()
