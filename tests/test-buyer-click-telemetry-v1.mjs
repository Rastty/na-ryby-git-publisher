import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
const script=readFileSync('assets/nry-buyer-clicks-v1.js','utf8');
assert.ok(!/preventDefault\s*\(/.test(script));
assert.ok(!/\.href\s*=|location\.assign|location\.replace/.test(script));
assert.ok(!/sendBeacon\s*\(/.test(script));
assert.ok(!/localStorage|sessionStorage|document\.cookie/.test(script));

function browser({page='/jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne/',
  host='https://na-ryby.eu',gtagEnabled=true}={}){
  const callbacks=[],calls=[];
  const base=host+page;
  const loc=new URL(base);
  const context={
    URL,
    Set,
    Object,
    location:{origin:loc.origin,pathname:loc.pathname,href:loc.href},
    document:{addEventListener:(kind,fn,opts)=>{
      assert.equal(kind,'click');assert.equal(opts.passive,true);
      assert.equal(opts.capture,true);callbacks.push(fn);
    }},
    window:{...(gtagEnabled?{gtag:(...a)=>calls.push(a)}:{})}
  };
  vm.runInNewContext(script,context,{timeout:1500});
  const click=(href,{trusted=true}={})=>{
    const link={href};
    const element={closest:selector=>selector==='a[href]'?link:null};
    for(const cb of callbacks)cb({isTrusted:trusted,target:element});
  };
  return {callbacks,calls,click};
}
const a=browser();
assert.equal(a.callbacks.length,1);
a.click('https://na-ryby.eu/signalizatory');
assert.equal(a.calls.length,1);
assert.equal(a.calls[0][0],'event');
assert.equal(a.calls[0][1],'nry_buyer_offer_click');
assert.equal(a.calls[0][2].buyer_page,'pipaky_guide');
assert.equal(a.calls[0][2].buyer_cta_type,'internal_affiliate_redirect');
assert.equal(a.calls[0][2].transport_type,'beacon');
assert.ok(!JSON.stringify(a.calls).includes('/signalizatory'));
const before=JSON.stringify(a.calls);
a.click('https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/');
a.click('https://na-ryby.eu/signalizatory',{trusted:false});
a.click('https://www.tkqlhce.com/click-12345-15734253?url=https://example.com');
a.click('http://www.tkqlhce.com/click-8287231-15734253');
a.click('https://www.tkqlhce.com/click-8287231-15734253-extra');
assert.equal(JSON.stringify(a.calls),before,'unrelated, untrusted, nonHTTPS and wrong CJ property ignored');
const b=browser({page:'/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'});
b.click('https://www.tkqlhce.com/click-8287231-15320199?url=https%3A%2F%2Fwww.chytapust.cz%2FSignalizatory-zaberu%2Fsample');
assert.equal(b.calls.length,1);
assert.equal(b.calls[0][2].buyer_page,'alarm_guide');
assert.equal(b.calls[0][2].buyer_cta_type,'cj_product_link');
assert.ok(!JSON.stringify(b.calls).includes('tkqlhce'));
assert.ok(!JSON.stringify(b.calls).includes('chytapust'));
assert.ok(!JSON.stringify(b.calls).includes('sample'));
const c=browser({gtagEnabled:false});c.click('https://na-ryby.eu/signalizatory');assert.equal(c.calls.length,0);
const d=browser({page:'/some-article/'});assert.equal(d.callbacks.length,0);
const e=browser({host:'https://other.example'});assert.equal(e.callbacks.length,0);
console.log('PASS: true clicks on two allowlisted buyer articles only, consent-respecting gtag, no synthetic visits or raw URL data');
