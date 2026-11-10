#!/usr/bin/env python3
"""Offline exact-origin schema classifier contracts; never uses the network."""
import json
import runpy
from pathlib import Path

m = runpy.run_path(str(Path(__file__).resolve().parents[1] /
                       "scripts/audit-sonar-faq-schema-origin.py"))
old = m["OLD"]
safe = m["SAFE"]
tree = {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {"@type": "Question", "name": "Does Garmin fit?",
         "acceptedAnswer": {"@type": "Answer", "text": old}}
    ]
}
schema = '<script type="application/ld+json">' + json.dumps(tree, ensure_ascii=False) + '</script>'
page = '<html><main>' + safe + '</main>' + schema + '</html>'
finding = m["script_findings"](page)
assert len(finding) == 1
assert finding[0]["type"] == "application/ld+json"
assert finding[0]["json_valid"] is True
assert finding[0]["root_type"] == "FAQPage"
assert "FAQPage" in finding[0]["matching_schema_types"]
assert "Answer" in finding[0]["matching_schema_types"]
assert "mainEntity.[].acceptedAnswer.text" in finding[0]["matching_json_paths"]
assert old not in json.dumps(finding)
assert "Does Garmin fit?" not in json.dumps(finding)
assert m["script_findings"]('<main>' + old + '</main>') == []
assert m["script_findings"]('<script type="application/ld+json">{invalid ' + old +
                           '</script>')[0]["json_valid"] is False
rest = json.dumps([{"id": 118455, "slug": m["SLUG"],
                    "content": {"rendered": "<p>" + old + "</p>"},
                    "yoast_head_json": None}], ensure_ascii=False)
summary = m["audit"](page, rest)
assert summary["rest_status"] == "POST_118455_IDENTIFIED"
assert summary["rest_content_old_claim"] is True
assert summary["rest_content_jsonld_script_matches"] == 0
assert summary["public_html_script_matches"] == 1
assert summary["wordpress_writes"] == 0
assert m["audit"](page, "{}")["rest_status"] == "IDENTITY_NOT_PROVEN"
for forbidden in ("https://other.example/", m["PAGE"]+"?unexpected=1"):
    try:
        m["audited_get"](forbidden)
    except ValueError:
        pass
    else:
        raise AssertionError("unapproved origin / URL must never be requested")
print("OK sonar FAQ JSON-LD schema provenance classifier (read-only)")
