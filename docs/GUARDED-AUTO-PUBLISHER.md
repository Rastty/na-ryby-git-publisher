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
