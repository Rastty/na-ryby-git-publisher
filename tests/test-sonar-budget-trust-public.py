#!/usr/bin/env python3
"""Offline contract; never calls a live URL or merchant."""
import runpy
from pathlib import Path

m = runpy.run_path(str(Path(__file__).resolve().parents[1] /
                       "scripts/verify-sonar-budget-trust-118455-public.py"))
html = ('<html><head><link rel="canonical" href="' + m["URL"] +
        '"></head><body>' + m["MARKER"] +
        '<main>' + '<p>'.join(m["REQUIRED"]) + '</main></body></html>')
assert m["verify"](html)["status"] == "PUBLIC_VERIFIED"
assert m["verify"](html)["already_safe_cases"] == 0
alternative = html.replace(m["REQUIRED"][4], m["SAFE_EXISTING_CASE_5"])
assert m["verify"](alternative)["status"] == "PUBLIC_VERIFIED"
assert m["verify"](alternative)["already_safe_cases"] == 1
# A stale best-offer claim is never an acceptable alternative.
stale5 = html.replace(m["REQUIRED"][4],
    "Chyť a pusť tu má rozumnou aktuální nabídku včetně baterie a nabíječky.")

for name, changed in [
    ("missing_marker", html.replace(m["MARKER"], "")),
    ("wrong_canonical", html.replace(m["URL"], "https://na-ryby.eu/other/")),
    ("old_prompt", html.replace("</main>", "Zadání sice zmiňuje</main>")),
    ("missing_correction", html.replace(m["REQUIRED"][2], "MISSING")),
    ("stale_offer_case5", stale5),
    ("missing_both_safe_variants_case5", html.replace(m["REQUIRED"][4], "UNKNOWN"))
]:
    try:
        m["verify"](changed)
    except AssertionError:
        pass
    else:
        raise AssertionError("Verifier must fail closed for " + name)
# Diagnostic classification must preserve and identify context without
# weakening the existing fail-closed visible/hidden assertion.
probe = m["Document"]()
old = m["FORBIDDEN"][8]
probe.feed("<main><p>" + old + "</p></main><script type=\"application/ld+json\">"
           + old + "</script><style>" + old + "</style>")
assert old in " ".join(probe.visible_parts)
assert old in " ".join(probe.script_parts)
assert old in " ".join(probe.hidden_parts)
only_schema = m["Document"]()
only_schema.feed("<main><p>Safe buyer FAQ</p></main>"
                 "<script type=\"application/ld+json\" class=\"yoast-schema-graph\">"
                 + old + "</script>")
assert only_schema.script_records[0]["type"] == "application/ld+json"
assert only_schema.script_records[0]["class"] == "yoast-schema-graph"
assert old in only_schema.script_records[0]["body"]
assert old not in " ".join(only_schema.visible_parts)
assert old in " ".join(only_schema.script_parts)

print("OK sonar-budget-public-verifier-offline-contract")
