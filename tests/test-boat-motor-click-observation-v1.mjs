import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const script=readFileSync('assets/nry-boat-motor-click-v1.js','utf8');
for(const danger of ['preventDefault(', 'sendBeacon(', 'fetch(', 'XMLHttpRequest',
    'document.cookie', 'localStorage', 'sessionStorage', 'location.assign', 'location.replace']) {
  assert.ok(!script.includes(danger),'Never alter navigation or create tracking: '+danger);
}
function browser(path, {host='https://na-ryby.eu',gtagEnabled=true}={}) {
  const registered=[], calls=[];
  const base=new URL(path,host);
  vm.runInNewContext(script,{
    URL, Set, Object,
    location:{origin:base.origin,pathname:base.pathname,href:base.href},
    window:gtagEnabled?{gtag:(...args)=>calls.push(args)}:{},
    document:{addEventListener:(kind,callback,opts)=>{
      assert.equal(kind,'click');assert.equal(opts.passive,true);
      assert.equal(opts.capture,true);registered.push(callback);
    }}
  },{timeout:1000});
  const click=(href,trusted=true)=>{
    const link={href};
    const target={closest:q=>q==='a[href]'?link:null};
    for(const callback of registered)callback({target,isTrusted:trusted});
  };
  return {registered,calls,click};
}
const pages=[
  ['/nejlepsi-lodni-elektromotory-pro-rybare/','motor_electric'],
  ['/nejlepsi-nafukovaci-cluny-pro-rybare/','boat_inflatable']
];
const destinations=['/Elektromotory-265/','/Cluny-228/',
  '/Baterie-nabijecky-k-elektromotorum/'];
const link=path=>'https://www.tkqlhce.com/click-100927011-12347575?url='+
  encodeURIComponent('https://www.chytapust.cz'+path);
for(const [route,role] of pages) {
  const client=browser(route);
  assert.equal(client.registered.length,1);
  for(const destination of destinations) {
    client.click(link(destination));
    const call=client.calls.at(-1);
    assert.equal(call[0],'event');assert.equal(call[1],'nry_buyer_offer_click');
    assert.equal(call[2].buyer_page,role);
    assert.equal(call[2].buyer_cta_type,'cj_other_identity_unverified');
    assert.equal(call[2].transport_type,'beacon');
    assert.deepEqual(Object.keys(call[2]).sort(),['buyer_page','buyer_cta_type','transport_type'].sort());
    assert.ok(!JSON.stringify(call).includes('chytapust'));
    assert.ok(!JSON.stringify(call).includes('100927011'));
  }
  const count=client.calls.length;
  for(const bad of [
    link('/Bivaky/'),link('/Cluny-228/').replace('100927011','8287231'),
    link('/Cluny-228/').replace('12347575','12347576'),
    link('/Cluny-228/')+'&secret=x',
    link('/Cluny-228/').replace('www.tkqlhce.com','www.tkqlhce.com.evil.invalid'),
    link('/Cluny-228/').replace('https://www.tkqlhce.com','http://www.tkqlhce.com'),
    link('/Cluny-228/').replace('www.chytapust.cz','evil.example'),
    link('/Cluny-228/')+'#sensitive',
    link('/Cluny-228/?campaign=secret'),
    'https://www.chytapust.cz/Cluny-228/'
  ])client.click(bad);
  client.click(link('/Cluny-228/'),false);
  assert.equal(client.calls.length,count,'all unscoped/insecure/untrusted clicks ignored');
  const noConsent=browser(route,{gtagEnabled:false});
  noConsent.click(link('/Cluny-228/'));
  assert.equal(noConsent.calls.length,0,'existing gtag controls consent');
}
for(const route of ['/poradce-vybavy/','/zavazeci-lodky-pro-rybare/','/']){
  assert.equal(browser(route).registered.length,0,'no unrelated page instrumented');
}
assert.equal(browser(pages[0][0],{host:'https://other.example'}).registered.length,0,
  'different host blocked');
console.log('PASS exact two-guide real-click observer, strict 26-link shape and zero side effects');
