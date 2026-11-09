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
        add_action('init', array(__CLASS__, 'owner_bootstrap'),9);
        add_action('init', array(__CLASS__, 'schedule'));
        add_action(self::EVENT, array(__CLASS__, 'tick'));
        // Deploy is followed by a public GET; run a new bounded manifest immediately.
        add_action('wp_loaded', array(__CLASS__, 'recover_price_manifest_after_update'), 29);
        add_action('wp_loaded', array(__CLASS__, 'recover_carp_3m_manifest_after_update'), 29);
        add_action('wp_loaded', array(__CLASS__, 'tick_on_new_manifest'), 30);
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_nry_gp_auto_toggle', array(__CLASS__, 'toggle'));
    }


    // Owner directive 2026-11-02: enable guarded Git publishing without manual toggle.
    // The already-present historical manifest becomes a baseline, never replayed.
    // A subsequent administrator disable remains authoritative.
    public static function owner_bootstrap() {
        $flag='nry_gp_owner_bootstrap_20261102';
        if (get_option($flag,false)!==false || rtrim(home_url('/'),'/')!=='https://na-ryby.eu') return;
        $manifest=NRY_Git_Publisher::manifest();
        if (is_wp_error($manifest) || ($manifest['schema_version']??null)!==7
            || ($manifest['batch_id']??'')!=='chair-selection-next-four-20261025-v1'
            || count($manifest['edits']??array())!==4) return;
        $previous=get_option(self::LAST_BATCH,'');
        if ($previous!=='' && $previous!==$manifest['batch_id']) return;
        $digest=hash('sha256',wp_json_encode($manifest));
        if ($previous==='' && !add_option(self::LAST_BATCH,$manifest['batch_id'],'',false)) return;
        if (get_option(self::LAST_MANIFEST_SHA,'')==='') {
            if (!add_option(self::LAST_MANIFEST_SHA,$digest,'',false)) return;
        } elseif (get_option(self::LAST_MANIFEST_SHA,'')!==$digest) return;
        update_option(self::ENABLED,'yes',false);
        if (!add_option($flag,'yes','',false)) return;
        self::schedule();
        self::receipt('owner_enabled_existing_batch_skipped',array('baseline'=>$manifest['batch_id']));
    }


    /**
     * Narrow, one-time owner-authorized recovery of an atomic plugin update
     * where the OLD V8 parser saw the NEW five-price manifest mid-deployment
     * and stopped with invalid_manifest_on_deploy. It must not override any
     * administrator disable, any other safety stop, or an active/stale lock.
     *
     * Recovery performs NO content writes itself. The existing tick() runs
     * afterward and rechecks source SHA, all five safe spans, unique backup,
     * atomic lock, exact candidate and WordPress-local readback. The one-shot
     * option is deliberately never deleted, even after a failed tick.
     */
    public static function recover_price_manifest_after_update() {
        if (rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
            || get_option(self::ENABLED,'no')==='yes'
            || get_option('nry_gp_owner_bootstrap_20261102',false)!=='yes'
            || get_option('nry_gp_owner_price_recovery_20261107',false)!==false
            || get_option(self::LOCK,false)!==false
            || get_option(self::LAST_BATCH,'')!=='pipaky-pokrocile-rozhodnuti-36702-20261107-v1'
            || !is_string(get_option(self::LAST_MANIFEST_SHA,''))
            || !preg_match('/^[a-f0-9]{64}$/D',get_option(self::LAST_MANIFEST_SHA,''))) return;
        $prior=get_option(self::RECEIPT,array());
        if (!is_array($prior)
            || ($prior['status']??'')!=='stopped'
            || ($prior['reason']??'')!=='invalid_manifest_on_deploy') return;
        $manifest=NRY_Git_Publisher::manifest();
        if (is_wp_error($manifest) || ($manifest['schema_version']??null)!==8
            || ($manifest['batch_id']??'')!=='pipaky-pokrocile-five-price-fix-36702-20261107-v1'
            || count($manifest['edits']??array())!==1) return;
        $e=$manifest['edits'][0];
        if (($e['post_id']??null)!==36702
            || ($e['placement']??'')!=='price_corrections_only'
            || ($e['source_sha256']??'')!=='b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735'
            || ($e['source_modified_gmt']??'')!=='2026-11-07 11:23:04'
            || ($e['source_min_bytes']??0)!==12938
            || ($e['source_max_bytes']??0)!==12939) return;
        $rows=NRY_Git_Publisher::plan($manifest);
        if (is_wp_error($rows) || count($rows)!==1
            || ($rows[0]['id']??null)!==36702
            || ($rows[0]['status']??'')!=='ready'
            || ($rows[0]['source_sha']??'')!==$e['source_sha256']
            || !is_string($rows[0]['candidate_sha']??null)
            || !preg_match('/^[a-f0-9]{64}$/D',$rows[0]['candidate_sha'])) return;
        // Last millisecond recheck before claiming the exclusive recovery.
        if (get_option(self::ENABLED,'no')==='yes'
            || get_option(self::LOCK,false)!==false
            || get_option(self::RECEIPT,array())!==$prior) return;
        $receipt=array('batch'=>$manifest['batch_id'],
            'at'=>gmdate('c'),'reason'=>'owner_validated_single_price_batch');
        if (!add_option('nry_gp_owner_price_recovery_20261107',$receipt,'','no')) return;
        update_option(self::ENABLED,'yes',false);
        self::schedule();
        self::receipt('owner_recovered_validated_manifest',array(
            'batch_id'=>$manifest['batch_id'],'writes'=>0));
    }


    /**
     * Owner-approved one-shot recovery for the already reviewed 54272 carp-rod
     * factual correction. The previous manifest was applied, then the native
     * publisher stopped on invalid_manifest_on_deploy while the next Git
     * deployment was in progress. Do not recover admin disables or any other
     * stop. Never perform a write here; regular tick() keeps all safety gates.
     */
    public static function recover_carp_3m_manifest_after_update() {
        if (rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
            || get_option(self::ENABLED,'no')==='yes'
            || get_option('nry_gp_owner_bootstrap_20261102',false)!=='yes'
            || get_option('nry_gp_owner_carp_3m_recovery_20261109',false)!==false
            || get_option(self::LOCK,false)!==false
            || get_option(self::LAST_BATCH,'')!=='echolot-budget-118455-spec-bonus-fix-20261108-v1'
            || !is_string(get_option(self::LAST_MANIFEST_SHA,''))
            || !preg_match('/^[a-f0-9]{64}$/D',get_option(self::LAST_MANIFEST_SHA,''))) return;
        $prior=get_option(self::RECEIPT,array());
        if (!is_array($prior)
            || ($prior['status']??'')!=='stopped'
            || ($prior['reason']??'')!=='invalid_manifest_on_deploy') return;
        $manifest=NRY_Git_Publisher::manifest();
        if (is_wp_error($manifest) || ($manifest['schema_version']??null)!==8
            || ($manifest['batch_id']??'')!=='carp-rods-3m-54272-source-trust-20261109-v1'
            || count($manifest['edits']??array())!==1) return;
        $e=$manifest['edits'][0];
        if (($e['post_id']??null)!==54272
            || ($e['slug']??'')!=='kaprove-pruty-3m-recenze-nejlepsich-vyrobku-na-trhu'
            || ($e['edit_id']??'')!=='kaprove-pruty-3m-54272-20261109-v1'
            || ($e['placement']??'')!=='prepend'
            || ($e['source_sha256']??'')!=='73cd04df7abb7199f68397323a9139ae791f3f0a20bb79333da9d7d65df60751'
            || ($e['source_modified_gmt']??'')!=='2023-06-12 22:16:22'
            || ($e['source_min_bytes']??0)!==10207
            || ($e['source_max_bytes']??0)!==10208
            || count($e['text_corrections']??array())!==9) return;
        $rows=NRY_Git_Publisher::plan($manifest);
        if (is_wp_error($rows) || count($rows)!==1
            || ($rows[0]['id']??null)!==54272
            || ($rows[0]['status']??'')!=='ready'
            || ($rows[0]['source_sha']??'')!==$e['source_sha256']
            || !is_string($rows[0]['candidate_sha']??null)
            || !preg_match('/^[a-f0-9]{64}$/D',$rows[0]['candidate_sha'])) return;
        // Atomic single-use owner gate: a concurrent worker cannot claim twice.
        if (get_option(self::ENABLED,'no')==='yes'
            || get_option(self::LOCK,false)!==false
            || get_option(self::RECEIPT,array())!==$prior) return;
        if (!add_option('nry_gp_owner_carp_3m_recovery_20261109',
                array('batch'=>$manifest['batch_id'],'at'=>gmdate('c')),'','no')) return;
        update_option(self::ENABLED,'yes',false);
        self::schedule();
        self::receipt('owner_recovered_validated_manifest',array(
            'batch_id'=>$manifest['batch_id'],'writes'=>0));
    }

    public static function tick_on_new_manifest() {
        if (get_option(self::ENABLED,'no')!=='yes'
            || rtrim(home_url('/'),'/')!=='https://na-ryby.eu') return;
        // No stale batch replay; the tick itself performs all schema/identity/
        // source-SHA/backup/atomic-lock and readback gates before any write.
        $manifest=NRY_Git_Publisher::manifest();
        if (is_wp_error($manifest)) { self::stop('invalid_manifest_on_deploy'); return; }
        if (($manifest['batch_id']??'')===get_option(self::LAST_BATCH,'')) return;
        self::tick();
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
            self::receipt('applied_and_verified_local', array(
                'batch_id' => $batch, 'writes' => (int) ($result['writes'] ?? 0)
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
