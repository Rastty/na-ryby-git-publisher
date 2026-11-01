<?php
/**
 * One exact existing-page release gate. Git deployment never edits WordPress.
 * Requires a candidate-bound 108-case receipt and explicit admin nonce/Apply.
 * Local WordPress readback is NOT independent public/browser verification.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Advisor_Existing_Page_Release_V1 {
    const ID = 119865;
    const SLUG = 'poradce-vybavy';
    const BACKUP = 'nry_advisor_existing_119865_backup_v1';
    const RECEIPT = 'nry_advisor_existing_119865_receipt_v1';
    const LOCK = 'nry_advisor_existing_119865_lock_v1';

    private static function valid_sha($value) {
        return is_string($value) && preg_match('/^[a-f0-9]{64}$/D', $value);
    }
    private static function urls($body) {
        // Preserve every literal external destination, including CJ tracking.
        preg_match_all("~https?://[^\\s\"'<>]+~i", $body, $matches);
        return $matches[0];
    }
    private static function cj_routes($body) {
        // Include relative /click-* routes which a generic external URL
        // extractor would miss. Never silently rewrite the CJ property.
        preg_match_all("~/click-[0-9]+-[0-9]+[^\\s\"'<>]*~i", $body, $matches);
        return $matches[0];
    }
    private static function unsafe_active_markup($body) {
        // Whitespace and case variants of script src bypass a literal
        // "<script src=" check. A relative src is unsafe too: it could
        // introduce unreviewed executable code without changing any URL list.
        $decoded = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return preg_match('~<script\\b[^>]*\\bsrc\\s*=~i', $decoded) === 1
            || preg_match('~<a\\b[^>]*\\bhref\\s*=\\s*(?:"\\s*|\\x27\\s*|)(?:javascript|data)\\s*:~i', $decoded) === 1;
    }
    private static function identity($post) {
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'page'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && is_string($post->post_content ?? null)
            && get_permalink(self::ID) === 'https://na-ryby.eu/poradce-vybavy/';
    }
    public static function inspect() {
        $blocked = function($reason) { return array('status'=>'blocked','reason'=>$reason); };
        if (rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') { return $blocked('site_origin'); }
        $file = __DIR__.'/advisor-existing-page-v1.json';
        $asset = __DIR__.'/assets/advisor-existing-page-v1.html';
        if (!is_file($file) || !is_readable($file) || !is_file($asset) || !is_readable($asset)) {
            return $blocked('reviewed_manifest_or_candidate_not_staged');
        }
        $manifest = json_decode(file_get_contents($file), true);
        if (!is_array($manifest) || ($manifest['schema'] ?? null) !== 1
            || ($manifest['page_id'] ?? null) !== self::ID
            || ($manifest['slug'] ?? null) !== self::SLUG
            || !self::valid_sha($manifest['source_sha256'] ?? null)
            || !self::valid_sha($manifest['candidate_sha256'] ?? null)
            || !is_string($manifest['source_modified_gmt'] ?? null)
            || !preg_match('/^20\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/D', $manifest['source_modified_gmt'])
            || ($manifest['approval'] ?? '') !== 'admin_apply_required') {
            return $blocked('manifest_invalid');
        }
        // A dated 108-case browser receipt must match the exact staged bytes.
        // Its GitHub URL is for independent review, not a live deployment claim.
        $safety = $manifest['offer_safety'] ?? null;
        $tested = is_array($safety) ? strtotime($safety['tested_at_utc'] ?? '') : false;
        if (!is_array($safety) || ($safety['candidate_sha256'] ?? '') !== $manifest['candidate_sha256']
            || ($safety['scenarios_tested'] ?? null) !== 108
            || ($safety['expired_offer_clickouts'] ?? null) !== 0
            || $tested === false || $tested > time() || time() - $tested > 72*3600
            || !is_string($safety['evidence_url'] ?? null)
            || !preg_match('~^https://github\.com/Rastty/prometheus-growth-os/actions/runs/[0-9]+$~D',
                $safety['evidence_url'])) {
            return $blocked('fresh_candidate_bound_108_case_receipt_required');
        }
        $bytes = filesize($asset);
        if ($bytes === false || $bytes < 10000 || $bytes > 120000) { return $blocked('candidate_size'); }
        $candidate = file_get_contents($asset);
        if (!is_string($candidate) || hash('sha256',$candidate) !== $manifest['candidate_sha256']
            || strpos($candidate,'nry-advisor-live-v1') === false
            || strpos($candidate,'data-nry-results') === false
            || strpos($candidate,'data-nry-affiliate-disclosure="advisor-v3"') === false
            || strpos($candidate,'<script') === false
            || self::unsafe_active_markup($candidate)) {
            return $blocked('candidate_hash_or_structure');
        }
        $post = get_post(self::ID);
        if (!self::identity($post)) { return $blocked('page_identity'); }
        $current_sha = hash('sha256',$post->post_content);
        $backup = get_option(self::BACKUP, false);
        if (is_array($backup) && ($backup['candidate_sha256'] ?? '') === $current_sha
            && ($backup['source_sha256'] ?? '') === $manifest['source_sha256']
            && hash('sha256',$backup['source_content'] ?? '') === $manifest['source_sha256']) {
            return array('status'=>'already_applied_pending_public_readback',
                'source_sha256'=>$manifest['source_sha256'], 'candidate_sha256'=>$current_sha);
        }
        if ($backup !== false) { return $blocked('immutable_backup_conflict'); }
        if ($current_sha !== $manifest['source_sha256']
            || $post->post_modified_gmt !== $manifest['source_modified_gmt']) {
            return $blocked('source_sha_or_modified_drift');
        }
        if (self::urls($post->post_content) !== self::urls($candidate)) {
            return $blocked('external_destination_drift');
        }
        $source_routes = self::cj_routes($post->post_content);
        if (!$source_routes || $source_routes !== self::cj_routes($candidate)
            || count(array_filter($source_routes, function($url) {
                return strpos($url, '/click-8287231-') !== 0;
            })) > 0) {
            return $blocked('cj_route_or_property_drift');
        }
        if (hash_equals($current_sha, $manifest['candidate_sha256'])) {
            return $blocked('source_equals_candidate_without_backup');
        }
        return array('status'=>'ready','source_sha256'=>$current_sha,
            'candidate_sha256'=>$manifest['candidate_sha256'],
            'modified_gmt'=>$post->post_modified_gmt,'candidate'=>$candidate);
    }
    private static function acquire() {
        // Atomic add_option blocks concurrent admin writes; stale lock fails closed.
        return add_option(self::LOCK, gmdate('c'), '', false);
    }
    public static function execute() {
        if (!current_user_can('manage_options') || !current_user_can('edit_post',self::ID)
            || !current_user_can('unfiltered_html')) {
            return array('status'=>'blocked','reason'=>'missing_unfiltered_editor_capability');
        }
        if (!self::acquire()) { return array('status'=>'blocked','reason'=>'writer_lock'); }
        try {
            $pre = self::inspect();
            if ($pre['status'] !== 'ready') { return array('status'=>'blocked','reason'=>$pre['reason'] ?? $pre['status']); }
            $post = get_post(self::ID);
            if (!self::identity($post) || hash('sha256',$post->post_content) !== $pre['source_sha256']
                || $post->post_modified_gmt !== $pre['modified_gmt']) {
                return array('status'=>'blocked','reason'=>'concurrent_source_drift');
            }
            $backup = array('page_id'=>self::ID,'source_sha256'=>$pre['source_sha256'],
                'candidate_sha256'=>$pre['candidate_sha256'], 'source_content'=>$post->post_content,
                'source_modified_gmt'=>$post->post_modified_gmt,'created_at_utc'=>gmdate('c'));
            if (!add_option(self::BACKUP,$backup,'',false)
                || get_option(self::BACKUP,false) !== $backup) {
                return array('status'=>'blocked','reason'=>'private_backup_not_persisted');
            }
            $fresh = get_post(self::ID);
            if (!self::identity($fresh) || hash('sha256',$fresh->post_content) !== $pre['source_sha256']
                || $fresh->post_modified_gmt !== $pre['modified_gmt']) {
                return array('status'=>'blocked','reason'=>'source_changed_after_backup');
            }
            $written = wp_update_post(array('ID'=>self::ID,'post_content'=>$pre['candidate']),true);
            $after = get_post(self::ID);
            if (is_wp_error($written) || (int)$written !== self::ID || !self::identity($after)
                || hash('sha256',$after->post_content) !== $pre['candidate_sha256']) {
                // Never overwrite a concurrent editor's different content.
                if (self::identity($after) && hash('sha256',$after->post_content) === $pre['candidate_sha256']) {
                    $restore = wp_update_post(array('ID'=>self::ID,'post_content'=>$backup['source_content']),true);
                    $check = get_post(self::ID);
                    return array('status'=>!is_wp_error($restore) && self::identity($check)
                        && hash('sha256',$check->post_content) === $pre['source_sha256']
                        ? 'rolled_back_local_verified' : 'rollback_uncertain', 'writes'=>1);
                }
                return array('status'=>'write_uncertain_manual_review','writes'=>1);
            }
            return array('status'=>'applied_pending_independent_public_readback',
                'page_id'=>self::ID,'source_sha256'=>$pre['source_sha256'],
                'candidate_sha256'=>$pre['candidate_sha256'],'writes'=>1,
                'at_utc'=>gmdate('c'));
        } finally { delete_option(self::LOCK); }
    }
    public static function rollback() {
        if (!current_user_can('manage_options') || !current_user_can('edit_post',self::ID)
            || !current_user_can('unfiltered_html')) {
            return array('status'=>'blocked','reason'=>'missing_unfiltered_editor_capability');
        }
        if (!self::acquire()) { return array('status'=>'blocked','reason'=>'writer_lock'); }
        try {
            $backup = get_option(self::BACKUP,false);
            $post = get_post(self::ID);
            if (!is_array($backup) || !self::identity($post)
                || !self::valid_sha($backup['source_sha256'] ?? null)
                || !self::valid_sha($backup['candidate_sha256'] ?? null)
                || hash('sha256',$backup['source_content'] ?? '') !== $backup['source_sha256']
                || hash('sha256',$post->post_content) !== $backup['candidate_sha256']) {
                return array('status'=>'blocked','reason'=>'backup_or_live_content_drift');
            }
            $res = wp_update_post(array('ID'=>self::ID,'post_content'=>$backup['source_content']),true);
            $after = get_post(self::ID);
            return array('status'=>!is_wp_error($res) && self::identity($after)
                && hash('sha256',$after->post_content) === $backup['source_sha256']
                ? 'rolled_back_local_verified' : 'rollback_uncertain', 'writes'=>1);
        } finally { delete_option(self::LOCK); }
    }
    public static function menu() {
        add_management_page('Na-Ryby Advisor Existing Page','Na-Ryby Advisor Release',
            'manage_options','nry-advisor-existing-release',array(__CLASS__,'page'));
    }
    public static function page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $state = self::inspect();
        $last = get_option(self::RECEIPT,array());
        echo '<div class="wrap"><h1>Na-Ryby /poradce-vybavy/ — bezpečná aktualizace #119865</h1>';
        echo '<p>Žádná automatická publikace. Pouze přesný zdroj a kandidát s SHA, neměnné zálohy a admin Apply. '
            .'Lokální ověření není produkční public/browser readback.</p>';
        echo '<p>Stav: <strong>'.esc_html($state['status']).'</strong></p>';
        if (isset($state['reason'])) { echo '<p>Blokace: '.esc_html($state['reason']).'</p>'; }
        if (isset($state['source_sha256'])) { echo '<p>Zdroj SHA: <code>'.esc_html($state['source_sha256']).'</code></p>'; }
        if (isset($state['candidate_sha256'])) { echo '<p>Kandidát SHA: <code>'.esc_html($state['candidate_sha256']).'</code></p>'; }
        if (isset($last['status'])) { echo '<p>Poslední akce: '.esc_html($last['status']).'</p>'; }
        if ($state['status'] === 'ready') { self::form('nry_advisor_existing_apply','Apply: aktualizovat pouze poradce #119865'); }
        // Keep rollback reachable even if a later Git deploy removes the
        // candidate asset/manifest. The rollback itself still requires an
        // exact candidate SHA match and will not overwrite concurrent edits.
        if (is_array(get_option(self::BACKUP,false))) {
            self::form('nry_advisor_existing_rollback','Rollback: obnovit přesnou zálohu poradce');
        }
        echo '</div>';
    }
    private static function form($action,$label) {
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
        echo '<input type="hidden" name="action" value="'.esc_attr($action).'">';
        wp_nonce_field($action); submit_button($label); echo '</form>';
    }
    public static function handle_apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_advisor_existing_apply');
        update_option(self::RECEIPT,self::execute(),false);
        wp_safe_redirect(admin_url('tools.php?page=nry-advisor-existing-release')); exit;
    }
    public static function handle_rollback() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_advisor_existing_rollback');
        update_option(self::RECEIPT,self::rollback(),false);
        wp_safe_redirect(admin_url('tools.php?page=nry-advisor-existing-release')); exit;
    }
}
add_action('admin_menu',array('NRY_Advisor_Existing_Page_Release_V1','menu'));
add_action('admin_post_nry_advisor_existing_apply',array('NRY_Advisor_Existing_Page_Release_V1','handle_apply'));
add_action('admin_post_nry_advisor_existing_rollback',array('NRY_Advisor_Existing_Page_Release_V1','handle_rollback'));
