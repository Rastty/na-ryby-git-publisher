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

  // Local planner is independent of analytics and never submits user inputs.
  // Reuse the six already verified anchor targets, without changing their order.
  function initStayPlanner() {
    if (location.pathname!=='/ubytovani-2/' || typeof panel.querySelector!=='function') return;
    const root=panel.querySelector('[data-nry-stay-planner="v1"]');
    if (!root || root.hidden!==true) return;
    const fields=Object.fromEntries(['region','type','people','nights','budget','extras'].map(
      key=>[key,root.querySelector('[data-nry-plan="'+key+'"]')]));
    const out=Object.fromEntries(['recommendation','total','perperson'].map(
      key=>[key,root.querySelector('[data-nry-plan-output="'+key+'"]')]));
    if (Object.values(fields).some(x=>!x) || Object.values(out).some(x=>!x)) return;
    const format=new Intl.NumberFormat('cs-CZ',{maximumFractionDigits:0});
    const money=n=>format.format(n)+' Kč';
    const number=(field,min,max)=>{
      const value=field.value.trim();
      if (!/^\d+$/.test(value)) return null;
      const n=Number(value);
      return Number.isSafeInteger(n)&&n>=min&&n<=max?n:null;
    };
    function update(){
      let index=0,reason='Porovnejte více lokalit a typů ubytování.';
      const region=fields.region.value, type=fields.type.value;
      if(type==='private'){
        index=5;reason='Nejprve vyberte provozovatele soukromé vody a ověřte konkrétní lovné místo; průvodce zahrnuje různé oblasti.';
      } else if(region==='slapy'){
        index=1;reason='Začněte u Slap; ověřte typ ubytování a vzdálenost k povolenému lovišti.';
      } else if(region==='lipno'){
        index=2;reason='Na Lipně porovnejte ubytování, břeh a dostupnost zamýšleného loviště.';
      } else if(region==='vranov'||(region==='any'&&(type==='wellness'||type==='group'))){
        index=type==='wellness'?4:3;
        reason=type==='wellness'?'Vranovský srub je tip pro pohodlný pobyt; potvrďte kapacitu, vybavení a stav vody.':'U Vranova začněte Cyklochatou; potvrďte kapacitu, cenu a přístup k vodě.';
      } else if(type==='family'){
        reason='Pro rodinu porovnejte zázemí pro děti, bezpečný přístup k vodě a stravování.';
      }
      const target=ORIGIN+cfg.choices[index][1];
      anchors.forEach(a=>{
        if(a.getAttribute('href')===target)a.setAttribute('data-nry-recommended','true');
        else a.removeAttribute('data-nry-recommended');
      });
      out.recommendation.textContent=reason;
      const p=number(fields.people,1,30),n=number(fields.nights,1,60),
        b=number(fields.budget,0,1000000),e=number(fields.extras,0,1000000);
      if([p,n,b,e].includes(null)){
        out.total.textContent='Vyplňte celé hodnoty v povoleném rozsahu.';
        out.perperson.textContent='';return;
      }
      if(e>b){
        out.total.textContent='Ostatní náklady překračují rozpočet o '+money(e-b)+'.';
        out.perperson.textContent='Na ubytování nezbývá rozpočet.';return;
      }
      out.total.textContent='Na ubytování zbývá '+money(b-e)+' za celý pobyt.';
      out.perperson.textContent='Max. '+money(Math.floor((b-e)/(p*n)))+' na osobu a noc';
    }
    Object.values(fields).forEach(f=>{f.addEventListener('input',update);f.addEventListener('change',update);});
    update();root.hidden=false;
  }
  initStayPlanner();

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
