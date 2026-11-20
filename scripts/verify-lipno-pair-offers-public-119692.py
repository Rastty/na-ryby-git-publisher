#!/usr/bin/env python3
"""Read-only preflight and exact post-deploy public proof of Lipno-for-two CTAs.

GETs only Na-Ryby page and anonymous REST. No affiliate requests/clicks, WP
writes, synthetic reservations or new attribution instrumentation.
"""
import hashlib
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request

sys.path.insert(0,str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

SITE='https://na-ryby.eu'
PAGE='/rybarsky-vikend-na-lipne-pro-dva-jak-ho-naplanovat-aby-to-davalo-smysl/'
REST=SITE+'/wp-json/wp/v2/posts/119692?_fields=id,type,status,slug,link,modified_gmt,content'
SHA='864eed266554f655484067ca749336089829d4fbce64e57561a07aab255b29a3'
JBX=('https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5='
     'https%3A%2F%2Fwww.hauzi.cz%2Fjbx-resort-albatros')
VYHLIDKA=('https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5='
          'https%3A%2F%2Fwww.hauzi.cz%2Fkomfortny-penzion-so-saunou-na-lipne')
STAYS=SITE+'/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'
DECISION='<section id="decision-box" style="margin: 0 0 24px;">'
TABLE='<section id="tabulka" style="margin: 0 0 24px;">'


class Surface(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.canonicals=[]
        self.markers=0
        self.offers=[]
        self.links=[]
        self.inside=False

    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if tag=='link' and 'canonical' in (a.get('rel') or '').split():
            self.canonicals.append(a.get('href'))
        if tag=='section' and a.get('data-nry-lipno-pair')=='v1':
            self.markers+=1
            self.inside=True
        if self.inside and tag=='article' and a.get('data-nry-lipno-pair-offer'):
            self.offers.append(a['data-nry-lipno-pair-offer'])
        if self.inside and tag=='a':
            self.links.append((a.get('href'),a.get('target'),a.get('rel')))

    def handle_endtag(self,tag):
        if tag=='section' and self.inside:
            self.inside=False


def verify_rest(data):
    assert data['id']==119692 and data['type']=='post' and data['status']=='publish','POST_IDENTITY_DRIFT'
    assert data['slug']==PAGE.strip('/') and data['link']==SITE+PAGE,'CANONICAL_DRIFT'
    assert data['modified_gmt']=='2026-05-24T22:01:42','MODIFIED_GMT_DRIFT'
    body=data['content']['rendered']
    assert len(body.encode('utf-8'))>22000,'SOURCE_TOO_SHORT'
    assert hashlib.sha256(body.encode('utf-8')[:512]).hexdigest()==SHA,'RENDERED_SOURCE_DRIFT'
    assert body.count(DECISION)==1 and body.count(TABLE)==1,'DECISION_OR_TABLE_DRIFT'
    assert body.index(DECISION)<body.index(TABLE),'DECISION_ORDER_DRIFT'
    assert 'tracking.affiliateport.eu' not in body,'ALREADY_HAS_AFFILIATE_LINKS'


def verify_public(html,*,deployed):
    page=Surface()
    page.feed(html)
    page.close()
    assert page.canonicals==[SITE+PAGE] and 'postid-119692' in html,'PUBLIC_IDENTITY_DRIFT'
    assert 'Rybářský víkend na Lipně pro dva' in html,'HEADING_DRIFT'
    assert 'id="decision-box"' in html and 'id="tabulka"' in html,'LEGACY_STRUCTURE_MISSING'
    if not deployed and page.markers==0:
        assert not page.offers and not page.links,'UNEXPECTED_PREDEPLOY_OFFERS'
        return {'status':'LIPNO_PAIR_119692_PREFLIGHT_READY','postId':119692,
                'merchantLinksPresent':False,'plannedMerchantLinks':2,'plannedInternalLinks':1,
                'merchantRequests':0,'wordpressWrites':0,'syntheticClicks':0}
    assert page.markers==1 and page.offers==['jbx','vyhlidka'],'REVIEWED_OFFERS_DRIFT'
    assert page.links==[(JBX,'_blank','sponsored nofollow noopener'),
                        (VYHLIDKA,'_blank','sponsored nofollow noopener'),
                        (STAYS,None,None)],'UNREVIEWED_LINK_OR_REL'
    idx_dec=html.index('id="decision-box"')
    idx_marker=html.index('data-nry-lipno-pair="v1"')
    idx_table=html.index('id="tabulka"')
    assert idx_dec<idx_marker<idx_table,'BUYER_CONTEXT_POSITION_DRIFT'
    for phrase in ('JBX Resort Albatros','Pension Vyhlídka','povolenku ani právo rybařit',
                   'Partnerské odkazy'):
        assert phrase in html,'TRUST_OR_OFFER_TEXT_DRIFT'
    return {'status':'LIPNO_PAIR_119692_PUBLIC_VERIFIED','postId':119692,
            'visibleReviewedOffers':2,'reviewedInternalLinks':1,'originalDecisionAndTableVisible':True,
            'merchantRequests':0,'wordpressWrites':0,'syntheticClicks':0,
            'realClicks':None,'completedReservations':None,'paidCommissions':None}


def get(url,max_bytes):
    assert url in (SITE+PAGE,REST),'UNAPPROVED_GET'
    req=Request(url,headers={'User-Agent':'NaRyby-Lipno-Pair-ReadOnly/1.0',
                             'Cache-Control':'no-cache,no-store'})
    with guarded_urlopen(req,timeout=25) as response:
        assert response.status==200 and response.geturl()==url,'STATUS_OR_REDIRECT_DRIFT'
        data=response.read(max_bytes+1)
        assert len(data)<=max_bytes,'RESPONSE_TOO_LARGE'
        return data


def main():
    assert sys.argv[1:] in (['--preflight'],['--verify']),'PHASE_REQUIRED'
    verify_rest(json.loads(get(REST,200000).decode('utf-8')))
    deployed=sys.argv[1]=='--verify'
    result=verify_public(get(SITE+PAGE,2000000).decode('utf-8','replace'),deployed=deployed)
    print(json.dumps(result,ensure_ascii=False))


if __name__=='__main__':
    try:
        main()
    except (AssertionError,ValueError,KeyError,OSError) as exc:
        print(json.dumps({'status':'LIPNO_PAIR_119692_BLOCKED','reason':str(exc)[:170]}))
        raise SystemExit(1)
