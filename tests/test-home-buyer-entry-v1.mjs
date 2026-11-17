import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {runInNewContext} from 'node:vm';

const src=readFileSync(new URL('../assets/nry-home-buyer-entry-v1.js',import.meta.url),'utf8');
const urls={
  carp_pillar:'/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/',
  echolot_budget:'/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',
  sonar_practical:'/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/',
  baitboat_picker:'/jak-vybrat-zavazeci-lodku/',
  chair_hub:'/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/',
  equipment_advisor:'/poradce-vybavy/'
};
function load(origin='https://na-ryby.eu',pathname='/',search='') {
  let listener;
  const events=[];
  const w={gtag:(...a)=>events.push(a)};
  const document={addEventListener:(name,fn,opts)=>{
    assert.equal(name,'click');
    assert.equal(opts.capture,true); assert.equal(opts.passive,true);
    listener=fn;
  }};
  runInNewContext(src,{window:w,document,location:{origin,pathname,search,href:origin+pathname},URL});
  return {listener,events,window:w};
}
function click(ctx,path,{kind='nav',trusted=true,target='https://na-ryby.eu',query='',hash=''}={}) {
  const link={href:target+path+query+hash,closest:(selector)=>{
    if (selector.startsWith('nav[')) return kind==='nav'?{}:null;
    if (selector.startsWith('p[')) return kind==='advisor'?{}:null;
    return null;
  }};
  const event={isTrusted:trusted,target:{closest:(sel)=>sel==='a[href]'?link:null}};
  ctx.listener?.(event);
}
const ctx=load();
assert.equal(typeof ctx.listener,'function','only exact site and homepage are active');
for (const [type,path] of Object.entries(urls)) {
  click(ctx,path,{kind:type==='equipment_advisor'?'advisor':'nav'});
  const [method,name,params]=ctx.events.at(-1);
  assert.equal(method,'event'); assert.equal(name,'nry_buyer_guide_click');
  assert.equal(params.buyer_page,'homepage');
  assert.equal(params.guide_type,type); assert.equal(params.transport_type,'beacon');
  assert.deepEqual(Object.keys(params).sort(),['buyer_page','guide_type','transport_type']);
}
assert.equal(ctx.events.length,6,'one event for each of six known entries');
const count=ctx.events.length;
click(ctx,urls.carp_pillar,{trusted:false});
click(ctx,urls.carp_pillar,{kind:'advisor'});
click(ctx,urls.equipment_advisor,{kind:'nav'});
click(ctx,urls.chair_hub,{query:'?utm_source=bad'});
click(ctx,urls.chair_hub,{hash:'#section'});
click(ctx,urls.chair_hub,{target:'https://other.example'});
click(ctx,'/unknown/');
assert.equal(ctx.events.length,count,'ignore synthetic, wrong container, altered or offsite destinations');
delete ctx.window.gtag;
click(ctx,urls.carp_pillar);
assert.equal(ctx.events.length,count,'never initialize tracker without consent-ready gtag');
for (const args of [
  ['https://other.example','/',''],['https://na-ryby.eu','/post/',''],
  ['https://na-ryby.eu','/','?preview=true']
]) assert.equal(load(...args).listener,undefined,'unexpected browser location');
console.log('PASS six exact first-party homepage entries, authentic clicks only, no payload URLs or writes');
