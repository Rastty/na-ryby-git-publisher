/* Real first-party equipment-hub guide clicks; existing consent-aware gtag only. */
(() => {
 'use strict';
 const ORIGIN='https://na-ryby.eu',PATH='/category/vybaveni-pro-rybare/';
 const ROUTES=[
  ['carp_rods','/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'],
  ['reels','/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/'],
  ['sonar','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'],
  ['alarms','/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'],
  ['bivvy','/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'],
  ['baitboat','/zavazeci-lodky-pro-rybare/']
 ];
 if(location.origin!==ORIGIN||location.pathname!==PATH||location.search||location.hash||
    window.__nryEquipmentHubClickV1===true)return;
 window.__nryEquipmentHubClickV1=true;
 document.addEventListener('click',event=>{
  if(event?.isTrusted!==true||typeof window.gtag!=='function'||
     typeof event.target?.closest!=='function')return;
  const a=event.target.closest('a[data-nry-equipment-choice][href]');
  if(!a||typeof a.closest!=='function')return;
  const root=a.closest('section[data-nry-equipment-hub="v1"]');
  const sections=document.querySelectorAll('section[data-nry-equipment-hub="v1"]');
  if(!root||sections.length!==1||sections[0]!==root)return;
  const links=Array.from(root.querySelectorAll('a[data-nry-equipment-choice][href]'));
  if(links.length!==ROUTES.length||!links.includes(a))return;
  for(let i=0;i<ROUTES.length;i++){
   const [role,path]=ROUTES[i],el=links[i];
   if(el.getAttribute('data-nry-equipment-choice')!==role||
      el.getAttribute('href')!==ORIGIN+path||
      !el.matches('a.nry-gear-archive-entry__choice'))return;
  }
  let u;try{u=new URL(a.href);}catch{return;}
  if(u.origin!==ORIGIN||u.username||u.password||u.port||u.search||u.hash||
     u.href!==a.getAttribute('href'))return;
  try{window.gtag('event','nry_buyer_guide_click',{
   buyer_page:'equipment_hub',
   guide_type:a.getAttribute('data-nry-equipment-choice'),
   transport_type:'beacon'
  });}catch{/* navigation unaffected */}
 },{capture:true,passive:true});
})();
