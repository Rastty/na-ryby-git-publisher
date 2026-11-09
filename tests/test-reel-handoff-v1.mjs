import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

const source=readFileSync('assets/nry-reel-handoff-v1.js','utf8');
assert(!/preventDefault\s*\(|stopPropagation\s*\(|sendBeacon\s*\(|\bfetch\s*\(|XMLHttpRequest|localStorage|sessionStorage|document\.cookie/.test(source));
assert(!/\.href\s*=|location\.(?:assign|replace)/.test(source));

const paths={
 '/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/':'reel_guide',
 '/nejlepsi-feederove-navijaky-srovnani/':'feeder_reels',
 '/pece-o-navijak-po-sezone-jak-ho-neznicit-spatnym-mazanim/':'reel_care',
 '/poradce-vybavy/':'advisor'
};
function browser({pathname='/navijaky-dashboard/',origin='https://na-ryby.eu',search='',hash='',gtag=true,nav=true,throwing=false,loading=false}={}){
 const callbacks=[],events=[],ready=[];
 let raw='';
 const anchor={getAttribute:k=>k==='href'?raw:null};
 const navObj={
   contains: a=>a===anchor,
   addEventListener: (type,cb,opts)=>{
     assert.equal(type,'click');
     assert.equal(opts.capture,true); assert.equal(opts.passive,true);
     callbacks.push(cb);
   }
 };
 const document={
   readyState:loading?'loading':'complete',
   querySelector:selector=>selector==='nav[data-nry-reel-nextsteps="v1"]'&&nav?navObj:null,
   addEventListener:(type,fn)=>{assert.equal(type,'DOMContentLoaded');ready.push(fn);}
 };
 const window={};
 if(gtag)window.gtag=(...args)=>{if(throwing)throw Error('analytics failed');events.push(args);};
 const location={origin,pathname,search,hash};
 const context={window,location,document,URL,Object};
 vm.runInNewContext(source,context,{timeout:1000});
 vm.runInNewContext(source,context,{timeout:1000});
 if(loading)ready.forEach(fn=>fn());
 return {
   callbacks,events,
   click:(href,{trusted=true,inside=true}={})=>{
     raw=href;
     const el={closest:sel=>sel==='a[href]'&&inside?anchor:null};
     for(const fn of callbacks)fn({isTrusted:trusted,target:el});
   }
 };
}
const good=browser();
assert.equal(good.callbacks.length,1,'no duplicate handlers');
for(const [path,role] of Object.entries(paths)){
 good.click('https://na-ryby.eu'+path);
 const evt=good.events.at(-1);
 assert.equal(evt[0],'event');
 assert.equal(evt[1],'nry_reel_guide_click');
 assert.deepEqual(JSON.parse(JSON.stringify(evt[2])),{
   source_page:'reel_dashboard',guide_type:role,transport_type:'beacon'
 });
 assert(!JSON.stringify(evt).includes(path),'no raw destination URL');
}
assert.equal(good.events.length,4);
for(const href of [
 'https://other.example/poradce-vybavy/',
 'https://na-ryby.eu/poradce-vybavy/?my=email',
 'https://na-ryby.eu/poradce-vybavy/#user',
 'http://na-ryby.eu/poradce-vybavy/',
 'https://na-ryby.eu/unknown/',
 'https://user:pass@na-ryby.eu/poradce-vybavy/',
 'javascript:alert(1)'
])good.click(href);
good.click('https://na-ryby.eu/poradce-vybavy/',{trusted:false});
good.click('https://na-ryby.eu/poradce-vybavy/',{inside:false});
assert.equal(good.events.length,4,'no offsite/untrusted/outside navigation');
const noAnalytics=browser({gtag:false});noAnalytics.click('https://na-ryby.eu/poradce-vybavy/');
assert.equal(noAnalytics.events.length,0);
const badAnalytics=browser({throwing:true});badAnalytics.click('https://na-ryby.eu/poradce-vybavy/');
assert.equal(badAnalytics.events.length,0);
assert.equal(browser({pathname:'/poradce-vybavy/'}).callbacks.length,0);
assert.equal(browser({origin:'https://other.example'}).callbacks.length,0);
assert.equal(browser({search:'?q=me'}).callbacks.length,0);
assert.equal(browser({hash:'#x'}).callbacks.length,0);
assert.equal(browser({nav:false}).callbacks.length,0);
const delayed=browser({loading:true});assert.equal(delayed.callbacks.length,1);
console.log('PASS: 4 exact real handoffs, first-party only, no affiliate events or telemetry without gtag');
