#!/usr/bin/env python3
"""Read-only diagnostic of an existing public baitboat buyer article.
No authenticated APIs, merchant calls, visitor tracking, WP writes, or content dump.
"""
import hashlib
import html
import json
import re
from urllib.request import Request,urlopen
from urllib.parse import urlencode
from urllib.error import HTTPError,URLError
from html.parser import HTMLParser

BASE="https://na-ryby.eu"
SLUG="zavazeci-lodky-pro-rybare"
URL=BASE+"/"+SLUG+"/"
TARGET=BASE+"/jak-vybrat-zavazeci-lodku/"
API=BASE+"/wp-json/wp/v2/posts?"+urlencode({
    "slug":SLUG,
    "_fields":"id,slug,status,link,modified_gmt,content"
})

def get(url,limit=1500000):
    req=Request(url,headers={"User-Agent":"NaRyby-ArticleEntry-ReadOnly/1.0","Accept":"text/html,application/json","Cache-Control":"no-cache"})
    with urlopen(req,timeout=30) as r:
        assert r.status==200 and r.geturl()==url, "redirect/status"
        body=r.read(limit+1)
        assert len(body)<=limit,"oversize"
        return body.decode("utf-8","replace")

class Attrs(HTMLParser):
    def __init__(self):
        super().__init__()
        self.anchors=[]
        self.ids=[]
        self.class_values=[]
        self.canonical=[]
    def handle_starttag(self,tag,attrs):
        d=dict(attrs)
        if tag=="a":self.anchors.append(d.get("href",""))
        if "class" in d:self.class_values.append(d["class"])
        if "id" in d:self.ids.append(d["id"])
        if tag=="link" and "canonical" in (d.get("rel","")).split():self.canonical.append(d.get("href",""))

def main():
    rendered=get(URL)
    doc=Attrs();doc.feed(rendered)
    h=[re.sub(r"\s+"," ",html.unescape(re.sub(r"<[^>]+>"," ",raw))).strip() for raw in re.findall(r"<h[23]\b[^>]*>(.*?)</h[23]>",rendered,re.I|re.S)]
    postids=sorted(set(re.findall(r"(?:postid-|post-)(\d{2,8})", " ".join(doc.class_values))))
    c={
     "mode":"ANONYMOUS_READ_ONLY",
     "article":URL,
     "http":200,
     "public_body_sha256":hashlib.sha256(rendered.encode("utf-8")).hexdigest(),
     "body_bytes":len(rendered.encode("utf-8")),
     "canonical":doc.canonical[:2],
     "postid_css_candidates":postids[:5],
     "internal_picker_link_count":sum(TARGET in value for value in doc.anchors),
     "picker_literal_count":rendered.count(TARGET),
     "headings":h[:17],
     "external_writes":0
    }
    try:
      items=json.loads(get(API,limit=750000))
      if type(items)==list and len(items)==1:
        p=items[0]
        content=(p.get("content") or {}).get("rendered","")
        c["wordpress_public_rest"]={
          "id":p.get("id"),"slug":p.get("slug"),"link":p.get("link"),
          "modified_gmt":p.get("modified_gmt"),"status":p.get("status"),
          "source_rendered_sha256":hashlib.sha256(content.encode("utf-8")).hexdigest(),
          "source_rendered_picker_count":content.count(TARGET),
          "source_rendered_headings":[re.sub(r"<[^>]+>","",v).strip() for v in re.findall(r"<h[23]\b[^>]*>(.*?)</h[23]>",content,re.I|re.S)][:12]
        }
      else:c["wordpress_public_rest"]={"status":"AMBIGUOUS_OR_MISSING","count":len(items) if isinstance(items,list) else None}
    except (HTTPError,URLError,TimeoutError,ValueError,AssertionError) as e:
      c["wordpress_public_rest"]={"status":"UNAVAILABLE_"+type(e).__name__}
    print(json.dumps(c,ensure_ascii=False,sort_keys=True))
if __name__=="__main__":main()
