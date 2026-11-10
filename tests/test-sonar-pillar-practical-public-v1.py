#!/usr/bin/env python3
"""No network fixture verifying precise published navigation and safety."""
import runpy
from pathlib import Path

m=runpy.run_path(str(Path(__file__).resolve().parents[1] /
    "scripts/verify-sonar-pillar-practical-public-v1.py"))
source=m["SOURCE_MARKER"]
links=" ".join('<a href="'+x+'">guide</a>' for x in m["OLD_LINKS"])
section=('<section '+source+'>'+m["PILLAR_HEADING"]+
    '<p>Pokud stávající vybavení dostačuje, další nákup není nutný.</p>'
    '<ul>'+''.join('<li>Scénář</li>' for _ in range(4))+'</ul>'
    '<p>Ověřte aktuální konečnou cenu kompletu. '+links+'</p></section>')
paragraph=('<p '+m["NEW_MARKER"]+'>Echolot už máte? '
 '<a href="'+m["PRACTICAL"]+'">praktický návod</a></p>')
trailer='<h2>Jak vybrat echolot pro rybaření: Kompletní průvodce</h2>'
content=section+trailer+'<p>'+('obsah článku '*100)+'</p>'
page={"id":118553,"slug":"jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce",
 "type":"post","status":"publish","link":m["PAGE"],
 "modified_gmt":"2026-11-04T11:27:17","content":{"rendered":content}}
html='<html> '+m["PAGE"]+' '+content+('test site '*7000)+'</html>'
assert m["evaluate"](page,html,False)["status"]=="PUBLIC_PREFLIGHT_VERIFIED"
result=section+paragraph+trailer+'<p>'+('obsah článku '*100)+'</p>'
# Render-only main-loop filter does not mutate the REST API representation.
page["content"]["rendered"]=content
html='<html>'+m["PAGE"]+' '+result+' '+('test site '*7000)+'</html>'
assert m["evaluate"](page,html,True)["status"]=="PUBLIC_HANDOFF_VERIFIED"
assert m["evaluate"](page,html,True)["affiliateLinksRewritten"]==0
assert m["evaluate"](page,html,True)["ga4GuideHandoffProven"] is False
for name,altered in [
 ("missing marker",result.replace(source,"different")),
 ("edited no-buy advice",result.replace('další nákup není nutný','nový nákup')),
 ("extra link",result+m["PRACTICAL"]),
 ("duplicate marker",result+paragraph),
 ("broken decision list",result.replace('<li>','<li/>',1))
]:
 p={**page,"content":{"rendered":content}}
 try:m["evaluate"](p,'<html>'+m["PAGE"]+altered+('test site '*7000)+'</html>',True)
 except AssertionError:pass
 else:raise AssertionError(name+" must block")
try:m["live_get"]("https://example.com/unknown",500)
except AssertionError:pass
else:raise AssertionError("Third-party network must be blocked")
print("PASS source-bound pillar-to-practical public readback, no fake traffic")
