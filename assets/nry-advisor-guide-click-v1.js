/**
 * Na-Ryby Advisor V4: genuine internal-guide handoff measurement.
 *
 * This is a strictly read-only enhancement: no WordPress writes, redirects,
 * affiliate link changes, cookies, new collection endpoints or synthetic
 * clicks. No user answer values, raw URLs, query strings or identifying data
 * are collected. Existing gtag/GTM consent handling owns actual delivery.
 */
(() => {
  'use strict';
  const ORIGIN = 'https://na-ryby.eu';
  const ADVISOR = '/poradce-vybavy/';
  const EVENT = 'nry_advisor_guide_click';
  const GUIDE_ROLES = Object.freeze({
    '/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/':'carp_rod',
    '/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/':'reel',
    '/nejlepsi-privlacove-navijaky-jak-vybrat/':'spinning_reel',
    '/nejlepsi-feederove-navijaky-srovnani/':'feeder_reel'
  });

  if (location.origin !== ORIGIN || location.pathname !== ADVISOR ||
      location.search || location.hash) return;
  if (window.__nryAdvisorGuideClickV1 === true) return;

  function start() {
    const root = document.querySelector('.nry-setup');
    if (!root || !root.querySelector('[data-nry-results]')) return;
    if (window.__nryAdvisorGuideClickV1 === true) return;
    window.__nryAdvisorGuideClickV1 = true;

    // Exact feeder result: prefer the more relevant existing comparison,
    // retain the generic guide as a secondary link, never touch products.
    // Submit bubbles from the form only AFTER its existing inline renderer.
    // An editor-changed result/anchor must win: fail closed.
    root.addEventListener('submit', event => {
      if (event?.isTrusted !== true) return;
      const form = root.querySelector('form');
      const panel = root.querySelector('[data-nry-results]');
      if (!form || event.target !== form || !panel || panel.hidden !== false ||
          form.elements?.style?.value !== 'feeder') return;
      const headline = panel.querySelector('.nry-result__title');
      const links = panel.querySelector('.nry-result__links');
      if (!headline || !/^Feeder\s*[–-]\s*sestava pro /u.test(headline.textContent || '') ||
          !links) return;
      const anchors = links.querySelectorAll('a[href]');
      if (anchors.length !== 1) return;
      const old = anchors[0];
      const original = ORIGIN + '/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/';
      if (old.getAttribute('href') !== original ||
          old.textContent !== 'Průvodce výběrem rybářského navijáku →') return;
      const supplemental = document.createElement('a');
      supplemental.href = original;
      supplemental.textContent = 'Obecný průvodce výběrem navijáku →';
      old.href = ORIGIN + '/nejlepsi-feederove-navijaky-srovnani/';
      old.textContent = 'Feederové navijáky: praktické srovnání →';
      links.append(supplemental);
    });

    root.addEventListener('click', event => {
      if (event?.isTrusted !== true ||
          typeof event?.target?.closest !== 'function') return;
      const anchor = event.target.closest('[data-nry-results] .nry-result__links a[href]');
      if (!anchor || typeof anchor.getAttribute !== 'function') return;
      const panel = anchor.closest('[data-nry-results]');
      if (!panel || panel.hidden !== false) return;
      let guideType;
      try {
        const raw = anchor.getAttribute('href');
        if (typeof raw !== 'string' || raw.length > 200 || !raw) return;
        const url = new URL(raw, ORIGIN);
        if (url.origin !== ORIGIN || url.username || url.password ||
            url.search || url.hash) return;
        guideType = GUIDE_ROLES[url.pathname];
      } catch {
        return;
      }
      if (!guideType) return;
      const props = {
        advisor_version: 4,
        guide_type: guideType,
        destination_kind: 'internal_buying_guide'
      };
      try {
        if (typeof window.gtag === 'function') {
          window.gtag('event', EVENT, props);
        } else if (Array.isArray(window.dataLayer)) {
          window.dataLayer.push({event: EVENT, ...props});
        }
      } catch {
        // Measurement never interrupts navigation or the advisor.
      }
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }
})();
