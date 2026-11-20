"""Offline read-only GSC snippet probe + public head parser regressions."""
import json
import runpy
import sys
from pathlib import Path

root = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(root / "scripts"))
code = runpy.run_path(str(root / "scripts/verify-gsc-stays-snippets-public.py"))
inspect = code["inspect_html"]
inspect_clean = code["inspect_clean_html"]
pages = code["PAGES"]
id_ = 12166
url, title, desc = pages[id_]
row = {
    "status": "NRY_GSC_STAYS_SNIPPET_PROBE_V1",
    "postId": id_, "eligible": True, "modifiedMatches": True, "sourceMatches": True,
    "title": {"calls": 1, "applied": True,
              "first": {"inputBytes": 32, "inputSha256": "a"*64,
                        "baselineMatched": True, "applied": True}},
    "description": {"calls": 1, "applied": True,
                    "first": {"inputBytes": 0, "inputSha256": "b"*64,
                              "baselineMatched": True, "applied": True}},
    "wordpressWrites": 0, "merchantRequests": 0, "syntheticClicks": 0,
}

def fixture(record, meta_title=title, meta_desc=desc, canonical=url):
    return ('<html><head><title>' + meta_title + '</title>'
            + '<meta name="description" content="' + meta_desc + '">'
            + '<link rel="canonical" href="' + canonical + '">'
            + '</head><body><!-- NRY_GSC_STAYS_SNIPPET_PROBE_V1 '
            + json.dumps(record) + ' --></body></html>')

valid = inspect(fixture(row), id_)
assert valid["canonicalOK"] and valid["titleOK"] and valid["descriptionOK"]
assert valid["eligible"] and valid["titleApplied"] and valid["descriptionApplied"]
clean = inspect_clean(fixture(row), id_)
assert clean["cleanCanonicalOK"] and clean["cleanTitleOK"] and clean["cleanDescriptionOK"]
assert len(clean["cleanTitleSha256"]) == 64 and len(clean["cleanDescriptionSha256"]) == 64
assert inspect_clean(fixture(row, meta_title="cached old title"), id_)["cleanTitleOK"] is False
assert inspect_clean(fixture(row, meta_desc="cached old meta"), id_)["cleanDescriptionOK"] is False
assert inspect_clean(fixture(row, canonical="https://wrong.example/"), id_)["cleanCanonicalOK"] is False
assert inspect(fixture(dict(row, eligible=False)), id_)["eligible"] is False
assert inspect(fixture(row, meta_title="old"), id_)["titleOK"] is False
assert inspect(fixture(row, meta_desc="old"), id_)["descriptionOK"] is False
assert inspect(fixture(row, canonical="https://wrong.example/"), id_)["canonicalOK"] is False

for wrong in (
    "<html></html>",
    fixture(dict(row, postId=14517)),
    fixture(dict(row, wordpressWrites=1)),
    fixture(dict(row, merchantRequests=1)),
    fixture(dict(row, syntheticClicks=1)),
    fixture(dict(row, title=dict(row["title"], calls=101))),
    fixture(dict(row, description=dict(row["description"], first=dict(
        row["description"]["first"], inputSha256="wrong")))),
    fixture(row) + fixture(row),
):
    try:
        inspect(wrong, id_)
    except (AssertionError, KeyError, TypeError, ValueError):
        continue
    raise AssertionError("UNSAFE_PROBE_OR_HEAD_ACCEPTED")
# Reuse the already-reviewed Dyje 6 HTML ceiling and preserve a GET-only
# main-push rerun path for verifier-only fixes (never retrigger FTP payload).
script = (root / "scripts/verify-gsc-stays-snippets-public.py").read_text()
workflow = (root / ".github/workflows/gsc-stays-snippet-live-proof.yml").read_text()
assert "response.read(1_600_001)" in script
assert "len(content) <= 1_600_000" in script
assert "  push:\n    branches: [main]\n    paths:" in workflow
assert "github.event_name == 'push' || steps.provenance.outputs.deployed == 'true'" in workflow
print("GSC_STAYS_SNIPPETS_PUBLIC_PARSER_OK")
