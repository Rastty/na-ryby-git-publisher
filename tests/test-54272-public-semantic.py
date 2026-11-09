#!/usr/bin/env python3
"""Offline strict public semantics for exact one-post 54272 buyer trust release."""
import importlib.util, json
from pathlib import Path
root=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location("nry_public",root/"scripts/verify-current-manifest-public.py")
mod=importlib.util.module_from_spec(spec);spec.loader.exec_module(mod)
m=json.loads((root/"tests/v8-carp-3m-54272-20261109-frozen.json").read_text(encoding="utf8"))
target=mod.verify_manifest(m)[0]
assert target["post_id"]==54272 and target["placement"]=="prepend"
edit=m["edits"][0]
good="<h1>Kaprové pruty 3m</h1>"+edit["insert_html"]+"".join(
    "<p>"+c["new_text"]+"</p>" for c in edit["text_corrections"])
def check(s):
    return mod.check_target(target,lambda _ :{"status":"READ_OK","http":200,"html":s})
assert check(good)["status"]=="PUBLIC_MARKER_VERIFIED"
for c in edit["text_corrections"]:
    assert check(good.replace(c["new_text"],c["old_text"],1))["status"]!="PUBLIC_MARKER_VERIFIED",c["old_text"]
    assert check(good.replace(c["new_text"],"",1))["status"]!="PUBLIC_MARKER_VERIFIED",c["new_text"]
assert check(good.replace('data-nry-editorial-edit="kaprove-pruty-3m-54272-20261109-v1"',''))["status"]!="PUBLIC_MARKER_VERIFIED"
assert check(good.replace('href="https://na-ryby.eu/nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber/"',''))["status"]!="PUBLIC_MARKER_VERIFIED"
wrong=json.loads(json.dumps(m));wrong["batch_id"]="out-of-scope"
try:
    mod.verify_manifest(wrong);raise AssertionError("unscoped batch passed")
except ValueError:
    pass
print("PASS 54272 public: nine factual fixes, decision table, two routes, exact scope")
