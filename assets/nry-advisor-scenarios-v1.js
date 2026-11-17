/* Optional advisor shortcuts. Uses the existing engine and consent-aware analytics.
 * No direct merchant link, affiliate rewriting, new endpoint or synthetic traffic.
 */
(() => {
  'use strict';
  if (location.origin !== 'https://na-ryby.eu' ||
      location.pathname !== '/poradce-vybavy/') return;
  if (window.__nryAdvisorScenariosV1 === true) return;

  const SCENARIOS = Object.freeze([
    {id:'carp_first',label:'Kapr: první výprava',values:{
      style:'kapr',water:'mala',travel:'pesky',experience:'zacatecnik',budget:'3000'}},
    {id:'feeder_river',label:'Feeder: chytání na řece',values:{
      style:'feeder',water:'reka',travel:'pesky',experience:'zacatecnik',budget:'5000'}},
    {id:'spinning_light',label:'Přívlač: lehká sestava',values:{
      style:'privlac',water:'mala',travel:'pesky',experience:'zacatecnik',budget:'5000'}}
  ]);
  const EXPECTED = Object.freeze({
    style:['kapr','feeder','privlac'], water:['mala','reka','velka'],
    travel:['pesky','auto'], experience:['zacatecnik','zkuseny'],
    budget:['3000','5000','10000']
  });

  function fieldsMatch(form) {
    return Object.keys(EXPECTED).every(name => {
      const select = form.elements?.[name];
      if (!select || select.tagName !== 'SELECT' || !select.options) return false;
      const actual = Array.from(select.options, option => option.value);
      return actual.length === EXPECTED[name].length &&
        EXPECTED[name].every((value, i) => actual[i] === value);
    });
  }

  function start() {
    const root = document.querySelector('.nry-setup');
    const form = root?.querySelector('form.nry-setup__form');
    const fields = form?.querySelector('.nry-fields');
    const panel = root?.querySelector('[data-nry-results]');
    if (!form || !fields || !panel || !fieldsMatch(form) ||
        root.querySelector('[data-nry-advisor-scenarios="v1"]')) return;

    const section = document.createElement('section');
    section.className = 'nry-advisor-scenarios';
    section.setAttribute('data-nry-advisor-scenarios', 'v1');
    section.setAttribute('aria-label', 'Rychlé scénáře výběru rybářské sestavy');
    const heading = document.createElement('h4');
    heading.textContent = 'Začněte jedním kliknutím';
    const subtitle = document.createElement('p');
    subtitle.textContent = 'Vyberte typickou situaci. Předvyplněné hodnoty můžete kdykoliv upravit.';
    const choices = document.createElement('div');
    choices.className = 'nry-advisor-scenarios__choices';

    SCENARIOS.forEach(scenario => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'nry-advisor-scenarios__choice';
      button.textContent = scenario.label;
      button.addEventListener('click', event => {
        if (event.isTrusted !== true || !fieldsMatch(form)) return;
        Object.entries(scenario.values).forEach(([name, value]) => {
          form.elements[name].value = value;
        });
        // Never leave a previous recommendation visible after changing inputs.
        panel.hidden = true;
        panel.replaceChildren();
        const hint = root.querySelector('[data-nry-refresh]');
        if (hint) hint.hidden = true;
        try {
          if (typeof window.gtag === 'function') {
            window.gtag('event', 'nry_advisor_scenario_select', {
              scenario_type: scenario.id, transport_type: 'beacon'
            });
          }
        } catch { /* Analytics must never block a recommendation. */ }
        // Use the actual existing form handler; do not duplicate its product
        // or affiliate-selection logic. requestSubmit does not navigate away.
        if (typeof form.requestSubmit === 'function') form.requestSubmit();
        else form.querySelector('button[type="submit"]')?.focus();
      });
      choices.append(button);
    });
    section.append(heading, subtitle, choices);
    fields.before(section);
    window.__nryAdvisorScenariosV1 = true;
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
