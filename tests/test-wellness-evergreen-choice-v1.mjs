import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const code=fs.readFileSync(new URL('../assets/nry-wellness-evergreen-choice-v1.js',import.meta.url),'utf8');
const ORIGIN='https://na-ryby.eu';
const paths=[
'/wellness-a-rybolov-v-jednom-luxusni-ubytovani-pro-narocne-rybare/',
'/wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice/'
];
const targetPaths={
  srub:'/chata-rybarsky-wellness-srub/',
  stays:'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
};
function simulate({path=paths[0],key='srub',href=null,trusted=true,inside=true,gtag=true,
                   origin=ORIGIN,search='' }={}) {
  const events=[];let listener=null;let attrCalls=[];
  const link={
    href:href??ORIGIN+targetPaths[key],
    getAttribute:()=>key,
    closest:()=>inside?{}:null
  };
  const context={
    location:{origin,pathname:path,search},
    document:{addEventListener:(name,fn,opts)=>{assert.equal(name,'click');listener=fn;attrCalls.push(opts);}},
    window:gtag?{gtag:(...args)=>events.push(args)}:{},
    URL
  };
  vm.runInNewContext(code,context);
  if(listener) listener({isTrusted:trusted,target:{closest:()=>link}});
  return {events,hasListener:Boolean(listener),options:attrCalls[0]??null};
}
for (const path of paths) {
  for (const key of Object.keys(targetPaths)) {
    const ok=simulate({path,key});
    assert.equal(ok.events.length,1,'trusted exact internal choice counted');
    assert.equal(ok.events[0][0],'event');
    assert.equal(ok.events[0][1],'nry_buyer_guide_click');
    assert.equal(ok.events[0][2].guide_type,key);
    assert.equal(ok.events[0][2].transport_type,'beacon');
    assert.equal(ok.events[0][2].buyer_page,path===paths[0]?'wellness_editorial_61382':'private_wellness_69482');
    assert.deepEqual(Object.keys(ok.events[0][2]).sort(),['buyer_page','guide_type','transport_type']);
    assert.equal(simulate({path,key,trusted:false}).events.length,0);
    assert.equal(simulate({path,key,inside:false}).events.length,0);
    assert.equal(simulate({path,key,gtag:false}).events.length,0);
    assert.equal(simulate({path,key,href:ORIGIN+targetPaths[key]+'?partner=1'}).events.length,0);
    assert.equal(simulate({path,key,href:ORIGIN+'/wrong/'}).events.length,0);
    assert.equal(simulate({path,key,href:'https://tracking.affiliateport.eu/click'}).events.length,0);
  }
}
assert.equal(simulate({path:'/unrelated/'}).hasListener,false);
assert.equal(simulate({search:'?preview=1'}).hasListener,false);
assert.equal(simulate({origin:'https://untrusted.example'}).hasListener,false);
console.log('WELLNESS_EVERGREEN_TRUSTED_FIRST_PARTY_CLICKS_OFFLINE_PASS');
