/* Genuine clicks on exact source-approved Lipno reservation panels only. */
(() => {
  'use strict';
  const routes=Object.freeze({
    '/levne-ubytovani-u-lipna-pro-partu-kdy-vyjde-dobre-cena-na-osobu/':['parta','vyhlidka','kohoutu'],
    '/levne-ubytovani-u-lipna-na-vikend-co-jeste-dava-smysl/':['vikend','kohoutu','vyhlidka'],
    '/levna-chata-apartman-nebo-pohodlnejsi-pobyt-u-lipna-co-dava-rybari-vetsi-smysl/':['typ','vyhlidka','jbx'],
    '/na-cem-u-ubytovani-u-lipna-nesetrit-a-kde-naopak-klidne-ano/':['rozpocet','kohoutu','vyhlidka'],
    '/ubytovani-u-lipna-blizko-vody-pro-rybare-kdy-to-dava-smysl-a-kdy-ne/':['u_vody','kohoutu','vyhlidka']
  });
  const urls=Object.freeze({
    kohoutu:'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F',
    vyhlidka:'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fkomfortny-penzion-so-saunou-na-lipne',
    jbx:'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fjbx-resort-albatros'
  });
  const route=routes[location.pathname];
  if(location.origin!=='https://na-ryby.eu'||!route||window.__nryLipnoReservationClicks)return;
  window.__nryLipnoReservationClicks=true;
  document.addEventListener('click',e=>{
    if(e.isTrusted!==true||typeof window.gtag!=='function'||typeof e.target?.closest!=='function')return;
    const link=e.target.closest('a[data-nry-lipno-offer][href]');
    const panel=link?.closest('section[data-nry-lipno-reservation="v1"]');
    if(!panel||panel.getAttribute('data-nry-lipno-role')!==route[0]||
       document.querySelectorAll('section[data-nry-lipno-reservation="v1"]').length!==1)return;
    const links=Array.from(panel.querySelectorAll('nav a[data-nry-lipno-offer]'));
    if(links.length!==2||!links.includes(link))return;
    for(let i=0;i<2;i++){
      if(links[i].getAttribute('data-nry-lipno-offer')!==route[i+1]||
         links[i].getAttribute('href')!==urls[route[i+1]]||
         links[i].getAttribute('rel')!=='sponsored nofollow noopener')return;
    }
    try{window.gtag('event','nry_buyer_offer_click',{buyer_page:'lipno_'+route[0],stay_offer_id:link.getAttribute('data-nry-lipno-offer'),placement_id:'lipno_reservation_v1',transport_type:'beacon'});}catch{}
  },{capture:true,passive:true});
})();
