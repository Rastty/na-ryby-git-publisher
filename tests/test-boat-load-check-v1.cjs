const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { estimate } = require('../assets/nry-boat-load-v1.js');
const data = { capacity: 390, people: 175, motor: 12, battery: 22, gear: 65, other: 15 };
assert.deepEqual(estimate(data), { used: 289, remaining: 101 });
assert.deepEqual(estimate({ ...data, capacity: 289 }), { used: 289, remaining: 0 });
assert.deepEqual(estimate({ ...data, capacity: 280 }), { used: 289, remaining: -9 });
assert.deepEqual(estimate({ ...data, motor: 12.5 }), { used: 289.5, remaining: 100.5 });
for (const invalid of [undefined, NaN, Infinity, -1, '12', null, 900]) {
  assert.equal(estimate({ ...data, battery: invalid }), null);
}
assert.equal(estimate({ ...data, capacity: 0 }), null);
assert.equal(estimate({ ...data, people: 0 }), null);
assert.equal(estimate({ ...data, capacity: 2501 }), null);
const script = fs.readFileSync(path.join(__dirname, '../assets/nry-boat-load-v1.js'), 'utf8');
for (const danger of ['fetch(', 'XMLHttpRequest', 'sendBeacon', 'localStorage', 'sessionStorage', 'document.cookie', 'gtag(', 'window.open(']) {
  assert.ok(!script.includes(danger), 'No hidden measurement or outbound side effect: ' + danger);
}
assert.ok(script.includes("event.preventDefault()"), 'Do not navigate on submit');
assert.ok(script.includes('textContent'), 'Result is rendered as text, not HTML');
console.log('PASS boat payload arithmetic, boundary guards, no network/analytics/storage');
