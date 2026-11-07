<?php
/**
 * Exact post-publication source pinned ONE-FIELD Yoast description repair.
 * Historical post 36702: published four-step buyer decision already verified.
 * Does not change title, body, images, link destinations, price or canonical.
 * No REST mutation or unauthenticated public action; one guarded WP-load hook.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Advanced_Pipaky_Description_V1 {
    const ID = 36702;
    const SLUG = 'pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu';
    const SOURCE_SHA = 'b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735';
    const SOURCE_BYTES = 12938;
    const MODIFIED_GMT = '2026-11-07 11:23:04';
    const ORIGINAL_META_HASH = 'd76fb27cc3756e747d995bd3ac990a1eb9d3ce39214a1cd8bfc2d81124c79f0c';
    const ORIGINAL_META_BYTES = 226;
    const EMPTY_TITLE_HASH = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
    const NEW_META = 'Pípáky pro pokročilé: kdy se vyplatí příposlech, jak ověřit párování, ochranu před deštěm a kompatibilitu starší sady. Konkrétní kontrolní kroky před nákupem.';
    const TITLE_KEY = '_yoast_wpseo_title';
    const META_KEY = '_yoast_wpseo_metadesc';
    const BACKUP = 'nry_pipaky_advanced_description_36702_backup_20261107_v1';
    const RECEIPT = 'nry_pipaky_advanced_description_36702_receipt_20261107_v1';
    const LOCK = 'nry_pipaky_advanced_description_36702_lock_20261107_v1';

    private static function exact_source($p) {
        return rtrim(home_url('/'), '/') === 'https://na-ryby.eu'
            && is_object($p)
            && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && strlen($p->post_content) === self::SOURCE_BYTES
            && hash('sha256', $p->post_content) === self::SOURCE_SHA
            && get_permalink(self::ID) === 'https://na-ryby.eu/' . self::SLUG . '/';
    }

    public static function inspect() {
        if (!self::exact_source(get_post(self::ID))) {
            return array('status' => 'blocked', 'reason' => 'post_identity_or_source_drift');
        }
        $title = get_post_meta(self::ID, self::TITLE_KEY, true);
        $meta = get_post_meta(self::ID, self::META_KEY, true);
        if (!is_string($title) || !is_string($meta)) {
            return array('status' => 'blocked', 'reason' => 'meta_type');
        }
        // A manually changed Yoast title must not be overwritten/assumed.
        if (metadata_exists('post', self::ID, self::TITLE_KEY)
            || hash('sha256', $title) !== self::EMPTY_TITLE_HASH) {
            return array('status' => 'blocked', 'reason' => 'title_drift');
        }
        if (!metadata_exists('post', self::ID, self::META_KEY)) {
            return array('status' => 'blocked', 'reason' => 'description_missing');
        }
        if ($meta === self::NEW_META) {
            return array('status' => 'already_applied', 'source_sha256' => self::SOURCE_SHA);
        }
        if (strlen($meta) !== self::ORIGINAL_META_BYTES
            || hash('sha256', $meta) !== self::ORIGINAL_META_HASH) {
            return array('status' => 'blocked', 'reason' => 'description_drift');
        }
        return array('status' => 'ready', 'original_meta' => $meta);
    }

    public static function execute() {
        $pre = self::inspect();
        if ($pre['status'] === 'already_applied') {
            return array('status' => 'already_applied_no_write', 'writes' => 0);
        }
        if ($pre['status'] !== 'ready') {
            return array('status' => 'blocked_no_writes', 'reason' => $pre['reason'], 'writes' => 0);
        }
        $backup = array(
            'post_id' => self::ID,
            'source_sha256' => self::SOURCE_SHA,
            'source_modified_gmt' => self::MODIFIED_GMT,
            'meta_key' => self::META_KEY,
            'had_original' => true,
            'original_meta' => $pre['original_meta']
        );
        if (!add_option(self::BACKUP, $backup, '', 'no')
            && get_option(self::BACKUP, null) !== $backup) {
            return array('status' => 'backup_conflict_no_writes', 'writes' => 0);
        }
        $fresh = self::inspect();
        if (get_option(self::BACKUP, null) !== $backup
            || $fresh['status'] !== 'ready'
            || $fresh['original_meta'] !== $pre['original_meta']) {
            return array('status' => 'concurrent_change_or_backup_failed', 'writes' => 0);
        }
        $write = update_post_meta(self::ID, self::META_KEY, self::NEW_META);
        $after = self::inspect();
        if ($write !== false
            && $after['status'] === 'already_applied'
            && metadata_exists('post', self::ID, self::META_KEY)) {
            return array('status' => 'applied_and_verified', 'writes' => 1,
                'post_id' => self::ID, 'meta_key' => self::META_KEY,
                'backup_present' => true, 'source_sha256' => self::SOURCE_SHA);
        }
        // Never silently accept a partial write; restore exactly the old
        // Yoast field only, leaving every other field untouched.
        $restored = update_post_meta(self::ID, self::META_KEY, $backup['original_meta']);
        return array('status' => ($restored !== false
            && get_post_meta(self::ID, self::META_KEY, true) === $backup['original_meta'])
            ? 'rolled_back_verified' : 'rollback_uncertain',
            'writes' => 0);
    }

    public static function maybe_execute() {
        if (is_array(get_option(self::RECEIPT, null))) return;
        $lock = array('created_gmt' => gmdate('c'),
            'batch' => 'pipaky-professional-description-36702-20261107-v1');
        if (!add_option(self::LOCK, $lock, '', 'no')) return;
        $result = self::execute();
        add_option(self::RECEIPT, $result, '', 'no');
    }
}
add_action('wp_loaded', array('NRY_Advanced_Pipaky_Description_V1','maybe_execute'), 38);
