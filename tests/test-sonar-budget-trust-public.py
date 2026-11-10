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
for name, changed in [
    ("missing_marker", html.replace(m["MARKER"], "")),
    ("wrong_canonical", html.replace(m["URL"], "https://na-ryby.eu/other/")),
    ("old_prompt", html.replace("</main>", "Zadání sice zmiňuje</main>")),
    ("missing_correction", html.replace(m["REQUIRED"][2], "MISSING"))
]:
    try:
        m["verify"](changed)
    except AssertionError:
        pass
    else:
        raise AssertionError("Verifier must fail closed for " + name)
print("OK sonar-budget-public-verifier-offline-contract")
