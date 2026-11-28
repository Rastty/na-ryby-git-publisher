import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const source=fs.readFileSync(new URL('../assets/nry-archive-stay-click-v1.js',import.meta.url),'utf8');
const ORIGIN='https://na-ryby.eu';
const cases=[
  {
    path:'/ubytovani-2/',
    marker:'section[data-nry-legacy-stays-entry="v1"]',
    role:'legacy_stays_archive_2017',
    choices:[
      ['stays_hub','/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'],
      ['slapy_stays','/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/'],
      ['lipno_stays','/rybareni-s-ubytovanim-jihocesky-kraj/'],
        ['vranov_group','/rybarska-chata-cyklochata-vranovska-prehrada/'],
        ['vranov_wellness','/chata-rybarsky-wellness-srub/'],
        ['private_water_stays','/nejlepsi-soukrome-reviry-s-ubytovanim-v-ceske-republice-zazitek-ktery-stoji-za-to/']
    ]
  },{
    path:'/rybareni-s-ubytovanim-jihomoravsky-kraj/',
    marker:'section[data-nry-jm-archive-stays="v1"]',
    role:'south_moravia_stays_2017',
    choices:[
      ['cyklochata','/rybarska-chata-cyklochata-vranovska-prehrada/'],
      ['wellness_srub','/chata-rybarsky-wellness-srub/'],
      ['stays_hub','/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/']
    ]
  }
];
function emulate({cfg=cases[0],home=false,change=null,origin=ORIGIN,search='',hash='',panels=1,gtag=true}={}) {
  const events=[];
  let listener=null,options=null;
  const refs=(home?[ORIGIN+'/']:[]).concat(cfg.choices.map(row=>ORIGIN+row[1]));
  if(change) change(refs);
  const panel={};
  const anchors=refs.map(url=>({
    href:url,
    getAttribute:(key)=>key==='href'?url:null,
    closest:(selector)=>selector===cfg.marker?panel:null
  }));
  panel.querySelectorAll=(selector)=>selector==='a[href]'?anchors:[];
  const context={
    location:{origin,pathname:cfg.path,search,hash},
    window:gtag?{gtag:(...args)=>events.push(args)}:{},
    document:{
      querySelectorAll:(selector)=>selector===cfg.marker?Array.from({length:panels},()=>panel):[],
      addEventListener:(name,fn,opts)=>{
        assert.equal(name,'click');listener=fn;options=opts;
      }
    },
    URL
  };
  vm.runInNewContext(source,context,{timeout:1000});
  function click(index,{trusted=true,inside=true,external=false}={}) {
    if(!listener)return;
    const link=anchors[index];
    listener({isTrusted:trusted,target:{
      closest:()=>external?{
        ...link,
        closest:()=>inside?panel:null
      }:inside?link:{...link,closest:()=>null}
    }});
  }
  return {events,listener,options,click,anchors};
}
for(const cfg of cases) {
  for(const home of [false,true]) {
    const run=emulate({cfg,home});
    assert.equal(typeof run.listener,'function','exact visible panel arms');
    assert.equal(run.options.capture,true);
    assert.equal(run.options.passive,true);
    const offset=home?1:0;
    for(let i=0;i<cfg.choices.length;i++) {
      run.click(i+offset);
      assert.equal(run.events.length,i+1,'real click one event');
      const [type,name,payload]=run.events[i];
      assert.equal(type,'event');
      assert.equal(name,'nry_buyer_guide_click');
      assert.equal(payload.buyer_page,cfg.role);
      assert.equal(payload.guide_type,cfg.choices[i][0]);
      assert.equal(payload.transport_type,'beacon');
      assert.deepEqual(Object.keys(payload).sort(),['buyer_page','guide_type','transport_type']);
      assert.equal(JSON.stringify(payload).includes('http'),false,'never send URLs');
    }
    const before=run.events.length;
    if(home)run.click(0);
    run.click(offset,{trusted:false});
    run.click(offset,{inside:false});
    assert.equal(run.events.length,before,'unowned, homepage and untrusted excluded');
  }
  assert.equal(emulate({cfg,search:'?preview=1'}).listener,null);
  assert.equal(emulate({cfg,origin:'https://example.test'}).listener,null);
  assert.equal(emulate({cfg,hash:'#x'}).listener,null);
  assert.equal(emulate({cfg,panels:2}).listener,null);
  assert.equal(emulate({cfg,gtag:false}).events.length,0);
  const unsafe=[
    refs=>refs.unshift('https://evil.example/'),
    refs=>refs.unshift('https://na-ryby.eu/dalsi/'),
    refs=>refs.unshift('https://na-ryby.eu/?utm=1'),
    refs=>refs.push(ORIGIN+'/'),
    refs=>refs[1]=refs[0],
    refs=>refs[0]+='?query=1',
    refs=>refs.reverse(),
    refs=>refs.splice(0,1)
  ];
  for(const mutate of unsafe) {
    const attempt=emulate({cfg,change:mutate});
    assert.equal(attempt.listener,null,'source-drift extra/duplicate/other urls fail closed');
  }
}
const wrong={...cases[0],path:'/something-unrelated/'};
assert.equal(emulate({cfg:wrong}).listener,null);
console.log('ARCHIVE_STAY_TWO_TRUSTED_CLICK_ROUTES_OFFLINE_PASS');
