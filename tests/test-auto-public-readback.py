#!/usr/bin/env python3
"""Offline tests: no network operations."""
import importlib.util
from pathlib import Path
from unittest import TestCase, main

path = Path(__file__).resolve().parents[1] / "scripts" / "verify-current-manifest-public.py"
spec = importlib.util.spec_from_file_location("verify_public", path)
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)


class Contract(TestCase):
    def manifest(self):
        return {"schema_version": 7, "site": "https://na-ryby.eu",
                "batch_id": "test-batch-0001", "edits": [
                    {"post_id": 34913, "slug": "rybarske-kreslo-pro-tezsi-rybare-s-nosnosti-120-kg",
                     "edit_id": "chair-rated-120kg-fit-34913-v1"}]}

    def test_pass(self):
        data = self.manifest()
        result = module.audit(data, lambda _: {"status": "READ_OK", "http": 200,
            "html": '<section data-nry-editorial-edit="chair-rated-120kg-fit-34913-v1"></section>'})
        self.assertEqual(result["status"], "PUBLIC_VERIFIED_ALL")

    def test_missing_marker_is_not_public(self):
        result = module.audit(self.manifest(), lambda _: {"status": "READ_OK", "http": 200, "html": ""})
        self.assertEqual(result["status"], "PUBLIC_NOT_VERIFIED")

    def test_duplicate_marker_is_not_public(self):
        marker = 'data-nry-editorial-edit="chair-rated-120kg-fit-34913-v1"'
        result = module.audit(self.manifest(), lambda _: {"status": "READ_OK", "http": 200, "html": marker + marker})
        self.assertEqual(result["rows"][0]["marker_count"], 2)
        self.assertEqual(result["status"], "PUBLIC_NOT_VERIFIED")

    def test_network_error_not_accepted(self):
        result = module.audit(self.manifest(), lambda _: {"status": "READER_ERROR"})
        self.assertEqual(result["status"], "PUBLIC_NOT_VERIFIED")

    def test_hub_marker_alone_does_not_justify_trust_repair_success(self):
        m = {"schema_version": 8, "site": "https://na-ryby.eu",
             "batch_id": "chair-buyer-trust-60341-20261104-v1",
             "edits": [{"post_id": 60341,
                 "slug": "nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby",
                 "edit_id": "chair-hub-trust-60341-v1"}]}
        marker = 'data-nry-editorial-edit="chair-hub-trust-60341-v1"'
        def fetch(html):
            return module.audit(m, lambda _: {"status": "READ_OK", "http": 200, "html": html})
        self.assertEqual(fetch('<section '+marker+'></section>')["status"], "PUBLIC_NOT_VERIFIED")
        clean = ('<section '+marker+'>Na-ryby.eu je rybářský informační magazín</section>')
        self.assertEqual(fetch(clean)["status"], "PUBLIC_VERIFIED_ALL")
        stale = clean + '<p>Naše společnost se specializuje na výrobu a prodej</p>'
        self.assertEqual(fetch(stale)["status"], "PUBLIC_NOT_VERIFIED")
        sales = clean + '<p>Jsme si jisti, že si u nás vyberete židličku na ryby</p>'
        self.assertEqual(fetch(sales)["status"], "PUBLIC_NOT_VERIFIED")

    def test_echolot_budget_release_requires_proven_buyer_journey(self):
        m = {"schema_version": 8, "site": "https://na-ryby.eu",
             "batch_id": "echolot-budget-118455-20261104-v1",
             "edits": [{"post_id": 118455,
                        "slug": "nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz",
                        "edit_id": "echolot-budget-decision-118455-v1"}]}
        marker = 'data-nry-editorial-edit="echolot-budget-decision-118455-v1"'
        def check(markup):
            return module.audit(m, lambda _: {"status": "READ_OK", "http": 200,
                                                "html": markup})
        base = '<section '+marker+'>'
        self.assertEqual(check(base)["status"], "PUBLIC_NOT_VERIFIED")
        price = "nepředstavují dnes ověřený ceník"
        self.assertEqual(check(base+price)["status"], "PUBLIC_NOT_VERIFIED")
        anchor1 = 'href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/"'
        anchor2 = 'href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/"'
        anchor3 = 'href="https://na-ryby.eu/poradce-vybavy/"'
        complete = base+price+' nemusíte kupovat nové '+anchor1+anchor2+anchor3
        self.assertEqual(check(complete)["status"], "PUBLIC_VERIFIED_ALL")
        self.assertTrue(check(complete)["rows"][0]["semantic_trust_verified"])
        self.assertEqual(check(complete.replace(anchor3,""))["status"], "PUBLIC_NOT_VERIFIED")
        self.assertEqual(check(complete.replace(price,""))["status"], "PUBLIC_NOT_VERIFIED")

    def test_wrong_schema(self):
        data = self.manifest()
        data["schema_version"] = 4
        with self.assertRaises(ValueError):
            module.verify_manifest(data)

    def test_duplicate_post(self):
        data = self.manifest()
        data["edits"] *= 2
        with self.assertRaises(ValueError):
            module.verify_manifest(data)

    def test_oversize(self):
        data = self.manifest()
        data["edits"] *= 5
        with self.assertRaises(ValueError):
            module.verify_manifest(data)

    def test_unsafe_slug(self):
        data = self.manifest()
        data["edits"][0]["slug"] = "../example.com"
        with self.assertRaises(ValueError):
            module.verify_manifest(data)


if __name__ == "__main__":
    main()
