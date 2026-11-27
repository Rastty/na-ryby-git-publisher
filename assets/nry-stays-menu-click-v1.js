/* Genuine sitewide stay-choice navigation only. Reuses existing gtag/consent
 * and nry_buyer_guide_click; no URLs, query strings, cookies, identities,
 * local storage, redirects, affiliate mutations or synthetic events.
 */
(() => {
  'use strict';
  const ORIGIN='https://na-ryby.eu';
  const HUB=ORIGIN+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
  const ROUTES=Object.freeze({
    [ORIGIN+'/rybarska-chata-cyklochata-vranovska-prehrada/']:'cyklochata',
    [ORIGIN+'/chata-rybarsky-wellness-srub/']:'wellness_srub'
  });
  if (location.origin!==ORIGIN || window.__nryStaysNavClickV1===true) return;
  window.__nryStaysNavClickV1=true;
  document.addEventListener('click', event => {
    if (event.isTrusted!==true || typeof window.gtag!=='function'
        || !event.target || typeof event.target.closest!=='function') return;
    const link=event.target.closest('a[href]');
    if (!link || typeof link.closest!=='function'
        || typeof link.getAttribute!=='function') return;
    const child=link.closest('li.nry-stays-menu-choice-v1');
    if (!child || typeof child.querySelectorAll!=='function') return;
    const anchors=child.querySelectorAll('a[href]');
    if (anchors.length!==1 || anchors[0]!==link) return;
    const raw=link.getAttribute('href');
    if (!Object.prototype.hasOwnProperty.call(ROUTES,raw)) return;
    const parent=child.parentElement?.closest?.('li.menu-item-has-children');
    if (!parent || typeof parent.querySelector!=='function') return;
    const parentLink=parent.querySelector('a[href]');
    if (!parentLink || parentLink.getAttribute('href')!==HUB) return;
    let url;
    try { url=new URL(link.href,ORIGIN); } catch { return; }
    if (url.href!==raw || url.origin!==ORIGIN || url.search || url.hash
        || url.username || url.password || url.port) return;
    try {
      window.gtag('event','nry_buyer_guide_click',{
        buyer_page:'sitewide_stays_menu',
        guide_type:ROUTES[raw],
        transport_type:'beacon'
      });
    } catch { /* Never interrupt a genuine navigation. */ }
  },{capture:true,passive:true});
})();
