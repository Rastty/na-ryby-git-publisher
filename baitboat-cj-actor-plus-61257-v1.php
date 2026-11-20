<?php
/**
 * One-product, source-bound CJ visitor-link correction for buyer post #61257.
 * Disabled by default. The only activation path is a manage_options + nonce
 * admin Apply; no WP article write, merchant request or synthetic click.
 * Deleting the approval option immediately restores the original hrefs.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Baitboat_CJ_Actor_Plus_61257_V1 {
    const POST_ID = 61257;
    const SLUG = 'zavazeci-lodky-pro-rybare';
    const MODIFIED_GMT = '2026-05-13 10:22:17';
    const APPROVAL = 'nry_cj_actor_plus_61257_v1_approval';
    const RECEIPT = 'nry_cj_actor_plus_61257_v1_receipt';
    const MERCHANT = 'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Actor-Plus-Basic-cerna';
    // Exact link supplied by the site owner, not a guessed publisher-ID swap.
    const OWN_LINK = 'https://www.dpbolvw.net/click-8287231-12347575?url=https%3A%2F%2Fwww.chytapust.cz%2FZavazeci-lodky-2165%2FBoatman-Zavazeci-lodicka-Actor-Plus-Basic-cerna';
    const EXPECTED = 4;

    /** An exact old-property CJ anchor for this ONE merchant product only. */
    public static function old_href($href) {
        if (!is_string($href) || strlen($href) > 600) { return false; }
        $p = parse_url(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (!is_array($p) || !in_array($p['scheme'] ?? '', array('http', 'https'), true)
            || !in_array($p['host'] ?? '', array('www.kqzyfj.com', 'www.tkqlhce.com',
                'www.jdoqocy.com', 'www.dpbolvw.net', 'www.anrdoezrs.net'), true)
            || ($p['path'] ?? '') !== '/click-100927011-12347575'
            || isset($p['user']) || isset($p['pass']) || isset($p['port'])
            || isset($p['fragment'])) { return false; }
        $q = $p['query'] ?? '';
        if (substr($q, 0, 4) !== 'url=' || strpos($q, '&') !== false
            || strpos($q, ';') !== false) { return false; }
        return rawurldecode(substr($q, 4)) === self::MERCHANT;
    }

    /** Pure HTML transformation, all-or-nothing for exactly four anchors. */
    public static function rewrite_html($html) {
        if (!is_string($html) || !class_exists('WP_HTML_Tag_Processor')
            || strpos($html, '100927011') === false || strpos($html, self::OWN_LINK) !== false) {
            return false;
        }
        $tags = new WP_HTML_Tag_Processor($html);
        $changed = 0;
        while ($tags->next_tag(array('tag_name' => 'A'))) {
            if (!self::old_href($tags->get_attribute('href'))) { continue; }
            $tags->set_attribute('href', self::OWN_LINK);
            $rels = preg_split('/\s+/', strtolower(trim((string) $tags->get_attribute('rel'))));
            $rels = array_values(array_filter($rels, 'strlen'));
            foreach (array('sponsored', 'nofollow', 'noopener', 'noreferrer') as $rel) {
                if (!in_array($rel, $rels, true)) { $rels[] = $rel; }
            }
            $tags->set_attribute('rel', implode(' ', $rels));
            $changed++;
        }
        if ($changed !== self::EXPECTED) { return false; }
        $out = $tags->get_updated_html();
        return substr_count($out, self::OWN_LINK) === self::EXPECTED ? $out : false;
    }

    /** Private WordPress source; never infer this SHA from public REST rendered HTML. */
    public static function inspection() {
        $p = function_exists('get_post') ? get_post(self::POST_ID) : false;
        $ready = is_object($p) && (int) ($p->ID ?? 0) === self::POST_ID
            && ($p->post_type ?? '') === 'post' && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null);
        $source = $ready ? $p->post_content : '';
        $candidate = $ready ? self::rewrite_html($source) : false;
        $ready = $ready && is_string($candidate);
        $source_sha = $ready ? hash('sha256', $source) : '';
        $candidate_sha = $ready ? hash('sha256', $candidate) : '';
        $saved = get_option(self::APPROVAL, false);
        $approved = $ready && is_array($saved)
            && is_string($saved['source_sha'] ?? null)
            && is_string($saved['candidate_sha'] ?? null)
            && hash_equals($source_sha, $saved['source_sha'])
            && hash_equals($candidate_sha, $saved['candidate_sha'])
            && ($saved['link_sha'] ?? '') === hash('sha256', self::OWN_LINK);
        return array('ready' => $ready, 'approved' => $approved,
            'conflict' => $saved !== false && !$approved,
            'source_sha' => $source_sha, 'candidate_sha' => $candidate_sha);
    }

    public static function render($html) {
        if (!function_exists('is_single') || !is_single(self::POST_ID)
            || !function_exists('is_singular') || !is_singular('post')) { return $html; }
        $s = self::inspection();
        if (!$s['approved']) { return $html; }
        $out = self::rewrite_html($html);
        return $out === false ? $html : $out;
    }

    public static function admin_menu() {
        add_management_page('Boatman CJ 61257', 'Boatman CJ 61257', 'manage_options',
            'nry-cj-boatman-61257', array(__CLASS__, 'admin_page'));
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $s = self::inspection();
        $r = get_option(self::RECEIPT, array());
        echo '<div class="wrap"><h1>Na-Ryby: Boatman Actor Plus Basic — 4 odkazy</h1>';
        echo '<p>Jen článek #61257, pouze čtyři přesné staré odkazy Chyť a pusť. '
            . 'Schválený odkaz dodal majitel; API entitlement, dostupnost a provize tím nejsou prokázané. '
            . 'Původní obsah a ostatních 16 odkazů zůstává v databázi beze změny. '
            . 'Vypnutí ihned vrátí návštěvníkům původní podobu.</p>';
        echo '<p>Ověřený zdroj: <code>' . esc_html($s['source_sha'] ?: 'NEZNÁMÝ') . '</code>; '
            . 'návrh: <code>' . esc_html($s['candidate_sha'] ?: 'NEPLATNÝ') . '</code>.</p>';
        echo '<p>Stav: <strong>' . ($s['approved'] ? 'ACTIVE — verify public readback'
            : ($s['conflict'] ? 'BLOCKED — changed source or approval' : ($s['ready'] ? 'READY' : 'BLOCKED — source drift')))
            . '</strong></p>';
        if (is_array($r) && isset($r['status'])) {
            echo '<p>Poslední akce: ' . esc_html($r['status']) . '</p>';
        }
        if ($s['ready'] && !$s['approved'] && !$s['conflict']) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
                . '<input type="hidden" name="action" value="nry_cj_actor_plus_61257_apply">';
            wp_nonce_field('nry_cj_actor_plus_61257_apply');
            submit_button('Apply přesně 4 odkazy Boatman');
            echo '</form>';
        }
        if (get_option(self::APPROVAL, false) !== false) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">'
                . '<input type="hidden" name="action" value="nry_cj_actor_plus_61257_disable">';
            wp_nonce_field('nry_cj_actor_plus_61257_disable');
            submit_button('Rollback — vrátit původní odkazy', 'secondary');
            echo '</form>';
        }
        echo '</div>';
    }

    public static function apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_cj_actor_plus_61257_apply');
        $s = self::inspection();
        $ok = false;
        if ($s['ready'] && !$s['conflict']) {
            $saved = array('source_sha' => $s['source_sha'],
                'candidate_sha' => $s['candidate_sha'],
                'link_sha' => hash('sha256', self::OWN_LINK), 'at_utc' => gmdate('c'));
            $ok = add_option(self::APPROVAL, $saved, '', false);
        }
        $after = self::inspection();
        update_option(self::RECEIPT, array('status' => $ok && $after['approved']
            ? 'approved_pending_public_readback' : 'blocked_or_failed',
            'at_utc' => gmdate('c')), false);
        wp_safe_redirect(admin_url('tools.php?page=nry-cj-boatman-61257'));
        exit;
    }

    public static function disable() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_cj_actor_plus_61257_disable');
        delete_option(self::APPROVAL);
        update_option(self::RECEIPT, array('status' => get_option(self::APPROVAL, false) === false
            ? 'disabled_verified' : 'disable_failed', 'at_utc' => gmdate('c')), false);
        wp_safe_redirect(admin_url('tools.php?page=nry-cj-boatman-61257'));
        exit;
    }

    public static function boot() {
        add_filter('the_content', array(__CLASS__, 'render'), 98);
    }
}
add_action('init', array('NRY_Baitboat_CJ_Actor_Plus_61257_V1', 'boot'));
add_action('admin_menu', array('NRY_Baitboat_CJ_Actor_Plus_61257_V1', 'admin_menu'));
add_action('admin_post_nry_cj_actor_plus_61257_apply', array('NRY_Baitboat_CJ_Actor_Plus_61257_V1', 'apply'));
add_action('admin_post_nry_cj_actor_plus_61257_disable', array('NRY_Baitboat_CJ_Actor_Plus_61257_V1', 'disable'));
