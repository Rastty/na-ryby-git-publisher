#!/usr/bin/env python3
"""Anonymous GET-only preflight/public proof for a narrow public contact correction.

No WordPress writes, no requests to email or merchants; do not print contact or
raw document contents. The legal policy itself is NOT assessed for compliance.
"""
import json, re, sys
from datetime import datetime, timezone
from html.parser import HTMLParser
from urllib.parse import urlencode
from urllib.request import Request, urlopen

ROOT = 'https://na-ryby.eu'
PAGE = ROOT + '/ochrana-osobnich-udaju-gdpr/'
HOME = ROOT + '/'
API = ROOT + '/wp-json/wp/v2/pages?' + urlencode({
    'slug':'ochrana-osobnich-udaju-gdpr',
    '_fields':'id,slug,status,link,modified_gmt,content'})
OLD_CONTACT = 'xfit.redakce@gmail.com'
PUBLIC_CONTACT = 'na.ryby.eu@gmail.com'
DRAFT = '(Volitelně: IČO, sídlo / adresa podnikání – pokud máte)'
MARKER = 'data-nry-privacy-contact-proof="20261111-v1"'
SOURCE_HEADING = 'Kdo je správce osobních údajů'
RIGHTS_HEADING = 'Jak nás můžete kontaktovat'

class Links(HTMLParser):
    def __init__(self):
        super().__init__()
        self.canonicals=[]
    def handle_starttag(self, tag, attrs):
        if tag != 'link':
            return
        d=dict(attrs)
        if 'canonical' in (d.get('rel') or '').split():
            self.canonicals.append(d.get('href'))

def check_html(html, after):
    if not isinstance(html,str) or len(html)<5000 or len(html)>950000:
        raise AssertionError('HTML_UNVERIFIABLE')
    c=Links()
    c.feed(html)
    if c.canonicals!=[PAGE]:
        raise AssertionError('CANONICAL_DRIFT')
    if html.count(SOURCE_HEADING)<1 or html.count(RIGHTS_HEADING)<1:
        raise AssertionError('POLICY_HEADINGS_MISSING')
    if after:
        if html.count(MARKER)!=1 or OLD_CONTACT in html or DRAFT in html:
            raise AssertionError('PUBLIC_CORRECTION_NOT_VISIBLE')
        # Both body contacts plus the already existing site footer.
        if html.count(PUBLIC_CONTACT)<3:
            raise AssertionError('BODY_CONTACT_NOT_REPLACED')
    else:
        if html.count(MARKER)!=0 or html.count(OLD_CONTACT)!=2 or html.count(DRAFT)!=1:
            raise AssertionError('PRE_RELEASE_BASELINE_DRIFT counts='+str((html.count(MARKER),html.count(OLD_CONTACT),html.count(DRAFT))))
    return 'PUBLIC_VERIFIED' if after else 'PUBLIC_PREFLIGHT_VERIFIED'

def get(url, limit=950000):
    req=Request(url, method='GET', headers={
        'User-Agent':'NaRyby-Privacy-Contact-IndependentReadOnly/1.0',
        'Accept':'text/html,application/json',
        'Cache-Control':'no-cache'})
    with urlopen(req,timeout=24) as resp:
        if resp.status!=200 or resp.geturl()!=url:
            raise RuntimeError('HTTP_OR_REDIRECT')
        raw=resp.read(limit+1)
    if len(raw)>limit:
        raise RuntimeError('RESPONSE_OVERSIZE')
    return raw.decode('utf-8','replace')

def preflight_source(item):
    if not isinstance(item,dict) or item.get('slug')!='ochrana-osobnich-udaju-gdpr' or item.get('link')!=PAGE or item.get('status')!='publish':
        raise AssertionError('WP_REST_SOURCE_IDENTITY_DRIFT')
    post_id=item.get('id')
    modified=item.get('modified_gmt')
    rendered=(item.get('content') or {}).get('rendered','')
    if type(post_id) is not int or post_id<1 or not isinstance(modified,str) or not re.fullmatch(r'20[0-9]{2}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}',modified):
        raise AssertionError('WP_REST_ID_OR_DATE_MISSING')
    # The REST-rendered source does not include front-end-only the_content
    # filters, so it can check the same baseline on preflight only.
    if (not isinstance(rendered,str) or rendered.count(OLD_CONTACT)!=2
            or rendered.count(DRAFT)!=1):
        raise AssertionError('WP_REST_SOURCE_NOT_STABLE')
    return post_id,modified

def main():
    if len(sys.argv)!=2 or sys.argv[1] not in ('--preflight','--verify'):
        raise SystemExit('usage: --preflight | --verify')
    after=sys.argv[1]=='--verify'
    html=get(PAGE)
    state=check_html(html,after)
    if not after:
        landing=get(HOME)
        if PUBLIC_CONTACT not in landing:
            raise AssertionError('HOMEPAGE_PUBLIC_CONTACT_NOT_CONFIRMED')
        pages=json.loads(get(API))
        if not isinstance(pages,list) or len(pages)!=1:
            raise AssertionError('WP_REST_SOURCE_AMBIGUOUS')
        post_id,modified=preflight_source(pages[0])
    else:
        post_id,modified=None,None
    print(json.dumps({
        'status':state,'checkedUtc':datetime.now(timezone.utc).isoformat(),
        'pageId':post_id,'modifiedGmt':modified,
        'homepageContactConfirmed':not after,'wordpressWrites':0,
        'merchantRequests':0,'externalEmailsSent':0,
        'legalComplianceCertified':False
    },sort_keys=True))

if __name__=='__main__':
    main()
