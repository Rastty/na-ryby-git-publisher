"""Offline bounds and raw HTML slice identity; no network."""
import importlib.util
from pathlib import Path
from hashlib import sha256

src = Path(__file__).resolve().parents[1] / 'scripts/diagnose-jihocesky-13088-frontend.py'
spec = importlib.util.spec_from_file_location('jcdigest', src)
m = importlib.util.module_from_spec(spec)
spec.loader.exec_module(m)
inner = ('\n<div class="su-table su-table-alternate">'
         '<div id="su-post-17530"></div><div id="su-post-17753"></div>'
         + '<td>Revíry</td><td>Ubytování</td>' + ('x' * 7500) + '</div>\n')
source = '<html><div class="td-post-content tagdiv-type">' + inner + '</div></html>'
extracted = m.ContentDiv(source).extract()
assert extracted == inner
assert sha256(extracted.encode()[:512]).hexdigest() != '0' * 64
try:
    m.ContentDiv('<div class="wrong">' + inner + '</div>').extract()
except AssertionError:
    pass
else:
    raise AssertionError('UNSCOPED_CONTAINER_ACCEPTED')
try:
    m.ContentDiv(source + source).extract()
except AssertionError:
    pass
else:
    raise AssertionError('DUPLICATE_CONTAINER_ACCEPTED')
print('JIHOCESKY_13088_READONLY_FINGERPRINT_OFFLINE_OK')
