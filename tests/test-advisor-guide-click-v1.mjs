import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../assets/nry-advisor-guide-click-v1.js', import.meta.url), 'utf8');
assert(!/fetch\s*\(|sendBeacon\s*\(|XMLHttpRequest|localStorage|document\.cookie|preventDefault\s*\(/.test(source),
  'No new transport, cookies, visitor state or navigation interception');
const guides = Object.freeze({
  '/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/':'carp_rod',
  '/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/':'reel',
  '/nejlepsi-privlacove-navijaky-jak-vybrat/':'spinning_reel',
  '/nejlepsi-feederove-navijaky-srovnani/':'feeder_reel'
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
assert.equal(good.callbacks.filter(x=>x.type==='click').length,1,'exactly one click handler');
assert.equal(good.callbacks.filter(x=>x.type==='submit').length,1,'exactly one safe result repair handler');
for(const [href,role] of Object.entries(guides)){
  good.click('https://na-ryby.eu'+href);
  assert.equal(good.events.at(-1)[0],'event');
  assert.equal(good.events.at(-1)[1],'nry_advisor_guide_click');
  assert.deepEqual(JSON.parse(JSON.stringify(good.events.at(-1)[2])),{
    advisor_version:4,guide_type:role,destination_kind:'internal_buying_guide'
  });
}
assert.equal(good.events.length,4);
for(const href of [
 'https://www.chytapust.cz/kaprove-pruty/',
 'https://evil.example/whatever/',
 'https://na-ryby.eu/poradce-vybavy/',
 '/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/?user=123',
 '/vse-o-kaprovych-prutech-jak-vybrat-delku-akci-a-libraz/#f',
 '//evil.example/link',
 'javascript:alert(1)'
])good.click(href);
assert.equal(good.events.length,4,'arbitrary merchant/query/URL never measured');
good.click(Object.keys(guides)[0],{trusted:false});
good.click(Object.keys(guides)[0],{resultVisible:false});
good.click(Object.keys(guides)[0],{guide:false});
assert.equal(good.events.length,4,'untrusted or stale/hidden result never measured');
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


function feederHarness({style='feeder', title='Feeder – sestava pro řeku',
  originalHref='https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/',
  label='Průvodce výběrem rybářského navijáku →', visible=true,
  anchorsCount=1, expectedForm=true, trusted=true}={}) {
  const callbacks=[], appended=[];
  const anchor={
    href:originalHref,textContent:label,
    getAttribute(name){assert.equal(name,'href');return originalHref;}
  };
  const links={
    querySelectorAll(name){assert.equal(name,'a[href]');return anchorsCount===1?[anchor]:[anchor,anchor];},
    append(element){appended.push(element);}
  };
  const panel={
    hidden:!visible,
    querySelector(name){
      if(name==='.nry-result__title')return {textContent:title};
      if(name==='.nry-result__links')return links;
      return null;
    }
  };
  const form={elements:{style:{value:style}}};
  const root={
    querySelector(name){
      if(name==='form')return form;
      if(name==='[data-nry-results]')return panel;
      return null;
    },
    addEventListener(type,callback){callbacks.push({type,callback});}
  };
  const document={
    readyState:'complete',
    querySelector(name){return name==='.nry-setup'?root:null;},
    createElement(name){assert.equal(name,'a');return {href:null,textContent:''};}
  };
  const location={origin:'https://na-ryby.eu',pathname:'/poradce-vybavy/',search:'',hash:''};
  const window={dataLayer:[]};
  vm.runInNewContext(source,{URL,window,location,document},{timeout:5000});
  const submit=callbacks.find(x=>x.type==='submit');
  assert(submit,'submit listener installed');
  submit.callback({isTrusted:trusted,target:expectedForm?form:{other:true}});
  return {anchor,appended,callbacks};
}
const fixed=feederHarness();
assert.equal(fixed.anchor.href,'https://na-ryby.eu/nejlepsi-feederove-navijaky-srovnani/');
assert.equal(fixed.anchor.textContent,'Feederové navijáky: praktické srovnání →');
assert.equal(fixed.appended.length,1);
assert.equal(fixed.appended[0].href,'https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/');
for (const config of [
  {style:'kapr'},{style:'privlac'},{title:'Editor adjusted result'},
  {originalHref:'https://evil.example/'},{label:'Modified by editor'},
  {visible:false},{anchorsCount:2},{expectedForm:false},{trusted:false}
]) {
  const other=feederHarness(config);
  assert.equal(other.appended.length,0,'guard blocks a nonmatching feeder context');
  assert.equal(other.anchor.href,config.originalHref||'https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/');
}
console.log('PASS: relevant feeder buyer guide first, general guide retained, fail-closed guards');


function testChange({field='style',tag='SELECT',fromForm=true,shown=true}={}) {
  const handlers=[];
  let clears=0;
  const form={};
  const hint={hidden:true};
  const panel={hidden:!shown,replaceChildren(){clears++;}};
  const root={
    querySelector(selector){
      if(selector==='form')return form;
      if(selector==='[data-nry-results]')return panel;
      if(selector==='[data-nry-refresh]')return hint;
      return null;
    },
    addEventListener(type,cb){handlers.push({type,cb});}
  };
  const document={readyState:'complete',querySelector(s){return s==='.nry-setup'?root:null;}};
  const window={dataLayer:[]};
  const location={origin:'https://na-ryby.eu',pathname:'/poradce-vybavy/',search:'',hash:''};
  vm.runInNewContext(source,{URL,document,window,location},{timeout:5000});
  const onChange=handlers.find(x=>x.type==='change');
  assert(onChange,'single root change handler');
  onChange.cb({target:{name:field,tagName:tag,form:fromForm?form:{}}});
  return {clears,hidden:panel.hidden,hintVisible:!hint.hidden};
}
for(const field of ['style','water','travel','experience','budget']){
  const x=testChange({field});
  assert.deepEqual(x,{clears:1,hidden:true,hintVisible:true},'decision field invalidates stale UI');
}
for(const invalid of [
  {field:'unrelated'},{tag:'INPUT'},{fromForm:false},{shown:false}
]){
  const x=testChange(invalid);
  assert.equal(x.clears,0,'unrelated or inactive change does not mutate result');
}
console.log('PASS: any decision change hides stale results and removes old links before next submit');
