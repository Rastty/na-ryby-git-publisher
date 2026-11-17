"""Read-only QA for a source-bound, unpublished fishing energy buyer guide."""

import hashlib
from html.parser import HTMLParser
import json
from pathlib import Path
import unittest
from urllib.parse import urlparse


ROOT = Path(__file__).resolve().parents[1]
META = ROOT / "editorial/solar-power-guide-20261117.json"


class InspectHTML(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.tags = []
        self.links = []
        self.markers = []

    def handle_starttag(self, tag, attrs):
        attr = dict(attrs)
        self.tags.append(tag)
        if tag == "article":
            self.markers.append(attr.get("data-nry-editorial-candidate"))
        if tag == "a":
            self.links.append((attr.get("href"), attr.get("rel", "")))


class SolarBuyerEditorialTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.meta = json.loads(META.read_text(encoding="utf-8"))
        cls.body = (ROOT / cls.meta["content_file"]).read_bytes()
        cls.text = cls.body.decode("utf-8")
        cls.html = InspectHTML()
        cls.html.feed(cls.text)

    def test_source_bound_nonpublic_candidate(self):
        self.assertEqual(self.meta["status"], "editorial_candidate_not_published")
        self.assertEqual(self.meta["site"], "https://na-ryby.eu")
        self.assertEqual(hashlib.sha256(self.body).hexdigest(), self.meta["content_sha256"])
        self.assertEqual(self.html.markers, [self.meta["candidate_id"]])
        self.assertIn("authenticated", self.meta["publication_contract"])
        self.assertEqual(self.meta["affiliate_placements"], 0)

    def test_no_unreviewed_merchant_redirect_or_unsafe_markup(self):
        for tag in ("script", "iframe", "form", "style", "img", "object", "embed"):
            self.assertNotIn(tag, self.html.tags)
        self.assertEqual(len(self.html.links), 4)
        approved_sources = set(self.meta["manufacturer_sources"])
        reviewed_internal = {
            "https://na-ryby.eu/co-si-vzit-k-vode-poprve-uplny-checklist-pro-zacatecnika/",
            "https://na-ryby.eu/jak-vybrat-zavazeci-lodku/",
        }
        self.assertEqual({url for url, _ in self.html.links}, approved_sources | reviewed_internal)
        for url, _ in self.html.links:
            parsed = urlparse(url)
            self.assertEqual(parsed.scheme, "https")
            self.assertFalse(parsed.username or parsed.password or parsed.port)
            self.assertIn(parsed.hostname, ("www.foxint.com", "na-ryby.eu"))
        self.assertNotIn("go.dognet.com", self.text)
        self.assertNotIn("kqzyfj.com", self.text)

    def test_examples_and_solar_caveat_are_verifiable(self):
        self.assertEqual(20 + 3 * 4, 32)
        self.assertEqual(round(32 * 1.25), 40)
        self.assertEqual(2 * 20 + 3 * 8 + 2 * 30, 124)
        self.assertEqual(round(124 * 1.25), 155)
        for result in ("32 Wh", "40 Wh", "124 Wh", "155 Wh"):
            self.assertIn(result, self.text)
        for term in ("Wireless", "MPPT", "80 W", "napětí", "polaritu", "starší"):
            self.assertIn(term, self.text)
        self.assertIn("https://www.mrk.cz/diskuse.php?id=780171", self.meta["demand_evidence"])


if __name__ == "__main__":
    unittest.main()
