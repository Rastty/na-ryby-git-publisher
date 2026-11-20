#!/usr/bin/env python3
"""Read-only diagnosis of exact frontend content bytes versus public REST.

Prints bounded counts/digests only; never WordPress content, merchant requests,
credentials, personal data, or synthetic clicks. No mutation.
"""
import hashlib
import json
from html.parser import HTMLParser
from urllib.request import Request, HTTPRedirectHandler, build_opener

SITE = 'https://na-ryby.eu'
PAGE = SITE + '/rybareni-s-ubytovanim-jihocesky-kraj/'
REST = SITE + '/wp-json/wp/v2/posts/13088?_fields=id,type,status,slug,link,modified_gmt,content'
EXPECTED_REST_PREFIX = '3778effd5b7c012dfeedbc3cc2f1e13f07769efd805fabaf6996535c292bb801'


class NoRedirect(HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


class ContentDiv(HTMLParser):
    """Extract exact original HTML slice within a single td-post-content div."""

    def __init__(self, source):
        super().__init__(convert_charrefs=False)
        self.source = source
        self.lines = [0]
        for index, char in enumerate(source):
            if char == '\n':
                self.lines.append(index + 1)
        self.depth = 0
        self.start = None
        self.stop = None
        self.found = 0

    def absolute_position(self):
        line, column = self.getpos()
        return self.lines[line - 1] + column

    def handle_starttag(self, tag, attrs):
        if self.depth == 0 and tag == 'div' and 'td-post-content' in dict(attrs).get('class', '').split():
            self.found += 1
            self.start = self.absolute_position() + len(self.get_starttag_text())
            self.depth = 1
        elif self.depth and tag == 'div':
            self.depth += 1

    def handle_endtag(self, tag):
        if self.depth and tag == 'div':
            self.depth -= 1
            if self.depth == 0:
                self.stop = self.absolute_position()

    def extract(self):
        self.feed(self.source)
        self.close()
        assert self.found == 1 and self.start is not None and self.stop is not None, 'CONTENT_DIV_NOT_UNIQUE'
        return self.source[self.start:self.stop]


def inspect(rest_json, frontend_html):
    assert rest_json['id'] == 13088 and rest_json['type'] == 'post', 'WRONG_POST'
    assert rest_json['link'] == PAGE and rest_json['status'] == 'publish', 'WRONG_POST_STATE'
    rendered = rest_json['content']['rendered'].encode('utf-8')
    rest_prefix = hashlib.sha256(rendered[:512]).hexdigest()
    assert rest_prefix == EXPECTED_REST_PREFIX, 'REST_PREFIX_DRIFT'
    inner = ContentDiv(frontend_html).extract()
    html_bytes = inner.encode('utf-8')
    assert 7000 <= len(html_bytes) <= 1000000, 'FRONTEND_SIZE_OUTSIDE_BOUND'
    flags = {
        'historicalTable': '<div class="su-table su-table-alternate">' in inner,
        'revLabels': '<td>Revíry</td>' in inner and '<td>Ubytování</td>' in inner,
        'postAnchors': 'id="su-post-17530"' in inner and 'id="su-post-17753"' in inner,
        'internalStaysAlreadyPresent': '/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/' in inner,
        'newModuleBlock': 'data-nry-jihocesky-stays="v1"' in inner,
    }
    return {
        'status': 'JIHOCESKY_13088_READONLY_FINGERPRINT',
        'postId': 13088, 'restPrefixSha256': rest_prefix,
        'frontendPrefixSha256': hashlib.sha256(html_bytes[:512]).hexdigest(),
        'frontendTrimmedPrefixSha256': hashlib.sha256(html_bytes.lstrip()[:512]).hexdigest(),
        'frontendBytes': len(html_bytes), 'restRenderedBytes': len(rendered),
        'frontendRestPrefixEqual': html_bytes[:512] == rendered[:512],
        'guards': flags, 'networkDestinations': 2, 'wordpressWrites': 0,
        'merchantRequests': 0, 'syntheticClicks': 0,
    }


def get(url, max_bytes):
    assert url in (PAGE, REST), 'UNAPPROVED_URL'
    req = Request(url, headers={'Cache-Control': 'no-cache,no-store',
                                'User-Agent': 'NaRyby-Jihocesky-SourceDigest/1.0'})
    with build_opener(NoRedirect).open(req, timeout=25) as response:
        assert response.status == 200 and response.geturl() == url, 'HTTP_STATUS_OR_REDIRECT'
        data = response.read(max_bytes + 1)
        assert len(data) <= max_bytes, 'RESPONSE_TOO_LARGE'
        return data


if __name__ == '__main__':
    proof = inspect(json.loads(get(REST, 200000)), get(PAGE, 2000000).decode('utf-8', 'replace'))
    print(json.dumps(proof, sort_keys=True))
