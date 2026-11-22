/* Two verified wellness guides: observe genuine first-party navigation only.
 * Reuse existing gtag consent policy; do not create identifiers, cookies,
 * merchant requests, redirects, synthetic clicks or link mutations.
 */
(() => {
  'use strict';
  const ORIGIN='https://na-ryby.eu';
  const routes=Object.freeze({
    '/wellness-a-rybolov-v-jednom-luxusni-ubytovani-pro-narocne-rybare/':
      'wellness_editorial_61382',
    '/wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice/':
      'private_wellness_69482'
  });
  const targets=Object.freeze({
    srub:'/chata-rybarsky-wellness-srub/',
    stays:'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
  });
  if (location.origin!==ORIGIN || location.search ||
      !Object.prototype.hasOwnProperty.call(routes,location.pathname)) return;
  const buyerPage=routes[location.pathname];
  document.addEventListener('click', event => {
    if (event.isTrusted!==true || typeof window.gtag!=='function'
        || !event.target || typeof event.target.closest!=='function') return;
    const link=event.target.closest('a[data-nry-wellness-path][href]');
    if (!link || !link.href || typeof link.closest!=='function') return;
    if (!link.closest('section[data-nry-wellness-evergreen-handoff="v1"]')) return;
    const key=link.getAttribute('data-nry-wellness-path');
    if (!Object.prototype.hasOwnProperty.call(targets,key)) return;
    let destination;
    try { destination=new URL(link.href,location.origin); } catch { return; }
    if (destination.origin!==ORIGIN || destination.pathname!==targets[key]
        || destination.search || destination.hash
        || destination.username || destination.password || destination.port) return;
    try {
      window.gtag('event','nry_buyer_guide_click',{
        buyer_page:buyerPage,guide_type:key,transport_type:'beacon'
      });
    } catch { /* Analytics failure must not interfere with navigation. */ }
  }, {capture:true,passive:true});
})();
