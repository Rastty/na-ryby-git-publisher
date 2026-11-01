<?php
/**
 * Opt-in, bounded WordPress-local scheduled application of reviewed Git manifests.
 * No network endpoint or remote authentication surface. Disabled by default.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Guarded_Auto_Publisher {
    const ENABLED = 'nry_gp_auto_enabled_v1';
    const RECEIPT = 'nry_gp_auto_receipt_v1';
    const LAST_BATCH = 'nry_gp_auto_last_batch_v1';
    const LOCK = 'nry_gp_auto_lock_v1';
    const LAST_MANIFEST_SHA = 'nry_gp_auto_last_manifest_sha_v1';
    const EVENT = 'nry_gp_auto_tick_v1';

    public static function boot() {
        add_action('init', array(__CLASS__, 'schedule'));
        add_action(self::EVENT, array(__CLASS__, 'tick'));
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_nry_gp_auto_toggle', array(__CLASS__, 'toggle'));
    }

    public static function schedule() {
        if (get_option(self::ENABLED, 'no') === 'yes') {
            if (!wp_next_scheduled(self::EVENT)) {
                wp_schedule_event(time() + 300, 'hourly', self::EVENT);
            }
        } else {
            wp_clear_scheduled_hook(self::EVENT);
        }
    }

    private static function stop($reason) {
        update_option(self::ENABLED, 'no', false);
        wp_clear_scheduled_hook(self::EVENT);
        self::receipt('stopped', array('reason' => $reason));
    }

    private static function receipt($status, $extra = array()) {
        update_option(self::RECEIPT, array_merge(array(
            'status' => $status, 'at' => gmdate('c')
        ), $extra), false);
    }

    public static function tick() {
        if (get_option(self::ENABLED, 'no') !== 'yes') { return; }
        // A transient get+set pair is not an atomic lock. The WP options
        // table has a unique option_name, so add_option allows only one writer.
        $token = wp_generate_uuid4();
        $lock = array('token' => $token, 'expires' => time() + 10 * MINUTE_IN_SECONDS);
        if (!add_option(self::LOCK, $lock, '', 'no')) {
            $existing = get_option(self::LOCK, null);
            if (!is_array($existing) || !isset($existing['expires'])
                || (int) $existing['expires'] < time()) {
                // Do not delete/steal a stale lock: its former worker may still
                // exist. An administrator must investigate and reset it.
                self::stop('stale_or_invalid_lock_manual_reset');
            }
            return;
        }
        try {
            $manifest = NRY_Git_Publisher::manifest();
            if (is_wp_error($manifest)) { self::stop('invalid_manifest'); return; }
            // Restrict auto apply to existing bounded, source-protected contracts.
            if (!in_array($manifest['schema_version'] ?? null, array(5, 6, 7, 8), true)
                || count($manifest['edits']) < 1 || count($manifest['edits']) > 4) {
                self::stop('schema_or_batch_limit'); return;
            }
            $batch = $manifest['batch_id'];
            // Detect changes to a previously processed immutable batch ID.
            // A reused ID with different content must never be silently skipped.
            $digest = hash('sha256', wp_json_encode($manifest));
            $previous = get_option(self::LAST_BATCH, '');
            if ($previous === $batch) {
                if (get_option(self::LAST_MANIFEST_SHA, '') !== $digest) {
                    self::stop('previous_batch_manifest_drift');
                }
                return;
            }
            $rows = NRY_Git_Publisher::plan($manifest);
            if (is_wp_error($rows)) { self::stop('preflight_error'); return; }
            foreach ($rows as $row) {
                if (!in_array($row['status'], array('ready', 'already_applied'), true)) {
                    self::stop('preflight_blocked'); return;
                }
            }
            // Existing WordPress-local execute() performs per-post source checks,
            // non-public backups, SHA readback and bounded rollback.
            // The administrator may have disabled the publisher mid-preflight.
            if (get_option(self::ENABLED, 'no') !== 'yes') { return; }
            $result = NRY_Git_Publisher::execute($manifest);
            if (is_wp_error($result) || !is_array($result)
                || ($result['status'] ?? '') !== 'applied_and_verified') {
                self::stop(is_array($result) ? ($result['status'] ?? 'failed') : 'execution_error');
                return;
            }
            update_option(self::LAST_MANIFEST_SHA, $digest, false);
            update_option(self::LAST_BATCH, $batch, false);
            $writes = (int) ($result['writes'] ?? 0);
            // An idempotent replay with zero writes is not a newly published batch.
            // Local verification also never implies independent public LIVE readback.
            self::receipt($writes > 0 ? 'applied_and_verified_local' : 'already_applied_no_new_writes', array(
                'batch_id' => $batch, 'writes' => $writes
            ));
            // Local WP readback is not a substitute for independent public verification.
        } catch (\Throwable $e) {
            // A PHP exception cannot be treated as a verified rollback.
            // Pause for manual examination before any further batch.
            self::stop('unexpected_exception_manual_review');
        } finally {
            $held = get_option(self::LOCK, null);
            if (is_array($held) && ($held['token'] ?? null) === $token) {
                delete_option(self::LOCK);
            }
        }
    }

    public static function menu() {
        add_management_page('Na-Ryby Auto Publisher', 'Na-Ryby Auto Publisher',
            'manage_options', 'nry-gp-auto', array(__CLASS__, 'page'));
    }

    public static function page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $enabled = get_option(self::ENABLED, 'no') === 'yes';
        $last = get_option(self::RECEIPT, array());
        echo '<div class="wrap"><h1>Na-Ryby Auto Publisher</h1>';
        echo '<p>Automatické aplikování pouze pro již schválené Git manifesty schématu 5–8 (max. 4 články). Záloha, kontrola zdroje a SHA readback probíhají uvnitř WordPressu. Při chybě se automatika zastaví. WP-Cron závisí na provozu webu. Veřejné ověření je samostatný krok.</p>';
        echo '<p>Stav: <strong>' . esc_html($enabled ? 'ZAPNUTO' : 'VYPNUTO') . '</strong></p>';
        echo '<p>Poslední dávka: ' . esc_html((string) get_option(self::LAST_BATCH, 'žádná')) . '</p>';
        if (get_option(self::LOCK, false)) {
            echo '<p><strong>Pozor:</strong> Běh právě probíhá, nebo zůstal zámek. Při zastavení automatiky kvůli starému zámku nejprve ověřte, že nic neběží; reset vyžaduje správce serveru.</p>';
        }
        echo '<p>Poslední výsledek: ' . esc_html(wp_json_encode($last)) . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('nry_gp_auto_toggle');
        echo '<input type="hidden" name="action" value="nry_gp_auto_toggle">';
        echo '<input type="hidden" name="enable" value="' . ($enabled ? '0' : '1') . '">';
        submit_button($enabled ? 'Vypnout automatiku' : 'Zapnout automatiku (po kontrole release)');
        echo '</form></div>';
    }

    public static function toggle() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_gp_auto_toggle');
        $enabled = isset($_POST['enable']) && $_POST['enable'] === '1';
        update_option(self::ENABLED, $enabled ? 'yes' : 'no', false);
        self::schedule();
        self::receipt($enabled ? 'enabled_by_admin' : 'disabled_by_admin');
        wp_safe_redirect(admin_url('tools.php?page=nry-gp-auto'));
        exit;
    }
}
NRY_Guarded_Auto_Publisher::boot();
