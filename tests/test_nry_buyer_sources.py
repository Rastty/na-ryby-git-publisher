#!/usr/bin/env python3
import sys, unittest
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parents[1] / "scripts"))
from audit_nry_buyer_sources import inspect, url_for, CANDIDATES
class SourcePreflight(unittest.TestCase):
    def good(self,id=34858):
        return {"id":id,"slug":"detske-kreslo-na-ryby","status":"publish",
                "type":"post","link":"https://na-ryby.eu/detske-kreslo-na-ryby/",
                "modified_gmt":"2026-11-02T12:01:02",
                "content":{"rendered":"<h2>Dětské rybářské křeslo</h2>"+("<p>Výběr dle věku a výšky dítěte.</p>"*100)}}
    def test_strict_read_only_scope_and_unknown_source(self):
        self.assertEqual(len(CANDIDATES),4)
        self.assertEqual(url_for(34858).split("?")[0],
                         "https://na-ryby.eu/wp-json/wp/v2/posts/34858")
        for id,hint in CANDIDATES:
            with self.subTest(id=id):
                out=inspect(id,hint,self.good(id))
                self.assertEqual(out["state"],"PUBLIC_REST_FOUND_NEEDS_PRIVATE_SOURCE_GSC_CHECK")
                self.assertFalse(out["publication_authorized"])
                self.assertEqual(out["authenticated_source_sha256"],"UNKNOWN")
                self.assertEqual(out["gsc_protection_state"],"UNKNOWN_NOT_CHECKED")
                self.assertEqual(out["wordpress_writes"],0)
    def test_mismatch_and_missing_source_fail_closed(self):
        for mutate in (
            lambda p:p.update(id=123),
            lambda p:p.update(status="draft"),
            lambda p:p.update(type="page"),
            lambda p:p.update(link="https://another.example/"),
            lambda p:p.update(modified_gmt="yesterday"),
            lambda p:p.update(content={"rendered":"short"}),
        ):
            p=self.good();mutate(p)
            self.assertFalse(inspect(34858,"test",p)["publication_authorized"])
        self.assertEqual(inspect(38064,"protect",self.good(38064))["state"],"PROTECTED_SKIP")
    def test_source_does_not_collect_raw_content_or_external_routes(self):
        p=self.good()
        p["content"]["rendered"] += '<p><a href="https://merchant.example/secret?token=123">x</a></p>'
        out=inspect(34858,"test",p)
        self.assertNotIn("content",out)
        self.assertNotIn("rendered_html",out)
        self.assertNotIn("merchant.example",str(out))
        self.assertEqual(out["rendered_link_count"],1)
if __name__=="__main__":
    unittest.main()
