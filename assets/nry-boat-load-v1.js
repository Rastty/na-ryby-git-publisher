/* Site-local, form-only arithmetic. Never calls analytics, merchants, storage, or network. */
(function () {
  'use strict';
  function estimate(values) {
    const rules = { capacity: [1, 2500], people: [1, 1000], motor: [0, 800], battery: [0, 800], gear: [0, 800], other: [0, 800] };
    for (const [key, limits] of Object.entries(rules)) {
      const value = values[key];
      if (typeof value !== 'number' || !Number.isFinite(value) || value < limits[0] || value > limits[1]) {
        return null;
      }
    }
    const used = values.people + values.motor + values.battery + values.gear + values.other;
    const remaining = values.capacity - used;
    return { used: Math.round(used * 10) / 10, remaining: Math.round(remaining * 10) / 10 };
  }
  if (typeof module !== 'undefined' && module.exports) module.exports = { estimate };
  if (typeof document === 'undefined') return;
  const form = document.querySelector('[data-nry-boat-load-form="v1"]');
  const output = document.querySelector('[data-nry-boat-load-result="v1"]');
  if (!form || !output) return;
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
      output.textContent = 'Zkontrolujte prosím všechna pole, zvlášť údaj výrobce.';
      return;
    }
    const input = {};
    for (const name of ['capacity', 'people', 'motor', 'battery', 'gear', 'other']) {
      const field = form.elements.namedItem(name);
      if (!field || String(field.value).trim() === '') {
        output.textContent = 'Vyplňte všechna pole včetně hmotnosti lidí.';
        return;
      }
      input[name] = Number(field.value);
    }
    const result = estimate(input);
    if (!result) {
      output.textContent = 'Zkontrolujte platné hmotnosti v kilogramech.';
      return;
    }
    const fmt = value => value.toLocaleString('cs-CZ', { maximumFractionDigits: 1 });
    if (result.remaining < 0) {
      output.textContent = 'Celková zátěž ' + fmt(result.used) + ' kg: překračujete uvedené maximum o ' +
        fmt(-result.remaining) + ' kg. S touto sestavou nevyplouvejte.';
    } else if (result.remaining === 0) {
      output.textContent = 'Celková zátěž ' + fmt(result.used) + ' kg: jste přesně na uvedeném maximu bez hmotnostní rezervy.';
    } else {
      output.textContent = 'Celková zátěž ' + fmt(result.used) + ' kg. Do uvedeného maxima zbývá ' +
        fmt(result.remaining) + ' kg. To samo o sobě nepotvrzuje bezpečnost plavby.';
    }
  });
})();
