# Na-Ryby.eu — current project state

Updated: 2026-11-20 (Europe/Prague). This file records **verified state**, not a percentage-complete estimate or a claim of live revenue.

## TURBO checkpoint — 2026-11-20

1. **P0 release of existing merged growth changes:** clean `main` transport and guarded workflow passed offline checks, but a fresh FTPS production deployment is **not authorized/verified** while the owner-disarmed release gate remains unresolved. Public homepage still shows its old revír/stay section. No protected variables changed.
2. **P0 advisor revenue bottleneck:** on **2026-11-20** a real public-browser visitor scenario on `/poradce-vybavy/` returned valuable guidance and two internal buyer guides, but **zero individual product offers**: 14 October source evidence exceeds the engine's seven-day freshness limit. The exact expired-offer guard remains enabled. The live output also falsely described the two internal guides as direct e-shop product links. This revision stages a narrowly guarded, advisor-only wording correction and consent-path `nry_advisor_stale_result_view` event (only for genuine submits), plus a reviewed-guides next step. No claims of delivered events or live code rollout.
3. **P1 discovery and economic readback:** [PR #307](https://github.com/Rastty/na-ryby-git-publisher/pull/307) merged three first-party routes under `Revíry`; [PR #309](https://github.com/Rastty/na-ryby-git-publisher/pull/309) merged the independent GET-only `python3 scripts/verify-revir-menu-discovery-public.py --public` readback. Neither is yet proven deployed. After a real release, verify rendered menu/home/stay/advisor paths and compare real GSC/GA4 and consented affiliate outcomes. Windsor.ai currently only lists `https://klix.cz/` under connected Search Console accounts.

## Next three revenue-focused cycles (ordered by expected leverage)

1. **Advisor stale-offer conversion salvage — implemented in this revision, awaiting gated release:** on an actual result with the expired notice and 1–2 approved local guides, explain the route truthfully, promote the existing buyer-guide handoff and record an aggregate stale-exposure event. Reject editor drift, hidden results, extra/external links, untrusted or fake submits. Do **not** revive expired merchant cards or change CJ tracking.
2. **Restore real, verified advisor product recommendations:** acquire fresh, licensed merchant/SKU evidence for the 14-item cohort, confirm stock/price and the approved Na-Ryby CJ property and destinations, then run the candidate-bound 108-case browser safety suite and exact WordPress Admin Apply/independent readback. No usable current offer feed or release receipt was verified during this audit; merely changing the expiry date is prohibited.
3. **Release + measure prior SEO/navigation work:** after explicit bounded FTPS authorization, ship verified main without overwriting other workers, complete independent public readback of menu, article and advisor, then evaluate per-query GSC CTR and actual first-party guide/affiliate funnel events over sufficient time. Do not fabricate an uplift from staged work.

## Goal

Grow durable Czech fishing organic traffic and trustworthy paths from revír and accommodation advice to verified, attributable affiliate offers. Reuse compatible Prometheus Core capabilities, and close the loop **evidence → safe change → public verification → measured outcome**. Prioritize economic impact over feature count.

## Code versus production

- GitHub `main` reviewed at `0c0ffb0` on 2026-11-20 (includes #309); this revision stages plugin header **0.16.20** with the advisor script **1.3.0**. A merge or PR workflow is **not** proof of production deployment.
- First controlled WEDOS FTPS run [44699762724](https://github.com/Rastty/na-ryby-git-publisher/actions/runs/44699762724): code transport and swap occurred; job's final `deploy` status was **failure** because the historical V8 WordPress content acceptance was not satisfied. [PR #300](https://github.com/Rastty/na-ryby-git-publisher/pull/300) subsequently corrected the frozen-manifest acceptance condition, but was not itself another deployment.
- The owner explicitly confirmed the release switch `NRY_FTPS_RELEASE_ARMED=false` after that run. Its **current GitHub variable value was not independently readable** in this audit. Never infer that a later merge or manual workflow trigger silently authorizes production writes.
- Independent public homepage readback on 2026-11-20 still shows the old `Kam na ryby` and `Výprava na více dní` cards, **without** the new direct revír/stay links. The following code changes are therefore **staged, not verified live**.

## Recently merged, not publicly verified as deployed

| PR | Scope | Evidence / follow-up |
| --- | --- | --- |
| [#301](https://github.com/Rastty/na-ryby-git-publisher/pull/301) | Source-bound Yoast snippet CTR pilot for Dyje 6, Brno chata, South Moravia holidays | 4 PR CI checks passed; GSC observation needs a separately verified release |
| [#302](https://github.com/Rastty/na-ryby-git-publisher/pull/302) | Organic community distribution copy and tagged links | Prepared only; no posts made to external communities |
| [#303](https://github.com/Rastty/na-ryby-git-publisher/pull/303) | Homepage direct links to Dyje 6, South Moravia revíry and stay guide | Render-only, source-locked; 4 PR CI checks passed |
| [#304](https://github.com/Rastty/na-ryby-git-publisher/pull/304) | Top-level `Ubytování` menu bridge from 2017 archive to current stay offers guide | Frontend-only; original archive remains accessible; 4 PR CI checks passed |
| [#305](https://github.com/Rastty/na-ryby-git-publisher/pull/305) | Two reliable onward paths below old map-only page | Historical iframe preserved; 4 PR CI checks passed |
| [#307](https://github.com/Rastty/na-ryby-git-publisher/pull/307) | Three contextual links in `Revíry` main menu submenu | Original parent link preserved; 4 PR workflow checks passed |
| [#309](https://github.com/Rastty/na-ryby-git-publisher/pull/309) | Independent public menu discovery verifier | GET-only; rejects absent/duplicate/off-site nav; **readback will be meaningful only after deployment** |

## Verified release readiness

- A **clean checkout** of `main` at the SHA above passed `python3 tests/test-wedos-ftps-transport.py`: **20/20 tests**. This includes checkout payload bounds, required PHP modules, exact target, TLS, private vault and rollback behavior. A dirty local worktree lacking new files in the Git index is **not** valid evidence of a main-branch transport defect.
- Clean checked-out runtime selection for `afdfcf9`: **109 files**, **900250 bytes** (68 PHP), below the `MAX_FILES=220` and `MAX_BYTES=12000000` transport limits. This is an **offline** package test, not a remote FTPS preflight.
- The guarded deployment workflow requires `NRY_AUTO_DEPLOY_ENABLED=true` **and** `NRY_FTPS_RELEASE_ARMED=true`; merge alone cannot deploy while that gate remains disarmed.
- Other agents own 12 open PRs [#283–#294](https://github.com/Rastty/na-ryby-git-publisher/pulls); several are deliberately draft/disarmed or related to intermittent WordPress Deployer HTTP 401. Do not merge or rewrite their branches by assumption. [#288](https://github.com/Rastty/na-ryby-git-publisher/pull/288) addresses safe 401 classification in a separate lane.

## Growth measurement baseline

- [Prometheus Core GSC #2027](https://github.com/Rastty/prometheus-growth-os/issues/2027), property `https://na-ryby.eu/`, 28 complete days 2026-10-19..11-15: **813 search clicks / 17,583 impressions / 4.62% CTR**, versus **777 / 14,739 / 5.27%** in the preceding 28 days. Search clicks are not sessions or commissions.
- [GSC page audit #2029](https://github.com/Rastty/prometheus-growth-os/issues/2029), 90d through 2026-11-14: Dyje 6 **374 impressions / 0 clicks**, Brno chata **134 / 0**, South Moravia holiday **83 / 0**. These justify an experiment but do not establish uplift.
- Current revenue, approved/paid commissions, incremental traffic attributable to these staged PRs and any release A/B effect: **UNKNOWN**, not zero.

## Constraints and next priorities

1. **P0 — obtain explicit authorization for the next bounded FTPS production release.** Do not change protected variables without it. Before release, recheck current main SHA, no competing writer, live source, factual scope and rollback/vault; use the existing guarded transport. After release, independently verify public plugin markers, original content preservation and the relevant exact page/menu/advisor links. Roll back on failure and report precisely what was or was not published.
2. **P1 — close the advisor's verified-offer and measurement loop.** Restore real product recommendations only from current independently verified merchant evidence and candidate-bound QA, with no bypass of the seven-day guard. After actual deployment record the release SHA/time; measure genuine consented guide-click, stale-result, affiliate outbound events, and sufficient post-recrawl GSC CTR, not artificial merchant visits.
3. **P2 — unblock or route high-value existing draft work.** Coordinate with owners of open PR #283–#294 and the independent 401 incident rather than creating duplicate site-specific modules.
4. **P3 — select the next new content cluster from observed search demand and offer evidence.** Do not repeatedly rewrite the same fishing-stay articles or count unshipped commits as growth.
