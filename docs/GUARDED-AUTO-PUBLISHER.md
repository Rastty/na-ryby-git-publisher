# Guarded automatic editorial publishing (candidate v0.10.0)

**Not active merely by merging or deploying.** The new Tools → Na-Ryby Auto Publisher page must be explicitly enabled by a WordPress administrator after the plugin deployment, smoke checks and release authorization.

- WordPress-local WP-Cron, hourly (traffic dependent). No public unauthenticated write endpoint, new token, or GitHub REST write to WordPress.
- Only already reviewed Git manifests using existing schemas **5, 6 or 7**, at most **4** edits per batch; all other schemas fail closed. No auto publishing of new pages, Yoast metadata or checklist.
- Only manifest rows with `ready` or `already_applied` are accepted.
- Existing `NRY_Git_Publisher::execute` enforces the precise source identity, guarded backups, SHA readback, and rollback.
- Batch ID deduplication, 10-minute lock, last receipt in non-autoloaded WP options.
- An unexpected schema, preflight error, or failed execute **turns the feature OFF**. Restoring it requires a WP administrator.
- Receipt `applied_and_verified_local` is **not** proof of public URL / cache parity. Independent public checks are required by the release workflow.
- Plugin updates and pull requests do **not** authorize switching this on automatically.
- Use a controlled canary with a fresh authorized batch, inspect WordPress revision and independent public HTML, then enable ongoing batches.
- The Fleet's own read-only dispatch remains unchanged. Changes must still be reviewed and promoted to the authorized Git mirror through the existing process.

Emergency stop: Tools → Na-Ryby Auto Publisher → Vypnout automatiku. If an applied post must be restored, use guarded saved source and WordPress revisions, never blindly force overwrite after concurrent edits.

## Additional protections (PR #48 hardening)

- The scheduler uses an **atomic database option lock**, not a non-atomic transient check/set. An abandoned/invalid lock permanently pauses automation until an administrator confirms the old worker is gone and resets the option.
- Completed batch ID **and canonical decoded manifest digest** are persisted. Reusing an already processed batch ID with different contents disables automation, rather than silently skipping unexpected content.
- An unexpected PHP exception stops subsequent automatic publication, preserves the reason and releases the lock owned by that worker. It does **not** claim that earlier writes were rolled back.
- Each successful local batch returns `applied_and_verified_local`. This must never be shown as external LIVE proof.
- The separate workflow `guarded-auto-public-readback.yml` performs **independent scheduled public HTTP checks** against the exact current manifest, writes an evidence artifact, and fails visibly if expected markers are missing. Missing public markers may mean cache lag, undeployed manifest or an actual publication fault; investigate rather than force a rewrite.
- The public HTTP monitor is a **reader**, not a deployment or approval mechanism.

## Known end-to-end blockers (verified 2026-11-01)

1. The owner's production screenshot shows active **v0.8.0**, whereas public repo main currently has **v0.9.0**. PR v0.10.0 is a candidate and is **not installed**. A controlled Deployer for Git update and public smoke check are mandatory.
2. The existing private Prometheus public mirror exporter `ops/build-na-ryby-public-deployer-release-v1.mjs` still accepts only schemas **1–4** and a fixed `Version: 0.4.0`; its sync helper assumes **exactly four files**. **It cannot promote new schema 5–7 editorial batches or this modern multi-file plugin without a separate reviewed migration.** Do not change it by simply bypassing integrity checks.
3. The WordPress plugin offers an **Update with Git** action. Automated push-to-deploy for new manifest revisions is **not verified**. Even after WP-Cron is enabled, no new batch can auto-publish until its manifest is safely delivered to WordPress.
4. The central Fleet is independently configured for **read-only dispatch**. This PR does not change Fleet production authorization or unilaterally grant publishing privileges.

**Production readiness requires proving each hop:** reviewed manifest in Git → authenticated Deployer Git update → current plugin/manifest readback → guarded WordPress-local apply → independent public HTTP proof → immutable release receipt. Do not merge/enable this PR as a shortcut around missing transport or release authorization.
