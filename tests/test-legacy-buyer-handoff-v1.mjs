// Run actual production asset in isolated VM with a fake DOM and fake gtag.
// No browser network, no real analytics account and no generated production clicks.
import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const js=fs.readFileSync(new URL('../assets/nry-legacy-buyer-handoff-v1.js',import.meta.url),'utf8');
const origin='https://na-ryby.eu';
const cases=[
 ['/recenze-a-zkusenosti-lowrance-echolot-hds-live-7-se-sondou-active-imaging-3v1-baterie-nabijecka-zdarma/','legacy_hds_live_7','aside[data-nry-legacy-sonar-bridge="v1"][data-nry-legacy-sonar-kind="hds_live_7"]','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/','sonar_pillar'],
 ['/lowrance-echolot-hook-4-chirp-gps/','legacy_hook_4','aside[data-nry-legacy-sonar-bridge="v1"][data-nry-legacy-sonar-kind="hook_4"]','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/','sonar_pillar'],
 ['/holdcarp-solarni-panel-foldable-solar-system-20w-recenze/','holdcarp_solar_20w','aside[data-nry-holdcarp-energy-bridge="v1"][data-nry-holdcarp-model="20W"]','/powerbanka-nebo-solarni-panel-na-ryby/','energy_wh_guide'],
 ['/holdcarp-solarni-panel-foldable-solar-system-40w-recenze/','holdcarp_solar_40w','aside[data-nry-holdcarp-energy-bridge="v1"][data-nry-holdcarp-model="40W"]','/powerbanka-nebo-solarni-panel-na-ryby/','energy_wh_guide']
];
function fixture(row,opts={}){
 const [pathname,role,selector,dest,guide]=row;
 const events=[],handlers=[];
 const panel={querySelectorAll:s=>s==='a[href]'?(opts.extraAnchor?[link,{}]:[link]):[]};
 const link={href:opts.href??origin+dest,getAttribute:k=>k==='href'?(opts.raw??opts.href??origin+dest):null,
   closest:s=>s===selector&&!opts.outside?panel:null};
 const location={origin:opts.origin??origin,pathname:opts.path??pathname,search:opts.search??''};
 const document={querySelectorAll:s=>s===selector?(opts.extraPanel?[panel,{}]:[panel]):[],
   addEventListener:(name,fn,config)=>{assert.equal(name,'click');assert.equal(config.capture,true);assert.equal(config.passive,true);handlers.push(fn);}};
 const window=opts.noGtag?{}:{gtag:(...args)=>{if(opts.throwGtag)throw Error('offline test');events.push(JSON.parse(JSON.stringify(args)));}};
 const context=vm.createContext({document,window,location,URL});
 vm.runInContext(js,context);vm.runInContext(js,context);
 const click=(trusted=true)=>{for(const fn of handlers)fn({isTrusted:trusted,target:{closest:s=>s==='a[href]'?link:null}});};
 return {events,handlers,click,expected:['event','nry_buyer_guide_click',{buyer_page:role,guide_type:guide,transport_type:'beacon'}]};
}
for(const row of cases){
 const good=fixture(row);assert.equal(good.handlers.length,1,'one listener even with repeated script');
 good.click(false);assert.equal(good.events.length,0,'synthetic rejected');
 good.click();assert.deepEqual(good.events,[good.expected]);
 for(const opts of [{outside:true},{extraPanel:true},{extraAnchor:true},{href:'https://example.com/'},
   {href:origin+row[3]+'?x=1'},{href:origin+row[3]+'#x'},{raw:row[3]},
   {origin:'https://other.example'},{path:'/unrelated/'},{search:'?x=1'},
   {noGtag:true},{throwGtag:true}]){
    const bad=fixture(row,opts);bad.click();assert.equal(bad.events.length,0,JSON.stringify(opts));
 }
}
assert(!/fetch\s*\(|sendBeacon\s*\(|XMLHttpRequest|document\.cookie|localStorage|sessionStorage|preventDefault\s*\(/.test(js));
console.log('LEGACY_BUYER_HANDOFF_JS_PASS four exact events and negative cases; mocked analytics only');
