/* Four previously public buyer bridges; existing GA4 event contract.
 * Observe only the exact first-party CTA inside its matching rendered panel.
 * No cookies, identifiers, redirect, external URL capture or merchant calls.
 */
(() => {
  'use strict';
  const origin='https://na-ryby.eu';
  const routes=Object.freeze({
    '/recenze-a-zkusenosti-lowrance-echolot-hds-live-7-se-sondou-active-imaging-3v1-baterie-nabijecka-zdarma/':
      ['legacy_hds_live_7','aside[data-nry-legacy-sonar-bridge="v1"][data-nry-legacy-sonar-kind="hds_live_7"]','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/','sonar_pillar'],
    '/lowrance-echolot-hook-4-chirp-gps/':
      ['legacy_hook_4','aside[data-nry-legacy-sonar-bridge="v1"][data-nry-legacy-sonar-kind="hook_4"]','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/','sonar_pillar'],
    '/holdcarp-solarni-panel-foldable-solar-system-20w-recenze/':
      ['holdcarp_solar_20w','aside[data-nry-holdcarp-energy-bridge="v1"][data-nry-holdcarp-model="20W"]','/powerbanka-nebo-solarni-panel-na-ryby/','energy_wh_guide'],
    '/holdcarp-solarni-panel-foldable-solar-system-40w-recenze/':
      ['holdcarp_solar_40w','aside[data-nry-holdcarp-energy-bridge="v1"][data-nry-holdcarp-model="40W"]','/powerbanka-nebo-solarni-panel-na-ryby/','energy_wh_guide']
  });
  if (location.origin!==origin || location.search ||
      !Object.prototype.hasOwnProperty.call(routes,location.pathname)) return;
  if (window.__nryLegacyBuyerHandoffV1===true) return;
  const [buyerPage,selector,path,guideType]=routes[location.pathname];
  window.__nryLegacyBuyerHandoffV1=true;
  document.addEventListener('click', event => {
    if (event.isTrusted!==true || typeof window.gtag!=='function' ||
        !event.target || typeof event.target.closest!=='function') return;
    const link=event.target.closest('a[href]');
    if (!link || typeof link.closest!=='function' || typeof link.getAttribute!=='function') return;
    const panel=link.closest(selector);
    if (!panel || typeof panel.querySelectorAll!=='function') return;
    const panels=document.querySelectorAll(selector);
    const anchors=panel.querySelectorAll('a[href]');
    if (panels.length!==1 || panels[0]!==panel ||
        anchors.length!==1 || anchors[0]!==link ||
        link.getAttribute('href')!==origin+path) return;
    let destination;
    try { destination=new URL(link.href,origin); } catch { return; }
    if (destination.href!==origin+path) return;
    try {
      window.gtag('event','nry_buyer_guide_click',{
        buyer_page:buyerPage,guide_type:guideType,transport_type:'beacon'
      });
    } catch { /* Never obstruct real navigation. Existing consent is unchanged. */ }
  },{capture:true,passive:true});
})();
