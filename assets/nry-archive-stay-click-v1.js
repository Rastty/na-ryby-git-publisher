/* Real first-party accommodation decision clicks from TWO pre-existing, verified 2017 archives.
 * Only trusted user clicks on exact, source-bound six- or three-link panel. Reuse existing
 * consent-aware gtag/nry_buyer_guide_click; no new identifiers, cookies, merchant
 * calls, affiliate URL rewrites, redirects, storage or synthetic events.
 */
(() => {
  'use strict';
  const ORIGIN='https://na-ryby.eu';
  const ROUTES=Object.freeze({
    '/ubytovani-2/':Object.freeze({
      selector:'section[data-nry-legacy-stays-entry="v1"]',
      role:'legacy_stays_archive_2017',
      choices:Object.freeze([
        ['stays_hub','/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'],
        ['slapy_stays','/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/'],
        ['lipno_stays','/rybareni-s-ubytovanim-jihocesky-kraj/'],
        ['vranov_group','/rybarska-chata-cyklochata-vranovska-prehrada/'],
        ['vranov_wellness','/chata-rybarsky-wellness-srub/'],
        ['private_water_stays','/nejlepsi-soukrome-reviry-s-ubytovanim-v-ceske-republice-zazitek-ktery-stoji-za-to/']
      ])
    }),
    '/rybareni-s-ubytovanim-jihomoravsky-kraj/':Object.freeze({
      selector:'section[data-nry-jm-archive-stays="v1"]',
      role:'south_moravia_stays_2017',
      choices:Object.freeze([
        ['cyklochata','/rybarska-chata-cyklochata-vranovska-prehrada/'],
        ['wellness_srub','/chata-rybarsky-wellness-srub/'],
        ['stays_hub','/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/']
      ])
    })
  });
  if (location.origin!==ORIGIN || location.search || location.hash ||
      !Object.prototype.hasOwnProperty.call(ROUTES,location.pathname) ||
      window.__nryArchiveStayClickV1===true) return;
  const cfg=ROUTES[location.pathname];
  if (!document || typeof document.querySelectorAll!=='function') return;
  const panels=document.querySelectorAll(cfg.selector);
  if (panels.length!==1) return;
  const panel=panels[0];
  const anchors=Array.from(panel.querySelectorAll('a[href]'));
  const urls=anchors.map(link => link.getAttribute('href'));
  const expected=cfg.choices.map(row => ORIGIN+row[1]);
  // One exact homepage link may be introduced by TagDiv, as independently
  // observed on the South Moravia panel; never authorize arbitrary extras.
  const allowed=(
    urls.length===expected.length && urls.every((url,i)=>url===expected[i])
  )||(
    urls.length===expected.length+1 && urls[0]===ORIGIN+'/' &&
    urls.slice(1).every((url,i)=>url===expected[i])
  );
  if (!allowed) return;
  window.__nryArchiveStayClickV1=true;
  document.addEventListener('click', event => {
    if (event.isTrusted!==true || typeof window.gtag!=='function' ||
        !event.target || typeof event.target.closest!=='function') return;
    const link=event.target.closest('a[href]');
    if (!link || !anchors.includes(link) ||
        typeof link.closest!=='function' || link.closest(cfg.selector)!==panel) return;
    const href=link.getAttribute('href');
    const target=cfg.choices.find(row => ORIGIN+row[1]===href);
    if (!target) return; // TagDiv homepage context never emits a buyer event.
    let parsed;
    try { parsed=new URL(link.href,ORIGIN); } catch { return; }
    if (parsed.href!==href || parsed.origin!==ORIGIN ||
        parsed.search || parsed.hash || parsed.username || parsed.password || parsed.port) return;
    try {
      window.gtag('event','nry_buyer_guide_click',{
        buyer_page:cfg.role,guide_type:target[0],transport_type:'beacon'
      });
    } catch { /* Analytics must never interrupt genuine navigation. */ }
  },{capture:true,passive:true});
})();
