# South Moravia regional fishing-stay insertion — post 12166

Status: **SOURCE-PINNED OFFLINE CANDIDATE; NOT DEPLOYED**. Fresh
authenticated WP read-only REST `context=edit` on 18 Nov 2026 confirmed post
12166, `publish`, canonical slug and post-modified GMT
`2017-06-28 21:26:22`. `post_content` is exactly **1,749 UTF-8 bytes** with
SHA-256 `2bdb8599825facdb6bd90393136074ea5af40efe3b265e58ab75da99e0ff9587`;
the current WordPress `content.rendered` contains the original shortcode-
generated holiday catalogue and a single wrong-region Agoda CTA.
Both read-only fixtures are archived in byte-exact Base64 for offline tests.
The page must still pass a guarded deploy and independent public proof
before any live publication claim. A merged PR alone is never publication.

## Why this article, with limitations

Existing [Rybářská dovolená jižní Morava](https://na-ryby.eu/rybarska-dovolena-jizni-morava/)
is a broad holiday-intent archive, first published 2016, containing older
2017–18 entries for Nové Mlýny, Vranov, Brněnská přehrada and other locations.
Live public readback on 18 Nov 2026 still presented the historical material
as if terms, prices and availability could be found there. An additional
**concrete trust defect** was exposed by the authenticated source: the
South Moravia article contains a templated Agoda box that recommends
**Olomouc**, points to `www.agoda.com` through an old CJ redirect and
claims a guaranteed lowest price without source evidence. The exact-source
render-only repair replaces only this disqualified box and preserves every
other original link and the shortcode-generated historic catalogue. A callout
before the article's opening paragraph will distinguish three reviewed
current *merchant listing pages* from unverified older inventory and
immediately show the legally scoped revír route. This is a justified editorial candidate, **not** a
GSC-proven economic winner: the available Windsor GSC connector currently
exposes Klix.cz only. Do not invent query positions or booking estimates.

## Distinct locality and provenance

This block is explicitly **Nové Mlýny/Pasohlávky/Dolní Věstonice**, not a
promise of three stays equally close to Vranov, Brno or all southern Moravia.
The permitted Dyje 6 fishing section is governed by
[Rybníkářství Pohořelice](https://www.rybnikarstvipohorelice.cz/dyje-6/),
with a separate local permit and restricted southern-embankment access;
the reader follows the already [PUBLIC_VERIFIED Dyje 6 guide](https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/)
before buying the accommodation. No accommodation implies rights to fish,
access to nature reserves, a reserved room or a price guarantee.

| Source listing | Independent regional match | Affiliate evidence |
| --- | --- | --- |
| [Hotel Termal Mušov / Slevomat #2464086](https://www.slevomat.cz/akce/2464086-dovolena-na-palave-polopenze-i-vstup-do-wellness) | Pasohlávky, wellness/half-board; not a fishing permit | Owner `j3d7tsgc` and newly given campaign domain `na-ryby.eu`; new `utm_content=stay_12166_termal_musov`, previously owner-supplied `a_box` retained. No order or conversion proof |
| [Penzion Pasohlávky / Hportal](https://hportal.cz/cs/pasohlavky/penzion-pasohlavky/) | Pasohlávky 206, Hportal lists fishing among activities, not permit included | Approved Dognet `chid=5QoXiSqx`, percent-encoded property URL; no network redirect checked |
| [Chalupa Dolní Věstonice / Hauzi](https://www.hauzi.cz/chalupa-dolni-vestonice) | Dolní Věstonice, three bedrooms and up to eleven guests, not verified availability | Approved AffiliatePort `o=1990&a=1736`, `sub_id5` percent-encoded property URL, 30-day cookie, €2.40 *completed* reservation. No conversion proof |

Facts checked on official/merchant primary pages on 18 Nov 2026.

## Site scope, Core reuse and release gate

- `jizni-morava-stays-12166-v1.php` uses the same render-only, exact post
  ID + slug + canonical + GMT + raw source SHA filter at `PHP_INT_MAX` as
  the already proven Dyje 6 implementation; no WP stored post mutation,
  generic engine, redirects, cookies or owner impersonation.
- Three new CTAs must be exactly scoped to `data-nry-jm-stays="v1"`, retain
  the original links, and use the existing `nry_buyer_offer_click` path
  `buyer_page=jizni_morava_12166`, `source_post_id=12166`,
  `placement_id=jizni-morava-stays-12166-v1`, three exact offer IDs.
- Browser JS v1.11.2 is a cache-busting revision of the existing bundle;
  genuine `event.isTrusted`, existing consent/`window.gtag`, no PII or raw
  URLs. Site monitors for other pages must retain the live 1.11.1 preflight
  baseline and pin exact 1.11.2 bytes after deploy; no blind monitor disable.
- Source read (`context=edit`) and exact `content.rendered` fixture prove
  the unique historical anchors and wrong-region Agoda box. Source or
  rendered-box drift blocks the entire transformation, and a conservative
  original-href comparison permits only the single reviewed Agoda removal
  plus exact new merchant/revír destinations. CSS and the block itself do
  not activate unless raw post SHA, GMT, identity and canonical all match.
- Only reviewed guarded Git Publisher deploy and **independent anonymous**
  same-page GET proof are publication evidence; proof must check exact three
  outbound URLs, sponsor rel, preserved historic catalogue, removal of the
  **Olomouc Agoda box and lowest-price claim**, one archive notice,
  CSS and byte-identical JS. The monitor never follows affiliate links.
- Hand off verified field/URL/placement release to Prometheus Core issue
  #1975. Real consented clicks, accepted GA4 events, affiliate orders and
  approved/paid commissions remain **UNKNOWN** until data comes from Core
  and merchant networks; do not call a click or card display a sale.

Rollback: revert only the reviewed require/allowlist and guarded-deploy the
previous Git Publisher state. There is no WordPress stored-content revision
to undo. Recheck the public page after rollback.
