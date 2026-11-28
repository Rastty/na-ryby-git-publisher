/* Measured genuine first-party guide handoffs from seven exact equipment archives.
 * Uses existing consent-controlled gtag; no merchant hrefs, cookies, user IDs,
 * query strings, new endpoints or synthetic events.
 */
(() => {
  'use strict';
  const ORIGIN='https://na-ryby.eu';
  const PREFIX='/category/vybaveni-pro-rybare/';
  const ROUTES=Object.freeze({
    'pruty':Object.freeze([
      ['carp_rod_guide','/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'],
      ['carp_rod_budget','/nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber/'],
      ['carp_rod_youth','/kaprove-pruty-pro-deti-a-mladez-jak-vybrat-spravne/']
    ]),
    'signalizatory':Object.freeze([
      ['signal_guide','/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'],
      ['signal_budget','/kompletni-sada-31-s-priposlechem-do-3-000-kc-co-opravdu-koupit/'],
      ['signal_range','/jak-vybrat-signalizator-pro-lov-na-velkych-prehradach-a-rekach/']
    ]),
    'navijaky':Object.freeze([
      ['reel_guide','/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/'],
      ['feeder_reel','/nejlepsi-feederove-navijaky-srovnani/'],
      ['spinning_reel','/nejlepsi-privlacove-navijaky-jak-vybrat/']
    ]),
    'echoloty-na-lod':Object.freeze([
      ['sonar_guide','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'],
      ['sonar_budget','/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/'],
      ['sonar_setup','/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/']
    ]),
    'bivaky':Object.freeze([
      ['bivak_guide','/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'],
      ['bivak_budget','/nejlepsi-rybarske-bivaky-do-10-000-kc/'],
      ['fishing_stays','/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/']
    ]),
    'lehatka':Object.freeze([
      ['bedchair_fit','/rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/'],
      ['bedchair_budget','/nejlepsi-rybarska-lehatka-do-5000-kc/'],
      ['bedchair_load','/jake-rybarske-lehatko-pro-tezsi-rybare-jak-vybrat-spravne/']
    ]),
    'zavazeci-lodky':Object.freeze([
      ['baitboat_choice','/zavazeci-lodky-pro-rybare/'],
      ['baitboat_sonar','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/']
    ])
  });
  const slug=location.pathname.startsWith(PREFIX) &&
    location.pathname.endsWith('/') ?
    location.pathname.slice(PREFIX.length,-1) : '';
  if (location.origin!==ORIGIN || location.search || location.hash ||
      !Object.prototype.hasOwnProperty.call(ROUTES,slug) ||
      window.__nryGearArchiveClickV1===true) return;
  window.__nryGearArchiveClickV1=true;
  const expected=ROUTES[slug];

  function exactPanel(section) {
    if (!section || section.getAttribute('data-nry-gear-archive')!=='v1' ||
        section.getAttribute('data-nry-gear-category')!==slug) return null;
    const sections=document.querySelectorAll('section[data-nry-gear-archive="v1"]');
    if (sections.length!==1 || sections[0]!==section) return null;
    const all=Array.from(section.querySelectorAll('a[href]'));
    // TagDiv has inserted one exact homepage context link in other scoped
    // panels. Accept only that specific link first; reject any other extras.
    if (all.length===expected.length+1 &&
        all[0].getAttribute('href')===ORIGIN+'/') all.shift();
    if (all.length!==expected.length) return null;
    for (let i=0;i<expected.length;i++) {
      const [role,path]=expected[i],link=all[i];
      if (link.getAttribute('data-nry-gear-guide')!==role ||
          link.getAttribute('href')!==ORIGIN+path ||
          !link.matches('a.nry-gear-archive-entry__choice')) return null;
    }
    return all;
  }

  document.addEventListener('click', event => {
    if (event?.isTrusted!==true || typeof window.gtag!=='function' ||
        typeof event.target?.closest!=='function') return;
    const anchor=event.target.closest('a[data-nry-gear-guide][href]');
    if (!anchor || typeof anchor.closest!=='function') return;
    const section=anchor.closest('section.nry-gear-archive-entry');
    const list=exactPanel(section);
    if (!list || !list.includes(anchor)) return;
    let url;
    try { url=new URL(anchor.href,ORIGIN); } catch { return; }
    if (url.origin!==ORIGIN || url.search || url.hash ||
        url.username || url.password || url.port ||
        url.href!==anchor.getAttribute('href')) return;
    try {
      window.gtag('event','nry_buyer_guide_click',{
        buyer_page:'gear_archive_'+slug,
        guide_type:anchor.getAttribute('data-nry-gear-guide'),
        transport_type:'beacon'
      });
    } catch { /* Measurement must not interrupt genuine navigation. */ }
  },{capture:true,passive:true});
})();
