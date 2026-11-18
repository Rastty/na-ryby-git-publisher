# Fishing stays CZ — source-first commercial audit (18 Nov 2026)

**Owner affiliate confirmation (18 Nov 2026, newer than the frozen audit
snapshot below):** Hauzi.cz/.sk is **approved** through AffiliatePort,
advertiser `o=1990`, publisher `a=1736`. Its property-level deep link uses
`https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=DEEPURL`,
where `DEEPURL` is the percent-encoded reviewed Hauzi destination; it is
not a raw unescaped URL. Advertiser terms report a **30-day cookie** and
**€2.40 CPA for a completed reservation** (goal `Reservation (2957)`), not
per outbound click. The first Hauzi listing is live on Dyje 6 post **14517**
after [site PR #261](https://github.com/Rastty/na-ryby-git-publisher/pull/261),
guarded [deploy #44321689496](https://github.com/Rastty/na-ryby-git-publisher/actions/runs/44321689496)
and independent [PUBLIC_VERIFIED readback #44322013396](https://github.com/Rastty/na-ryby-git-publisher/actions/runs/44322013396).
There are three *individual* exact offers in the single Dyje 6 offer panel;
no real click, reservation, approved or paid commission is asserted.

The owner also provided a generated **Slevomat.cz** direct affiliate example
for Imperial Karlovy Vary (`/akce/2442260-...`):
`utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_na-ryby.eu`,
`utm_source=affiliate`, `utm_medium=cpc`, and `utm_term=j3d7tsgc`.
This confirms partner ID `j3d7tsgc` and the owner-provided campaign-domain
spelling **`na-ryby.eu` for future CZ deep links**. The exact example omits
`a_box`, while the earlier owner-provided generic program URL included
`a_box=j3d7tsgc`. Existing source-locked live 59891 and Dyje 6 links use
the earlier `narybyCZ` generator-derived campaign label *and* `a_box`.
Neither variant has a measured affiliate conversion receipt in this review:
do **not** silently swap existing monetized destinations or infer a broken
attribution from different UTM-label spellings. Imperial is a sample of
link construction, not a vetted rybářský pobyt or locality recommendation.
Future source-reviewed fishing stays should use the exact new owner-approved
domain spelling, with merchant attribution and actual order evidence reviewed
in Prometheus Core. No synthetic redirect test is permitted.

**Next locality, awaiting fresh source lock:** existing post **12166**
([Rybářská dovolená jižní Morava](https://na-ryby.eu/rybarska-dovolena-jizni-morava/))
can potentially route readers to the existing Nové Mlýny/Dyje 6 guide and
the *geographically matched* Pasohlávky/Dolní Věstonice offers. Its older
2016–18 property descriptions need trust review, fresh authenticated raw
source/hash and a separate guarded release. The currently available Windsor
GSC connection exposes only Klix.cz, so 12166 is an **editorial candidate**,
not a numerically established GSC winner. No WP write or published placement
on 12166 is claimed.

**Production follow-up (18 Nov 2026, after the historical audit below):**
The standalone post **59891** affiliate canary is **applied and independently
public-verified**. The owner supplied the guarded Git Publisher receipt
`applied_and_verified` with subsequent `already_applied`; authenticated
read-only WP REST returned `modified_gmt=2026-11-18T10:32:38` and the exact
v9 block at the start of `content.raw`. Independent anonymous rendered
HTML readback of the actual article showed the one release marker, all five
published Slevomat/Hportal CTA URLs with `rel=sponsored nofollow noopener`,
the three relevant internal links and the original article after the block.
**No repeat WordPress write is required**. The older source-binding hashes,
`publishable=false` review records and "not published" wording below describe
the immutable *pre-release evidence snapshot*, not the site's present state.
The sole active site release remains the immutable v9 canary; the main
manifest was not replaced.

**Measurement follow-up:** page 59891 was initially not included in the
existing `nry-buyer-clicks-v1.js` enqueue allowlist. The subsequent
source-locked measurement change reuses that **same** existing consent-aware
`gtag` click event channel for only the five exact published links and is
separately gated by CI, guarded plugin deploy and public JS-byte readback.
Instrumented code in Git does **not** establish an accepted real GA4/Core
event. Actual clicks, bookings, approved/paid commissions and attribution all
remain **UNKNOWN** until source-authorized measurements arrive. No
synthetic link requests, impression-to-click guesses or second engine.

**Hracholusky locality follow-up:** the published 2018 post **17606** still
contains a legacy Agoda/CJ outbound box, an unsupported lowest-price claim,
and old bungalow details whose current saleability was not independently
confirmed. The proposed `hracholusky-stay-trust-17606-v1.php` is a **render-only,
source-drift-locked trust correction**, not a WordPress stored-content write:
when the exact old box, published post identity and 2018 modified timestamp
match, it replaces only that box with a clear historic-information warning,
honest mention of the **different property** Pension U Vlčků and an internal
link to the already-live, tracked 59891 offers. It introduces no second
merchant/outbound link and makes no claim about fishing permits or inventory.
All other source HTML, links, post fields and originals remain untouched;
the entire intervention is reversible via the guarded Git deploy. Any mismatch
preserves the old rendered content and is a **blocked/fail-closed** signal,
not an invitation to relax the guard. `scripts/verify-hracholusky-stay-trust-public.py`
is the separate anonymous GET-only monitor. A green PR is not proof of a live
change; final production status requires successful deploy and public proof.

**Status: OFFLINE REVIEW ONLY. Five partner-derived deep-link candidates and two HTML recommendation blocks have been prepared and syntax-tested; no new WordPress content or links were published.**

## North Star and separation of ownership

Na-ryby.eu should let visitors start at a fishing water, understand fishing conditions, and choose genuinely relevant accommodation with verified booking/affiliate links. New revíry may be added incrementally. The matching, freshness, provider normalization, attribution, order and approved/paid commission join belong to shared Prometheus Core; this repository only supplies CZ site editorial identities, observed merchant facts and eventual safe WordPress presentation.

Do not create a new site matching engine, redirect service, unconsented tracker, or economic scorer. Do not automatically convert private ponds into official ČRS fishing revír IDs. Every fishing right, geographic match and stay status requires source evidence. Promote existing buyer-traffic pages before creating new article volumes.

## Existing pages — editorial inventory, NOT GSC priority

Live public WordPress search endpoint returned multiple existing holiday pages. Exact REST post source identity verified for:

| Post | Existing page / editorial assessment | Next safe intervention |
| --- | --- | --- |
| 59891 | [Rybářské pobyty v přírodě](https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/) — practical refreshed evergreen guidance, no current directly bookable curated stays in inspected introduction | Best **editorial candidate** for first non-invasive offer block once source, affiliate link and Core measurement are proven |
| 59271 | [Rodinná dovolená u vody](https://na-ryby.eu/rodinna-dovolena-u-vody-kam-na-ryby-s-detmi/) — generic pond/river/cottage suggestions, no independently verified family room/child conditions in opening | Source-check real child-friendly offers before any recommendation |
| 12166 | [Rybářská dovolená jižní Morava](https://na-ryby.eu/rybarska-dovolena-jizni-morava/) — 2016/2017–18 historical catalogue, old property extracts and many older onward pages | Refresh the existing region experience and verify local offers; **do not** inject South-Bohemia or Central-Bohemia offers merely for conversion |
| 59113 | [Dovolená na privátním revíru](https://na-ryby.eu/nezapomenutelna-rybarska-dovolena-na-privatnim-reviru-s-komfortnim-ubytovanim/) — misleading first-person text claiming the publisher owns/operates private revíry | Trust repair before monetizing; no invented operator/ownership claims |

The [Znojmo search landing](https://na-ryby.eu/s/rybarska-dovolena-znojmo/) repeats broad boilerplate (even unrelated destinations) and old outgoing `chata.cz` inventory. This merits SEO/UX correction, but the route is outside the guarded content cohort and must not be mass-rewritten without fresh safe source and release authority.

Available Windsor.ai Search Console connector exposes only `https://klix.cz/`, not Na-ryby.eu. **No Na-ryby GSC rows, page-query impressions, clicks, position or attributable revenue were obtained.** These candidate pages are chosen on observed editorial relevance, not represented as numerical GSC winners. Do not fabricate CTR or opportunity scores. An existing direct GSC export/API connection in Core may resolve this; require actual data before ranking.

## Slevomat candidate source checks

Official merchant pages opened and reviewed on 18 Nov 2026. The offer's existence and its variant text are not proof of room availability for the visitor's chosen date.

- **Statek Jedraž**, [offer 2462030](https://www.slevomat.cz/akce/2462030-statek-na-jihu-cech-snidane-i-privatni-sauna-ci-rybareni): some variants include own-gear private-pond catch-and-release, 2 rods/24 h; May–November Saturday exclusion; merchant validity to 30 Nov 2027. Appropriate to general private-pond stays, **not** South Moravia. Store this private water with no invented public revír ID.
- **Hausbót Pohodička**, [offer 2345808](https://www.slevomat.cz/akce/2345808-hausbot-na-rybnicku-pobyt-s-rybolovem-i-lodkou): Stuchanov, Sedlec-Prčice area, 2–4 people, private catch-and-release pond and small rowing boat. Page also contains the warning "Nabídka už není k dispozici" and capacity errors alongside a 3 Jan 2027 validity label. **Block public recommendation until actual saleable availability is confirmed.**
- **Hotel Kouty**, [offer 2450904](https://www.slevomat.cz/akce/2450904-hotel-uprostred-lesu-v-posazavi-snidane-i-wellnes): wellness and family context; listed "rybářský lístek po celou dobu pobytu" does not by itself prove a legally valid fishing entitlement at a named water. Block explicit fishing recommendation until merchant conditions are clear.
- **Exclude** [offer 2459432](https://www.slevomat.cz/akce/2459432-moderni-chata-v-jiznich-cechach-vc-sauny-a-virivky): pond in reconstruction, no fishing or swimming.
- **Exclude** [offer 1512080](https://www.slevomat.cz/akce/1512080-pobyt-v-bacalskem-mlyne-s-jidlem-i-rybarenim): indexed fishing variant expired in 2019.

## Offer presentation and activation gate

The first controlled activation on post 59891 should lead with **one clearly distinguished property whose selected variant includes fishing**, followed by a separately labeled set of geographically appropriate accommodation alternatives. Each entry needs a real property name, honestly stated fishing/permit conditions, a last-source-check date and an “Ověřit termín a nabídku” CTA; a relevant existing site guide may be linked **only with an honest geographic and topic label**. No fake stock, prices, proximity, paid endorsement, or claim to be the water operator. A smaller locality-specific block should be activated only after a real first-page readback.

## New owner-provided publisher route evidence — 18 Nov 2026

The owner additionally confirmed **Hportal.cz approved through Dognet** and supplied the example redirect shape `https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fjesenik%2Fpension-iva%2F`. The review stores `chid=5QoXiSqx` as **owner-supplied**, constructs future Hportal destination URLs with `encodeURIComponent`, and validates them offline. Do **not** ping Dognet links as an artificial test click. A matching URL is not confirmation of earned commission. The example Pension Iva does not list fishing under its reviewed amenities; it was not recommended solely on the basis of being the tracking example.

The owner supplied a Slevomat landing link with `a_box=j3d7tsgc`, but UTM placeholders `VASEID`, `VASEID_DOMENACZ` and `cokoliv`. The publicly linked [Slevomat affiliate generator](https://www.slevomat.cz/affiliate-link-generator) was opened and tested with a **dummy partner ID and dummy domain** (no owner's identifier sent to the Google-hosted form). It confirmed the UTM shape `utm_source=affiliate`, `utm_medium=cpc`, `utm_campaign=dis_akv_gen_cze_all_buy_<AffilID>_<domenaCZ>`, `utm_term=<AffilID>` and optional `utm_content`. The generator output itself omitted `a_box`, so the reviewed Jedraž link *adds the owner's independently supplied* `a_box=j3d7tsgc` alongside the corrected UTM fields. This is **syntax/reconstruction evidence, not a logged-in AffilBox conversion validation**. Use publisher domain label `narybyCZ`. No artificial Slevomat click was sent.

Hauzi CZ/SK is still pending owner-reported AffiliatePort approval; there are no Hauzi tracked links.

### Verified Hportal source listings and locality joins

| Property and owner-derived destination | Verified listing facts | Area and site guide context | Caveat |
| --- | --- | --- | --- |
| [Pension U Vlčků](https://hportal.cz/cs/ulice/pension-u-vlcku/) | Hracholusky 29; Hportal lists fishing and water sports | Hracholusky; [older accommodation article](https://na-ryby.eu/bungalov-u-prehrady-hracholusky/) | No fishing entitlement or room inventory confirmed |
| [Penzion Janata](https://hportal.cz/cs/rabyne/penzion-janata/) | Nová Rabyně 25, Slapy; fishing, beach/water-sports amenities | Slapy; [broader Prague-region fishing guide](https://na-ryby.eu/zimni-privlac-v-okoli-prahy-kde-najit-nejlepsi-reviry/) | No claim that the stay includes a Slapy permit |
| [Pension U Kohoutů](https://hportal.cz/cs/horni-plana/penzion-u-kohoutu/) | Horní Planá; fishing listed; family rooms | Lipno; [broader deep-spinning guide](https://na-ryby.eu/vybrane-reviry-vhodne-pro-hlubinnou-privlac-v-cesku/) | Family room does not prove children's suitability/availability for all dates |
| [Pension Rybářská Bašta](https://hportal.cz/cs/rozmberk-nad-vltavou/pension-rybarska-basta/) | Rožmberk nad Vltavou 124; fishing activity listed | Vltava locality; no verified exact site revír page | **Not** Rybník Rožmberk near Třeboň; never conflate |

The Hportal merchant pages are primary source descriptions of amenities, **not independent verification that the advertised property is bookable for a particular date or that a named public fishery's rules are satisfied**. Exact ČRS/MRS revír ID is deliberately null for all four area entries. No invented distance estimates.

### Safe placement drafts and release block

- `editorial/fishing-stays-cz-20261118-recommendation-blocks.html`: a semantically structured, clearly marked affiliate block for existing evergreen post **59891**, led by the Slevomat offer that explicitly includes fishing in selected variants; four Hportal location alternatives; a smaller Hracholusky alternative for an existing 2018 accommodation page.
- Six outbound CTA placements reference five source-reviewed owner-derived links. `rel="sponsored nofollow noopener"` and honest permissions/dates/availability wording are checked offline. Two included internal links refer to **broad fishing guides**, not property-specific legal revír detail pages.
- `scripts/verify-fishing-stays-cz-offline-v1.mjs` verifies the owner-supplied network identifiers, UTM shape, Dognet redirect `url` encoding, merchant allowlist, area/water separation, linked HTML consistency and absence of placeholder IDs. All 7 offers deliberately remain `publishable=false`, since this status means **no completed production safety release yet**, not unavailability of every room.

This site's WPVibe account reported **99/100 free-tier calls used** in the rolling 24-hour window. More importantly, WordPress publication through the Git publisher requires a **fresh source/hash binding, private rollback, authorized WP Admin Apply and independent public readback**. The next section records the newly obtained binding for post 59891, but no private rollback or authenticated write receipt exists yet; do not append the new schema to live `manifest.json` blindly or bypass its guard. A GitHub merge and offline test do not count as publication. When the authorized admin path is available, release just one entry first (post 59891), verify the live link and real consent-aware click instrumentation, and only then roll out to locality pages.

## Source-bound release canary (second pass, same day)

An authenticated *read-only* WordPress REST `context=edit` response for post **59891** was obtained on 18 Nov 2026. The actual `post_content` was **14,101 UTF-8 bytes** with SHA-256 `0edcd92cf829f6386277a24c371cd1a3239430fff9b1c10e9280bc926a1d8e79` and last-modified GMT `2026-04-14 14:33:18`. Full raw content is deliberately **not** copied into a public Git repository. The reviewed first section has **4,234 bytes**, SHA-256 `531bd77829fdc59f8cdee206df1259dad942efc40d67c6e3c60a2c1a5c73c86b`, and the fixed `fishing-stays-cz-59891-20261118-v1` marker. These are now pinned in the evidence JSON and offline test.

The existing v5–v8 WordPress publisher **correctly rejects external links**. For this narrowly approved owner-program canary, schema **v9** is added to the *same* publisher with:

1. Exactly one fixed post, slug, batch ID, source GMT, size and SHA; no generic new writer.
2. An immutable first-section HTML hash, exact Slevomat and Dognet attribution values, explicit merchant URL allowlist, and exact three internal-area links. Any changed offer/ID requires a separately reviewed code revision.
3. Existing single-post source-drift stop, private WordPress-local backup, nonce/capability-protected administrator execution and post-write SHA readback; the WP cron auto-publisher **remains restricted to schema 5–8**. A v9 manifest must never be put live before the v9 plugin code and an authorized manual application path are confirmed.
4. Read-only candidate serialization through `node scripts/build-fishing-stays-cz-manifest-v9.mjs --stdout` and a PHP offline fail-closed test. This script does **not** overwrite the active `manifest.json` or write to WordPress. Independent public readback is still mandatory after activation.

**Current status: tested candidate, not a live CTA.** A source hash alone is not a private rollback snapshot. Before live execution the WordPress editor/Deployer must confirm no other writer, current source equality, the immutable manifest, authorized Admin Apply and complete public verification. If the post has changed, regenerate the evidence and pass a fresh review; do not force an old SHA through.

### Standalone Admin Apply successor — no main-manifest swap

The production Deployer pull of main after PR #250 completed with a successful guarded deployment job (GitHub Actions run `44276892074`, 18 Nov 2026). The live health endpoint still reports the older distinct schema-v8 recovery cohort; its status is **not** evidence that the 59891 affiliate block was published. An authenticated `wp-admin` view could not be opened in this execution session: it redirected to WordPress login. WPVibe source-file inspection also cannot inspect the plugin (installed WPVibe enforces a 64-KiB file limit), and its rolling-call budget is nearly exhausted. Do not equate these restrictions with authorization to bypass the admin gate.

To make this exact release operable without replacing the unrelated active `manifest.json`, the next version of the existing Git Publisher includes a separately committed, immutable `fishing-stays-cz-59891-release-v9.json` candidate. It is validated by the **same** schema-v9 allowlist and its source/HTML SHA guards. The management screen (Nástroje → Na-Ryby Git Publisher) displays the independent 59891 preflight and offers a dedicated one-post Apply only if:

1. An authenticated WordPress administrator has the existing `manage_options` capability and a valid per-action nonce.
2. The main auto-publisher has been **normally disabled by an administrator** via its existing Tools screen, and the existing atomic writer lock is unoccupied. The new Apply takes **that same lock**, never a parallel writer.
3. The exact source post remains unchanged, the candidate still matches all static allowlists, and a private WordPress-local backup can be verified before the first write.

The candidate is a review-and-apply payload, **not a deploy-triggered auto-publication**. On a successful local receipt, independently read the public 59891 page and confirm the exact block, merchant destinations, internal links and accessibility; capture genuine user interactions through the shared Prometheus contract and keep order/approved/paid commission state unknown until network evidence is available. The new GET-only `/poradce-vybavy/?nry_fishing_stays_release_proof=1` returns only bounded readiness and marker counts, no content, credentials, clicks or WordPress writes. A failed or drifted source is a hard stop; never regenerate an SHA solely to force publication.

First live Admin Apply attempt returned `v9_kses_drift_no_writes`: WordPress performed **zero post-content writes**, and the source-bound candidate remains staged. Before changing the writer, the existing read-only proof adds three boolean diagnostics (`source_preserved`, `insert_preserved`, `candidate_preserved`) under `kses_diagnostic`. This only reveals whether WordPress's native KSES would alter the immutable existing source, the vetted insertion, or the assembled candidate; it does not disclose any source HTML or relax global WP content filters. Use its live result to select a narrowly justified correction, then re-test rollback and public readback.

**Live KSES diagnosis 18 Nov 2026:** `source_preserved=false`, `insert_preserved=true`, `candidate_preserved=false`, `preflight_status=ready`, `marker_count=0`, `wordpress_writes=0`. The new vetted affiliate section is KSES-safe; only legacy article markup prevents a byte-preserving ordinary WordPress update. The narrowly scoped correction now requires a *freshly validated exact schema-v9 allowlist even inside direct `execute()`*, the immutable `insert_html` to remain byte-identical under native `wp_kses_post`, and the candidate to be **exactly** `insert_html + newline + SHA-pinned unmodified source`. The single already-existing trusted writer (used for v5–v8) then temporarily detaches only the named legacy KSES post-save filters *during this one WordPress-local write or rollback*, restores every previous filter in `finally`, and performs the original private snapshot + per-post SHA readback. This is not a sitewide KSES disable, cannot run from cron schema v9, and still requires normal WordPress Admin capability, nonce, normally disabled main auto writer and the shared atomic lock. New offline tests cover direct-invalid v9 invocation and scoped filter restoration, including write exceptions. The public proof now reports `v9_safe_legacy_writer_ready` to distinguish this exact code from the earlier blocked build. Do not claim publication until the owner-authorized Apply receipt and independent public render verify it.

An eligible production batch additionally needs: fresh authenticated post raw source SHA/modified GMT and safe snapshot, exact post/slug guards, one-writer check, WP Admin gate if content is mutated, successful authorized Deployer if plugin code changes, independent authenticated/public HTML readback, and exact rollback path. CI/Git merge alone does **not** prove publication.

## Core handoff contract (no new Na-ryby engine)

Minimum adapter payload for the Core owner to reconcile with **existing** Product/Offer Intelligence and Economic Evidence:

1. `market/site`, `water_identity + fishery_type + evidence`, `stay_offer_id + merchant + destination + last_checked`, `fishing_rights + family_conditions`, `geographic_match_confidence`, `availability_state`.
2. `affiliate_program_status`, `publisher_identity_proof`, `generated_deeplink`, `offer_freshness` and **fail-closed** publishability. A merchant detail page is not entitlement evidence.
3. Source-page and placement/release IDs for permitted consent-aware real outbound events. Join to real Slevomat/AffilBox order, approved and paid commission events when available; **never** infer attributable revenue from clicks alone.
4. Predeclared first controlled test: verify a live offer block on post 59891, track real outbound clicks by existing Core measurement contract, establish Slevomat order feedback and then test reuse on a second source page/revír. The current baseline (clicks, orders, commission) is **unknown**.

This audit is bounded, offline and reversible; it changes no public URLs or live affiliate traffic. Site Owner can work with the Core owner in parallel without assigning duplicate shared-engine development to this repo.
