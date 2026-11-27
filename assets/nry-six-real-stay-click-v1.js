/* Genuine clicks only to six exact first-party stay-article offer blocks.
 * Reuses existing consent-aware gtag and nry_buyer_offer_click event.
 * Never sends an affiliate URL, query, user identifier or synthetic click.
 */
(() => {
  'use strict';
  const ORIGIN='https://na-ryby.eu';
  const URLS=Object.freeze({
  "cyklo": "https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcyklochata",
  "srub": "https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub",
  "more": "https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcz%2Fpro-dva%2Fpro-rybare%2Fwellness",
  "lipno": "https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F"
});
  const ROUTES=Object.freeze({
    'rybarska-chata-bitov':Object.freeze({cohort:'stay_bitov_alternatives',offers:Object.freeze(["cyklo","srub"])}),
    'rybareni-a-romantika-tipy-na-pobyty-pro-dva-zamilovane-rybare':Object.freeze({cohort:'stay_romance_two',offers:Object.freeze(["srub","more"])}),
    'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice':Object.freeze({cohort:'stay_wellness_private',offers:Object.freeze(["srub","more"])}),
    'rybarske-dobrodruzstvi-soukrome-rybareni-s-ubytovanim-v-ceske-prirode':Object.freeze({cohort:'stay_private_adventure',offers:Object.freeze(["more","cyklo"])}),
    'rybareni-s-ubytovanim-v-ceske-republice-doporucene-lokality':Object.freeze({cohort:'stay_cz_regions',offers:Object.freeze(["lipno","more"])}),
    'tipy-na-skvele-mista-pro-rybareni-s-ubytovanim-u-vody-v-cesku':Object.freeze({cohort:'stay_tips_at_water',offers:Object.freeze(["lipno","more"])})
  });
  const slug=location.pathname.startsWith('/')&&location.pathname.endsWith('/')?
    location.pathname.slice(1,-1):'';
  if(location.origin!==ORIGIN || location.search || location.hash ||
    !Object.prototype.hasOwnProperty.call(ROUTES,slug) ||
    window.__nrySixRealStayClicks===true)return;
  window.__nrySixRealStayClicks=true;
  const route=ROUTES[slug];
  document.addEventListener('click',event=>{
    if(event?.isTrusted!==true || typeof window.gtag!=='function'
      || typeof event.target?.closest!=='function')return;
    const link=event.target.closest('a[data-nry-real-stay-offer][href]');
    const panel=link?.closest?.('section[data-nry-real-stay-offers="v1"]');
    if(!panel || panel.getAttribute('data-nry-real-stay-slug')!==slug)return;
    const panels=document.querySelectorAll('section[data-nry-real-stay-offers="v1"]');
    if(panels.length!==1||panels[0]!==panel)return;
    const choices=Array.from(panel.querySelectorAll('nav[aria-label="Dvě ověřitelné partnerské nabídky"] a[data-nry-real-stay-offer]'));
    if(choices.length!==2)return;
    for(let i=0;i<2;i++){
      const expected=route.offers[i],a=choices[i];
      if(a.getAttribute('data-nry-real-stay-offer')!==expected ||
        a.getAttribute('href')!==URLS[expected] ||
        a.getAttribute('rel')!=='sponsored nofollow noopener')return;
    }
    if(!choices.includes(link))return;
    let url;try{url=new URL(link.href)}catch{return}
    const role=link.getAttribute('data-nry-real-stay-offer');
    if(url.href!==URLS[role]||url.username||url.password||url.hash)return;
    try{window.gtag('event','nry_buyer_offer_click',{
      buyer_page:route.cohort,stay_offer_id:role,
      placement_id:'six_verified_stay_guides_v1',transport_type:'beacon'
    })}catch{ /* Outbound navigation must always work. */ }
  },{capture:true,passive:true});
})();
