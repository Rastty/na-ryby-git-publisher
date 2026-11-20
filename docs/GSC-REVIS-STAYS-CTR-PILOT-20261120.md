# Na-Ryby: GSC-backed snippet experiment, 20 Nov 2026

## Evidence / opportunity

GSC property `https://na-ryby.eu/`, 90 completed days 2026-08-17 through
2026-11-14, source [Prometheus Core #2029](https://github.com/Rastty/prometheus-growth-os/issues/2029).
The 28-day site baseline from [#2027](https://github.com/Rastty/prometheus-growth-os/issues/2027)
was 813 clicks / 17,583 impressions / 4.62% CTR versus 777 / 14,739 / 5.27% prior.
These are **GSC search clicks**, not sessions or commission.

| Post | Page | Clicks | Impressions | CTR | Avg position |
| ---: | --- | ---: | ---: | ---: | ---: |
| 14517 | Věstonická nádrž / Dyje 6 | 0 | 374 | 0% | 7.7 |
| 17591 | Chata Brněnská přehrada | 0 | 134 | 0% | 6.2 |
| 12166 | Rybářská dovolená jižní Morava | 0 | 83 | 0% | 7.5 |

Together 591 observed impressions / 0 clicks in a 90d historical page view.
This is a **priority heuristic**, not an expected uplift or a controlled causal baseline.

## Experiment

The existing WordPress Git Publisher loads `gsc-revir-stays-seo-20261120-v1.php`.
Its two `wpseo_*` output filters change the rendered Yoast title/description
**only** if all of the following still hold:

1. Exact published post ID, slug, canonical URL, host, and type match.
2. Existing authenticated raw post content SHA-256 and modified GMT match
   already source-locked modules in the repository.
3. Current output title/description exactly equals the anonymous public
   baseline captured on Nov 20. If another editor changes the snippet,
   the site passes their copy through unchanged.
4. Not an admin or preview request.

No post-content, stored Yoast meta, canonical, affiliate links, tracking,
cookies, outbound requests, WordPress write API or redirects are touched.
One `require_once` removal reverts the experiment instantly upon a later
authorized plugin release; original stored SEO fields are left intact.

## Publication and measurement gates

- Git merge **is not publication**. Until owner-controlled FTPS release is
  separately armed, the live public baseline remains unchanged.
- After authorized, successful plugin deployment, GET each exact URL and check
  title and meta-description strings once, original canonical and post body,
  plus source still matching; any mismatch is **not published**.
- Preserve last stable plugin archive and roll back if a live snippet is
  wrong or any unrelated page changes.
- Wait for Google to recrawl. Then compare **same three canonical pages** in
  GSC over 28-day post-release versus a 28-day pre-release window; record
  clicks, impressions, CTR, average position, query/device mix, and days since
  recrawl. Segment by page, do not assume a CTR change is caused by title
  alone. Keep revenue/conversions `UNKNOWN` without separately observed data.
- No paid advertising, synthetic clicks, third-party affiliate probes,
  indexing spam, or additional scheduled public checks in this change.
