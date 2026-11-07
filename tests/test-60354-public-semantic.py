#!/usr/bin/env python3
"""False-positive controls for public 60354 correction-only readback."""
import importlib.util
import json
from pathlib import Path

root=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location("nry_public",root/"scripts/verify-current-manifest-public.py")
module=importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
manifest=json.loads((root/"tests/v8-alarm-ranking-60354-claims-20261107-frozen.json").read_text(encoding="utf8"))
target=module.verify_manifest(manifest)[0]
assert target["post_id"]==60354 and target["placement"]=="ranking_claims_only"
rules=manifest["edits"][0]["text_corrections"]
good=(
 "<h1>Nejlepší rybářské hlásiče a signalizátory</h1>"
 "<p>Zfish DAM Nash Hlásiče v AKCI</p>"
 +("<p>bez zveřejněného testovacího skóre</p>"*6)
 +"".join("<p>"+r["new_text"]+"</p>" for r in rules[2:])
)
def verify(body):
 return module.check_target(target,lambda _:{"status":"READ_OK","http":200,"html":body})
ok=verify(good)
assert ok["status"]=="PUBLIC_MARKER_VERIFIED"
assert ok["semantic_trust_verified"] is True and ok["marker_count"]==0
for bad in (
 good.replace("bez zveřejněného testovacího skóre","⭐⭐⭐⭐⭐",1),
 good.replace("bez zveřejněného testovacího skóre","",1),
 good.replace(rules[2]["new_text"],rules[2]["old_text"]),
 good.replace(rules[5]["new_text"],"100% vodotěsné"),
 good.replace("Nash","Neznámý model"),
 good.replace(rules[7]["new_text"],""),
):
 failed=verify(bad)
 assert failed["semantic_trust_verified"] is False
 assert failed["status"]!="PUBLIC_MARKER_VERIFIED"
assert verify(good.replace("Hlásiče v AKCI",""))["status"]!="PUBLIC_MARKER_VERIFIED"
print("PASS #60354 public requires six corrected stars, full text substitutions, brands and CTA; marker-only impossible")
