# Visible CTA cards for the already published fishing-stays article (post 59891)

Status: **release candidate only; production CSS not yet verified**. On
18 November 2026, public GET preflight confirmed one existing v9 section,
all five exact reviewed sponsored destinations, the original article and
canonical URL. Public WordPress REST confirms published post 59891; its
`modified_gmt` has advanced since the original editorial insertion, so the
CSS eligibility checks the exact published post identity, one marked section,
all five reviewed offer IDs and **all five original affiliate URLs**. This
keeps the guard intact when WordPress reformats the stored block or editorial
copy changes without changing the merchant links. It never
changes the WordPress post or another contributor's newer copy.

## Visitor outcome

Five historical in-text CTAs become visibly separated cards. The first
source-reviewed stay, Statek Jedraž, has an emphasized border. On desktop,
each existing sponsored offer link becomes a clear, right-side green button;
on a narrow screen, the same button occupies its own row below the property
description. Existing first-party area/context links stay ordinary links.
All five hrefs, offer IDs, labels, descriptions and tracking JS remain
byte-for-byte untouched by this change. No price, inventory or implied
fishing permit is added.

The existing `buyer-click-telemetry-v1.php` now enqueues a small local CSS file only on
the exact single post ID, canonical permalink and published raw-content
marker with all five exact offer IDs and approved URLs once. Affiliate drift, changed identity,
draft, preview or admin view fail closed. CSS selectors are fully scoped to
`data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"`.

Release requires successful authorized guarded deploy, then independent
`python3 scripts/verify-fishing-stays-cta-59891-public.py --verify`, checking
the five existing exact affiliate URLs and the served CSS bytes. The same
script with `--preflight` is read-only and should pass before deployment.
Git merge, CI or a `200` for the article itself is **not** installation proof.

Rollback is removal of the isolated CTA style hook and guarded redeploy. No
database rollback required. Whether the stronger CTA improves actual
user clicks, completed reservations or paid commissions remains unknown
until measured from consenting traffic and partner reports through
Prometheus Core; no synthetic affiliate clicks are used.

**Known delivery block at preparation:** guarded deploy run
`44329561032` passed offline verification but WordPress Deployer returned
**HTTP 401** at the exact authorized webhook request. This is an
authentication/permission block; renew/repair the authorized WordPress
Deployer URL configuration and re-run the normal guarded workflow, then
verify both South Moravia post 12166 and this CTA change publicly.
