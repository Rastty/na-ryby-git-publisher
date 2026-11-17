/* Existing-consent GA4 first-party guide-click signal, no cookies/storage. */
(() => {
  'use strict';
  if (typeof location === 'undefined' || location.origin !== 'https://na-ryby.eu') return;
  const pages = Object.freeze({
    '/nejlepsi-darky-pro-rybare-tipy-na-nadcasove-a-prakticke-prezentace/': 'gift_evergreen',
    '/novinky-pro-rybare-nejzajimavejsi-vanocni-darky-na-trhu/': 'gift_christmas'
  });
  if (!Object.prototype.hasOwnProperty.call(pages, location.pathname)) return;
  const routes = Object.freeze({
    night: '/celovky-pro-rybare-porovnani-cen-a-vykonu/',
    spinning: '/nejlepsi-privlacove-pruty-do-3-000-kc/',
    feeder: '/nejlepsi-method-feeder-pruty-do-3-500-kc/',
    camp: '/nejlepsi-rybarske-bivaky-do-10-000-kc/'
  });
  document.addEventListener('click', event => {
    if (event.isTrusted !== true || typeof window.gtag !== 'function') return;
    if (!event.target || typeof event.target.closest !== 'function') return;
    const link = event.target.closest('nav[data-nry-gift-buyer="v1"] a[data-nry-gift-path]');
    if (!link || !link.href) return;
    const route = link.getAttribute('data-nry-gift-path');
    if (!Object.prototype.hasOwnProperty.call(routes,route)) return;
    let url;
    try { url=new URL(link.href,location.href); } catch { return; }
    if (url.origin !== 'https://na-ryby.eu' || url.pathname !== routes[route]
        || url.search || url.hash || url.username || url.password || url.port) return;
    try {
      window.gtag('event','nry_buyer_guide_click',{
        buyer_page:pages[location.pathname],guide_type:'gift_'+route,transport_type:'beacon'
      });
    } catch { /* Never prevent navigation if analytics is unavailable. */ }
  }, {capture:true,passive:true});
})();
