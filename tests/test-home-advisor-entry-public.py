#!/usr/bin/env python3
from pathlib import Path
import runpy

x=runpy.run_path(str(Path(__file__).parents[1] /
    "scripts/verify-home-advisor-entry-public.py"))
html=('<html><head><link rel="canonical" href="'+x["HOME"]+
    '"></head><body><div '+x["OLD_MARKER"]+'></div>'+
    '<p>'+x["HERO"]+'</p><p '+x["MARKER"]+'><a href="'+x["ADVISOR"]+'">'+
    x["COPY"]+'</a>. '+x["CAUTION"]+'</p><h2>'+x["CATEGORY"]+'</h2></body></html>')
assert x["verify"](html)["state"]=="PUBLIC_VERIFIED"
for broken in [
 html.replace(x["MARKER"],""),
 html.replace(x["MARKER"],x["MARKER"]*2),
 html.replace(x["HOME"],"https://na-ryby.eu/missing/"),
 html.replace(x["ADVISOR"],"https://merchant.example.invalid/"),
 html.replace(x["CAUTION"],"MISSING"),
 html.replace(x["CATEGORY"],"MISSING"),
 html.replace(x["HERO"],"MISSING"),
]:
    try: x["verify"](broken)
    except AssertionError: pass
    else: raise AssertionError("MUST_FAIL_CLOSED")
print("PASS homepage advisor readback fixture")
