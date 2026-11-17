/* Homepage first-party buyer entry measurement. Existing consent via gtag only.
 * No redirect, URL/query payload, visitor identifier, storage or synthetic events.
 */
(() => {
  'use strict';
  if (location.origin !== 'https://na-ryby.eu' || location.pathname !== '/' || location.search) return;

  const buyerRoutes = Object.freeze({
    '/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/': 'carp_pillar',
    '/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/': 'echolot_budget',
    '/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/': 'sonar_practical',
    '/jak-vybrat-zavazeci-lodku/': 'baitboat_picker',
    '/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/': 'chair_hub'
  });
  const advisorPath = '/poradce-vybavy/';

  document.addEventListener('click', event => {
    if (event.isTrusted !== true || typeof window.gtag !== 'function') return;
    if (!event.target || typeof event.target.closest !== 'function') return;
    const link = event.target.closest('a[href]');
    if (!link || !link.href || typeof link.closest !== 'function') return;
    let target;
    try { target = new URL(link.href, location.href); }
    catch { return; }
    if (target.origin !== 'https://na-ryby.eu' || target.search || target.hash
        || target.username || target.password || target.port) return;

    let type = null;
    const nav = link.closest('nav[data-nry-home-problem-shortcuts="v3"]');
    if (nav && Object.prototype.hasOwnProperty.call(buyerRoutes, target.pathname)) {
      // The existing nav has five exact, first-party problem paths. Ignore
      // template links or unrelated content using an otherwise valid URL.
      type = buyerRoutes[target.pathname];
    } else if (target.pathname === advisorPath &&
        link.closest('p[data-nry-home-advisor-entry="v1"]')) {
      type = 'equipment_advisor';
    }
    if (!type) return;
    try {
      window.gtag('event', 'nry_buyer_guide_click', {
        buyer_page: 'homepage', guide_type: type, transport_type: 'beacon'
      });
    } catch { /* Analytics errors never prevent navigation. */ }
  }, {capture: true, passive: true});
})();
