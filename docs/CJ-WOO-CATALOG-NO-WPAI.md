# Na-Ryby: CJ → WooCommerce catalog without WP All Import

**Status:** Candidate only. No WordPress database writes, CJ clickouts, product publications or Merchant Center changes have been executed.

## Why this exists
The site already has historically imported products and an active Google Merchant auto-feed. The WordPress import plugin is NOT the desired ongoing dependency. The safe replacement is a source-scoped feed sync, separate from the protected Git Publisher editorial write path and from Google Merchant Center.

This candidate uses:
- Authorized **CJ publisher feed export** for advertiser Chyť a pusť (CJ advertiser **4597444**), generated for **na-ryby.eu property PID 8287231**;
- WordPress **WooCommerce REST v3 API** directly, rather than WP All Import or WPVibe;
- default **read-only** collision preview; bounded `draft-pilot` may create up to **20 hidden drafts**;
- a strict allowlist for CJ click-link hosts and exact `click-8287231-...` pattern, without following tracking links.

## Required one-time configuration (GitHub repository secrets)
Store ONLY in GitHub repository/environment secrets, never issues, commits, chat or output:
- `NRY_CJ_FEED_URL`: approved, literal HTTPS `datatransfer.cj.com/datatransfer/files/.../outgoing/productcatalog/...` export URL **without username/password**.
- `NRY_CJ_FEED_USER` and `NRY_CJ_FEED_PASSWORD`: CJ HTTP delivery credentials for that export.
- `NRY_WC_CONSUMER_KEY` and `NRY_WC_CONSUMER_SECRET`: WooCommerce REST API **least-privilege** key/secret, read/write only when activation is intended.

The code refuses to download from arbitrary hosts, follow redirects, or use embedded URL credentials. It rejects oversized ZIPs and archives containing more than one feed. It supports CSV and XML with common feed field names; **real feed schema must be checked before applying**.

## Running
Workflow `NRY CJ WooCommerce catalog pilot (no WP All Import)`:
1. On PR, only syntax and offline tests run. No secrets, CJ requests or site writes.
2. `workflow_dispatch → dry-run` checks feed rows and reads WooCommerce product inventory. It reports only counts and a few stable SKU keys. It DOES NOT write or publish.
3. Once the CJ site-specific rights, exact feed fields, stock/price semantics, existing Woo SKU/ID/merchant-destination map, and affiliate URLs have been verified, an owner-approved `draft-pilot` requires `na-ryby-production` environment approval **and** repository variable `NRY_CJ_WOO_APPLY=YES`. It creates at most 20 external-product drafts with `catalog_visibility=hidden`; after every POST it independently GETs that exact Woo product ID and requires a hidden draft, correct SKU, price, affiliate URL and source tag before any next write. A missing or mismatched readback **stops the job** without retrying the ambiguous POST. It never makes a fresh product publicly visible.

Local, offline contract check:
```sh
python3 -m unittest discover -s tests -p 'test_cj_woo_catalog_sync.py' -v
```

## Non-destructive ownership contract
- Existing WooCommerce products from WP All Import, editorial content, and **all** existing SKUs (including our earlier managed drafts) are **never** changed in the pilot.
- **CREATE-ONLY in the pilot:** no WooCommerce PUT/DELETE at all. An exact managed SKU found in inventory is skipped, even when it is still a hidden draft. Refreshing existing products requires a later separately reviewed release and source/receipt checks.
- A matching existing title under another SKU **or an identical decoded Chyť a pusť product destination** is a collision and is skipped; old CJ site PID and UTM tags do not hide same-destination duplicates. Variant-specific query parameters remain distinct. Ambiguous variants are not silently merged. Hidden drafts are included in the inventory check.
- Missing or unverified price, explicit CZK currency (feed field or price suffix), stock, correct PID tracking URL **or an exact HTTPS Chyť a pusť product destination** means skip. Never assume an unspecified currency is CZK or follow tracking URLs.
- No automatic deletion, archival, or update of vanished or existing imported products.
- Media are intentionally not copied until feed image permissions, attribution and WordPress media/storage behavior are reviewed. Product snippets without images remain drafts.
- No automatic scheduled writes yet. After one read-only report and first approved pilot, review mapping and receipts; only then decide whether to enable a cron.

## Measurement and Merchant policy
This imports external affiliate product cards for **organic SEO and referral**, not Shopping ads/free listings. Google says merchant product listings require products available for direct checkout on the listing store, with CSS exceptions. Never submit these affiliate draft products to Merchant Center or assume an existing AUTOFEED is policy approval.

Proof of revenue requires independent CJ approved/paid commissions; a GitHub run, WordPress draft or outbound click is NOT revenue evidence.

## Operational blockers
Current WPVibe free quota was exhausted on 2026-11-20; this candidate does not depend on WPVibe. This code cannot be run against production until correct CJ feed URL/HTTP credentials and Woo API keys exist as secrets and its real schema has passed read-only preview.

Rollback of this candidate before activation: close PR. After a pilot: delete only new products marked by exact source tag with an operator-reviewed receipt; do not delete preexisting WP All Import products. Existing Git Publisher is unaffected.
