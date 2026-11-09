import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

const src=readFileSync('assets/nry-reel-handoff-v1.js','utf8');
const events=[],registered={old:[],situations:[]};
let chosen='';
const anchors={
  old:{getAttribute:k=>k==='href'?chosen:null},
  situations:{getAttribute:k=>k==='href'?chosen:null}
};
function nav(scope){return {
  contains:el=>el===anchors[scope],
  addEventListener:(type,fn,opts)=>{
    assert.equal(type,'click');
    assert.equal(opts.capture,true);
    assert.equal(opts.passive,true);
    registered[scope].push(fn);
  }
}}
const old=nav('old'),situations=nav('situations');
const document={readyState:'complete',querySelector:selector=>
  selector==='nav[data-nry-reel-nextsteps="v1"]'?old:
  selector==='nav[data-nry-reel-situations="v1"]'?situations:null};
const window={gtag:(...args)=>events.push(args)};
const location={origin:'https://na-ryby.eu',pathname:'/navijaky-dashboard/',search:'',hash:''};
const context={document,window,location,URL,Object};
vm.runInNewContext(src,context,{timeout:1000});
vm.runInNewContext(src,context,{timeout:1000});
assert.equal(registered.old.length,1,'existing bottom-nav listener kept once');
assert.equal(registered.situations.length,1,'new situation listener registered once');
function click(scope,href,options={}){
  chosen=href;
  const target={closest:k=>k==='a[href]'&&options.inside!==false?anchors[scope]:null};
  for(const handler of registered[scope])handler({target,isTrusted:options.trusted!==false});
}
const expectedOld=[
  ['/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/','reel_guide'],
  ['/nejlepsi-feederove-navijaky-srovnani/','feeder_reels'],
  ['/pece-o-navijak-po-sezone-jak-ho-neznicit-spatnym-mazanim/','reel_care'],
  ['/poradce-vybavy/','advisor']
];
const expectedSituations=[
  ['/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/','beginner_reel'],
  ['/nejlepsi-privlacove-navijaky-jak-vybrat/','spinning_reels'],
  ['/nejlepsi-feederove-navijaky-srovnani/','feeder_situations'],
  ['/nejlepsi-kaprove-navijaky/','carp_reels']
];
for(const [scope,rows] of [['old',expectedOld],['situations',expectedSituations]]){
  for(const [path,role] of rows){
    click(scope,'https://na-ryby.eu'+path);
    const e=events.at(-1);
    assert.equal(e[0],'event');
    assert.equal(e[1],'nry_reel_guide_click');
    assert.deepEqual(JSON.parse(JSON.stringify(e[2])),{
      source_page:'reel_dashboard',guide_type:role,transport_type:'beacon'
    });
    assert(!JSON.stringify(e).includes(path),'never leak raw href');
  }
}
assert.equal(events.length,8);
for(const url of [
  'http://na-ryby.eu/nejlepsi-kaprove-navijaky/',
  'https://na-ryby.eu/nejlepsi-kaprove-navijaky/?v=user',
  'https://na-ryby.eu/nejlepsi-kaprove-navijaky/#anchor',
  'https://other.example/nejlepsi-kaprove-navijaky/',
  'https://na-ryby.eu/unknown/'
])click('situations',url);
click('situations','https://na-ryby.eu/poradce-vybavy/'); // cross-nav forbidden
click('old','https://na-ryby.eu/nejlepsi-kaprove-navijaky/'); // cross-nav forbidden
click('situations','https://na-ryby.eu/nejlepsi-kaprove-navijaky/',{trusted:false});
click('situations','https://na-ryby.eu/nejlepsi-kaprove-navijaky/',{inside:false});
assert.equal(events.length,8,'no test-click or invalid destination passes');
assert(!/preventDefault\s*\(|stopPropagation\s*\(|sendBeacon\s*\(|\bfetch\s*\(|localStorage|document\.cookie/.test(src));
console.log('PASS: 4 existing + 4 situation real-click role paths and zero synthetic dispatch');
