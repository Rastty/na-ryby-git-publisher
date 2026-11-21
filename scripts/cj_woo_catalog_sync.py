#!/usr/bin/env python3
"""CJ -> Na-Ryby WooCommerce affiliate catalog pilot; never promotes to Merchant Center.

Default: READ ONLY. --apply creates at most 20 hidden draft EXTERNAL products.
Existing Woo products (managed or unmanaged) are never updated, deleted or published.
Requires a real publisher-specific CJ ZIP/XML/CSV export and Woo REST credentials.
"""
import argparse
import base64
import csv
import io
import json
import os
import re
import sys
import urllib.parse
import urllib.request
import zipfile
from decimal import Decimal, InvalidOperation
from xml.etree import ElementTree

SHOP = "https://na-ryby.eu"
FEED_HOST = "datatransfer.cj.com"
SOURCE = "cj-chytapust-4597444-pid8287231"
CJ_HOSTS = {"dpbolvw.net", "tkqlhce.com", "anrdoezrs.net", "kqzyfj.com",
            "jdoqocy.com", "qksrv.net", "emjcd.com", "apmebf.com", "q4ee0.com"}
MAX_ZIP = 40_000_000
MAX_EXTRACT = 100_000_000
MAX_PRODUCTS = 10000


def pick(row, *names):
    lookup = {re.sub(r"[^a-z0-9]", "", str(k).lower()): v for k, v in row.items()}
    for name in names:
        val = lookup.get(re.sub(r"[^a-z0-9]", "", name.lower()))
        if val is not None and str(val).strip():
            return str(val).strip()
    return ""


def affiliate_ok(value):
    try:
        u = urllib.parse.urlsplit(value)
        if u.scheme != "https" or not u.hostname or u.username or u.password:
            return False
        host = u.hostname.lower()
        return (host in CJ_HOSTS or host.startswith("www.") and host[4:] in CJ_HOSTS) and \
            re.match(r"^/click-8287231-[0-9]{4,20}(?:$|/)", u.path) is not None
    except (ValueError, TypeError):
        return False


def normalize(row):
    ident = pick(row, "id", "sku", "product_id", "productid", "merchant_sku", "catalog_id")
    title = pick(row, "title", "name", "product_name", "productname")
    link = pick(row, "buyurl", "clickurl", "click_url", "affiliate_url", "link", "producturl")
    availability = pick(row, "availability", "in_stock", "stock_status", "stock")
    currency = pick(row, "currency", "currency_code")
    price_raw = pick(row, "price", "sale_price", "retailprice")
    if not ident or len(ident) > 100 or not re.fullmatch(r"[\w.:-]+", ident, re.UNICODE):
        return None, "missing_or_invalid_id"
    if len(title) < 5 or len(title) > 240:
        return None, "invalid_title"
    if not affiliate_ok(link):
        return None, "missing_verified_site_pid_click_url"
    # A numeric price alone does not establish CZK; the feed may be in EUR.
    if currency:
        if currency.upper() != "CZK":
            return None, "non_czk"
    elif not re.search(r"(?:CZK|Kč|Kc)\s*$", price_raw, flags=re.I):
        return None, "unverified_currency"

    if availability.lower() in {"out of stock", "out_of_stock", "outofstock", "false", "0", "no", "unavailable"}:
        return None, "unavailable"
    if availability.lower() not in {"in stock", "in_stock", "instock", "true", "1", "yes", "available"}:
        return None, "unverified_availability"
    price_raw = re.sub(r"\s*(CZK|Kč|Kc)\s*$", "", price_raw, flags=re.I).replace("\u00a0", "").replace(" ", "").replace(",", ".")
    try:
        price = Decimal(price_raw)
    except (InvalidOperation, ValueError):
        return None, "invalid_price"
    if not price.is_finite() or price <= 0 or price > 10000000:
        return None, "invalid_price"
    sku = "nry-cj-" + ident
    if len(sku) > 110:
        return None, "invalid_sku"
    return {"sku": sku, "title": title, "external_url": link,
            "regular_price": str(price.quantize(Decimal("0.01"))),
            "description": pick(row, "description", "long_description")[:2500]}, None


