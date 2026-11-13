// Product-selector behavior against the exact PHP-produced, owner-gated V5 HTML.
const assert=require('assert'),vm=require('vm'),{execFileSync}=require('child_process'),path=require('path');
const root=path.resolve(__dirname,'..');
const html=execFileSync('php',[path.join(__dirname,'test-baitboat-stock-guard-v1.php'),'--render'],{encoding:'utf8'});
const js=html.match(/<script type="module">([\s\S]*?)<\/script>/)?.[1];
assert(js,'generated selector module');
const values={budget:'',range:'',sonar:'',gps:'',load:''};
const elements=new Map();
for(const [id,value] of Object.entries(values))elements.set(id,{value,callbacks:[],addEventListener(_,fn){this.callbacks.push(fn)}});
elements.set('has-adapter',{checked:false,callbacks:[],addEventListener(_,fn){this.callbacks.push(fn)}});
for(const id of ['result-name','result-description','result-why','result-caveat','result-price','result-link','result-breakdown','result-availability','result-addon-links','result-image']){
 elements.set(id,{textContent:'',href:'',hidden:id==='result-image',children:[],replaceChildren(){this.children=[]},append(el){this.children.push(el)},addEventListener(){},removeAttribute(k){if(k==='href')this.href='';if(k==='src')this.src=''}});
}
const document={getElementById(id){assert(elements.has(id),id);return elements.get(id)},createElement(){return {textContent:'',href:'',target:'',rel:''}},querySelectorAll(){return []}};
const ctx=vm.createContext({document,Intl,Number,String,Object,Array});
vm.runInContext(js,ctx);
const set=(data)=>{for(const [id,value] of Object.entries(data))elements.get(id).value=String(value);elements.get('budget').callbacks[0]();return elements.get('result-name').textContent};
assert.equal(set({budget:35000,range:500,sonar:'yes',gps:'yes',load:2.5}),'Vhodná loďka je u prodejce nedostupná','do not recommend out-of-stock Vulcan PRO');
assert.match(elements.get('result-description').textContent,/Vulcan PRO.*nebyl u prodejce dostupný/,'explain availability rather than misleading budget');
assert(!elements.get('result-link').href.includes('Vulcan-Pro'),'no dead-product CTA');
assert.equal(set({load:2}),'Parametricky vhodné: Boatman Vulcan Mini PRO','best remaining technical fit');
assert.match(elements.get('result-availability').textContent,/Skladová dostupnost není ověřena/);
assert.match(elements.get('result-link').textContent,/Ověřit dostupnost/);
assert.match(elements.get('result-link').href,/click-8287231-15734253/);
assert.match(set({budget:25000,range:150,sonar:'yes',gps:'yes',load:1}),/Parametricky vhodné:/);
assert.equal(elements.get('result-link').hidden,false);
console.log('PASS: out-of-stock model cannot win ranking; other models are technical-only and CJ-tracked');
