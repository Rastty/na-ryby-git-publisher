# Bivak buyer intent: read-only click cohort (2026-11-17)

## Source evidence

Independently read from the public WordPress REST API on 2026-11-17:

| WordPress ID | Canonical path | `modified_gmt` | Observed outbound paths |
|---|---|---|---|
| 118388 | `/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/` | `2026-11-15T14:03:11` | One legacy OKfish Dognet category CTA; existing Chyť a pusť CJ and Parys CJ model CTAs; internal bivak hub |
| 119575 | `/nejlepsi-rybarske-bivaky-do-10-000-kc/` | `2026-05-13T14:09:39` | Existing Chyť a pusť CJ links using a different, **not site-entitlement-verified**, publisher/program identity; internal bivak hub |

This work adds only those two ID+permalink pairs to the already deployed, consent-aware `nry-buyer-offer-click-v1` frontend instrumentation. No post content, CTA, image, stock claim, redirect, CJ creative or merchant link is changed. The script remains passive, uses existing `gtag` only when available, and requires a genuine browser event (`isTrusted`). It never sends a URL, destination ID, affiliate query, visitor identifier or recommendation answer to GA4.

### Aggregate event meaning

- `buyer_page=bivak_pillar` and `buyer_page=bivak_budget` distinguish the two exact guides.
- `buyer_cta_type=cj_product_link` means an observed outbound link with the configured-shape historical CJ identity already counted by this frontend; it does **not** prove CJ attribution.
- `buyer_cta_type=cj_other_identity_unverified` means an outgoing link matching the exact alternate CJ identity and a canonical merchant destination already observed on post 119575. **Publisher entitlement is unverified for Na-Ryby**; do not treat it as a tracked commission.
- `buyer_cta_type=dognet_legacy_outbound` is the one exact existing OKfish CTA on post 118388, not a new placement or a product recommendation.
- `nry_buyer_guide_click`, `guide_type=bivak_hub` is a real visitor's internal move to an existing bivak guide.

Unrelated paths, altered merchant redirects, extra query fields and synthetic clicks are excluded. No event is emitted when `gtag` is unavailable; all consent and attribution decisions remain with existing site configuration.

### Operational readback and interpretation

After main branch deployment, run `python3 scripts/audit-buyer-click-live-readiness.py`. It fetches seven fixed public pages plus one same-origin JS asset if required. `BYTE_IDENTICAL_TO_REVIEWED_JS` and a single script tag on both new pages establish *installation/readback only*. They do **not** establish any click, GA4 ingestion, Google Search growth, sale, approved commission or payout.

All existing carp, chair and sonar public-readback verifiers retain an exact
baseline `ver=1.8.0` for PR preflight and exact `ver=1.9.0` + asset SHA equality
for **post-deployment** proof. Updating the shared JS without these version
pins would create a false public-verification failure.

Once GA4 provides a valid observation window with consenting traffic, compare the `nry_buyer_offer_click` counts by `buyer_page` and `buyer_cta_type`, separately from `nry_buyer_guide_click`. Do not infer commercial conversion rates from click counts alone. Await the site's approved commission reporting for attributable earnings.

## Guardrails and next decision

Prometheus Core's current `PROMETHEUS-AFFILIATE-SITE-POLICIES-01.json` distinguishes Na-Ryby from ChytrýRybář, forbids inherited CJ templates, and requires site-specific entitlement/release review before **new** affiliate placements or link substitutions. Therefore neither the old Dognet URL nor the alternate CJ identity is modified in this work, even though the owner wants to retire OKfish. Route a future source-pinned replacement through the established entitlement, review, rollback and production-readback process. This read-only cohort is a precursor to that decision, not permission to switch tracking IDs.
