#!/usr/bin/env python3
"""Offline negative controls: no merchant contacts/clicks/GA4 claims."""
import importlib.util
from pathlib import Path
import sys
ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT / "scripts"))
spec = importlib.util.spec_from_file_location("buyer_live", ROOT / "scripts/audit-buyer-click-live-readiness.py")
mod = importlib.util.module_from_spec(spec)
spec.loader.exec_module(mod)
p = mod.ASSET
one = ('<html><head><script defer src="'+p+'?ver=1.4.0"></script></head></html>').encode()
assert mod.inspect_document(one)==1
assert mod.inspect_document(b'<script src="//example.com/evil.js"></script>')==0
assert mod.inspect_document(('<script src="https://other.example'+p+'"></script>').encode())==0
assert mod.inspect_document(('<script src="'+p+'?url=merchant"></script>').encode())==0
assert mod.inspect_document(('<script src="'+p+'"></script><script src="'+p+'"></script>').encode())==2
assert mod.inspect_document(b'<script>gtag("event","fake")</script>')==0
calls = []
source = (ROOT / "assets/nry-buyer-clicks-v1.js").read_bytes()
def fake_get(url, maximum):
    calls.append(url)
    if url.endswith(".js?ver=1.4.0"): return source
    if url.endswith(mod.PAGES['unscoped_control']): return b'<html><body>control</body></html>'
    return one
result = mod.audit(fake_get)
assert result['pages']['pipaky_guide']['state']=='DIRECT_SCRIPT_ENQUEUED'
assert result['pages']['alarm_guide']['state']=='DIRECT_SCRIPT_ENQUEUED'
assert result['pages']['sonar_pillar']['state']=='DIRECT_SCRIPT_ENQUEUED'
assert result['pages']['baitboat_guide']['state']=='DIRECT_SCRIPT_ENQUEUED'
assert result['pages']['unscoped_control']['state']=='DIRECT_SCRIPT_NOT_OBSERVABLE'
assert result['assetIntegrity']=='BYTE_IDENTICAL_TO_REVIEWED_JS'
assert result['ga4DeliveryProven'] is False and result['paidCommissionProven'] is False
assert result['wordpressWrites']==0 and result['syntheticClickEvents']==0
assert calls==[mod.SITE+v for v in mod.PAGES.values()]+[mod.SITE+mod.ASSET+"?ver=1.4.0"]
calls.clear()
def no_script(url,maximum): calls.append(url);return b"<html><body></body></html>"
no=mod.audit(no_script)
assert no['assetIntegrity']=='NO_DIRECT_SCRIPT_TAG_TO_VALIDATE'
assert len(calls)==len(mod.PAGES)
assert all(item['state']=='DIRECT_SCRIPT_NOT_OBSERVABLE' for item in no['pages'].values())
def safe_failed_page(url, maximum):
    if url.endswith(mod.PAGES['alarm_guide']):
        raise ValueError("READONLY_REDIRECT_OR_ORIGIN_DRIFT")
    return one
try:
    mod.audit(safe_failed_page)
    assert False, "failure must not turn into verified instrumentation"
except ValueError as exc:
    assert str(exc)=="PAGE_ALARM_GUIDE_READONLY_REDIRECT_OR_ORIGIN_DRIFT"
def sensitive_failed_page(url, maximum):
    raise ValueError("secret URL ?affiliate_id=123")
try:
    mod.audit(sensitive_failed_page)
    assert False, "failed network cannot pass"
except ValueError as exc:
    assert str(exc)=="PAGE_PIPAKY_GUIDE_ValueError", "never leak arbitrary underlying exceptions"
print("PASS buyer-click audit: five exact GETs, 1 local asset only if used, no GA4/CJ/fake-click proofs")
