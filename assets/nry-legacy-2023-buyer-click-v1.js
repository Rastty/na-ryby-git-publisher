/* Genuine user handoffs from six exact 2023 buyer posts. No synthetic events. */
(() => {
  'use strict';
  const ORIGIN='https://na-ryby.eu';
  const COHORTS=Object.freeze({
    'nejlepsi-rybarske-lehatko-jak-vybrat-nejlepsi-lehatko-pro-rybare':'legacy_bedchair_choice_2023',
    'vysoce-hodnocena-rybarska-lehatka-podrobna-recenze-a-porovnani-popularnich-modelu':'legacy_bedchair_reviews_2023',
    'nejlepsi-rybarske-pruty-s-navijakem-podle-zkusenych-rybaru':'legacy_rod_reel_set_2023',
    'nejlepsi-rybarske-hlasice-a-signalizatory':'legacy_alarm_choice_2023',
    'recenze-nejlevnejsich-rybarskych-prutu-pro-zacatecniky-i-pokrocile':'legacy_budget_rods_2023',
    'detske-rybarske-pruty-s-nejlepsi-cenu-a-kvalitou':'legacy_junior_rods_2023'
  });
  const ROUTES=Object.freeze({
    'nejlepsi-rybarske-lehatko-jak-vybrat-nejlepsi-lehatko-pro-rybare':Object.freeze([
      ['bedchair_guide','/rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/'],
      ['bedchair_5000','/nejlepsi-rybarska-lehatka-do-5000-kc/'],
      ['bedchair_weight','/nejlepsi-rybarska-lehatka-pro-tezsi-postavy-jak-vybrat-spravne/']
    ]),
    'vysoce-hodnocena-rybarska-lehatka-podrobna-recenze-a-porovnani-popularnich-modelu':Object.freeze([
      ['bedchair_comparison','/nejlepsi-rybarska-lehatka-na-trhu-recenze-a-porovnani/'],
      ['bedchair_trip','/nejlepsi-rybarska-lehatka-pro-delsi-vypravy-top-vyber-a-srovnani/'],
      ['bedchair_5000','/nejlepsi-rybarska-lehatka-do-5000-kc/']
    ]),
    'nejlepsi-rybarske-pruty-s-navijakem-podle-zkusenych-rybaru':Object.freeze([
      ['starter_set','/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/'],
      ['rod_choice','/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'],
      ['reel_choice','/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/']
    ]),
    'nejlepsi-rybarske-hlasice-a-signalizatory':Object.freeze([
      ['alarm_choice','/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'],
      ['alarm_3000','/kompletni-sada-31-s-priposlechem-do-3-000-kc-co-opravdu-koupit/'],
      ['alarm_troubleshoot','/proc-mi-signalizator-pipa-bez-zaberu-5-nejcastejsich-pricin-a-jak-je-vyresit/']
    ]),
    'recenze-nejlevnejsich-rybarskych-prutu-pro-zacatecniky-i-pokrocile':Object.freeze([
      ['beginner_fishing','/rybareni-pro-zacatecniky-jak-zacit-co-koupit-a-prvni-kroky/'],
      ['starter_set','/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/'],
      ['carp_3000','/nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber/']
    ]),
    'detske-rybarske-pruty-s-nejlepsi-cenu-a-kvalitou':Object.freeze([
      ['junior_rod','/kaprove-pruty-pro-deti-a-mladez-jak-vybrat-spravne/'],
      ['beginner_fishing','/rybareni-pro-zacatecniky-jak-zacit-co-koupit-a-prvni-kroky/'],
      ['starter_set','/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/']
    ])
  });
  const slug=location.pathname.startsWith('/')&&location.pathname.endsWith('/')?
    location.pathname.slice(1,-1):'';
  if(location.origin!==ORIGIN || location.search || location.hash ||
    !Object.prototype.hasOwnProperty.call(ROUTES,slug) ||
    window.__nryLegacy2023BuyerClick===true) return;
  window.__nryLegacy2023BuyerClick=true;
  const expected=ROUTES[slug];
  document.addEventListener('click',event=>{
    if(event?.isTrusted!==true || typeof window.gtag!=='function' ||
      typeof event.target?.closest!=='function')return;
    const link=event.target.closest('a[data-nry-legacy-guide][href]');
    const panel=link?.closest?.('section[data-nry-legacy-2023="v1"]');
    if(!panel || panel.getAttribute('data-nry-legacy-slug')!==slug) return;
    const panels=document.querySelectorAll('section[data-nry-legacy-2023="v1"]');
    if(panels.length!==1 || panels[0]!==panel) return;
    const choices=Array.from(panel.querySelectorAll('a.nry-gear-archive-entry__choice'));
    if(choices.length!==3)return;
    for(let i=0;i<3;i++){
      const anchor=choices[i], [role,path]=expected[i];
      if(anchor.getAttribute('data-nry-legacy-guide')!==role ||
        anchor.getAttribute('href')!==ORIGIN+path)return;
    }
    if(!choices.includes(link)) return;
    let url;
    try{url=new URL(link.href,ORIGIN)}catch{return}
    if(url.origin!==ORIGIN || url.search || url.hash || url.port ||
      url.username || url.password || url.href!==link.getAttribute('href'))return;
    try{window.gtag('event','nry_buyer_guide_click',{
      buyer_page:COHORTS[slug],
      guide_type:link.getAttribute('data-nry-legacy-guide'),
      transport_type:'beacon'
    })}catch{ /* Navigation must never depend on analytics. */ }
  },{capture:true,passive:true});
})();
