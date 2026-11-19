# Na-Ryby: optional WEDOS FTPS code release (not yet production-enabled)

Status: **STAGED / NOT DEPLOYED**. This is an alternative code transport within
the existing guarded GitHub Actions workflow, not a WordPress content-write API.

## One-time owner/hosting setup

1. In WEDOS Webhosting FTP accounts, create a **separate restricted FTP account**
   rooted at the WordPress site's **`wp-content`** directory. Do not use the
   hosting-wide main FTP account. Confirm the login sees the existing
   `plugins/na-ryby-git-publisher/na-ryby-git-publisher.php` under its FTP root.
   Only this exact existing plugin is an allowed deployment target.
2. Store its actual assigned WEDOS hostname, username and password as GitHub
   **environment secrets** on `na-ryby-production`:
   `NRY_FTPS_HOST`, `NRY_FTPS_USER`, `NRY_FTPS_PASSWORD`.
   No credentials in commits, issues, workflow logs or chat.
3. In the same GitHub environment, set `NRY_FTPS_WP_CONTENT` to `/` if the FTP
   account is rooted at `wp-content`. An existing full-path FTP account can use
   `/www/wp-content` or `/www/domains/na-ryby.eu/wp-content` only after exact
   manual path confirmation. The script rejects any other path.
4. **Before** merging this transport, set `NRY_AUTO_DEPLOY_ENABLED=false` to
   avoid the legacy HTTP 401 Deployer being run on the setup merge. Merge only
   after review, keep existing protected checks, and set
   `NRY_DEPLOY_TRANSPORT=ftps` before enabling deployment again.
5. Run **one** reviewed, main-only controlled release; require authenticated
   WordPress-local receipt and independent exact public readback before
   treating it as live. Existing higher-risk content/admin gates still apply.

## What the FTPS alternative does

- Requires exact GitHub `main` commit SHA, all root PHP dependencies, the
  pinned manifest and integrity file; no wildcard remote upload.
- Transfers only root PHP/JSON/HTML, `assets/` and `editorial/` tracked files.
  CI files, tests, git metadata and unrelated host files are not transferred.
- Connects to a `.wedos.net` FTP host with explicit TLS on port 21 and verified
  server certificate, with encrypted data transfers; no HTTP route or Wordfence
  exception is needed.
- Creates or verifies a `wp-content/.nry-ftps-vault/.htaccess` access-deny rule
  **before** placing code there; it rejects a vault with a changed/missing deny
  rule. Staging and backup both live underneath this protected parent, outside
  `plugins/`. It retrieves every staged file to verify SHA-256, then renames
  the old plugin into the protected backup and swaps the staged directory into
  the exact installed plugin path. No unrelated plugin is modified. Before
  staging anything, a temporary random `.txt` file must return **HTTP 403 or
  404** to an independent anonymous HTTPS GET; HTTP 200, a redirect to public
  content, an upstream HTTP 401, or an unavailable site blocks deployment.
- If the second rename fails while the FTP session is live, immediately moves
  the prior directory back. On ambiguous disconnections, **stop**; inspect the
  exact remote paths and restore the backup under controlled operator review.
- Leaves the previous code directory for recovery. It does not touch the
  WordPress database. Database rollback is owned by the separate existing
  source-locked WordPress publisher with private content backups/receipts.
- Retains the existing production GitHub Environment, single-writer concurrency,
  source pins, guarded publication checks, and independent public readbacks.
  A code upload alone is **never** counted as content PUBLIC_VERIFIED.

## Failure and rollback boundaries

- Missing secret, unknown host/path, wrong branch/SHA, absent plugin identity,
  transport failure or digest drift: **no content publish claimed**.
- Staging failure leaves existing installed plugin untouched; an orphaned
  stage must be reviewed/removed separately before retrying the same SHA.
- Failed second rename restores the previous plugin if the FTP session is
  healthy. If remote outcome is ambiguous, preserve the backup and stop;
  never guess the live state or delete backups automatically.
- If the subsequent public readback fails after code activation, do not blindly
  revert code in isolation: the WordPress-local guarded publisher may already
  have changed source-pinned post content. Inspect its private backup/receipt
  and recover code + content together if needed.
- **Controlled code rollback (after receipt review):** stop the production
  writer, confirm the exact failed release SHA and vault deny rule, and check
  whether WordPress changed any DB content. When code-only restoration is
  coherent with that receipt, use the same scoped FTPS account to rename
  `plugins/na-ryby-git-publisher` to
  `.nry-ftps-vault/.nry-ftps-failed-<SHA12>` (must not already exist), then
  `.nry-ftps-vault/.nry-ftps-backup-<SHA12>` to
  `plugins/na-ryby-git-publisher`. Keep the failed code in the protected vault,
  and independently confirm the site and plugin state. If any step is
  ambiguous, stop rather than overwriting either copy. If content was changed,
  first follow the private WordPress receipt and its coordinated rollback.
- Keep Wordfence enabled. A failure of anonymous public readback may still be
  caused by upstream HTTP 401; report `PUBLIC_NOT_VERIFIED`, not success.

## Cost and deployment policy

No Deployer for Git, WPVibe, new paid service or recurring WordPress approval
is needed for pre-authorized routine batches. The initial scoped FTP credential
and GitHub secret setup is a one-time owner action. Protected cohorts, missing
advertiser entitlement, destructive SEO changes or administrator-only actions
are **not** silently approved or bypassed by a file transport.
