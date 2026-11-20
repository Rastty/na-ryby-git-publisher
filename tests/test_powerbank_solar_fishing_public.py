"""Offline contract for the independent, GET-only public power/solar guide audit."""

import importlib.util
from email.message import Message
from pathlib import Path
import unittest
from unittest.mock import patch


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

    def test_long_wordpress_entry_allowed_but_guide_stays_strict(self):
        class FakeResponse:
            status = 200

            def __init__(self, url, body):
                self.url = url
                self.body = body
                self.headers = Message()
                self.headers.add_header("Content-Type", "text/html; charset=utf-8")
                self.headers.add_header("X-NRY-Power-Guide-SHA256", AUDIT.SHA)

            def __enter__(self):
                return self

            def __exit__(self, *_):
                return False

            def geturl(self):
                return self.url

            def read(self, n):
                return self.body[:n]

        typical_entry = b"x" * 450_000
        with patch.object(AUDIT, "urlopen", return_value=FakeResponse(AUDIT.ENTRY, typical_entry)):
            html, _ = AUDIT.fetch(AUDIT.ENTRY, "ENTRY")
        self.assertEqual(len(html), len(typical_entry))

        with patch.object(AUDIT, "urlopen", return_value=FakeResponse(AUDIT.GUIDE, typical_entry)):
            with self.assertRaisesRegex(ValueError, "GUIDE_TOO_LARGE"):
                AUDIT.fetch(AUDIT.GUIDE, "GUIDE")

        huge_entry = b"x" * (AUDIT.MAX_ENTRY_BYTES + 1)
        with patch.object(AUDIT, "urlopen", return_value=FakeResponse(AUDIT.ENTRY, huge_entry)):
            with self.assertRaisesRegex(ValueError, "ENTRY_TOO_LARGE"):
                AUDIT.fetch(AUDIT.ENTRY, "ENTRY")

    def test_noncanonical_url_fails_closed(self):
        class Redirect:
            status = 200

            def __enter__(self):
                return self

            def __exit__(self, *_):
                return False

            def geturl(self):
                return AUDIT.GUIDE + "?changed=1"

        with patch.object(AUDIT, "urlopen", return_value=Redirect()):
            with self.assertRaisesRegex(ValueError, "GUIDE_REDIRECT_INVALID"):
                AUDIT.fetch(AUDIT.GUIDE, "GUIDE")


if __name__ == "__main__":
    unittest.main()
