/* Pure offline browser-calculator contract; no network, merchant, or WP calls. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const html = fs.readFileSync(path.join(__dirname, '..', 'assets', 'nry-powerbank-solar-fishing-v1.html'), 'utf8');
const source = html.match(/<script type="module">([\s\S]*?)<\/script>/g);
assert.equal(source?.length, 1, 'one self-contained script');
assert.match(html, /<link rel="canonical" href="https:\/\/na-ryby\.eu\/powerbanka-nebo-solarni-panel-na-ryby\/">/);
assert.match(html, /data-nry-power-guide="v1"/);
assert.match(html, /data-nry-whcalc="v1"/);
assert.doesNotMatch(html, /<img\b|<form\b|<iframe\b|<script[^>]+src=|\bfetch\s*\(|\bsendBeacon\s*\(/i);
assert.doesNotMatch(html, /click-8287231-|click-100927011-|go\.dognet\.com/);
assert.match(html, /Built-in MPPT|vestavěné MPPT/);
assert.match(html, /do 80 W/);
assert.match(html, /aktuálním manuálu/);

const defaults = {
  'phone-w':15, 'phone-h':1, 'lamp-w':5, 'lamp-h':2,
  'other-w':20, 'other-h':4, nights:2,
};
const elements = new Map(Object.entries(defaults).map(([id, value]) => [id, {
  value:String(value), listeners:[], addEventListener(_name, fn) { this.listeners.push(fn); },
}]));
elements.set('wh-result', { textContent:'' });
elements.set('wh-breakdown', { textContent:'' });
const sandbox = {
  document: { getElementById(id) { assert.ok(elements.has(id), `known input ${id}`); return elements.get(id); } },
  Number, Math,
};
vm.runInNewContext(source[0].replace(/^<script type="module">|<\/script>$/g, ''), sandbox, {timeout:1000});
const result = () => elements.get('wh-result').textContent;
const detail = () => elements.get('wh-breakdown').textContent;
const edit = (id, val) => { elements.get(id).value=String(val); elements.get(id).listeners.forEach(fn=>fn()); };
assert.equal(result(), '328 Wh', 'default example 105 Wh/day *2*1.25/.8');
assert.match(detail(), /105 Wh × 2 dny/);
edit('nights',1);
assert.equal(result(), '164 Wh');
assert.match(detail(), /1 den/);
edit('nights',3);
assert.equal(result(), '492 Wh');
edit('other-h',25); // Clamp impossible usage to 24 h/day.
assert.equal(result(), '2 367 Wh');
edit('other-h',0);
edit('phone-w',0);
edit('lamp-w',0);
assert.equal(result(), '0 Wh', 'zero usage does not invent demand');
edit('phone-w','not-a-number');
assert.equal(result(), '0 Wh', 'invalid field fails to zero');
console.log('PASS: source-attributed Wh guide, calculator arithmetic, limits and no outbound tracking');
