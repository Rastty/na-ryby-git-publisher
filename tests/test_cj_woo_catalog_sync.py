#!/usr/bin/env python3
"""Offline safety contract for CJ -> WooCommerce catalog pilot."""
import importlib.util
import io
import pathlib
import unittest
import zipfile

PATH = pathlib.Path(__file__).resolve().parents[1] / "scripts/cj_woo_catalog_sync.py"
spec = importlib.util.spec_from_file_location("cj_sync", PATH)
sync = importlib.util.module_from_spec(spec)
spec.loader.exec_module(sync)

VALID = "https://www.dpbolvw.net/click-8287231-12347575?url=https%3A%2F%2Fwww.chytapust.cz%2Fprodukt"
ROW = {"id": "12345", "title": "Boatman Actor Plus zavážecí loďka",
       "buyurl": VALID, "price": "19 990,00 CZK",
       "currency": "CZK", "availability": "in stock"}


class PilotTests(unittest.TestCase):
    def test_site_property_guard(self):
        self.assertTrue(sync.affiliate_ok(VALID))
        self.assertFalse(sync.affiliate_ok(VALID.replace("8287231", "100927011")))
        self.assertFalse(sync.affiliate_ok(VALID.replace("www.dpbolvw.net", "evil.example")))
        self.assertFalse(sync.affiliate_ok("https://www.chytapust.cz/produkt"))
        self.assertFalse(sync.affiliate_ok("http://www.dpbolvw.net/click-8287231-12347575"))

    def test_product_normalization(self):
        data, reason = sync.normalize(ROW)
        self.assertIsNone(reason)
        self.assertEqual(data["sku"], "nry-cj-12345")
        self.assertEqual(data["regular_price"], "19990.00")
        self.assertEqual(data["external_url"], VALID)
        for field, value in [("currency", "EUR"), ("availability", "out_of_stock"),
                             ("buyurl", "https://www.chytapust.cz"), ("price", "")]:
            broken = {**ROW, field: value}
            self.assertIsNotNone(sync.normalize(broken)[1])

    def test_existing_legacy_never_overwritten(self):
        existing = [{"id": 7, "sku": "nry-cj-12345", "type": "external",
                     "name": ROW["title"], "status": "publish", "meta_data": []}]
        changes, report = sync.decide([ROW], existing)
        self.assertEqual(changes, [])
        self.assertEqual(report["blocked_existing"], 1)

    def test_title_duplicate_caught_even_with_other_sku(self):
        existing = [{"id": 7, "sku": "old-importer-id", "type": "external",
                     "name": ROW["title"], "meta_data": []}]
        changes, report = sync.decide([ROW], existing)
        self.assertEqual(changes, [])
        self.assertEqual(report["blocked_existing"], 1)

    def test_bounded_create_and_duplicate_feed(self):
        changes, report = sync.decide([ROW, ROW], [])
        self.assertEqual([x[0] for x in changes], ["create"])
        self.assertEqual(report["duplicate_feed"], 1)

    def test_even_owned_published_product_never_updated(self):
        existing = [{"id": 77, "sku": "nry-cj-12345", "type": "external",
                     "name": ROW["title"], "status": "publish",
                     "catalog_visibility": "visible", "meta_data": [
                        {"key": "_nry_cj_source", "value": sync.SOURCE}]}]
        changes, report = sync.decide([ROW], existing)
        self.assertEqual(changes, [])
        self.assertEqual(report["blocked_existing"], 1)
        self.assertEqual(report["update_managed"], 0)
        data, _ = sync.normalize(ROW)
        calls = []
        old = sync.wc_request
        try:
            sync.wc_request = lambda path, method, payload: calls.append((path, method, payload))
            with self.assertRaisesRegex(ValueError, "pilot_forbids_existing_product_writes"):
                sync.apply_changes([("update", 77, data)])
        finally:
            sync.wc_request = old
        self.assertEqual(calls, [])

    def test_even_owned_hidden_draft_not_mutated_in_pilot(self):
        existing = [{"id": 78, "sku": "nry-cj-12345", "type": "external",
                     "name": ROW["title"], "status": "draft",
                     "catalog_visibility": "hidden", "meta_data": [
                        {"key": "_nry_cj_source", "value": sync.SOURCE}]}]
        changes, report = sync.decide([ROW], existing)
        self.assertEqual(changes, [])
        self.assertEqual(report["blocked_existing"], 1)

    def test_unverified_currency_rejected_without_guessing(self):
        explicit = dict(ROW)
        explicit.pop("currency")
        explicit["price"] = "19990"
        self.assertEqual(sync.normalize(explicit)[1], "unverified_currency")
        explicit["price"] = "19990 CZK"
        self.assertEqual(sync.normalize(explicit)[0]["regular_price"], "19990.00")
        explicit["price"] = "19990 EUR"
        self.assertIsNotNone(sync.normalize(explicit)[1])

    def test_new_product_is_hidden_draft(self):
        changes, _ = sync.decide([ROW], [])
        calls = []
        old = sync.wc_request
        try:
            sync.wc_request = lambda path, method, payload: calls.append((path, method, payload))
            sync.apply_changes(changes)
        finally:
            sync.wc_request = old
        self.assertEqual(calls[0][1], "POST")
        self.assertEqual(calls[0][2]["type"], "external")
        self.assertEqual(calls[0][2]["status"], "draft")
        self.assertEqual(calls[0][2]["catalog_visibility"], "hidden")

    def test_zip_csv_read(self):
        buf = io.BytesIO()
        with zipfile.ZipFile(buf, "w") as z:
            z.writestr("products.csv", "id,title,buyurl,price,currency,availability\n"
                                      "12345,Boatman Actor Plus zavážecí loďka," + VALID +
                                      ",19990,CZK,in stock\n")
        rows = sync.unpack(buf.getvalue())
        self.assertEqual(len(rows), 1)
        self.assertEqual(sync.normalize(rows[0])[0]["sku"], "nry-cj-12345")

    def test_google_rss_xml_read(self):
        xml = (b'<rss xmlns:g="http://base.google.com/ns/1.0"><channel><item>'
               b'<g:id>12345</g:id><title>Boatman Actor Plus lodicka</title>'
               b'<g:price>19990 CZK</g:price><g:availability>in stock</g:availability>'
               b'<link>' + VALID.replace("&", "&amp;").encode() +
               b'</link></item></channel></rss>')
        rows = sync.unpack(xml)
        self.assertEqual(len(rows), 1)
        self.assertEqual(sync.normalize(rows[0])[0]["sku"], "nry-cj-12345")

    def test_missing_affiliate_link_is_not_imported(self):
        row = {**ROW, "buyurl": ""}
        changes, report = sync.decide([row], [])
        self.assertFalse(changes)
        self.assertEqual(report["rejected"]["missing_verified_site_pid_click_url"], 1)


if __name__ == "__main__":
    unittest.main()
