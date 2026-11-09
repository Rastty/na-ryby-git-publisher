/**
 * Exact first-party reel dashboard -> advice/buyer-guide click measurement.
 * Only genuine clicks. Never captures href, search, user IDs, products or prices.
 * Existing site gtag and consent configuration determine analytics delivery.
 */
(() => {
  'use strict';
  const ORIGIN = 'https://na-ryby.eu';
  const PAGE = '/navijaky-dashboard/';
  const ROLE = Object.freeze({
    '/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/': 'reel_guide',
    '/nejlepsi-feederove-navijaky-srovnani/': 'feeder_reels',
    '/pece-o-navijak-po-sezone-jak-ho-neznicit-spatnym-mazanim/': 'reel_care',
    '/poradce-vybavy/': 'advisor'
  });
  const SITUATIONS = Object.freeze({
    '/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/': 'beginner_reel',
    '/nejlepsi-privlacove-navijaky-jak-vybrat/': 'spinning_reels',
    '/nejlepsi-feederove-navijaky-srovnani/': 'feeder_situations',
    '/nejlepsi-kaprove-navijaky/': 'carp_reels'
  });
  if (location.origin !== ORIGIN || location.pathname !== PAGE ||
      location.search || location.hash || window.__nryReelHandoffV1 === true) return;

  function init() {
    const nav = document.querySelector('nav[data-nry-reel-nextsteps="v1"]');
    if (!nav || window.__nryReelHandoffV1 === true) return;
    const situationNav = document.querySelector('nav[data-nry-reel-situations="v1"]');
    const destinations = [[nav, ROLE]];
    if (situationNav && situationNav !== nav) destinations.push([situationNav, SITUATIONS]);
    window.__nryReelHandoffV1 = true;
    for (const [container, roles] of destinations) container.addEventListener('click', event => {
      if (event?.isTrusted !== true || typeof event?.target?.closest !== 'function') return;
      const link = event.target.closest('a[href]');
      if (!link || !container.contains(link) || typeof link.getAttribute !== 'function') return;
      const raw = link.getAttribute('href');
      if (typeof raw !== 'string' || raw.length > 240 || !raw) return;
      let url;
      try { url = new URL(raw, ORIGIN); } catch { return; }
      if (url.origin !== ORIGIN || url.username || url.password ||
          url.search || url.hash || url.port) return;
      const guide = roles[url.pathname];
      if (!guide || typeof window.gtag !== 'function') return;
      try {
        window.gtag('event', 'nry_reel_guide_click', {
          source_page: 'reel_dashboard', guide_type: guide, transport_type: 'beacon'
        });
      } catch {
        // A broken analytics setup must not change navigation.
      }
    }, {capture: true, passive: true});
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, {once:true});
  } else {
    init();
  }
})();
