import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../assets/nry-advisor-guide-click-v1.js', import.meta.url), 'utf8');
assert(!/fetch\s*\(|sendBeacon\s*\(|XMLHttpRequest|localStorage|document\.cookie|preventDefault\s*\(/.test(source),
  'No new transport, cookies, visitor state or navigation interception');
const guides = Object.freeze({
  '/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/':'carp_rod',
  '/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/':'reel',
  '/nejlepsi-privlacove-navijaky-jak-vybrat/':'spinning_reel'
});
function browser({origin='https://na-ryby.eu',pathname='/poradce-vybavy/',
  gtag=true,withRoot=true,analyticsThrows=false}={}) {
  const callbacks=[],events=[],dataLayer=[];
  let rawHref=null,visible=true,guideSelected=true;
  const panel={get hidden(){return !visible;}};
  const anchor={
    getAttribute(key){assert.equal(key,'href');return rawHref;},
    closest(sel){return sel==='[data-nry-results]'?panel:null;}
  };
  const root={
    querySelector(sel){return sel==='[data-nry-results]'?panel:null;},
    addEventListener(type,callback){callbacks.push({type,callback});}
  };
  const document={
    readyState:'complete',
    querySelector(sel){return sel==='.nry-setup'&&withRoot?root:null;}
  };
  const window={dataLayer};
  if(gtag)window.gtag=(...args)=>{
    if(analyticsThrows)throw Error('analytics unavailable');
    events.push(args);
  };
  const location={origin,pathname,search:'',hash:''};
  const context={URL,window,location,document};
  vm.runInNewContext(source,context,{timeout:5000});
  vm.runInNewContext(source,context,{timeout:5000}); // no duplicate handlers
  return {
    callbacks,events,dataLayer,
    click(href,{trusted=true,resultVisible=true,guide=true}={}){
      rawHref=href;visible=resultVisible;guideSelected=guide;
      for(const cb of callbacks.filter(x=>x.type==='click'))cb.callback({
        isTrusted:trusted,
        target:{closest(sel){
          return sel==='[data-nry-results] .nry-result__links a[href]'&&guideSelected?anchor:null;
        }}
      });
    }
  };
}
const good=browser();
assert.equal(good.callbacks.length,1,'exactly one delegated handler');
for(const [href,role] of Object.entries(guides)){
  good.click('https://na-ryby.eu'+href);
  assert.equal(good.events.at(-1)[0],'event');
  assert.equal(good.events.at(-1)[1],'nry_advisor_guide_click');
  assert.deepEqual(JSON.parse(JSON.stringify(good.events.at(-1)[2])),{
    advisor_version:4,guide_type:role,destination_kind:'internal_buying_guide'
  });
}
assert.equal(good.events.length,3);
for(const href of [
 'https://www.chytapust.cz/kaprove-pruty/',
 'https://evil.example/whatever/',
 'https://na-ryby.eu/poradce-vybavy/',
 '/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/?user=123',
 '/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/#f',
 '//evil.example/link',
 'javascript:alert(1)'
])good.click(href);
assert.equal(good.events.length,3,'arbitrary merchant/query/URL never measured');
good.click(Object.keys(guides)[0],{trusted:false});
good.click(Object.keys(guides)[0],{resultVisible:false});
good.click(Object.keys(guides)[0],{guide:false});
assert.equal(good.events.length,3,'untrusted or stale/hidden result never measured');
assert(!JSON.stringify(good.events).includes('chytapust'));
const layer=browser({gtag:false});
layer.click(Object.keys(guides)[1]);
assert.equal(layer.dataLayer.length,1);
assert.equal(layer.dataLayer[0].event,'nry_advisor_guide_click');
assert.equal(layer.dataLayer[0].guide_type,'reel');
const throwing=browser({analyticsThrows:true});
throwing.click(Object.keys(guides)[2]);
assert.equal(throwing.events.length,0,'analytics failures must not break guide navigation');
assert.equal(browser({pathname:'/wrong-article/'}).callbacks.length,0);
assert.equal(browser({origin:'https://other.example'}).callbacks.length,0);
assert.equal(browser({withRoot:false}).callbacks.length,0);
console.log('PASS: real advisor V4 internal-guide clicks only, no synthetic events, no private URL, zero writes');
