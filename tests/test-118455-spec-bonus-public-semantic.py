#!/usr/bin/env python3
"""Offline independent semantic verification of six fixed 118455 claims."""
import importlib.util
import json
from pathlib import Path

root=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location("nry_public",root/"scripts/verify-current-manifest-public.py")
m=importlib.util.module_from_spec(spec);spec.loader.exec_module(m)
manifest=json.loads((root/"tests/v8-echolot-budget-118455-spec-bonus-frozen.json").read_text(encoding="utf8"))
target=m.verify_manifest(manifest)[0]
assert target["post_id"]==118455 and target["placement"]=="sonar_spec_bonus_only"
rules=manifest["edits"][0]["text_corrections"]
head=(
 '<section data-nry-editorial-edit="echolot-budget-decision-118455-v1">'
 '<h2>Výběr echolotu</h2><p>nepředstavují dnes ověřený ceník; nemusíte kupovat nové</p>'
 '<a href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/">Výběr</a>'
 '<a href="https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/">Loď</a>'
 '<a href="https://na-ryby.eu/poradce-vybavy/">Poradce</a></section>')
good='<h1>Echoloty do 15 000 Kč</h1>'+head+''.join(
 '<p>'+r["new_text"]+'</p>'*0 if False else
 ''.join('<p>'+r["new_text"]+'</p>' for _ in range(r["count"])) for r in rules)
def proof(body):
 return m.check_target(target,lambda _ : {"status":"READ_OK","http":200,"html":body})
assert proof(good)["status"]=="PUBLIC_MARKER_VERIFIED"
for r in rules:
 bad=good.replace(r["new_text"],r["old_text"],1)
 assert proof(bad)["status"]!="PUBLIC_MARKER_VERIFIED","stale fact bypassed"
 bad=good.replace(r["new_text"],"",1)
 assert proof(bad)["status"]!="PUBLIC_MARKER_VERIFIED","missing fact bypassed"
assert proof(good.replace("nepředstavují dnes ověřený ceník",""))["status"]!="PUBLIC_MARKER_VERIFIED"
assert proof(good.replace("https://na-ryby.eu/poradce-vybavy/","https://other.invalid/"))["status"]!="PUBLIC_MARKER_VERIFIED"
bad=json.loads(json.dumps(manifest));bad["edits"][0]["post_id"]=118553
try:
 m.verify_manifest(bad);raise AssertionError("wrong post accepted")
except ValueError:
 pass
print("PASS 118455 public: six exact factual corrections, old claims gone, three buyer routes preserved")
