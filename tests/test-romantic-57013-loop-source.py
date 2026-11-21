"""Offline exact anonymous WordPress input probe parser. No networking."""
import runpy
from pathlib import Path
from html import escape

m = runpy.run_path(str(Path(__file__).resolve().parents[1] /
                       "scripts/verify-romantic-57013-loop-source.py"))
sha = "a" * 64
row = {
    "bytes": 5300, "prefixSha256": sha, "matchesHistoricalRestPrefix": False,
    "oldButtonCount": 1, "headingCount": 1, "categoryLinkCount": 1,
    "mainQuery": True, "inLoop": True, "eligible": True,
    "newSectionPresent": False,
}
proof = {
    "status": "NRY_ROMANTIC57013_GUARD_PROBE_V1",
    "postId": 57013, "modifiedGmt": "2026-04-19 12:01:55",
    "rawSourceSha256": "b"*64, "rawSourceBytes": 7000,
    "renderCalls": 1, "first": row, "best": row,
    "wordpressWrites": 0, "merchantRequests": 0, "syntheticClicks": 0,
}
post = {"id": 57013, "type": "post", "status": "publish",
        "slug": m["SLUG"], "link": m["URL"],
        "modified_gmt": "2026-04-19T12:01:55"}

import json
def doc(p=proof):
    return ('<html><head><link rel="canonical" href="' + m["URL"] + '"></head>'
            '<body class="postid-57013">'
            '<!-- NRY_ROMANTIC57013_PROBE_V1 ' + json.dumps(p) +
            ' --></body></html>')

v = m["verify"](doc(), post)
assert v["status"] == "NRY_ROMANTIC57013_CONTENT_INPUT_OBSERVED"
assert v["rawSourceSha256"] == "b"*64 and v["inputPrefixSha256"] == sha
assert v["oldButtonCount"] == v["headingCount"] == v["categoryLinkCount"] == 1
assert v["wordpressWrites"] == v["merchantRequests"] == v["syntheticClicks"] == 0
def blocked(html=doc(), rest=post):
    try:
        m["verify"](html, rest)
    except (ValueError, KeyError, TypeError):
        return
    raise AssertionError("bad production proof accepted")
blocked(doc().replace("postid-57013", "postid-57014"))
blocked(doc().replace('rel="canonical"', 'rel="alternate"'))
blocked(doc().replace("NRY_ROMANTIC57013_PROBE_V1 ", "REMOVED "))
blocked(doc() + doc())
blocked(doc({**proof, "rawSourceSha256": "not-a-sha"}))
blocked(doc({**proof, "renderCalls": 0, "best": None}))
blocked(doc({**proof, "wordpressWrites": 1}))
blocked(doc({**proof, "first": {**row, "oldButtonCount": -1}}))
blocked(doc(), {**post, "modified_gmt": "2026-04-20T00:00:00"})
print("ROMANTIC_57013_GET_ONLY_INPUT_PARSER_OK")
