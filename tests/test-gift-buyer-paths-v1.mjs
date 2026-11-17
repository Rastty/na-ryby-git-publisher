import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';

const script=readFileSync('assets/nry-gift-buyer-v1.js','utf8');
for(const forbidden of ['preventDefault(', 'sendBeacon(', 'fetch(', 'XMLHttpRequest',
    'document.cookie', 'localStorage', 'sessionStorage', 'location.assign', 'location.replace']) {
  assert.ok(!script.includes(forbidden),'no navigation or privacy side effects: '+forbidden);
}
const routes={night:'/celovky-pro-rybare-porovnani-cen-a-vykonu/',
  spinning:'/nejlepsi-privlacove-pruty-do-3-000-kc/',
  feeder:'/nejlepsi-method-feeder-pruty-do-3-500-kc/',
  camp:'/nejlepsi-rybarske-bivaky-do-10-000-kc/'};
function browser(path,{origin='https://na-ryby.eu',gtag=true}={}){
  const listeners=[],events=[];
  vm.runInNewContext(script,{
    URL,Object,
    location:{origin,pathname:path,href:origin+path},
    window:gtag?{gtag:(...args)=>events.push(args)}:{},
    document:{addEventListener:(name,listener,opts)=>{
      assert.equal(name,'click');assert.equal(opts.passive,true);assert.equal(opts.capture,true);
      listeners.push(listener);
    }}
  },{timeout:1000});
  const click=(key,href,trusted=true,inPanel=true)=>{
    const link={href,getAttribute:()=>key};
    const target={closest:selector=>inPanel && selector==='nav[data-nry-gift-buyer="v1"] a[data-nry-gift-path]'?link:null};
    for(const listener of listeners)listener({isTrusted:trusted,target});
  };
  return {click,events,listeners};
}
for(const [page,role] of [
  ['/nejlepsi-darky-pro-rybare-tipy-na-nadcasove-a-prakticke-prezentace/','gift_evergreen'],
  ['/novinky-pro-rybare-nejzajimavejsi-vanocni-darky-na-trhu/','gift_christmas']]){
  const ui=browser(page);assert.equal(ui.listeners.length,1);
  for(const [key,path] of Object.entries(routes)){
    ui.click(key,'https://na-ryby.eu'+path);
    assert.equal(ui.events.at(-1)[0],'event');
    assert.equal(ui.events.at(-1)[1],'nry_buyer_guide_click');
    assert.equal(ui.events.at(-1)[2].buyer_page,role);
    assert.equal(ui.events.at(-1)[2].guide_type,'gift_'+key);
    assert.deepEqual(Object.keys(ui.events.at(-1)[2]).sort(),['buyer_page','guide_type','transport_type'].sort());
    assert.ok(!JSON.stringify(ui.events.at(-1)).includes('https://'));
  }
  const n=ui.events.length;
  ui.click('night','https://na-ryby.eu'+routes.night,false);
  ui.click('night','https://na-ryby.eu'+routes.night,true,false);
  ui.click('night','https://na-ryby.eu'+routes.night+'?src=x');
  ui.click('night','https://na-ryby.eu'+routes.night+'#old');
  ui.click('night','https://example.com'+routes.night);
  ui.click('camp','https://na-ryby.eu'+routes.night);
  ui.click('fake','https://na-ryby.eu'+routes.night);
  assert.equal(ui.events.length,n,'no synthetic or altered guide click');
  const blocked=browser(page,{gtag:false});blocked.click('night','https://na-ryby.eu'+routes.night);
  assert.equal(blocked.events.length,0,'no analytics when existing gtag absent');
}
for(const other of ['/','/poradce-vybavy/','/nejlepsi-rybarske-bivaky-do-10-000-kc/']){
  assert.equal(browser(other).listeners.length,0);
}
assert.equal(browser('/novinky-pro-rybare-nejzajimavejsi-vanocni-darky-na-trhu/',
  {origin:'https://other.example'}).listeners.length,0,'different domain blocked');
console.log('PASS exact archived gift routes; genuine first-party clicks; zero side effects');
