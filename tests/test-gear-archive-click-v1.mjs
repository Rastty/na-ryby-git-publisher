import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source=fs.readFileSync(new URL('../assets/nry-gear-archive-click-v1.js',import.meta.url),'utf8');
const ORIGIN='https://na-ryby.eu';
const data={
  navijaky:[
    ['reel_guide','/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/'],
    ['feeder_reel','/nejlepsi-feederove-navijaky-srovnani/'],
    ['spinning_reel','/nejlepsi-privlacove-navijaky-jak-vybrat/']
  ],
  'echoloty-na-lod':[
    ['sonar_guide','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'],
    ['sonar_budget','/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/'],
    ['sonar_setup','/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/']
  ],
  bivaky:[
    ['bivak_guide','/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'],
    ['fishing_stays','/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/']
  ],
  'zavazeci-lodky':[
    ['baitboat_choice','/zavazeci-lodky-pro-rybare/'],
    ['baitboat_sonar','/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/']
  ]
};
function run({slug='navijaky',index=0,origin=ORIGIN,pathname=null,search='',hash='',
  trusted=true,gtag=true,raw=null,href=null,role=null,
  clickedOutside=false,duplicatePanel=false,extra=null,
  reorder=false,badMarker=false,badCategory=false,badClass=false}={}) {
  const events=[];let handler=null;let options=null;
  const routeList=data[slug]??data.navijaky;
  let section=null;
  const anchors=routeList.map(([type,path],i)=>({
    href:href!==null&&i===index?href:ORIGIN+path,
    getAttribute:name=>name==='href'?(raw!==null&&i===index?raw:ORIGIN+path):
      name==='data-nry-gear-guide'?(role!==null&&i===index?role:type):null,
    matches:sel=>!badClass&&sel==='a.nry-gear-archive-entry__choice',
    closest:sel=>sel==='section.nry-gear-archive-entry'?section:null
  }));
  if(extra!==null) {
    anchors.unshift({
      href:extra,getAttribute:name=>name==='href'?extra:null,
      matches:()=>false,closest:()=>section
    });
  }
  if(reorder)anchors.reverse();
  section={
    getAttribute:name=>name==='data-nry-gear-archive'?(badMarker?'nope':'v1'):
      name==='data-nry-gear-category'?(badCategory?'different':slug):null,
    querySelectorAll:sel=>sel==='a[href]'?anchors:[]
  };
  const doc={
    querySelectorAll:sel=>sel==='section[data-nry-gear-archive="v1"]'?
      (duplicatePanel?[section,section]:[section]):[],
    addEventListener:(type,fn,opts)=>{
      assert.equal(type,'click');
      handler=fn;options=opts;
    }
  };
  const context={
    location:{origin,pathname:pathname??('/category/vybaveni-pro-rybare/'+slug+'/'),
      search,hash},
    document:doc,
    window:gtag?{gtag:(...args)=>events.push(args)}:{},
    URL
  };
  vm.runInNewContext(source,context,{timeout:1000});
  if(handler)handler({isTrusted:trusted,target:{
    closest:()=>clickedOutside?null:anchors.find(a=>a.href===(href??ORIGIN+routeList[index][1]))??anchors[index]
  }});
  return {events,handler,options};
}
let positive=0;
for(const [slug,routes] of Object.entries(data)) {
  for(const [index,[type,path]] of routes.entries()){
    const out=run({slug,index});
    assert.equal(out.events.length,1,slug+':'+type);
    assert.equal(out.events[0][0],'event');
    assert.equal(out.events[0][1],'nry_buyer_guide_click');
    const props=out.events[0][2];
    assert.equal(props.buyer_page,'gear_archive_'+slug);
    assert.equal(props.guide_type,type);
    assert.equal(props.transport_type,'beacon');
    assert.deepEqual(Object.keys(props).sort(),['buyer_page','guide_type','transport_type']);
    assert.ok(!JSON.stringify(out.events).includes('http'));
    assert.equal(out.options.capture,true);
    assert.equal(out.options.passive,true);
    positive++;
    for(const bad of [
      {trusted:false},{gtag:false},{clickedOutside:true},{duplicatePanel:true},
      {extra:'https://other.example/'},{extra:ORIGIN+'/?utm=bad'},
      {extra:ORIGIN+'/wrong/'},{reorder:true},{badMarker:true},
      {badCategory:true},{badClass:true},{raw:ORIGIN+path+'?utm=wrong'},
      {href:ORIGIN+path+'?utm=wrong'},{href:'https://merchant.example/click'},
      {role:'fake_product'},{search:'?page=2'},{hash:'#more'},
      {origin:'https://other.example'}, {pathname:'/category/vybaveni-pro-rybare/other/'}
    ]){
      const neg=run({slug,index,...bad});
      assert.equal(neg.events.length,0,slug+':'+type+':'+JSON.stringify(bad));
    }
    assert.equal(run({slug,index,extra:ORIGIN+'/'}).events.length,1,
      'exact first homepage context only');
  }
}
assert.equal(positive,10);
console.log('GEAR_ARCHIVE_TEN_TRUSTED_GUIDE_ROLES_OFFLINE_VERIFIED');
