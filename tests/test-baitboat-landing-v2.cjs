// Offline source and recommendations contract. No live network or WP writes.
const fs=require("fs"),assert=require("assert"),vm=require("vm"),path=require("path");
const root=path.resolve(__dirname,"..");
const html=fs.readFileSync(path.join(root,"assets/baitboat-picker-v2.html"),"utf8");
const release=fs.readFileSync(path.join(root,"baitboat-release-v2.php"),"utf8");
const loader=fs.readFileSync(path.join(root,"na-ryby-git-publisher.php"),"utf8");
assert(html.includes('data-nry-baitboat-rc="v2"'));
assert(html.includes('href="https://na-ryby.eu/jak-vybrat-zavazeci-lodku/"'));
assert(html.includes("id=\"result-breakdown\""));
assert(html.includes("id=\"has-adapter\""));
assert(!html.includes("Google Ads pro tento obchod"));
assert(!/\\bfetch\s*\\(|XMLHttpRequest|sendBeacon\s*\\(/.test(html));
assert.equal((html.match(/<script\b/g)||[]).length,1);
assert.equal((html.match(/<\/script>/g)||[]).length,1);
assert(loader.includes("require_once __DIR__ . '/baitboat-release-v1.php'"));
assert(loader.includes("require_once __DIR__ . '/baitboat-release-v2.php'"));
assert(release.includes('template_redirect' + "', array('NRY_Baitboat_Release_V2', 'serve'), -1"));
assert(release.includes("approved_pending_public_readback"));
assert(release.includes("hash_equals("));
assert(release.includes("nry_baitboat_v2_apply")&&release.includes("nry_baitboat_v2_disable"));
assert(release.includes("data-nry-baitboat-rc=\"v2\""));
const links=[...html.matchAll(/href="(https:\/\/www\.chytapust\.cz\/[^"]+)"/g)].map(m=>m[1]);
assert(links.length>=10&&new Set(links).size>=6);
for(const link of links)assert(!/utm_source=chatgpt|click\.linksynergy|a_box=/.test(link));
const code=html.match(/<script type="module">([\s\S]*?)<\/script>/)[1];
const fields={budget:"25000",range:"150",sonar:"yes",gps:"yes",load:"1"};
const nodes=new Map();
for(const [id,value] of Object.entries(fields))nodes.set(id,{value,callbacks:[],addEventListener(_,cb){this.callbacks.push(cb)}});
nodes.set("has-adapter",{checked:false,callbacks:[],addEventListener(_,cb){this.callbacks.push(cb)}});
for(const id of ["result-name","result-description","result-why","result-caveat","result-price","result-link","result-breakdown","result-availability","result-addon-links"]){
nodes.set(id,{textContent:"",href:"",children:[],replaceChildren(){this.children=[]},append(item){this.children.push(item)}});
}
const document={
 getElementById(id){assert(nodes.has(id),"Missing HTML control "+id);return nodes.get(id)},
 createElement(tag){assert(["span","a"].includes(tag));return {tag,textContent:"",href:"",target:"",rel:""}}
};
const ctx=vm.createContext({document,Intl,Number,String,Object,Array});
vm.runInContext(code,ctx);
const name=()=>nodes.get("result-name").textContent;
const price=()=>nodes.get("result-price").textContent;
const set=(values={},hasAdapter=null)=>{
 for(const [k,v] of Object.entries(values))nodes.get(k).value=String(v);
 if(hasAdapter!==null)nodes.get("has-adapter").checked=hasAdapter;
 nodes.get("budget").callbacks[0]();
 return name();
};
assert.equal(name(),"Deeper SPARK");
assert(price().includes("23 259"),"Default kit price includes adaptor");
assert.equal(nodes.get("result-addon-links").children.length,1);
assert.equal(set({budget:10000,sonar:"no",gps:"no",load:1,range:150}),"Boatman Actor Basic");
assert.equal(set({budget:20000,sonar:"no",gps:"yes",load:2.5,range:500}),"Boatman Vulcan GPS");
assert.equal(set({budget:10000,sonar:"yes",gps:"yes",load:1,range:250}),"V této ceně nemáme poctivou shodu");
assert.equal(set({budget:25000,sonar:"yes",gps:"yes",load:1,range:250}),"Boatman Vulcan Mini PRO");
assert(!price().includes("26 249"),"SPARK kit over budget must not be selected");
assert.equal(set({budget:20000,sonar:"yes",gps:"yes",load:1,range:500}),"V této ceně nemáme poctivou shodu");
assert.equal(set({budget:35000,sonar:"yes",gps:"yes",load:2.5,range:500}),"Boatman Vulcan PRO");
assert.equal(set({budget:25000,sonar:"yes",gps:"yes",load:1,range:150}),"Deeper SPARK");
assert(price().includes("23 259"));
assert.equal(set({budget:25000,sonar:"yes",gps:"yes",load:1,range:150},true),"Deeper SPARK");
assert(price().includes("22 490"),"Own compatible adapter removes its cost");
const sparkNoAdapter=vm.runInContext('priceFor(products.find(p=>p.id==="spark"),250,false)',ctx);
const sparkOwnAdapter=vm.runInContext('priceFor(products.find(p=>p.id==="spark"),250,true)',ctx);
assert.equal(sparkNoAdapter.total,26249);
assert.deepEqual(Array.from(sparkNoAdapter.addons),["remote","adapter"]);
assert.equal(sparkOwnAdapter.total,25480);
assert.deepEqual(Array.from(sparkOwnAdapter.addons),["remote"]);
assert.equal(set({budget:25000,sonar:"yes",gps:"yes",load:1,range:500},false),"Boatman Vulcan Mini PRO");
assert.equal(set({budget:25000,sonar:"yes",gps:"yes",load:2.5,range:250}),"V této ceně nemáme poctivou shodu");
console.log("PASS: V2 boundaries, complete kit and accessory math, live V1 fallback wiring, 11 recommendation cases");
