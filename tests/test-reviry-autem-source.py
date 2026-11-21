"""Offline parse contract for no-write fishing-by-car source proof."""
import runpy
from pathlib import Path

module = runpy.run_path(str(Path(__file__).resolve().parents[1] / "scripts" /
                            "verify-reviry-autem-source.py"))
parse, slug, page, anchors = (module[k] for k in ("parse", "SLUG", "PAGE", "ANCHORS"))
post = {"id": 57421, "slug": slug, "link": page, "status": "publish",
        "type": "post", "modified_gmt": "2023-06-28T10:00:00",
        "content": {"rendered": (" ".join(anchors.values()) + " ") * 20}}
html = '<html><head><link rel="canonical" href="' + page + '"></head>' \
       '<body class="postid-57421">' + post["content"]["rendered"] + '</body></html>'
result = parse([post], html)
assert result["id"] == 57421
assert all(v >= 1 for v in result["anchorCounts"].values())
assert result["wordpressWrites"] == result["merchantRequests"] == result["syntheticClicks"] == 0
assert len(result["renderedPrefix512Sha256"]) == 64
for bad_data, bad_html in (([], html), ([{**post, "status": "draft"}], html),
                           ([post], html.replace("postid-57421", "postid-57422")),
                           ([post], html.replace("canonical", "alternative"))):
    try:
        parse(bad_data, bad_html)
    except (ValueError, KeyError):
        continue
    raise AssertionError("UNSAFE_SOURCE_ACCEPTED")
print("REVIRY_AUTEM_SOURCE_PARSER_OK")


verify_raw = module["verify_raw"]
proof = {
    "schema": 1, "state": "SOURCE_SHA_AVAILABLE_NO_PUBLICATION",
    "postId": 57421, "slug": slug, "canonical": page,
    "modifiedGmt": "2023-06-28 10:00:00",
    "sourceSha256": "a" * 64, "sourceBytes": 3001,
    "literalCounts": {"wrongGeo": 1, "podyji": 1, "sumava": 1, "lipno": 2},
    "wordpressWrites": 0, "merchantRequests": 0, "syntheticClicks": 0,
}
assert verify_raw(proof, result)["sourceSha256"] == "a" * 64
for bad in (
    {**proof, "sourceSha256": "not-a-hash"},
    {**proof, "postId": 0},
    {**proof, "state": "BLOCKED_SOURCE_DRIFT"},
    {**proof, "modifiedGmt": "2025-01-01 00:00:00"},
    {**proof, "literalCounts": {"wrongGeo": -1}},
    {**proof, "wordpressWrites": 1},
):
    try:
        verify_raw(bad, result)
    except ValueError:
        continue
    raise AssertionError("UNSAFE_RAW_PROOF_ACCEPTED")
print("REVIRY_AUTEM_RAW_SOURCE_PARSER_OK")
