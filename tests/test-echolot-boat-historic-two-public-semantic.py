#!/usr/bin/env python3
"""Exact two-post sonar buyer decision public-readback positive/negative QA."""
import importlib.util
import json
from pathlib import Path

ROOT=Path(__file__).resolve().parents[1]
spec=importlib.util.spec_from_file_location("nry_public",ROOT/"scripts/verify-current-manifest-public.py")
m=importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)
batch=json.loads((ROOT/"tests/v8-echolot-boat-historic-two-guides-20261108-frozen.json").read_text(encoding="utf8"))
targets=m.verify_manifest(batch)
assert [x["post_id"] for x in targets]==[60637,36518]
for target,edit in zip(targets,batch["edits"]):
    html=edit["insert_html"]
    def verify(source):
        return m.check_target(target,lambda _:{"status":"READ_OK","http":200,"html":source})
    good=verify("<html><body>"+html+"</body></html>")
    assert good["status"]=="PUBLIC_MARKER_VERIFIED",(target["post_id"],good)
    assert good["semantic_trust_verified"] is True
    assert good["marker_count"]==1
    assert verify(html.replace('<ul>','',1))["status"]!="PUBLIC_MARKER_VERIFIED"
    assert verify(html.replace('<h2>','<h4>',1))["status"]!="PUBLIC_MARKER_VERIFIED"
    assert verify(html.replace('https://na-ryby.eu/poradce-vybavy/','https://wrong.example/'))["status"]!="PUBLIC_MARKER_VERIFIED"
    assert verify(html.replace('data-nry-editorial-edit=', 'data-invalid-marker=',1))["status"]!="PUBLIC_MARKER_VERIFIED"
    if target["post_id"]==60637:
        assert verify(html.replace('Kajak nebo přenosná sestava','',1))["status"]!="PUBLIC_MARKER_VERIFIED"
        assert verify(html.replace('nemusíte kupovat novou','',1))["status"]!="PUBLIC_MARKER_VERIFIED"
    else:
        assert verify(html.replace('Nejde o aktuální žebříček','',1))["status"]!="PUBLIC_MARKER_VERIFIED"
        assert verify(html.replace('nový echolot nepotřebujete','',1))["status"]!="PUBLIC_MARKER_VERIFIED"
bad=json.loads(json.dumps(batch))
bad["edits"][0]["post_id"]=38064
try:
    m.verify_manifest(bad)
    assert False, "wrong protected ID"
except ValueError:
    pass
print("PASS two sonar public semantic readbacks: genuine boat/historic buyer advice, 7 exact internal links, no marker-only false positives")
