#!/usr/bin/env python3
"""Exact public semantic proof: 118455 historical price and stock corrections."""
import importlib.util
import json
from pathlib import Path

root=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location("nry_public",root/"scripts/verify-current-manifest-public.py")
module=importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
manifest=json.loads((root/"tests/v8-echolot-budget-118455-three-offers-frozen.json").read_text(encoding="utf8"))
target=module.verify_manifest(manifest)[0]
assert target["post_id"]==118455 and target["placement"]=="historic_sonar_offers_only"
rules=manifest["edits"][0]["text_corrections"]
r=(
 '<section data-nry-editorial-edit="echolot-budget-decision-118455-v1">'
 '<h2>Výběr echolotu</h2><p>nepředstavují dnes ověřený ceník; nemusíte kupovat nové</p>'
 '<a href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/">Výběr</a>'
 '<a href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/">Loď</a>'
 '<a href="https://na-ryby.eu/poradce-vybavy/">Poradce</a></section>'
)
good='<h1>Echoloty do 15 000 Kč</h1>'+r+''.join('<p>'+x["new_text"]+'</p>' for x in rules)
def verify(body):
 return module.check_target(target,lambda _:{"status":"READ_OK","http":200,"html":body})
ok=verify(good)
assert ok["status"]=="PUBLIC_MARKER_VERIFIED" and ok["semantic_trust_verified"] is True
assert ok["marker_count"]==0, "new HTML need not add another banner"
for i in range(3):
 old=good.replace(rules[i]["new_text"],rules[i]["old_text"],1)
 missing=good.replace(rules[i]["new_text"],'',1)
 assert verify(old)["status"]!="PUBLIC_MARKER_VERIFIED"
 assert verify(missing)["semantic_trust_verified"] is False
assert verify(good.replace("nepředstavují dnes ověřený ceník",""))["status"]!="PUBLIC_MARKER_VERIFIED"
assert verify(good.replace("https://na-ryby.eu/poradce-vybavy/","https://wrong.example/"))["status"]!="PUBLIC_MARKER_VERIFIED"
bad=json.loads(json.dumps(manifest));bad["edits"][0]["post_id"]=118553
try: module.verify_manifest(bad);assert False,"wrong article allowed"
except ValueError: pass
print("PASS 118455 public: three historic price/stock paragraphs corrected, prior guidance and routes preserved, no marker-only false positive")
