#!/usr/bin/env python3
"""Read-only, bounded source-provenance audit for one sonar FAQ JSON-LD claim.

Output contains only type names, whitelisted structural paths, booleans and
counts. Never prints article text, script content, product URLs or visitor data.
No merchant requests, analytics events, WordPress writes or search indexing.
"""
import json
import sys
from html.parser import HTMLParser
from pathlib import Path
from urllib.request import Request
from urllib.parse import quote

sys.path.insert(0, str(Path(__file__).resolve().parent))
from readonly_urlopen_retry import guarded_urlopen

SITE = "https://na-ryby.eu"
SLUG = "nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz"
PAGE = SITE + "/" + SLUG + "/"
REST = (SITE + "/wp-json/wp/v2/posts?slug=" + quote(SLUG) +
        "&_fields=id,slug,content,yoast_head_json")
OLD = "hlavní doporučení stavím jen na aktuálně dohledatelných nabídkách"
OLD_TAIL = ("V tomto článku ale hlavní doporučení stavím jen na aktuálně "
            "dohledatelných nabídkách z OKfish a Chyť a pusť.")
OLD_FULL = ("Ano jako obecně známý referenční model ano. " + OLD_TAIL)
SAFE = "Garmin Striker Vivid může být jednou z možností"
ALLOWED_PATHS = frozenset({
    "@graph", "@context", "@type", "mainEntity", "acceptedAnswer", "text",
    "name", "description", "articleBody", "headline", "about", "itemListElement",
    "item", "content", "hasPart", "subjectOf", "question", "answer",
    "reviewBody", "mentions", "author", "publisher", "isPartOf", "schema",
})
MAX_BYTES = 1800000


class ScriptReader(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.records = []
        self.active = None

    def handle_starttag(self, tag, attrs):
        if tag.lower() == "script":
            a = dict(attrs)
            self.active = {"type": str(a.get("type") or "")[:50], "body": ""}
            self.records.append(self.active)

    def handle_endtag(self, tag):
        if tag.lower() == "script":
            self.active = None

    def handle_data(self, data):
        if self.active is not None:
            self.active["body"] += data


def audited_get(url):
    if url not in (PAGE, REST):
        raise ValueError("FIXED_FIRST_PARTY_URL_ONLY")
    req = Request(url, headers={
        "User-Agent": "NaRyby-FAQSchemaProvenanceReadOnly/1.0",
        "Cache-Control": "no-cache, no-store",
    }, method="GET")
    with guarded_urlopen(req, timeout=25) as res:
        if res.status != 200:
            raise ValueError("EXACT_GET_NON_200")
        body = res.read(MAX_BYTES + 1)
    if len(body) > MAX_BYTES:
        raise ValueError("PUBLIC_MAX_BYTES_EXCEEDED")
    return body.decode("utf-8", "replace")


def sanitized_paths(tree, phrase):
    """Ground match in JSON structure; never return any untrusted JSON values."""
    matches = []
    types = set()

    def walk(node, path, depth):
        if depth > 16 or len(matches) >= 12:
            return
        if isinstance(node, dict):
            candidate_type = node.get("@type")
            if candidate_type and phrase in json.dumps(node, ensure_ascii=False):
                if isinstance(candidate_type, str) and len(candidate_type) < 80:
                    types.add(candidate_type if candidate_type in (
                        "FAQPage", "Question", "Answer", "Article", "BlogPosting",
                        "WebPage", "ItemList", "Product", "Review", "HowTo"
                    ) else "OTHER")
            for k, v in node.items():
                key = k if k in ALLOWED_PATHS else "OTHER_FIELD"
                walk(v, path + [key], depth + 1)
        elif isinstance(node, list):
            for v in node[:60]:
                walk(v, path + ["[]"], depth + 1)
        elif isinstance(node, str) and phrase in node:
            matches.append(".".join(path)[-200:])
    walk(tree, [], 0)
    return sorted(set(matches)), sorted(types)


def script_findings(markup):
    parser = ScriptReader()
    parser.feed(markup)
    matches = []
    for record in parser.records:
        if OLD not in record["body"]:
            continue
        tree = None
        try:
            tree = json.loads(record["body"])
        except (ValueError, TypeError):
            pass
        paths, types = sanitized_paths(tree, OLD) if tree is not None else ([], [])
        matches.append({
            "type": record["type"],
            "json_valid": tree is not None,
            "root_kind": "dict" if isinstance(tree, dict)
                else "list" if isinstance(tree, list)
                else "other" if tree is not None else "unparsed",
            "root_type": str(tree.get("@type"))[:50] if isinstance(tree, dict)
                and tree.get("@type") in ("FAQPage", "Question", "Answer",
                                         "Article", "BlogPosting", "WebPage") else "OTHER_OR_NONE",
            "top_level_graph": isinstance(tree, dict) and isinstance(
                tree.get("@graph"), list),
            "old_tail_exact_in_script": OLD_TAIL in record["body"],
            "old_full_exact_in_script": OLD_FULL in record["body"],
            "matching_json_paths": paths,
            "matching_schema_types": types,
        })
    return matches


def audit(page_html, rest_html=None):
    findings = script_findings(page_html)
    result = {
        "public_html_script_matches": len(findings),
        "public_script_structure": findings[:3],
        "visible_safe_phrase_present": SAFE in page_html,
        "legacy_phrase_in_public_page": OLD in page_html,
        "old_schema_in_page_head": (
            page_html.find(OLD_FULL) >= 0 and
            page_html.lower().find("<body") > page_html.find(OLD_FULL)
        ),
        "old_schema_before_content_marker": (
            page_html.find(OLD_FULL) >= 0 and
            page_html.find("<!-- nry-sonar-budget-trust-118455-v1 -->") >
            page_html.find(OLD_FULL)
        ),
        "rest_status": "NOT_READ",
        "wordpress_writes": 0, "merchant_requests": 0,
        "analytics_events": 0,
    }
    if rest_html is not None:
        try:
            posts = json.loads(rest_html)
            if (isinstance(posts, list) and len(posts) == 1
                and posts[0].get("id") == 118455
                and posts[0].get("slug") == SLUG):
                post = posts[0]
                rendered = post.get("content", {}).get("rendered", "")
                yoast = post.get("yoast_head_json")
                result.update({
                    "rest_status": "POST_118455_IDENTIFIED",
                    "rest_content_old_claim": OLD in rendered,
                    "rest_content_jsonld_script_matches": len(script_findings(rendered)),
                    "rest_yoast_json_old_claim": OLD in json.dumps(
                        yoast, ensure_ascii=False) if yoast else False,
                })
            else:
                result["rest_status"] = "IDENTITY_NOT_PROVEN"
        except (ValueError, TypeError, AttributeError):
            result["rest_status"] = "UNPARSEABLE"
    return result


if __name__ == "__main__":
    page = audited_get(PAGE)
    try:
        rest = audited_get(REST)
    except Exception:
        rest = None
    result = audit(page, rest)
    print(json.dumps(result, ensure_ascii=False, sort_keys=True))
    if not result["public_html_script_matches"]:
        raise SystemExit("BLOCKED: exact stale source script not visible; no edit authorized")
