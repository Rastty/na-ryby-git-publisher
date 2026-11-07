#!/usr/bin/env python3
"""Offline independent positive/negative public semantics for the next 42401 canary."""
import importlib.util
import json
from pathlib import Path

root = Path(__file__).resolve().parents[1]
spec = importlib.util.spec_from_file_location(
    "nry_public_verifier", root / "scripts/verify-current-manifest-public.py")
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)

manifest = json.loads(
    (root / "tests/v8-pipaky-42401-quick-choice-20261107-frozen.json").read_text(encoding="utf-8"))
e = manifest["edits"][0]
target = module.verify_manifest(manifest)[0]
assert target["post_id"] == 42401
assert target["placement"] == "prepend"
html = e["insert_html"] + "\n<article><p>Původní text ponechán.</p></article>"

def verify(html):
    return module.check_target(target, lambda _: {
        "status": "READ_OK", "http": 200, "html": html,
    })

good = verify(html)
assert good["status"] == "PUBLIC_MARKER_VERIFIED" and good["semantic_trust_verified"] is True
for altered in (
    html.replace("Začínáte s jedním prutem?", "Jiný problém"),
    html.replace("není důvod automaticky kupovat nový", "Kupte nový"),
    html.replace('href="https://na-ryby.eu/baterie-do-signalizatoru-ktere-vydrzi-nejdele-a-proc-nekupovat-levne/"', 'href="https://na-ryby.eu/jiny-pruvodce/"'),
    html.replace("<li>", "<p>", 1),
    '<section data-nry-editorial-edit="' + e["edit_id"] + '"></section>',
):
    result = verify(altered)
    assert result["status"] != "PUBLIC_MARKER_VERIFIED", result
    assert result["semantic_trust_verified"] is False
print("PASS: 42401 exact four-case public readback and five marker-only/partial failure controls")