def xml_rows(payload):
    root = ElementTree.fromstring(payload)
    for node in root.iter():
        if node.tag.rsplit("}", 1)[-1].lower() not in {"item", "product", "record"}:
            continue
        row = {}
        for child in node:
            key = child.tag.rsplit("}", 1)[-1].lower()
            if child.text and key not in row:
                row[key] = child.text
        if row:
            yield row


def read_rows(payload, filename):
    if len(payload) > MAX_EXTRACT:
        raise ValueError("uncompressed_feed_too_large")
    if filename.lower().endswith(".xml") or payload.lstrip().startswith(b"<?xml") or payload.lstrip().startswith(b"<rss"):
        return list(xml_rows(payload))
    sample = payload[:4096].decode("utf-8-sig", errors="replace")
    try:
        dialect = csv.Sniffer().sniff(sample, delimiters=",;\t|")
    except csv.Error:
        dialect = csv.excel
    return list(csv.DictReader(io.StringIO(payload.decode("utf-8-sig")), dialect=dialect))


def unpack(payload):
    if len(payload) > MAX_ZIP:
        raise ValueError("compressed_feed_too_large")
    if zipfile.is_zipfile(io.BytesIO(payload)):
        with zipfile.ZipFile(io.BytesIO(payload)) as z:
            entries = [e for e in z.infolist() if not e.is_dir()]
            if len(entries) != 1 or entries[0].file_size > MAX_EXTRACT:
                raise ValueError("zip_must_contain_one_bounded_feed")
            name = entries[0].filename
            if not name.lower().endswith((".xml", ".csv", ".txt", ".tsv")):
                raise ValueError("unsupported_feed_format")
            return read_rows(z.read(entries[0]), name)
    return read_rows(payload, "raw.xml" if payload.lstrip().startswith(b"<") else "raw.csv")


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        raise ValueError("redirect_not_allowed")


def request(url, user=None, password=None, payload=None, method="GET", limit=MAX_ZIP):
    headers = {"User-Agent": "nry-cj-catalog-v1", "Accept": "application/json, */*"}
    if user and password:
        encoded = base64.b64encode((user + ":" + password).encode()).decode()
        headers["Authorization"] = "Basic " + encoded
    if payload is not None:
        headers["Content-Type"] = "application/json"
        payload = json.dumps(payload).encode()
    req = urllib.request.Request(url, data=payload, headers=headers, method=method)
    opener = urllib.request.build_opener(NoRedirect())
    with opener.open(req, timeout=25) as resp:
        data = resp.read(limit + 1)
        if len(data) > limit:
            raise ValueError("http_response_too_large")
        return data


def get_feed():
    feed = os.getenv("NRY_CJ_FEED_URL", "")
    u = urllib.parse.urlsplit(feed)
    if u.scheme != "https" or u.hostname != FEED_HOST or u.username or u.password or \
       not u.path.startswith("/datatransfer/files/") or \
       "/outgoing/productcatalog/" not in u.path or u.query or u.fragment:
        raise ValueError("invalid_CJ_export_url")
    username = os.getenv("NRY_CJ_FEED_USER", "")
    password = os.getenv("NRY_CJ_FEED_PASSWORD", "")
    if not username or not password:
        raise ValueError("missing_CJ_HTTP_auth")
    return unpack(request(feed, username, password))


def wc_request(path, method="GET", payload=None):
    key = os.getenv("NRY_WC_CONSUMER_KEY", "")
    secret = os.getenv("NRY_WC_CONSUMER_SECRET", "")
    if not key or not secret:
        raise ValueError("missing_WooCommerce_credentials")
    url = SHOP + "/wp-json/wc/v3/products" + path
    return json.loads(request(url, key, secret, payload, method, limit=6_000_000))


