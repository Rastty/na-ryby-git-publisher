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
