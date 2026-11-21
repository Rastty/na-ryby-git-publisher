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
