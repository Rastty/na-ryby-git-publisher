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
    '/zavazeci-lodky-pro-rybare/':'baitboat_guide',
    '/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/':'carp_pillar',
    '/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/':'chair_hub',
    '/jak-vybrat-rybarske-kreslo/':'chair_evergreen'
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
    // Existing chair guide #11901 contains legacy HTTP CJ anchors. Do not
    // follow or rewrite them here; observe only verified-shape real user clicks.
    // Other pages and non-CJ insecure URLs remain blocked.
    const chairHttpCJ = page === 'chair_evergreen' &&
      url.protocol === 'http:' && networkHosts.has(url.hostname) &&
      (/^\/click-8287231-[0-9]{6,16}$/.test(url.pathname) ||
       /^\/click-[0-9]{6,12}-[0-9]{6,16}-[0-9]{9,18}$/.test(url.pathname));
    if ((url.protocol !== 'https:' && !chairHttpCJ) ||
        url.username || url.password || url.port) return;
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
    // Read-only navigation measurement across the five existing sonar guides.
    // The routes below are observed live internal guide links, never new
    // products, merchants, stock claims or affiliate destinations.
    if (['sonar_pillar','echolot_budget','sonar_boat','sonar_historic'].includes(page) &&
        url.origin === 'https://na-ryby.eu' && !url.search && !url.hash) {
      const pathRole = {
        '/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/':'sonar_pillar',
        '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/':'sonar_practical',
        '/deeper-pro-vs-pro-2-vs-chirp-2-ktery-se-vyplati/':'sonar_bank',
        '/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/':'sonar_boat',
        '/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/':'sonar_budget',
        '/poradce-vybavy/':'equipment_advisor'
      };
      const supported = {
        sonar_pillar: ['sonar_practical','sonar_bank','sonar_boat','sonar_budget','equipment_advisor'],
        echolot_budget: ['sonar_pillar','sonar_practical','sonar_boat','equipment_advisor'],
        sonar_boat: ['sonar_pillar','sonar_budget','equipment_advisor'],
        sonar_historic: ['sonar_pillar','sonar_boat','sonar_budget','equipment_advisor']
      };
      const guideType = Object.prototype.hasOwnProperty.call(pathRole,url.pathname)
        ? pathRole[url.pathname] : null;
      if (guideType && supported[page].includes(guideType)) {
        try {
          window.gtag('event','nry_buyer_guide_click',{
            buyer_page:page,guide_type:guideType,transport_type:'beacon'
          });
        } catch { /* Never interfere with navigation or consent. */ }
        return;
      }
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
    // Source-verified chair buyer journey: track only exact canonical guide
    // handoffs. No destination URL, product, visitor or query data is sent.
    if (url.origin === 'https://na-ryby.eu' && !url.search && !url.hash) {
      const chairGuideType = page === 'chair_hub' &&
        url.pathname === '/jak-vybrat-rybarske-kreslo/' ? 'chair_evergreen'
        : page === 'chair_evergreen' &&
          url.pathname === '/poradce-vybavy/' ? 'equipment_advisor' : null;
      if (chairGuideType) {
        try {
          window.gtag('event','nry_buyer_guide_click',{
            buyer_page:page,guide_type:chairGuideType,transport_type:'beacon'
          });
        } catch { /* Keep navigation and consent as configured. */ }
        return;
      }
    }
    let kind = null;
    if (url.origin === 'https://na-ryby.eu' &&
        ((url.pathname === '/signalizatory' || url.pathname === '/signalizatory/') ||
         (page === 'chair_hub' &&
          (url.pathname === '/kresla' || url.pathname === '/kresla/') &&
          !url.search && !url.hash))) {
      kind = 'internal_affiliate_redirect';
    } else if (networkHosts.has(url.hostname) &&
               /^\/click-8287231-[0-9]{6,16}$/.test(url.pathname)) {
      kind = url.protocol === 'http:' ? 'cj_http_existing' : 'cj_product_link';
    } else if (page === 'chair_evergreen' && networkHosts.has(url.hostname) &&
               /^\/click-[0-9]{6,12}-[0-9]{6,16}-[0-9]{9,18}$/.test(url.pathname) &&
               !url.pathname.startsWith('/click-8287231-')) {
      // Six existing older CJ links have a different publisher identity.
      // Count the visitor handoff, never claim commission ownership.
      kind = url.protocol === 'http:' ? 'cj_legacy_http_unverified'
        : 'cj_legacy_unverified';
    } else if (page === 'echolot_budget' &&
               url.hostname === 'go.dognet.com' &&
               (url.pathname.length > 1 || url.search.length > 1)) {
      // Current sonar budget guide contains genuine Dognet destinations.
      // Classify the outbound action, NOT a purchase or merchant conversion.
      kind = 'dognet_outbound';
    } else if ((page === 'echolot_budget' || page === 'sonar_pillar' ||
                page === 'carp_pillar') &&
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
