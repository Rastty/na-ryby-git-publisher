#!/usr/bin/env python3
"""Pure parser fixtures, never contacting WordPress or a merchant."""
from importlib.machinery import SourceFileLoader
from pathlib import Path
m = SourceFileLoader("stays_diag",
    str(Path(__file__).resolve().parents[1] / "scripts/diagnose-legacy-stays-render-public.py")).load_module()
html = ('<html><head><link href="nry-legacy-stays-entry-v1.css"></head>'
        '<body><h1>Ubytování v ČR</h1><h2>Rekreační dům Harvánek</h2></body></html>')
a = m.analyze(html)
assert a["css_count"] == 1 and a["exact_h1_count"] == 1
assert a["first_result_present"] and a["marker_count"] == 0
b = m.analyze(html.replace("<h1>Ubytování v ČR</h1>",
    "<h1><span>Ubytování v ČR</span></h1>"))
assert b["exact_h1_count"] == 0 and b["title_headings"] == ["Ubytování v ČR"]
assert b["title_inner_tag_names"] == ["span"], "actual 21 Nov live wrapper must be visible in diagnostic flags"
c = m.analyze(html.replace("Rekreační dům Harvánek","Nová nabídka"))
assert not c["first_result_present"]
print("NRY_LEGACY_STAYS_DIAGNOSTIC_OFFLINE_VERIFIED")
