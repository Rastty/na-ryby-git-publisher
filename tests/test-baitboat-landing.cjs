// Standalone source/interaction contracts for the Na-ryby.eu baitboat selector.
const fs = require("fs"),vm=require("vm"),assert=require("assert"),path=require("path");
const root=path.resolve(__dirname,"..");
const html=fs.readFileSync(path.join(root,"assets/baitboat-picker-v1.html"),"utf8");
const php=fs.readFileSync(path.join(root,"baitboat-release-v1.php"),"utf8");
const main=fs.readFileSync(path.join(root,"na-ryby-git-publisher.php"),"utf8");
function has(text){assert(html.includes(text),"Missing "+text);}
has('<html lang="cs">');has('data-nry-baitboat-rc="v1"');has('href="https://na-ryby.eu/jak-vybrat-zavazeci-lodku/"');
assert.equal((html.match(/<script\b/g)||[]).length,1);
assert.equal((html.match(/<\/script>/g)||[]).length,1);
assert(!/<iframe\b|<form\b|<script[^>]+src=/i.test(html));
assert(!/\bfetch\s*\(|XMLHttpRequest|sendBeacon\s*\(/i.test(html));
assert(!/\b(skladem|nejlepší na trhu|garantované úlovky)\b/i.test(html));
assert(main.includes("require_once __DIR__ . '/baitboat-release-v1.php'"));
assert(php.includes("hash_equals(")&&php.includes("nry_baitboat_v1_apply")&&php.includes("nry_baitboat_v1_disable"));
assert(php.includes("approved_pending_public_readback")&&php.includes("get_page_by_path"));
assert(php.includes("X-NRY-Baitboat-Asset-SHA256"));
const links=[...html.matchAll(/href="(https:\/\/www\.chytapust\.cz\/[^"]+)"/g)].map(m=>m[1]);
assert(links.length>=10,"Merchant links missing");
assert(new Set(links).size>=6,"Expected six real product destinations");
for(const link of links){assert(!/[\s<>]/.test(link)); assert(!link.includes("utm_source=chatgpt"));}
const sc=html.match(/<script type="module">([\s\S]*?)<\/script>/);
assert(sc,"Missing inline selector script");
const nodes=new Map();
const init={budget:"25000",range:"150",sonar:"yes",gps:"yes",load:"1"};
for(const id of Object.keys(init))nodes.set(id,{value:init[id],handlers:[],addEventListener(name,fn){this.handlers.push(fn);}});
for(const id of ["result-name","result-description","result-why","result-caveat","result-price","result-link"])nodes.set(id,{textContent:"",href:"",children:[],replaceChildren(){this.children=[]},append(item){this.children.push(item)}});
const document={getElementById(id){if(!nodes.has(id))throw Error("missing node: "+id);return nodes.get(id)},createElement(tag){assert.equal(tag,"span");return {textContent:""}}};
vm.runInNewContext(sc[1],{document,Intl,Number,String,Object,Array});
const set=(values)=>{for(const [k,v] of Object.entries(values))nodes.get(k).value=String(v);nodes.get("budget").handlers[0]();return nodes.get("result-name").textContent;};
assert.equal(nodes.get("result-name").textContent,"Deeper SPARK","default should be Spark");
assert.equal(set({budget:10000,sonar:"no",gps:"no",load:1,range:150}),"Boatman Actor Basic");
assert.equal(set({budget:20000,sonar:"no",gps:"yes",load:2.5,range:500}),"Boatman Vulcan GPS");
assert.equal(set({budget:10000,sonar:"yes",gps:"yes"}),"V této ceně nemáme poctivou shodu");
assert.equal(set({budget:25000,sonar:"yes",gps:"yes",load:2,range:500}),"Boatman Vulcan Mini PRO");
assert.equal(set({budget:20000,sonar:"yes",gps:"yes",load:1,range:500}),"V této ceně nemáme poctivou shodu");
assert.equal(set({budget:35000,sonar:"yes",gps:"yes",load:2.5,range:500}),"Boatman Vulcan PRO");
assert.equal(set({budget:25000,sonar:"yes",gps:"yes",load:1,range:250}),"Boatman Vulcan Mini PRO");
console.log("PASS: guarded route, links, markup, eight selection scenarios");