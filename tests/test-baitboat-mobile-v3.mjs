import {chromium} from "playwright";
import assert from "node:assert/strict";
import {pathToFileURL} from "node:url";
import fs from "node:fs";
import path from "node:path";
const source=pathToFileURL(path.resolve("assets/baitboat-picker-v3.html")).href;
fs.mkdirSync("artifacts",{recursive:true});
const browser=await chromium.launch({headless:true});
try {
 const context=await browser.newContext({viewport:{width:390,height:820},deviceScaleFactor:1});
 for(const width of [360,390,768,1280]){
   const page=await context.newPage();
   await page.setViewportSize({width,height:850});
   await page.goto(source,{waitUntil:"load"});
   assert.match(await page.title(),/Jak vybrat zavážecí loďku/);
   assert.equal((await page.locator(".merchant-photo").count()),6);
   assert.equal(await page.locator("#result-name").textContent(),"Začněte rozpočtem");
   assert.equal(await page.locator("#result-link").isVisible(),false);
   await page.locator("#budget").selectOption("25000");
   assert.match(await page.locator("#result-name").textContent(),/Předběžně/);
   assert.equal(await page.locator("#result-link").isVisible(),false);
   await page.locator("#range").selectOption("150");
   await page.locator("#sonar").selectOption("yes");
   await page.locator("#gps").selectOption("yes");
   await page.locator("#load").selectOption("1");
   assert.equal(await page.locator("#result-name").textContent(),"Deeper SPARK");
   assert.match((await page.locator("#result-price").textContent()).replace(/\s/g,""),/23259/);
   const src=await page.locator("#result-image").getAttribute("src");
   assert.match(src,/^https:\/\/i00\.eu\/img\/202\/1024x1024\//);
   assert.equal(await page.locator("#result-link").isVisible(),true);
   assert.match(await page.locator("#result-link").textContent(),/Zobrazit nabídku u Chyť a pusť/);
   await page.locator("#range").selectOption("250");
   assert.equal(await page.locator("#result-name").textContent(),"Boatman Vulcan Mini PRO");
   const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>window.innerWidth+2);
   assert.equal(overflow,false,"Horizontal overflow at "+width+"px");
   await page.screenshot({path:"artifacts/baitboat-v3-"+width+".png",fullPage:true});
   await page.close();
 }
 await context.close();
 console.log("PASS: 360/390/768/1280 V3 mobile, neutral brand start, gallery, CTA and live selection logic");
}finally{await browser.close();}
