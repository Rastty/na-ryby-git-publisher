import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';

const source = fs.readFileSync(new URL('../assets/nry-advisor-scenarios-v1.js', import.meta.url), 'utf8');
assert(!/fetch\s*\(|sendBeacon\s*\(|XMLHttpRequest|document\.cookie|localStorage|location\s*=|window\.open/.test(source),
  'No new network endpoint, cookie, redirect or merchant request');

function browser({origin='https://na-ryby.eu', pathname='/poradce-vybavy/',
  badOption=false, withRoot=true, requestSubmit=true, gtagThrows=false}={}) {
  const inserted=[], events=[];
  let sends=0, clears=0;
  const fields={before(node){inserted.push(node);}};
  const panel={hidden:false,replaceChildren(){clears++;}};
  const allowed={
    style:['kapr','feeder','privlac'],water:['mala','reka','velka'],
    travel:['pesky','auto'],experience:['zacatecnik','zkuseny'],
    budget:['3000','5000','10000']
  };
  const elements=Object.fromEntries(Object.entries(allowed).map(([name,values])=>[
    name,{tagName:'SELECT',options:values.map(value=>({value})),value:values[0]}
  ]));
  if(badOption)elements.water.options[0].value='changed-by-editor';
  const form={elements,
    querySelector(sel){return sel==='.nry-fields'?fields:null;},
    ...(requestSubmit?{requestSubmit(){sends++;}}:{})};
  const root={querySelector(sel){
    if(sel==='form.nry-setup__form')return form;
    if(sel==='[data-nry-results]')return panel;
    if(sel==='[data-nry-advisor-scenarios="v1"]')return inserted[0]||null;
    return null;
  }};
  const document={readyState:'complete',
    querySelector(sel){return sel==='.nry-setup'&&withRoot?root:null;},
    createElement(tag){return {
      tag,className:'',textContent:'',children:[],listeners:{},
      setAttribute(key,value){this[key]=value;},
      append(...nodes){this.children.push(...nodes);},
      addEventListener(event,listener){this.listeners[event]=listener;}
    };}
  };
  const window={gtag(...args){if(gtagThrows)throw Error('consent blocked');events.push(args);}};
  const location={origin,pathname};
  const context={document,window,location,console};
  vm.runInNewContext(source,context,{timeout:3000});
  vm.runInNewContext(source,context,{timeout:3000});
  return {inserted,events,elements,panel,click(index,trusted=true){
    const button=inserted[0]?.children?.[2]?.children?.[index];
    assert(button, 'scenario button exists');
    button.listeners.click({isTrusted:trusted});
  },get sends(){return sends;},get clears(){return clears;}};
}

const live=browser();
assert.equal(live.inserted.length,1,'one panel injected once');
assert.equal(live.inserted[0].children[2].children.length,3,'three usable scenarios');
live.click(1);
assert.equal(live.elements.style.value,'feeder');
assert.equal(live.elements.water.value,'reka');
assert.equal(live.elements.budget.value,'5000');
assert.equal(live.sends,1,'existing submit handler reused exactly once');
assert.equal(live.panel.hidden,true,'stale result hidden before recalculation');
assert.equal(live.clears,1,'stale result emptied');
assert.deepEqual(JSON.parse(JSON.stringify(live.events[0])),[
  'event','nry_advisor_scenario_select',{scenario_type:'feeder_river',transport_type:'beacon'}
]);
live.click(2);
assert.equal(live.elements.style.value,'privlac');
assert.equal(live.sends,2);
live.click(0);
assert.equal(live.elements.style.value,'kapr');
assert.equal(live.elements.budget.value,'3000');
assert.equal(live.sends,3);
live.click(0,false);
assert.equal(live.sends,3,'synthetic click ignored');
const blocked=browser({badOption:true});
assert.equal(blocked.inserted.length,0,'source option drift fails closed');
assert.equal(browser({pathname:'/other/'}).inserted.length,0);
assert.equal(browser({origin:'https://other.example'}).inserted.length,0);
assert.equal(browser({withRoot:false}).inserted.length,0);
const noAnalytics=browser({gtagThrows:true});
noAnalytics.click(0);
assert.equal(noAnalytics.sends,1,'analytics failure does not block advice');
console.log('PASS: three real one-click advisor scenarios, exact selectors, no new external URLs');
