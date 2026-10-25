#!/usr/bin/env python3
"""Read-only public WordPress product-advice opportunity audit, no credentials."""
import json
import re
import urllib.request
import urllib.error
import html
from datetime import datetime, timezone
from concurrent.futures import ThreadPoolExecutor
from urllib.parse import quote

IDS=[34882,34905,34913,34921,34977,35003,35011,35026]
SITE="https://na-ryby.eu"
HEADERS={"User-Agent":"NaRyby-EditorialReadOnly/1.0","Cache-Control":"no-cache"}

def request(url):
    try:
        with urllib.request.urlopen(urllib.request.Request(url,headers=HEADERS),timeout=17) as r:
            raw=r.read(380000).decode("utf-8","replace")
            return r.status,raw
    except (urllib.error.URLError,TimeoutError) as exc:
        return 0,str(exc)[:120]

def extract(row):
    post_id=row["id"]
    status,body=request(f"{SITE}/wp-json/wp/v2/posts/{post_id}?_fields=id,slug,title,modified_gmt,status,link,content")
    out={"postId":post_id,"wpRestHttp":status}
    if status!=200:
        out["error"]=body[:140]
        return out
    try:
        obj=json.loads(body)
        post=obj.get("content",{}).get("rendered","")
        title=html.unescape(re.sub("<[^>]+>","",obj.get("title",{}).get("rendered","")))
        headings=[html.unescape(re.sub("<[^>]+>","",h)).strip() for h in re.findall(r"<h[23][^>]*>.*?</h[23]>",post,re.I|re.S)]
        out.update({"title":title,"slug":obj.get("slug"),"status":obj.get("status"),
          "modifiedGmt":obj.get("modified_gmt"),"url":obj.get("link"),
          "renderedBytes":len(post.encode("utf-8")),"headings":headings[:12],
          "markers":re.findall(r'data-nry-editorial-edit="([^"]+)"',post)[:7],
          "images":len(re.findall(r"<img\\b",post,re.I)),
          "internalLinks":len(re.findall(r'href="https://na-ryby.eu/',post)),
          "issueIndicators":{
            "english_ai_refusal":"Sorry, I cannot perform this task" in post,
            "keyword_stuffing":"klíčová slova:" in post.lower(),
            "year_2020":"2020" in post,
          },
          "sourceSizeAndHashKnown":False})
    except Exception as exc:
        out["error"]="parse:"+str(exc)[:130]
    return out

def main():
    with ThreadPoolExecutor(max_workers=8) as pool:
        result=list(pool.map(lambda post_id: extract({"id":post_id}),IDS))
    print(json.dumps({"checkedAtUtc":datetime.now(timezone.utc).isoformat(),"results":result},ensure_ascii=False))
    with open("chair-candidate-public-audit.json","w",encoding="utf-8") as f:
        json.dump(result,f,ensure_ascii=False,indent=2)

if __name__=="__main__":
    main()
