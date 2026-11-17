/* Read-only real-click observer for exactly two historical buyer guides.
 * No URL transfer, no calculator inputs, no cookie/storage/new trackers.
 * Uses only existing gtag and its site-controlled consent configuration.
 */
(() => {
  'use strict';
  if (typeof location === 'undefined' || location.origin !== 'https://na-ryby.eu') return;
  const pages = Object.freeze({
    '/nejlepsi-lodni-elektromotory-pro-rybare/': 'motor_electric',
    '/nejlepsi-nafukovaci-cluny-pro-rybare/': 'boat_inflatable'
  });
  if (!Object.prototype.hasOwnProperty.call(pages, location.pathname)) return;
  const page = pages[location.pathname];
  const allowedDestinations = new Set([
    '/Elektromotory-265/', '/Cluny-228/', '/Baterie-nabijecky-k-elektromotorum/'
  ]);

  document.addEventListener('click', event => {
    if (event.isTrusted !== true || typeof window.gtag !== 'function') return;
    const target = event.target;
    if (!target || typeof target.closest !== 'function') return;
    const anchor = target.closest('a[href]');
    if (!anchor || !anchor.href) return;
    let url;
    try { url = new URL(anchor.href, location.href); } catch { return; }
    if (url.protocol !== 'https:' || url.hostname !== 'www.tkqlhce.com'
        || url.port || url.username || url.password
        || url.pathname !== '/click-100927011-12347575'
        || url.hash || [...url.searchParams.keys()].length !== 1
        || url.searchParams.getAll('url').length !== 1) return;
    let destination;
    try { destination = new URL(url.searchParams.get('url')); } catch { return; }
    if (destination.origin !== 'https://www.chytapust.cz'
        || destination.username || destination.password || destination.port
        || destination.search || destination.hash
        || !allowedDestinations.has(destination.pathname)) return;

    // The published CJ property here belongs to a different historical
    // identity. This is only a visitor outbound signal, NOT a credited sale.
    try {
      window.gtag('event', 'nry_buyer_offer_click', {
        buyer_page: page,
        buyer_cta_type: 'cj_other_identity_unverified',
        transport_type: 'beacon'
      });
    } catch { /* Never block the existing link or alter site consent. */ }
  }, {capture: true, passive: true});
})();
