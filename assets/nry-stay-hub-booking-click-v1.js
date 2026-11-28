/* Exact reviewed booking CTAs on the national stay hub. No user inputs,
 * URLs or user identifiers sent to analytics; existing consent-aware gtag only. */
(() => {
  'use strict';
  if(location.origin!=='https://na-ryby.eu'||
    location.pathname!=='/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'||
    location.search||location.hash||window.__nryStayHubBookingClicks)return;
  const prefix='https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2F';
  const routes=[
    {panel:'nav[data-nry-vranov-stays-59891="v1"]',attr:'data-nry-vranov-hub-offer',placement:'vranov_hub_booking_v1',offers:[['cyklo','cyklochata'],['srub','rybarsky-srub']]},
    {panel:'div[data-nry-lipno-hub-offers="v1"]',attr:'data-nry-lipno-hub-offer',placement:'lipno_hub_booking_v1',offers:[['vyhlidka','komfortny-penzion-so-saunou-na-lipne'],['jbx','jbx-resort-albatros']]}
  ];
  window.__nryStayHubBookingClicks=true;
  document.addEventListener('click',event=>{
    if(event.isTrusted!==true||typeof window.gtag!=='function'||typeof event.target?.closest!=='function')return;
    for(const route of routes){
      const link=event.target.closest('a['+route.attr+'][href]');
      if(!link)continue;
      const panels=document.querySelectorAll(route.panel);
      if(panels.length!==1||link.closest(route.panel)!==panels[0])return;
      const anchors=Array.from(panels[0].querySelectorAll('a['+route.attr+'][href]'));
      if(anchors.length!==2||!anchors.includes(link))return;
      for(let i=0;i<2;i++){
        const a=anchors[i],o=route.offers[i];
        if(a.getAttribute(route.attr)!==o[0]||a.getAttribute('href')!==prefix+o[1]||
          a.getAttribute('target')!=='_blank'||a.getAttribute('rel')!=='sponsored nofollow noopener')return;
      }
      try{window.gtag('event','nry_buyer_offer_click',{
        buyer_page:'fishing_stays_59891',buyer_cta_type:'fishing_stay_outbound',source_post_id:'59891',
        placement_id:route.placement,stay_offer_id:link.getAttribute(route.attr),
        stay_partner:'hauzi_affiliateport_cz',transport_type:'beacon'
      });}catch{}
      return;
    }
  },{capture:true,passive:true});
})();
