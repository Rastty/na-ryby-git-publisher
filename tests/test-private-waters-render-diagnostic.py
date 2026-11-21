#!/usr/bin/env python3
"""No-network negative fixtures for anonymous private-water render diagnostic."""
from importlib.machinery import SourceFileLoader
from pathlib import Path

m = SourceFileLoader("water_diag",
    str(Path(__file__).resolve().parents[1] / "scripts/diagnose-private-waters-render-public.py")).load_module()
html = ('<html><head><link href="nry-private-waters-examples-v1.css"></head>'
        '<body><h2>Podle čeho poznat dobrý soukromý revír</h2>'
        '<h2>Pro koho dávají soukromé revíry největší smysl</h2></body></html>')
clean = m.analyze(html)
assert clean["css_count"] == 1
assert clean["expected_heading_visible_count"] == 1
assert clean["expected_heading_exact_html_count"] == 1
assert clean["other_heading_visible_count"] == 1
assert clean["marker_exact_count"] == 0
nested = m.analyze(html.replace(
    "<h2>Podle čeho poznat dobrý soukromý revír</h2>",
    '<h2 class="wp-block-heading"><strong>Podle čeho poznat dobrý soukromý revír</strong></h2>'))
assert nested["expected_heading_visible_count"] == 1
assert nested["expected_heading_exact_html_count"] == 0
assert nested["nested_tags_in_target_headings"][0] == 1
with_marker = m.analyze(html.replace(
    "<h2>Podle čeho", '<section data-nry-private-water-examples="v1"></section><h2>Podle čeho'))
assert with_marker["marker_exact_count"] == 1 and with_marker["marker_dom_count"] == 1
assert with_marker["original_h2_before_new_marker"] is False
print("NRY_PRIVATE_WATERS_RENDER_DIAGNOSTIC_OFFLINE_VERIFIED")
