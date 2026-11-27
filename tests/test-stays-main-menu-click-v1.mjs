import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source=fs.readFileSync(new URL('../assets/nry-stays-menu-click-v1.js',import.meta.url),'utf8');
const ORIGIN='https://na-ryby.eu';
const HUB=ORIGIN+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
const OPTIONS=[
  [ORIGIN+'/rybarska-chata-cyklochata-vranovska-prehrada/','cyklochata'],
  [ORIGIN+'/chata-rybarsky-wellness-srub/','wellness_srub']
];
function run({kind=0,origin=ORIGIN,href=null,raw=null,
   trusted=true,gtag=true,parentHub=HUB,exactChild=true,
   exactParent=true,parentExists=true,subsection=true,menuChildren=1}={}) {
  const events=[];let handler=null;let options=null;
  const chosen=href??OPTIONS[kind][0];
  const link={
    href:chosen,
    getAttribute:name=>name==='href'?(raw??chosen):null,
    closest:sel=>sel==='li.nry-stays-menu-choice-v1'&&subsection?child:null
  };
  const child={
    querySelectorAll:sel=>sel==='a[href]'?(menuChildren===1?[link]:[link,link]):[],
    parentElement:{
      closest:sel=>sel==='li.menu-item-has-children'&&parentExists?parent:null
    }
  };
  const parent={
    querySelector:sel=>sel==='a[href]'&&exactParent?{
      getAttribute:name=>name==='href'?parentHub:null
    }:null
  };
  const context={
    location:{origin,pathname:'/another/article/',search:'?utm=visitor'},
    document:{addEventListener:(type,fn,opts)=>{assert.equal(type,'click');handler=fn;options=opts;}},
    window:gtag?{gtag:(...args)=>events.push(args)}:{},
    URL
  };
  vm.runInNewContext(source,context,{timeout:1000});
  if(handler)handler({isTrusted:trusted,target:{closest:()=>exactChild?link:{
    ...link, closest:()=>null
  }}});
  return {events,handler,options};
}
for(const [kind,[href,guide]] of OPTIONS.entries()) {
  const ok=run({kind});
  assert.equal(ok.events.length,1,'real verified menu click observed');
  assert.equal(ok.events[0][0],'event');
  assert.equal(ok.events[0][1],'nry_buyer_guide_click');
  assert.equal(ok.events[0][2].buyer_page,'sitewide_stays_menu');
  assert.equal(ok.events[0][2].guide_type,guide);
  assert.equal(ok.events[0][2].transport_type,'beacon');
  assert.deepEqual(Object.keys(ok.events[0][2]).sort(),
    ['buyer_page','guide_type','transport_type']);
  assert.ok(!JSON.stringify(ok.events).includes('http'),'never track raw href or user URL');
  assert.equal(ok.options.capture,true);
  assert.equal(ok.options.passive,true);
  assert.equal(run({kind,trusted:false}).events.length,0);
  assert.equal(run({kind,gtag:false}).events.length,0);
  assert.equal(run({kind,href:href+'?utm=unsafe'}).events.length,0);
  assert.equal(run({kind,href:'https://merchant.example/click'}).events.length,0);
  assert.equal(run({kind,raw:'/chata-rybarsky-wellness-srub/'}).events.length,0);
  assert.equal(run({kind,parentHub:ORIGIN+'/ubytovani-2/'}).events.length,0);
  assert.equal(run({kind,parentHub:ORIGIN+'/not-guide/'}).events.length,0);
  assert.equal(run({kind,exactParent:false}).events.length,0);
  assert.equal(run({kind,parentExists:false}).events.length,0);
  assert.equal(run({kind,subsection:false}).events.length,0);
  assert.equal(run({kind,menuChildren:2}).events.length,0);
  assert.equal(run({kind,exactChild:false}).events.length,0);
}
assert.equal(run({origin:'https://untrusted.example'}).handler,null);
console.log('STAYS_MAIN_MENU_TRUSTED_CLICK_OFFLINE_VERIFIED');
