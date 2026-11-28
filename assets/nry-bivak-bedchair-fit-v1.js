/* Local-only dimensions calculator; no storage, network or telemetry. */
(() => {
  'use strict';
  const p=document.querySelectorAll('section[data-nry-bivak-bedchair-fit="v1"]');
  if(location.origin!=='https://na-ryby.eu'||p.length!==1)return;
  const panel=p[0],result=panel.querySelector('[data-nry-fit-result]');
  const fields=['shelterLength','shelterWidth','bedLength','bedWidth','count','aisle','endGap','wallGap'].map(k=>panel.querySelector('[data-nry-fit-input="'+k+'"]'));
  if(!result||fields.some(x=>!x))return;
  const update=()=>{
    if(fields.some(f=>f.value===''||!f.checkValidity()||!Number.isInteger(Number(f.value)))){result.textContent='Doplňte platné celé rozměry v povoleném rozsahu.';result.dataset.fitState='invalid';return;}
    const [sl,sw,bl,bw,n,a,e,w]=fields.map(f=>Number(f.value));
    if(n!==1&&n!==2)return;
    const rl=bl+2*e,rw=n*bw+a+2*w,dl=sl-rl,dw=sw-rw;
    const fmt=v=>v<0?'−'+Math.abs(v):'+'+v;
    const state=dl<0||dw<0?'short':dl===0||dw===0?'exact':'fit';
    const headline=state==='short'?'Podle zadaných rozměrů se sestava nevejde.':state==='exact'?'Rozměry vycházejí přesně v jedné nebo obou osách.':'Zadaný obdélník rozměrově vychází.';
    result.dataset.fitState=state;
    result.textContent=headline+' Potřebujete '+rl+' × '+rw+' cm. Rozdíl v délce '+fmt(dl)+' cm, v šířce '+fmt(dw)+' cm. Ověřte tvar stěn a volný východ.';
  };
  fields.forEach(f=>{f.addEventListener('input',update);f.addEventListener('change',update);});
  update();
})();
