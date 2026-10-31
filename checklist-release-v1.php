<?php
/**
 * One allowlisted static visitor tool. Separate from editorial manifests.
 * No WordPress post, theme, option or route changes occur on Git pull.
 * A privileged administrator must explicitly approve the installed asset SHA.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Checklist_Release_V1 {
    const SLUG = 'co-sbalit-na-ryby';
    const APPROVAL = 'nry_checklist_v1_approved_asset';
    const RECEIPT = 'nry_checklist_v1_last_receipt';

    public static function asset_path() {
        return __DIR__ . '/assets/checklist-standalone.html';
    }

    public static function asset_sha() {
        $file = self::asset_path();
        if (!is_file($file) || !is_readable($file)) { return false; }
        $bytes = filesize($file);
        if ($bytes === false || $bytes < 15000 || $bytes > 100000) { return false; }
        $html = file_get_contents($file);
        if (!is_string($html)
            || strpos($html, 'data-nry-checklist-rc="v1"') === false
            || strpos($html, '<html lang="cs">') === false
            || strpos($html, '<script type="module">') === false
            || substr_count($html, '<script') !== 1
            || substr_count($html, '</script>') !== 1
            || strpos($html, '<link rel="canonical" href="https://na-ryby.eu/co-sbalit-na-ryby/">') === false
            || stripos($html, '</html>') === false
            || preg_match('~(?:<script[^>]+src\\s*=|<iframe\\b|<form\\b|\\bfetch\\s*\\(|\\bXMLHttpRequest\\b|\\bsendBeacon\\s*\\()~i', $html)) {
            return false;
        }
        return hash('sha256', $html);
    }

    public static function collision() {
        // Never shadow a real WordPress page, post, attachment or other content.
        if (get_page_by_path(self::SLUG, OBJECT, 'page')) { return true; }
        // WordPress 'any' excludes attachments and some non-public types;
        // status 'any' also excludes trash and auto-drafts. Check registered
        // types/statuses explicitly so an approved tool never shadows content
        // that already owns this slug (including private and inherited items).
        $types = array_values(get_post_types(array(), 'names'));
        $statuses = array_values(get_post_stati(array(), 'names'));
        if (!$types || !$statuses) { return true; } // fail closed
        $posts = get_posts(array(
            'name' => self::SLUG,
            'post_type' => $types,
            'post_status' => $statuses,
            'numberposts' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'suppress_filters' => true,
        ));
        return !empty($posts);
    }

    public static function inspection() {
        $sha = self::asset_sha();
        $origin_ok = rtrim(home_url('/'), '/') === 'https://na-ryby.eu';
        $collision = self::collision();
        $approved = get_option(self::APPROVAL, false);
        $approved_sha = is_array($approved) && isset($approved['sha256'])
            && is_string($approved['sha256']) ? $approved['sha256'] : '';
        $valid = $sha !== false && $origin_ok && !$collision;
        return array(
            'asset_sha' => $sha,
            'origin_ok' => $origin_ok,
            'collision' => $collision,
            'ready' => $valid,
            'approved' => $valid && $approved_sha !== '' && hash_equals($sha, $approved_sha),
            'approval_conflict' => $approved_sha !== '' && ($sha === false || !hash_equals($sha, $approved_sha)),
        );
    }

    public static function admin_menu() {
        add_management_page('Na-Ryby Checklist Release', 'Na-Ryby Checklist Release',
            'manage_options', 'nry-checklist-release', array(__CLASS__, 'admin_page'));
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $state = self::inspection();
        $receipt = get_option(self::RECEIPT, array());
        echo '<div class="wrap"><h1>Na-Ryby Checklist — schválené vydání</h1>';
        echo '<p>Samostatný nástroj na /co-sbalit-na-ryby/. Git update nic nepublikuje. '
            . 'Pouze správce smí schválit přesný SHA-256 souboru. '
            . 'Žádné články, affiliate odkazy, homepage ani WordPress REST zápisy se nemění.</p>';
        echo '<p>Soubor SHA-256: <code>' . esc_html($state['asset_sha'] ?: 'INVALID') . '</code></p>';
        echo '<p>Doména: ' . ($state['origin_ok'] ? 'OK' : 'BLOCKED')
            . '; kolize URL: ' . ($state['collision'] ? 'BLOCKED' : 'žádná')
            . '; vydání: <strong>' . ($state['approved'] ? 'approved (public readback still required)' : 'not published') . '</strong></p>';
        if (is_array($receipt) && isset($receipt['status'])) {
            echo '<p>Poslední akce: ' . esc_html($receipt['status']) . '</p>';
        }
        if ($state['ready'] && !$state['approved'] && !$state['approval_conflict']) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_checklist_v1_apply">';
            wp_nonce_field('nry_checklist_v1_apply');
            submit_button('Apply: povolit přesný checklist SHA'); echo '</form>';
        }
        if (get_option(self::APPROVAL, false) !== false) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_checklist_v1_disable">';
            wp_nonce_field('nry_checklist_v1_disable');
            submit_button('Rollback: vypnout checklist URL', 'secondary'); echo '</form>';
        }
        if (!$state['ready'] || $state['approval_conflict']) {
            echo '<p><strong>BLOCKED:</strong> změna souboru, domény nebo kolize URL. '
                . 'Nikdy nepřepisujte stávající schválení bez rollbacku a nové kontroly.</p>';
        }
        echo '</div>';
    }

    public static function apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_checklist_v1_apply');
        $state = self::inspection();
        if (!$state['ready'] || $state['approval_conflict']) {
            update_option(self::RECEIPT, array('status' => 'blocked_no_publication'), false);
        } else {
            $old = get_option(self::APPROVAL, false);
            if ($old === false) {
                $value = array('sha256' => $state['asset_sha'],
                    'approved_at_utc' => gmdate('c'), 'slug' => self::SLUG);
                $ok = add_option(self::APPROVAL, $value, '', false);
            } else {
                $ok = $state['approved'];
            }
            $after = get_option(self::APPROVAL, false);
            $verified = $ok && is_array($after) && isset($after['sha256'])
                && hash_equals($state['asset_sha'], $after['sha256']);
            update_option(self::RECEIPT, array(
                'status' => $verified ? 'approved_pending_public_readback' : 'approval_failed',
                'asset_sha256' => $state['asset_sha'],
                'at_utc' => gmdate('c'),
            ), false);
        }
        wp_safe_redirect(admin_url('tools.php?page=nry-checklist-release')); exit;
    }

    public static function disable() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_checklist_v1_disable');
        delete_option(self::APPROVAL);
        update_option(self::RECEIPT, array(
            'status' => get_option(self::APPROVAL, false) === false
                ? 'disabled_verified' : 'disable_failed',
            'at_utc' => gmdate('c'),
        ), false);
        wp_safe_redirect(admin_url('tools.php?page=nry-checklist-release')); exit;
    }

    public static function serve() {
        // An exact public path is the only supported route. Query parameters
        // remain available for the checklist's local sharing functionality.
        // Only safe retrieval methods may serve this allowlisted static asset.
        // Never hijack a POST/PUT/DELETE request to the same WordPress path.
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : '';
        if ($method !== 'GET' && $method !== 'HEAD') { return; }
        // A canonical WordPress home_url alone does not constrain the incoming
        // Host header: aliases and alternate virtual hosts must not serve this
        // SHA-approved asset. Allow only the canonical HTTPS authority.
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
        if ($host !== 'na-ryby.eu' && $host !== 'na-ryby.eu:443') { return; }
        $request = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($request, PHP_URL_PATH);
        if ($path !== '/' . self::SLUG && $path !== '/' . self::SLUG . '/') { return; }
        $state = self::inspection();
        if (!$state['approved']) { return; }
        $file = self::asset_path();
        // Recheck exact approved bytes immediately before serving.
        if (!is_file($file) || !hash_equals($state['asset_sha'], hash_file('sha256', $file))) { return; }
        status_header(200);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data:; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
        header('Cache-Control: no-store');
        header('X-NRY-Checklist-Asset-SHA256: ' . $state['asset_sha']);
        // HEAD has the same verified headers as GET but no response body.
        if ($method === 'GET') { readfile($file); }
        exit;
    }
}
add_action('admin_menu', array('NRY_Checklist_Release_V1', 'admin_menu'));
add_action('admin_post_nry_checklist_v1_apply', array('NRY_Checklist_Release_V1', 'apply'));
add_action('admin_post_nry_checklist_v1_disable', array('NRY_Checklist_Release_V1', 'disable'));
add_action('template_redirect', array('NRY_Checklist_Release_V1', 'serve'), 0);
