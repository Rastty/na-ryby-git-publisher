# Na-Ryby.eu — current project state

Updated: 2026-11-20 (Europe/Prague). This file records **verified state**, not a percentage-complete estimate or a claim of live revenue.

## Production release — verified 2026-11-20

- [Guarded FTPS run #570](https://github.com/Rastty/na-ryby-git-publisher/actions/runs/44726358287) **completed successfully** for exactly `main` SHA `4080438457bdbf2bd6aa96c30d38d0b5afcb14e0`. `verify`, `ftps_inspect` and `deploy` all succeeded. The encrypted FTPS step reported `FTPS_CODE_SWAPPED` at **12:22:14 CET** after exact-byte staging; the prior installed plugin was retained in a private rollback backup.
- Independent public verification after the swap: `/poradce-vybavy/` loads the exact-release `nry-advisor-guide-click-v1.js?ver=1.3.0`; `scripts/verify-advisor-guide-click-public.py` passed **byte-for-byte SHA-256** against reviewed source. A real public-browser beginner scenario shows two internal buyer guides with truthful expired-offer wording, **no stale merchant product cards**. `scripts/verify-revir-menu-discovery-public.py --public` returned `PUBLIC_VERIFIED_ALL` for three navigation menus; `scripts/verify-home-118641-public.py` returned `PUBLIC_VERIFIED_ALL` for the homepage. These are live code and rendered-page checks, not evidence of traffic or commissions.
- The release's historical immutable V8 content acceptance was **deferred**: live receipt `last_auto_receipt_status=disabled_by_admin`; the deployment correctly skipped new V8 public-manifest acceptance and **did not claim a WordPress editorial write**. Do not mistake this for a content-batch publication.
- The owner was instructed to return `NRY_FTPS_RELEASE_ARMED=false` after #570; **independent confirmation of the GitHub variable value remains pending**. This revision proposes replacing repeated arming with a manually dispatched, exact-40-character-SHA-bound release. Until merged and tested in GitHub, this workflow improvement is **not operational**. Do not merge it while the old arm variable may be true.

## TURBO checkpoint — 2026-11-20

1. **P0 release completed:** previously merged growth code has now passed the one-shot guarded FTPS transport and independent public homepage, menu and advisor verification above. Specific WordPress content edits require separate authenticated receipts; frozen V8 was deferred.
2. **P0 advisor revenue bottleneck remains:** the source evidence for 14 product offers dates to 14 October and exceeds the seven-day freshness limit. The public advisor now truthfully routes visitors to two verified internal guides; the guard against stale product cards remains in force. No GA4 event receipt, current product feed, affiliate conversion or revenue uplift has been verified.
3. **P1 discovery and economic readback:** [PR #307](https://github.com/Rastty/na-ryby-git-publisher/pull/307) and [PR #309](https://github.com/Rastty/na-ryby-git-publisher/pull/309) are now independently verified in public. Measure actual post-indexing GSC/GA4 and consented affiliate outcomes before attributing impact. Windsor.ai currently only lists `https://klix.cz/` under connected Search Console accounts.

## Next three revenue-focused cycles (ordered by expected leverage)

1. **Restore real, verified advisor product recommendations:** acquire fresh, licensed merchant/SKU evidence for the 14-item cohort, confirm stock/price and approved Na-Ryby CJ destinations, then run candidate-bound browser safety checks and exact WordPress Admin Apply/independent readback. Merely changing the expiry date is prohibited.
2. **Measure the released advisor and discovery journeys:** collect genuine consented stale-result/guide-click/outbound events, independent GSC per-query CTR after recrawl, and actual approved commissions. No change in revenue or search position is yet established.
3. **One-action safe releases + next high-value revír/stay routes:** land the SHA-bound manual-release workflow only after the old switch is confirmed off. Preserve private backup, source binding, one writer and independent public readback. Avoid duplicate content/PR work.

## Goal

Grow durable Czech fishing organic traffic and trustworthy paths from revír and accommodation advice to verified, attributable affiliate offers. Reuse compatible Prometheus Core capabilities, and close the loop **evidence → safe change → public verification → measured outcome**. Prioritize economic impact over feature count.

## Code versus production

- GitHub `main` deployed from exact SHA `4080438` on 2026-11-20; plugin header **0.16.20** and advisor script **1.3.0** are included. The public advisor script is byte-verified live. A merge or PR workflow alone remains **not** proof of deployment.
- First controlled WEDOS FTPS run [44699762724](https://github.com/Rastty/na-ryby-git-publisher/actions/runs/44699762724): code transport and swap occurred; job's final `deploy` status was **failure** because the historical V8 WordPress content acceptance was not satisfied. [PR #300](https://github.com/Rastty/na-ryby-git-publisher/pull/300) subsequently corrected the frozen-manifest acceptance condition, but was not itself another deployment.
- The owner explicitly confirmed `NRY_FTPS_RELEASE_ARMED=false` after the earlier run. #570 was subsequently owner-authorized with this gate temporarily enabled. **The owner's post-#570 disarm has been requested, but not yet verified.** Do not infer permission for another production write.
- Independent public homepage and nav readbacks after #570 passed, including the release's new revír/stay discovery routes.

## Recently merged — source and release history

| PR | Scope | Evidence / follow-up |
| --- | --- | --- |
| [#301](https://github.com/Rastty/na-ryby-git-publisher/pull/301) | Source-bound Yoast snippet CTR pilot for Dyje 6, Brno chata, South Moravia holidays | 4 PR CI checks passed; GSC observation needs a separately verified release |
| [#302](https://github.com/Rastty/na-ryby-git-publisher/pull/302) | Organic community distribution copy and tagged links | Prepared only; no posts made to external communities |
| [#303](https://github.com/Rastty/na-ryby-git-publisher/pull/303) | Homepage direct links to Dyje 6, South Moravia revíry and stay guide | Source-locked; public homepage contract passed after #570 |
| [#304](https://github.com/Rastty/na-ryby-git-publisher/pull/304) | Top-level `Ubytování` menu bridge from 2017 archive to current stay offers guide | Frontend-only; specific archive CTA still needs separately scoped public check |
| [#305](https://github.com/Rastty/na-ryby-git-publisher/pull/305) | Two reliable onward paths below old map-only page | Historical iframe preservation still needs separately scoped public check |
| [#307](https://github.com/Rastty/na-ryby-git-publisher/pull/307) | Three contextual links in `Revíry` main menu submenu | Public GET-only verifier passed all three rendered menus after #570 |
| [#309](https://github.com/Rastty/na-ryby-git-publisher/pull/309) | Independent public menu discovery verifier | `PUBLIC_VERIFIED_ALL`; zero writes, synthetic clicks or merchant calls |

## Verified release readiness

- A **clean checkout** of `main` at the SHA above passed `python3 tests/test-wedos-ftps-transport.py`: **20/20 tests**. This includes checkout payload bounds, required PHP modules, exact target, TLS, private vault and rollback behavior. A dirty local worktree lacking new files in the Git index is **not** valid evidence of a main-branch transport defect.
- Clean checked-out runtime selection for `afdfcf9`: **109 files**, **900250 bytes** (68 PHP), below the `MAX_FILES=220` and `MAX_BYTES=12000000` transport limits. This is an **offline** package test, not a remote FTPS preflight.
- The historical #570 guarded workflow required `NRY_AUTO_DEPLOY_ENABLED=true` **and** `NRY_FTPS_RELEASE_ARMED=true`. The proposed successor in this revision requires a manual `workflow_dispatch` with an exact `release_sha == github.sha` on `main`, plus the static emergency kill switch; no push can trigger a production deployment. **This change is not active until merged.**
- Other agents own 12 open PRs [#283–#294](https://github.com/Rastty/na-ryby-git-publisher/pulls); several are deliberately draft/disarmed or related to intermittent WordPress Deployer HTTP 401. Do not merge or rewrite their branches by assumption. [#288](https://github.com/Rastty/na-ryby-git-publisher/pull/288) addresses safe 401 classification in a separate lane.

## Growth measurement baseline

- [Prometheus Core GSC #2027](https://github.com/Rastty/prometheus-growth-os/issues/2027), property `https://na-ryby.eu/`, 28 complete days 2026-10-19..11-15: **813 search clicks / 17,583 impressions / 4.62% CTR**, versus **777 / 14,739 / 5.27%** in the preceding 28 days. Search clicks are not sessions or commissions.
- [GSC page audit #2029](https://github.com/Rastty/prometheus-growth-os/issues/2029), 90d through 2026-11-14: Dyje 6 **374 impressions / 0 clicks**, Brno chata **134 / 0**, South Moravia holiday **83 / 0**. These justify an experiment but do not establish uplift.
- Current revenue, approved/paid commissions, incremental traffic attributable to these staged PRs and any release A/B effect: **UNKNOWN**, not zero.

## Constraints and next priorities

1. **P0 — close the release gate and prevent repeat toggle work.** Confirm `NRY_FTPS_RELEASE_ARMED=false` after the successfully completed #570. Review, test and merge the proposed explicit SHA-pinned manual workflow; never merge while the old gate may be armed. Keep emergency kill switch, exact FTPS target, private rollback and independent readback.
2. **P1 — close the advisor's verified-offer and measurement loop.** Restore real product recommendations only from current independently verified merchant evidence and candidate-bound QA, with no bypass of the seven-day guard. After actual deployment record the release SHA/time; measure genuine consented guide-click, stale-result, affiliate outbound events, and sufficient post-recrawl GSC CTR, not artificial merchant visits.
3. **P2 — unblock or route high-value existing draft work.** Coordinate with owners of open PR #283–#294 and the independent 401 incident rather than creating duplicate site-specific modules.
4. **P3 — select the next new content cluster from observed search demand and offer evidence.** Do not repeatedly rewrite the same fishing-stay articles or count unshipped commits as growth.
