'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const fs = require('node:fs');
const estimate = require('../assets/nry-motor-battery-v1.js');

// A=20, h=2, usable=80%, reserve=30% => 65 Ah.
assert.equal(estimate(20, 2, 80, 30), 65);
assert.equal(estimate(25, 1, 100, 0), 25);
assert.equal(estimate(25, 1, 50, 30), 65);
assert.equal(estimate(1, 0.5, 100, 0), 5);
for (const input of [
  [0, 2, 80, 30], [151, 2, 80, 30], [20, 0, 80, 30],
  [20, 13, 80, 30], [20, 2, 0, 30], [20, 2, 101, 30],
  [20, 2, 80, -1], [20, 2, 80, 101], [NaN, 2, 80, 30],
  [Infinity, 2, 80, 30],
]) assert.equal(estimate(...input), null);

const js = fs.readFileSync(path.join(__dirname,'../assets/nry-motor-battery-v1.js'),'utf8');
for (const forbidden of ['localStorage','sessionStorage','fetch(', 'XMLHttpRequest','sendBeacon','gtag(']) {
  assert.ok(!js.includes(forbidden), `unexpected tracking / network primitive: ${forbidden}`);
}
console.log('PASS battery Ah arithmetic, validation, offline-only behavior');
