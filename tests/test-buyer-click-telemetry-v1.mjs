import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const script=readFileSync('assets/nry-buyer-clicks-v1.js','utf8');
assert.ok(!/preventDefault\s*\(/.test(script));
assert.ok(!/\.href\s*=|location\.assign|location\.replace/.test(script));
assert.ok(!/sendBeacon\s*\(/.test(script));
assert.ok(!/localStorage|sessionStorage|document\.cookie/.test(script));

function browser({page='/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/',
  host='https://na-ryby.eu',gtagEnabled=true}={}){
  const callbacks=[],calls=[];
  const base=host+page;
  const loc=new URL(base);
  const context={
    URL,
    Set,
    Object,
    location:{origin:loc.origin,pathname:loc.pathname,href:loc.href},
    document:{addEventListener:(kind,fn,opts)=>{
      assert.equal(kind,'click');assert.equal(opts.passive,true);
      assert.equal(opts.capture,true);callbacks.push(fn);
    }},
    window:{...(gtagEnabled?{gtag:(...a)=>calls.push(a)}:{})}
  };
  vm.runInNewContext(script,context,{timeout:1500});
  const click=(href,{trusted=true,sourcePinnedBaitboatCallout=false,
    sourcePinnedStayBlock=false,stayOfferId=null}={})=>{
    const link={href,closest:selector=>{
      if(selector==='aside[data-nry-baitboat-choice-link="v1"]' &&
         sourcePinnedBaitboatCallout) return {};
      if(selector==='section[data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"]' &&
         sourcePinnedStayBlock) return {};
      if(selector==='article[data-nry-offer-id]' && stayOfferId)
        return {getAttribute:name=>name==='data-nry-offer-id'?stayOfferId:null};
      return null;
    }};
    const element={closest:selector=>selector==='a[href]'?link:null};
    for(const cb of callbacks)cb({isTrusted:trusted,target:element});
  };
  return {callbacks,calls,click};
}
const a=browser();
assert.equal(a.callbacks.length,1);
a.click('https://na-ryby.eu/signalizatory');
assert.equal(a.calls.length,1);
assert.equal(a.calls[0][0],'event');
assert.equal(a.calls[0][1],'nry_buyer_offer_click');
assert.equal(a.calls[0][2].buyer_page,'pipaky_guide');
assert.equal(a.calls[0][2].buyer_cta_type,'internal_affiliate_redirect');
assert.equal(a.calls[0][2].transport_type,'beacon');
assert.ok(!JSON.stringify(a.calls).includes('/signalizatory'));
const before=JSON.stringify(a.calls);
a.click('https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/');
a.click('https://na-ryby.eu/signalizatory',{trusted:false});
a.click('https://www.tkqlhce.com/click-12345-15734253?url=https://example.com');
a.click('http://www.tkqlhce.com/click-8287231-15734253');
a.click('https://www.tkqlhce.com/click-8287231-15734253-extra');
assert.equal(JSON.stringify(a.calls),before,'unrelated, untrusted, nonHTTPS and wrong CJ property ignored');
const b=browser({page:'/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'});
b.click('https://www.tkqlhce.com/click-8287231-15320199?url=https%3A%2F%2Fwww.chytapust.cz%2FSignalizatory-zaberu%2Fsample');
assert.equal(b.calls.length,1);
assert.equal(b.calls[0][2].buyer_page,'alarm_guide');
assert.equal(b.calls[0][2].buyer_cta_type,'cj_product_link');
assert.ok(!JSON.stringify(b.calls).includes('tkqlhce'));
assert.ok(!JSON.stringify(b.calls).includes('chytapust'));
assert.ok(!JSON.stringify(b.calls).includes('sample'));
// New source-verified buyer routes must collect only a genuine first-party
// event on an actual approved CJ click, without URL or user data.
const additional=[
 ['/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/','pipaky_beginner'],
 ['/pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu/','pipaky_advanced'],
 ['/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/','pipaky_receiver'],
 ['/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/','echolot_budget'],
 ['/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/','sonar_pillar'],
 ['/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/','sonar_boat'],
 ['/porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023/','sonar_historic'],
 ['/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/','sonar_practical'],
 ['/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/','carp_pillar']
];
for(const [page,role] of additional){
 const client=browser({page});
 assert.equal(client.callbacks.length,1);
 client.click('https://www.jdoqocy.com/click-8287231-15320199?url=https%3A%2F%2Fwww.chytapust.cz%2Fsome-product');
 assert.equal(client.calls.length,1);
 assert.equal(client.calls[0][2].buyer_page,role);
 assert.equal(client.calls[0][2].buyer_cta_type,'cj_product_link');
 assert.ok(!JSON.stringify(client.calls).includes('chytapust'));
 client.click('https://www.jdoqocy.com/click-8287231-15320199',{trusted:false});
 assert.equal(client.calls.length,1,'never emit test/synthetic clicks');
}
// Two real outbound destination types observed on budget echolot #118455.
// They are visitor handoffs only, never a sale, fee or verified affiliate credit.
const sonarBudget=browser({page:'/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/'});
sonarBudget.click('https://go.dognet.com/?some-id=1');
assert.equal(sonarBudget.calls.length,1,'tracking query on Dognet root is legitimate outbound');
sonarBudget.click('https://go.dognet.com/path/to/offer?affiliate=REDACTED');
assert.equal(sonarBudget.calls.length,2);
assert.equal(sonarBudget.calls[0][1],'nry_buyer_offer_click');
assert.equal(sonarBudget.calls[0][2].buyer_page,'echolot_budget');
assert.equal(sonarBudget.calls[0][2].buyer_cta_type,'dognet_outbound');
sonarBudget.click('https://www.chytapust.cz/echolot-model/?a=secret');
assert.equal(sonarBudget.calls.length,3);
assert.equal(sonarBudget.calls[2][2].buyer_cta_type,'merchant_outbound');
assert.equal(sonarBudget.calls[2][2].transport_type,'beacon');
for (const call of sonarBudget.calls) {
  assert.deepEqual(Object.keys(call[2]).sort(),
    ['buyer_cta_type','buyer_page','transport_type'].sort(),
    'no href, product identifier or user answers in event');
  assert.ok(!JSON.stringify(call).includes('secret'),'no link parameters');
  assert.ok(!JSON.stringify(call).includes('dognet.com'),'no merchant URL');
  assert.ok(!JSON.stringify(call).includes('chytapust.cz'),'no merchant URL');
}
for(const href of [
 'https://evilchytapust.cz/echolot', 'https://chytapust.cz/echolot',
 'http://www.chytapust.cz/echolot', 'https://go.dognet.com:444/offer',
 'https://user:pass@go.dognet.com/offer', 'https://go.dognet.com/',
 'https://www.chytapust.cz/', 'https://go.dognet.com.evil.test/offer',
 'https://example.com/offer'
]) sonarBudget.click(href);
sonarBudget.click('https://www.chytapust.cz/echolot/',{trusted:false});
assert.equal(sonarBudget.calls.length,3,'invalid or untrusted offsite clicks ignored');
const sonarNoGtag=browser({
  page:'/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',
  gtagEnabled:false
});
sonarNoGtag.click('https://go.dognet.com/promo/path');
assert.equal(sonarNoGtag.calls.length,0,'no gtag/consent bypass');
for(const page of ['/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/',
                    '/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/']) {
  const control=browser({page});
  control.click('https://go.dognet.com/offer');
  control.click('https://www.chytapust.cz/echolot/');
  assert.equal(control.calls.length,0,'only source-bound budget article classified');
}
// Existing carp pillar has a direct Chyť a pusť category CTA. Measure the
// outbound handoff, not affiliate entitlement or a credited commission.
const carp=browser({page:'/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'});
carp.click('https://www.chytapust.cz/kaprove-pruty/?campaign=NOT_FOR_ANALYTICS');
assert.equal(carp.calls.length,1);
assert.equal(carp.calls[0][1],'nry_buyer_offer_click');
assert.equal(carp.calls[0][2].buyer_page,'carp_pillar');
assert.equal(carp.calls[0][2].buyer_cta_type,'merchant_outbound');
assert.deepEqual(Object.keys(carp.calls[0][2]).sort(),
  ['buyer_page','buyer_cta_type','transport_type'].sort());
assert.ok(!JSON.stringify(carp.calls).includes('NOT_FOR_ANALYTICS'));
for(const href of [
  'http://www.chytapust.cz/kaprove-pruty/',
  'https://www.chytapust.cz/',
  'https://www.chytapust.cz.evil.example/kaprove-pruty/',
  'https://chytapust.cz/kaprove-pruty/'
]) carp.click(href);
carp.click('https://www.chytapust.cz/kaprove-pruty/',{trusted:false});
assert.equal(carp.calls.length,1,'reject foreign, insecure, bare or automated carp shop clicks');
const carpNoGtag=browser({
  page:'/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/',
  gtagEnabled:false
});
carpNoGtag.click('https://www.chytapust.cz/kaprove-pruty/');
assert.equal(carpNoGtag.calls.length,0,'never override site consent');

const boatGuide='/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/';
const practical=browser({page:'/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/'});
practical.click('https://na-ryby.eu'+boatGuide);
assert.equal(practical.calls.length,1);
assert.equal(practical.calls[0][0],'event');
assert.equal(practical.calls[0][1],'nry_buyer_guide_click');
assert.equal(practical.calls[0][2].buyer_page,'sonar_practical');
assert.equal(practical.calls[0][2].guide_type,'sonar_boat');
assert.ok(!JSON.stringify(practical.calls).includes(boatGuide),'no raw URL');
for(const href of [
 'https://other.example'+boatGuide,'https://na-ryby.eu'+boatGuide+'?secret=x',
 'https://na-ryby.eu'+boatGuide+'#secret','http://na-ryby.eu'+boatGuide,
 'https://na-ryby.eu/poradce-vybavy/'
]) practical.click(href);
practical.click('https://na-ryby.eu'+boatGuide,{trusted:false});
assert.equal(practical.calls.length,1,'reject foreign, altered, untrusted handoffs');
const noGtag=browser({page:'/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/',gtagEnabled:false});
noGtag.click('https://na-ryby.eu'+boatGuide);
assert.equal(noGtag.calls.length,0,'no consent bypass');
const notSource=browser({page:'/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/'});
notSource.click('https://na-ryby.eu'+boatGuide);
assert.equal(notSource.calls.length,1,'existing budget-to-boat guide counted');
assert.equal(notSource.calls[0][1],'nry_buyer_guide_click');
assert.equal(notSource.calls[0][2].guide_type,'sonar_boat');

const pillar=browser({page:'/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'});
pillar.click('https://www.chytapust.cz/Echoloty/?utm_source=na-ryby');
assert.equal(pillar.calls.length,1);
assert.equal(pillar.calls[0][1],'nry_buyer_offer_click');
assert.equal(pillar.calls[0][2].buyer_page,'sonar_pillar');
assert.equal(pillar.calls[0][2].buyer_cta_type,'merchant_outbound');
pillar.click('https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/');
assert.equal(pillar.calls.length,2);
assert.equal(pillar.calls[1][1],'nry_buyer_guide_click');
assert.equal(pillar.calls[1][2].guide_type,'sonar_practical');
const sonarChoices=[
  ['/deeper-pro-vs-pro-2-vs-chirp-2-ktery-se-vyplati/','sonar_bank'],
  ['/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/','sonar_boat'],
  ['/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/','sonar_budget'],
  ['/poradce-vybavy/','equipment_advisor']
];
for (const [path,role] of sonarChoices) {
  pillar.click('https://na-ryby.eu'+path);
  assert.equal(pillar.calls.at(-1)[1],'nry_buyer_guide_click');
  assert.equal(pillar.calls.at(-1)[2].guide_type,role);
  assert.equal(pillar.calls.at(-1)[2].buyer_page,'sonar_pillar');
  assert.ok(!JSON.stringify(pillar.calls.at(-1)).includes(path),'never send URL');
}
for (const c of pillar.calls) {
  assert.deepEqual(Object.keys(c[2]).sort(),
    (c[1]==='nry_buyer_offer_click'
      ? ['buyer_page','buyer_cta_type','transport_type']
      : ['buyer_page','guide_type','transport_type']).sort());
  assert.ok(!JSON.stringify(c).includes('chytapust.cz'));
  assert.ok(!JSON.stringify(c).includes('/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/'));
}
for (const href of [
  'http://www.chytapust.cz/Echoloty/',
  'https://other.example/Echoloty/',
  'https://chytapust.cz/Echoloty/',
  'https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/?tag=1',
  'https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/#tracking',
  'https://evil.na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/'
]) pillar.click(href);
for (const [path] of sonarChoices) {
  pillar.click('https://na-ryby.eu'+path+'?test=1');
  pillar.click('https://na-ryby.eu'+path+'#test');
  pillar.click('https://not-na-ryby.eu'+path);
  pillar.click('https://na-ryby.eu'+path,{trusted:false});
}
pillar.click('https://www.chytapust.cz/Echoloty/',{trusted:false});
pillar.click('https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/',{trusted:false});
assert.equal(pillar.calls.length,6,'never emit untrusted, unscoped or disguised links');
const pillarNoConsent=browser({page:'/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/',gtagEnabled:false});
pillarNoConsent.click('https://www.chytapust.cz/Echoloty/');
pillarNoConsent.click('https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/');
for (const [path] of sonarChoices) pillarNoConsent.click('https://na-ryby.eu'+path);
assert.equal(pillarNoConsent.calls.length,0,'never bypass existing gtag consent');
// Existing cross-guide journeys were independently observed on the five
// public sonar articles. Count internal buyer decisions, not conversions.
const sonarRoutes=[
  ['/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',[
    ['/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/','sonar_pillar'],
    ['/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/','sonar_practical'],
    ['/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/','sonar_boat'],
    ['/poradce-vybavy/','equipment_advisor']]],
  ['/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/',[
    ['/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/','sonar_pillar'],
    ['/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/','sonar_budget'],
    ['/poradce-vybavy/','equipment_advisor']]],
  ['/porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023/',[
    ['/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/','sonar_pillar'],
    ['/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/','sonar_boat'],
    ['/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/','sonar_budget'],
    ['/poradce-vybavy/','equipment_advisor']]]
];
const sonarPageRoles={
  '/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/':'echolot_budget',
  '/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/':'sonar_boat',
  '/porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023/':'sonar_historic'
};
for (const [source,links] of sonarRoutes) {
  const viewer=browser({page:source});
  for (const [href,role] of links) {
    viewer.click('https://na-ryby.eu'+href);
    const last=viewer.calls.at(-1);
    assert.equal(last[1],'nry_buyer_guide_click');
    assert.equal(last[2].buyer_page,sonarPageRoles[source]);
    assert.equal(last[2].guide_type,role);
    assert.equal(last[2].transport_type,'beacon');
    assert.deepEqual(Object.keys(last[2]).sort(),['buyer_page','guide_type','transport_type'].sort());
    assert.ok(!JSON.stringify(last).includes(href),'no destination URL in GA4 payload');
  }
  const before=viewer.calls.length;
  for (const [href] of links) {
    viewer.click('https://na-ryby.eu'+href+'?ref=not-allowed');
    viewer.click('https://na-ryby.eu'+href+'#ref');
    viewer.click('https://bad.na-ryby.eu'+href);
    viewer.click('https://na-ryby.eu'+href,{trusted:false});
  }
  assert.equal(viewer.calls.length,before,'only trusted canonical internal decisions');
  const consent=browser({page:source,gtagEnabled:false});
  for (const [href] of links) consent.click('https://na-ryby.eu'+href);
  assert.equal(consent.calls.length,0,'no consent bypass for sonar routes');
}

// Existing source-pinned baitboat CTA: only genuine, callout-originating
// navigations count. Never use merchant conversions as a proxy.
const baitboat=browser({page:'/zavazeci-lodky-pro-rybare/'});
const picker='https://na-ryby.eu/jak-vybrat-zavazeci-lodku/';
assert.equal(baitboat.callbacks.length,1);
baitboat.click(picker,{sourcePinnedBaitboatCallout:true});
assert.equal(baitboat.calls.length,1);
assert.equal(baitboat.calls[0][0],'event');
assert.equal(baitboat.calls[0][1],'nry_buyer_guide_click');
assert.deepEqual(Object.keys(baitboat.calls[0][2]).sort(),
  ['buyer_page','guide_type','transport_type'].sort());
assert.equal(baitboat.calls[0][2].buyer_page,'baitboat_guide');
assert.equal(baitboat.calls[0][2].guide_type,'baitboat_picker');
assert.equal(baitboat.calls[0][2].transport_type,'beacon');
assert.ok(!JSON.stringify(baitboat.calls).includes('/jak-vybrat-zavazeci-lodku/'));
for(const href of [
 picker+'?utm_source=test',picker+'#results',
 'http://na-ryby.eu/jak-vybrat-zavazeci-lodku/',
 'https://evil.na-ryby.eu/jak-vybrat-zavazeci-lodku/',
 'https://other.example/jak-vybrat-zavazeci-lodku/',
 'https://na-ryby.eu/jak-vybrat-zavazeci-lodku-extra/'
]) baitboat.click(href,{sourcePinnedBaitboatCallout:true});
baitboat.click(picker); // Not from the exact source-verified callout.
baitboat.click(picker,{trusted:false,sourcePinnedBaitboatCallout:true});
assert.equal(baitboat.calls.length,1,'reject untrusted, unrelated, altered, and unpinned clicks');
const baitboatNoGtag=browser({page:'/zavazeci-lodky-pro-rybare/',gtagEnabled:false});
baitboatNoGtag.click(picker,{sourcePinnedBaitboatCallout:true});
assert.equal(baitboatNoGtag.calls.length,0,'never bypass existing consent');
const pickerPage=browser({page:'/jak-vybrat-zavazeci-lodku/'});
assert.equal(pickerPage.callbacks.length,0,'standalone V2 selector never instrumented or modified');
const wrongSource=browser({page:'/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'});
wrongSource.click(picker,{sourcePinnedBaitboatCallout:true});
assert.equal(wrongSource.calls.length,0,'only exact source article measures picker CTA');

const c=browser({gtagEnabled:false});c.click('https://na-ryby.eu/signalizatory');assert.equal(c.calls.length,0);
const d=browser({page:'/some-article/'});assert.equal(d.callbacks.length,0);
const e=browser({host:'https://other.example'});assert.equal(e.callbacks.length,0);
const advisor=browser({page:'/poradce-vybavy/'});
assert.equal(advisor.callbacks.length,0,'advisor has separate measurement, no double count');
// Independently observed chair surfaces: the hub links to /kresla and to
// the evergreen guide; the evergreen page has approved-shape CJ links.
const chairHub=browser({page:'/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/'});
chairHub.click('https://na-ryby.eu/kresla');
assert.equal(chairHub.calls.length,1);
assert.equal(chairHub.calls[0][1],'nry_buyer_offer_click');
assert.equal(chairHub.calls[0][2].buyer_page,'chair_hub');
assert.equal(chairHub.calls[0][2].buyer_cta_type,'internal_affiliate_redirect');
chairHub.click('https://na-ryby.eu/jak-vybrat-rybarske-kreslo/');
assert.equal(chairHub.calls.length,2);
assert.equal(chairHub.calls[1][1],'nry_buyer_guide_click');
assert.equal(chairHub.calls[1][2].guide_type,'chair_evergreen');
const hubCount=chairHub.calls.length;
for(const href of [
  'https://na-ryby.eu/kresla?ref=secret',
  'https://na-ryby.eu/kresla#secret',
  'http://na-ryby.eu/kresla',
  'https://evil.na-ryby.eu/kresla',
  'https://na-ryby.eu/jak-vybrat-rybarske-kreslo/?ref=secret',
  'https://na-ryby.eu/jak-vybrat-rybarske-kreslo/#secret',
  'https://na-ryby.eu/poradce-vybavy/'
]) chairHub.click(href);
chairHub.click('https://na-ryby.eu/kresla',{trusted:false});
assert.equal(chairHub.calls.length,hubCount,'chair hub cannot count altered or synthetic clicks');
for(const call of chairHub.calls){
  const keys=call[1]==='nry_buyer_offer_click'
    ? ['buyer_cta_type','buyer_page','transport_type']
    : ['buyer_page','guide_type','transport_type'];
  assert.deepEqual(Object.keys(call[2]).sort(),keys.sort());
  assert.ok(!JSON.stringify(call).includes('/kresla'));
}
const chairEvergreen=browser({page:'/jak-vybrat-rybarske-kreslo/'});
chairEvergreen.click('http://www.dpbolvw.net/click-8287231-12347575?url=https%3A%2F%2Fwww.chytapust.cz%2FSedacky');
assert.equal(chairEvergreen.calls.length,1);
assert.equal(chairEvergreen.calls[0][2].buyer_page,'chair_evergreen');
assert.equal(chairEvergreen.calls[0][2].buyer_cta_type,'cj_http_existing');
chairEvergreen.click('http://www.dpbolvw.net/click-7777777-12347575-1234567890123?url=https%3A%2F%2Fwww.chytapust.cz%2FSedacky');
assert.equal(chairEvergreen.calls.length,2);
assert.equal(chairEvergreen.calls[1][2].buyer_cta_type,'cj_legacy_http_unverified');
chairEvergreen.click('https://na-ryby.eu/poradce-vybavy/');
assert.equal(chairEvergreen.calls.length,3);
assert.equal(chairEvergreen.calls[2][1],'nry_buyer_guide_click');
assert.equal(chairEvergreen.calls[2][2].guide_type,'equipment_advisor');
for(const href of [
 'https://na-ryby.eu/kresla', // belongs only to the hub
 'https://www.dpbolvw.net/click-12345-12347575',
 'https://www.dpbolvw.net/click-8287231-12347575-extra',
 'https://na-ryby.eu/poradce-vybavy/?src=secret',
 'https://na-ryby.eu/poradce-vybavy/#secret'
]) chairEvergreen.click(href);
chairEvergreen.click('http://www.dpbolvw.net/click-8287231-12347575',{trusted:false});
assert.equal(chairEvergreen.calls.length,3,'chair evergreen rejects unapproved links');
const chairNoConsent=browser({page:'/jak-vybrat-rybarske-kreslo/',gtagEnabled:false});
chairNoConsent.click('http://www.dpbolvw.net/click-8287231-12347575');
chairNoConsent.click('https://na-ryby.eu/poradce-vybavy/');
assert.equal(chairNoConsent.calls.length,0,'no chair consent bypass');
const hubNoConsent=browser({page:'/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/',gtagEnabled:false});
hubNoConsent.click('https://na-ryby.eu/kresla');
assert.equal(hubNoConsent.calls.length,0,'no chair redirect consent bypass');

// Newly observed bivak buyer surfaces: neither redirect nor re-tag outgoing
// anchors. The historical CJ identity on #119575 is deliberately UNKNOWN;
// a click is not evidence of publisher entitlement or commission credit.
const bivakPillar=browser({
  page:'/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'
});
const oldDognet='https://go.dognet.com/?chid=5QoXiSqx&url='+
  encodeURIComponent('https://www.okfish.cz/bivaky-l8/kempingovy-program');
const genuineCJ='https://www.tkqlhce.com/click-8287231-15734253?url='+
  encodeURIComponent('https://www.chytapust.cz/Bivaky/Zfish-Bivak-Deluxe-King-Size-2-Man');
const bivakHub='https://na-ryby.eu/nejlepsi-rybarske-bivaky-jak-vybrat-ten-nejlepsi/';
bivakPillar.click(oldDognet);
assert.equal(bivakPillar.calls.at(-1)[2].buyer_cta_type,'dognet_legacy_outbound');
bivakPillar.click(genuineCJ);
assert.equal(bivakPillar.calls.at(-1)[2].buyer_cta_type,'cj_product_link');
bivakPillar.click(bivakHub);
assert.equal(bivakPillar.calls.at(-1)[1],'nry_buyer_guide_click');
assert.equal(bivakPillar.calls.at(-1)[2].guide_type,'bivak_hub');
assert.equal(bivakPillar.calls.at(-1)[2].buyer_page,'bivak_pillar');
for(const bad of [
  oldDognet+'&another=secret', oldDognet.replace('5QoXiSqx','other'),
  'https://go.dognet.com/?url=https%3A%2F%2Fwww.okfish.cz%2Fbivaky-l8%2Fkempingovy-program',
  'https://go.dognet.com.evil.example/?chid=5QoXiSqx',
  bivakHub+'?campaign=secret',
  'http://www.tkqlhce.com/click-8287231-15734253'
]) bivakPillar.click(bad);
bivakPillar.click(oldDognet,{trusted:false});
assert.equal(bivakPillar.calls.length,3,'only exact genuine bivak-pillar journeys');
const bivakBudget=browser({page:'/nejlepsi-rybarske-bivaky-do-10-000-kc/'});
const otherCJ='https://www.tkqlhce.com/click-100927011-12347575?url='+
  encodeURIComponent('https://www.chytapust.cz/Bivaky/Mivardi-Bivak-Entrix-XL');
bivakBudget.click(otherCJ);
assert.equal(bivakBudget.calls.length,1);
assert.equal(bivakBudget.calls[0][1],'nry_buyer_offer_click');
assert.equal(bivakBudget.calls[0][2].buyer_page,'bivak_budget');
assert.equal(bivakBudget.calls[0][2].buyer_cta_type,'cj_other_identity_unverified');
bivakBudget.click(bivakHub);
assert.equal(bivakBudget.calls.length,2);
assert.equal(bivakBudget.calls[1][1],'nry_buyer_guide_click');
assert.equal(bivakBudget.calls[1][2].guide_type,'bivak_hub');
for(const bad of [
  otherCJ+'&ref=secret',
  otherCJ.replace('100927011','100927012'),
  otherCJ.replace('12347575','12347576'),
  otherCJ.replace('www.chytapust.cz','www.example.com'),
  otherCJ.replace('Bivaky%2F','SomeOtherProducts%2F'),
  'http://www.tkqlhce.com/click-100927011-12347575',
  'https://www.tkqlhce.com/click-100927011-12347575?url='+
    encodeURIComponent('https://www.chytapust.cz/Bivaky/Mivardi-Entrix-XL?ref=secret'),
  bivakHub+'#secret'
]) bivakBudget.click(bad);
bivakBudget.click(otherCJ,{trusted:false});
assert.equal(bivakBudget.calls.length,2,'no alternate identity or destination tracking');
for(const client of [bivakPillar,bivakBudget])for(const call of client.calls){
  assert.deepEqual(Object.keys(call[2]).sort(),
    (call[1]==='nry_buyer_offer_click'
      ? ['buyer_page','buyer_cta_type','transport_type']
      : ['buyer_page','guide_type','transport_type']).sort());
  assert.ok(!JSON.stringify(call).includes('okfish'));
  assert.ok(!JSON.stringify(call).includes('chytapust'));
  assert.ok(!JSON.stringify(call).includes('100927011'));
  assert.ok(!JSON.stringify(call).includes('8287231'));
}
const bivakNoConsent=browser({page:'/nejlepsi-rybarske-bivaky-do-10-000-kc/',gtagEnabled:false});
bivakNoConsent.click(otherCJ);
bivakNoConsent.click(bivakHub);
assert.equal(bivakNoConsent.calls.length,0,'no new consent mechanism');
const genericPage=browser({page:'/jak-vybrat-rybarske-kreslo/'});
genericPage.click(otherCJ);
assert.equal(genericPage.calls.length,0,'historical bivak identity scoped to exactly one page');
console.log('PASS: bivak historical CJ, Dognet and internal journeys are consent-gated, read-only');

// Live published post 59891: only the five exact source-pinned merchant CTAs
// inside the actual release section may emit measurement metadata. Neither
// this fixture nor the JS ever calls the merchant or sends a synthetic event.
const stayPath='/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
const publishedHtml=readFileSync('editorial/fishing-stays-cz-20261118-recommendation-blocks.html','utf8');
const stayTargets=[...publishedHtml.matchAll(
  /<article data-nry-offer-id="([^"]+)"[\s\S]*?<p><a href="([^"]+)" rel="sponsored nofollow noopener"/g
) ].map(([,id,href])=>({id,href:href.replaceAll('&amp;','&')}));
assert.equal(stayTargets.length,5,'exact published Slevomat + Hportal records');
const stays=browser({page:stayPath});
assert.equal(stays.callbacks.length,1);
for(const {id,href} of stayTargets){
  stays.click(href,{sourcePinnedStayBlock:true,stayOfferId:id});
  assert.equal(stays.calls.length,stayTargets.indexOf(stayTargets.find(x=>x.id===id))+1);
  const [eventType,eventName,payload]=stays.calls.at(-1);
  assert.equal(eventType,'event');
  assert.equal(eventName,'nry_buyer_offer_click');
  assert.equal(payload.buyer_page,'fishing_stays_59891');
  assert.equal(payload.buyer_cta_type,'fishing_stay_outbound');
  assert.equal(payload.source_post_id,'59891');
  assert.equal(payload.placement_id,'fishing-stays-cz-59891-20261118-v1');
  assert.equal(payload.stay_offer_id,id);
  assert.equal(payload.stay_partner,id==='2462030'?'slevomat_cz':'hportal_dognet_cz');
  assert.equal(payload.transport_type,'beacon');
  assert.deepEqual(Object.keys(payload).sort(),[
    'buyer_page','buyer_cta_type','source_post_id','placement_id',
    'stay_offer_id','stay_partner','transport_type'
  ].sort());
  assert.ok(!JSON.stringify(payload).includes('https://'),'no href or visit data');
  assert.ok(!JSON.stringify(payload).includes('5QoXiSqx'),'no affiliate query');
}
const clickCount=stays.calls.length;
for(const {id,href} of stayTargets){
  stays.click(href,{stayOfferId:id}); // same merchant URL outside the block
  stays.click(href,{sourcePinnedStayBlock:true}); // no offer container
  stays.click(href,{sourcePinnedStayBlock:true,stayOfferId:'wrong-offer'});
  stays.click(href,{sourcePinnedStayBlock:true,stayOfferId:id,trusted:false});
  stays.click(href+'&extra=bad',{sourcePinnedStayBlock:true,stayOfferId:id});
  stays.click(href+'#fragment',{sourcePinnedStayBlock:true,stayOfferId:id});
  stays.click(href.replace('https://','http://'),{sourcePinnedStayBlock:true,stayOfferId:id});
  stays.click(href.replace('https://','https://evil.'),{sourcePinnedStayBlock:true,stayOfferId:id});
}
stays.click('https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fother%2F',
  {sourcePinnedStayBlock:true,stayOfferId:'hportal-penzion-janata'});
assert.equal(stays.calls.length,clickCount,'untrusted, tampered or misplaced stay links ignored');
const staysNoGtag=browser({page:stayPath,gtagEnabled:false});
staysNoGtag.click(stayTargets[0].href,
  {sourcePinnedStayBlock:true,stayOfferId:stayTargets[0].id});
assert.equal(staysNoGtag.calls.length,0,'never override site consent');
const unrelated=browser({page:'/jak-vybrat-rybarske-kreslo/'});
unrelated.click(stayTargets[0].href,
  {sourcePinnedStayBlock:true,stayOfferId:stayTargets[0].id});
assert.equal(unrelated.calls.length,0,'no broad merchant hostname tracking');
console.log('PASS: five exact stay offers measured on trusted click only, no new tracker');