def inventory():
    items = []
    for page in range(1, 102):
        batch = wc_request("?per_page=100&status=any&page=" + str(page))
        if not isinstance(batch, list):
            raise ValueError("Woo_inventory_not_a_list")
        items.extend(batch)
        if len(items) > MAX_PRODUCTS:
            raise ValueError("Woo_inventory_exceeds_safe_limit")
        if len(batch) < 100:
            return items
    raise ValueError("Woo_inventory_incomplete")


def decide(feed_rows, products, limit=20):
    by_sku = {str(p.get("sku", "")): p for p in products if p.get("sku")}
    titles = {str(p.get("name", "")).casefold().strip() for p in products}
    seen = set()
    changes = []
    counts = {"feed_rows": 0, "eligible": 0, "duplicate_feed": 0,
              "blocked_existing": 0, "create_draft": 0, "update_managed": 0, "rejected": {}}
    for row in feed_rows:
        counts["feed_rows"] += 1
        item, reason = normalize(row)
        if reason:
            counts["rejected"][reason] = counts["rejected"].get(reason, 0) + 1
            continue
        counts["eligible"] += 1
        if item["sku"] in seen:
            counts["duplicate_feed"] += 1
            continue
        seen.add(item["sku"])
        existing = by_sku.get(item["sku"])
        if existing:
            # Pilot is CREATE-ONLY, even for a source-tagged draft. It may have
            # been published since inventory GET; never mutate its affiliate URL.
            counts["blocked_existing"] += 1
        elif item["title"].casefold().strip() in titles:
            counts["blocked_existing"] += 1
        elif len(changes) < limit:
            changes.append(("create", None, item))
            counts["create_draft"] += 1
            titles.add(item["title"].casefold().strip())
    return changes, counts


def apply_changes(changes):
    for operation, product_id, item in changes:
        if operation != "create" or product_id is not None:
            raise ValueError("pilot_forbids_existing_product_writes")
        body = {"type": "external", "name": item["title"], "sku": item["sku"],
                "product_url": item["external_url"], "button_text": "Zobrazit v obchodě",
                "regular_price": item["regular_price"], "status": "draft",
                "catalog_visibility": "hidden", "description": item["description"],
                "meta_data": [{"key": "_nry_cj_source", "value": SOURCE}]}
        wc_request("", "POST", body)


def main():
    p = argparse.ArgumentParser()
    p.add_argument("--apply", action="store_true", help="Create only new hidden drafts; never update existing products")
    p.add_argument("--limit", type=int, default=20)
    p.add_argument("--feed-file", help="Offline fixture; only allowed without --apply")
    p.add_argument("--inventory-file", help="Offline fixture; only allowed without --apply")
    args = p.parse_args()
    if not 1 <= args.limit <= 20:
        p.error("limit must be 1..20")
    if args.apply and (args.feed_file or args.inventory_file or os.getenv("NRY_CJ_WOO_APPLY") != "YES"):
        p.error("apply requires explicit NRY_CJ_WOO_APPLY=YES and live sources")
    rows = unpack(open(args.feed_file, "rb").read()) if args.feed_file else get_feed()
    products = json.load(open(args.inventory_file)) if args.inventory_file else inventory()
    changes, counts = decide(rows, products, args.limit)
    # No CJ tracking links or merchant IDs appear in logs.
    print(json.dumps({"mode": "APPLY" if args.apply else "DRY_RUN",
                      "catalog": counts, "planned_actions": len(changes),
                      "sample": [{"action": op, "sku": i["sku"]} for op, _, i in changes[:5]]},
                     ensure_ascii=False))
    if args.apply:
        apply_changes(changes)
        print(json.dumps({"status": "DRAFT_ONLY_WRITES_FINISHED", "actions_attempted": len(changes)}))


if __name__ == "__main__":
    try:
        main()
    except Exception as error:
        # Never print exception strings from HTTP clients (may contain credentials/URLs).
        print(json.dumps({"status": "BLOCKED", "error_type": type(error).__name__}), file=sys.stderr)
        sys.exit(1)
