/* Source-pinned read-only calculation. No cookies, storage, network or analytics. */
(function (root) {
  'use strict';

  function estimate(current, hours, usablePercent, reservePercent) {
    var values = [current, hours, usablePercent, reservePercent];
    if (!values.every(Number.isFinite) || current < 1 || current > 150 ||
        hours < 0.5 || hours > 12 || usablePercent < 10 || usablePercent > 100 ||
        reservePercent < 0 || reservePercent > 100) return null;
    var need = current * hours * (1 + reservePercent / 100) / (usablePercent / 100);
    return Math.ceil(need / 5) * 5;
  }

  if (typeof module === 'object' && module.exports) module.exports = estimate;
  if (!root.document) return;
  var sections = root.document.querySelectorAll('[data-nry-motor-battery="v1"]');
  Array.prototype.forEach.call(sections, function (section) {
    var form = section.querySelector('[data-nry-motor-battery-form="v1"]');
    var output = section.querySelector('[data-nry-motor-battery-result="v1"]');
    if (!form || !output) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var fields = ['current', 'hours', 'usable', 'reserve'].map(function (name) {
        var field = form.elements.namedItem(name);
        return field && field.value.trim() !== '' ? Number(field.value) : NaN;
      });
      var capacity = estimate(fields[0], fields[1], fields[2], fields[3]);
      output.textContent = capacity === null
        ? 'Zkontrolujte zadané hodnoty a skutečný odběr motoru podle výrobce.'
        : 'Orientačně zvažte alespoň ' + capacity + ' Ah jmenovité kapacity při zadaných podmínkách. Ověřte omezení konkrétní baterie u výrobce.';
    });
  });
})(typeof window !== 'undefined' ? window : {});
