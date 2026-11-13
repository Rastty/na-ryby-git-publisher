/* Na-Ryby buyer offer click, v1. No URL alteration, no new tracker.
 * Fires only on a genuine browser click and only through the site's already
 * configured gtag (thus existing consent settings apply). Never sends hrefs,
 * link query params, user identities or unique click IDs. Zero synthetic events.
 */
(() => {
  'use strict';
  // This role map must match the PHP post-id/permalink allowlist exactly.
  // Advisor /poradce-vybavy/ has independent nry_advisor_product_click events.
  const allowedPages = Object.freeze({
    '/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/':'pipaky_guide',
    '/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/':'alarm_guide',
    '/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/':'pipaky_beginner',
    '/pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu/':'pipaky_advanced',
    '/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/':'pipaky_receiver',
    '/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/':'echolot_budget',
    '/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/':'sonar_pillar',
    '/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/':'sonar_boat',
    '/porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023/':'sonar_historic',
    '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/':'sonar_practical',
    '/zavazeci-lodky-pro-rybare/':'baitboat_guide'
  });
  if (location.origin !== 'https://na-ryby.eu' ||
      !Object.prototype.hasOwnProperty.call(allowedPages, location.pathname)) return;
  const page = allowedPages[location.pathname];
  const networkHosts = new Set([
    'www.kqzyfj.com','www.tkqlhce.com','www.jdoqocy.com',
    'www.dpbolvw.net','www.anrdoezrs.net'
  ]);
  document.addEventListener('click', event => {
    // Browser-generated user interaction only. Never send tests or bot clicks
    // to production GA4 from automated validation.
    if (event.isTrusted !== true || typeof window.gtag !== 'function') return;
    const element = event.target;
    if (!element || typeof element.closest !== 'function') return;
    const link = element.closest('a[href]');
    if (!link || !link.href) return;
    let url;
    try { url = new URL(link.href, location.href); }
    catch { return; }
    if (url.protocol !== 'https:' || url.username || url.password || url.port) return;
    // Count only the existing, source-pinned buyer callout, not every link
    // to the picker; the approved picker itself is never instrumented.
    if (page === 'baitboat_guide' &&
        url.origin === 'https://na-ryby.eu' &&
        url.pathname === '/jak-vybrat-zavazeci-lodku/' &&
        !url.search && !url.hash &&
        typeof link.closest === 'function' &&
        link.closest('aside[data-nry-baitboat-choice-link="v1"]')) {
      try {
        window.gtag('event','nry_buyer_guide_click',{
          buyer_page:page,guide_type:'baitboat_picker',transport_type:'beacon'
        });
      } catch { /* Preserve navigation; do not alter consent. */ }
      return;
    }
    // Measure a genuinely chosen first-party guide path, not a sale.
    // Two scoped sonar routes share one established, consent-gated event name.
    if (page === 'sonar_pillar' &&
        url.origin === 'https://na-ryby.eu' &&
        url.pathname === '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/' &&
        !url.search && !url.hash) {
      try {
        window.gtag('event','nry_buyer_guide_click',{
          buyer_page:page,guide_type:'sonar_practical',transport_type:'beacon'
        });
      } catch { /* Never interfere with navigation or consent. */ }
      return;
    }
    // Genuine first-party buyer-guide navigation, not an affiliate conversion.
    if (page === 'sonar_practical' &&
        url.origin === 'https://na-ryby.eu' &&
        url.pathname === '/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/' &&
        !url.search && !url.hash) {
      try {
        window.gtag('event','nry_buyer_guide_click',{
          buyer_page:page,guide_type:'sonar_boat',transport_type:'beacon'
        });
      } catch { /* Keep navigation intact; never override consent. */ }
      return;
    }
    let kind = null;
    if (url.origin === 'https://na-ryby.eu' &&
        (url.pathname === '/signalizatory' || url.pathname === '/signalizatory/')) {
      kind = 'internal_affiliate_redirect';
    } else if (networkHosts.has(url.hostname) &&
               /^\/click-8287231-[0-9]{6,16}$/.test(url.pathname)) {
      kind = 'cj_product_link';
    } else if (page === 'echolot_budget' &&
               url.hostname === 'go.dognet.com' &&
               (url.pathname.length > 1 || url.search.length > 1)) {
      // Current sonar budget guide contains genuine Dognet destinations.
      // Classify the outbound action, NOT a purchase or merchant conversion.
      kind = 'dognet_outbound';
    } else if ((page === 'echolot_budget' || page === 'sonar_pillar') &&
               url.pathname.length > 1 && url.hostname === 'www.chytapust.cz') {
      // Direct merchant links do not prove an affiliate attribution.
      kind = 'merchant_outbound';
    }
    if (!kind) return;
    // Existing gtag/GTM consent is never overridden or initialized here.
    // The destination/navigation and the anchor's existing behavior are intact.
    try {
      window.gtag('event','nry_buyer_offer_click',{
        buyer_page:page,buyer_cta_type:kind,transport_type:'beacon'
      });
    } catch { /* Analytics failures must never block navigation. */ }
  }, {capture:true,passive:true});
})();
