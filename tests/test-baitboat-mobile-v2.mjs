import {chromium} from "playwright";
import assert from "node:assert/strict";
import {pathToFileURL} from "node:url";
import path from "node:path";
import fs from "node:fs";
const file=pathToFileURL(path.resolve("assets/baitboat-picker-v2.html")).href;
fs.mkdirSync("artifacts",{recursive:true});
const browser=await chromium.launch({headless:true});
try{
 for(const width of [360,390,768,1280]){
  const page=await browser.newPage({viewport:{width,height:850},deviceScaleFactor:1});
  await page.route("**/*", async route=>{
   if(route.request().url().startsWith("file:")) await route.continue(); else await route.abort();
  });
  await page.goto(file,{waitUntil:"load"});
  await page.locator("#result-name").waitFor({state:"visible"});
  assert.match(await page.locator("#result-name").textContent(),/Deeper SPARK/);
  assert.match(await page.locator("#result-price").textContent(),/23\s?259/);
  await page.locator("#range").selectOption("250");
  assert.match(await page.locator("#result-name").textContent(),/Vulcan Mini PRO/);
  const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>window.innerWidth+2);
  assert.equal(overflow,false,"Horizontal body overflow at "+width+"px");
  const cta=page.locator("#result-link");
  assert.equal(await cta.getAttribute("rel"),"nofollow sponsored noopener");
  assert.match(await cta.getAttribute("href"),/^https:\/\/www\.chytapust\.cz\//);
  await page.screenshot({path:"artifacts/baitboat-v2-"+width+".png",fullPage:true});
  await page.close();
 }
 console.log("PASS: responsive 360/390/768/1280, dynamic recommendation, product CTA, screenshots");
}finally{await browser.close();}
