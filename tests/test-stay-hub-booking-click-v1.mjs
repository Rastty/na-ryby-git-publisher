import assert from 'node:assert/strict';
import fs from 'node:fs';
import vm from 'node:vm';
const code=fs.readFileSync(new URL('../assets/nry-stay-hub-booking-click-v1.js',import.meta.url),'utf8');
const prefix='https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2F';
for(const [panel,attr,placement,offers] of [
 ['nav[data-nry-vranov-stays-59891="v1"]','data-nry-vranov-hub-offer','vranov_hub_booking_v1',[['cyklo','cyklochata'],['srub','rybarsky-srub']]],
 ['div[data-nry-lipno-hub-offers="v1"]','data-nry-lipno-hub-offer','lipno_hub_booking_v1',[['vyhlidka','komfortny-penzion-so-saunou-na-lipne'],['jbx','jbx-resort-albatros']]]
]){
 const events=[];let listener;let duplicate=false;
 const container={};
 const links=offers.map(([key,slug])=>{
   const attrs={[attr]:key,href:prefix+slug,target:'_blank',rel:'sponsored nofollow noopener'};
   return {attrs,getAttribute:k=>attrs[k],closest:s=>s===panel?container:null};
 });
 container.querySelectorAll=()=>links;
 vm.runInNewContext(code,{location:{origin:'https://na-ryby.eu',pathname:'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/',search:'',hash:''},
   window:{gtag:(...args)=>events.push(args)},document:{querySelectorAll:s=>s===panel?(duplicate?[container,container]:[container]):[],addEventListener:(name,fn)=>{listener=fn;}}});
 const click=(i,trusted=true)=>listener({isTrusted:trusted,target:{closest:s=>s==='a['+attr+'][href]'?links[i]:null}});
 click(0);click(1);
 assert.equal(events.length,2);
 for(let i=0;i<2;i++){
   assert.equal(events[i][1],'nry_buyer_offer_click');
   assert.equal(events[i][2].placement_id,placement);
   assert.equal(events[i][2].stay_offer_id,offers[i][0]);
   assert.equal(JSON.stringify(events[i][2]).includes('https:'),false);
 }
 click(0,false);duplicate=true;click(0);duplicate=false;
 links[0].attrs.href='https://example.test/';click(1);
 assert.equal(events.length,2);
}
console.log('STAY_HUB_FOUR_GENUINE_CLICK_CONTRACT_PASS');
