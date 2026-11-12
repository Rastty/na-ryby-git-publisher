<?php
/**
 * V3 is SHA-approved with explicit photo rights attestation.
 * On V3 disable, the approved V2 continues serving at priority -1.
 * No automatic approval or WordPress editorial mutation.
 */
/**
 * One allowlisted static visitor tool. Separate from editorial manifests.
 * No WordPress post, theme, option or route changes occur on Git pull.
 * A privileged administrator must explicitly approve the installed asset SHA.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Baitboat_Release_V3 {
    const SLUG = 'jak-vybrat-zavazeci-lodku';
    const APPROVAL = 'nry_baitboat_v3_approved_asset';
    const RECEIPT = 'nry_baitboat_v3_last_receipt';

    public static function asset_path() {
        return __DIR__ . '/assets/baitboat-picker-v3.html';
    }

    public static function asset_sha() {
        $file = self::asset_path();
        if (!is_file($file) || !is_readable($file)) { return false; }
        $bytes = filesize($file);
        if ($bytes === false || $bytes < 15000 || $bytes > 100000) { return false; }
        $html = file_get_contents($file);
        if (!is_string($html)
            || strpos($html, 'data-nry-baitboat-rc="v3"') === false
            || strpos($html, '<html lang="cs">') === false
            || strpos($html, '<script type="module">') === false
            || substr_count($html, '<img class="merchant-photo"') !== 6
            || substr_count($html, 'https://i00.eu/img/202/1024x1024/') < 6
            || substr_count($html, '<script') !== 1
            || substr_count($html, '</script>') !== 1
            || strpos($html, '<link rel="canonical" href="https://na-ryby.eu/jak-vybrat-zavazeci-lodku/">') === false
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
        $approved_sha = is_array($approved) && !empty($approved['image_rights_confirmed']) && isset($approved['sha256'])
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
        add_management_page('Na-Ryby Baitboat V3 Release', 'Na-Ryby Baitboat V3 Release',
            'manage_options', 'nry-baitboat-v3-release', array(__CLASS__, 'admin_page'));
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $state = self::inspection();
        $receipt = get_option(self::RECEIPT, array());
        echo '<div class="wrap"><h1>Na-Ryby Výběr zavážecí loďky — vydání V3 s produktovými fotografiemi</h1>';
        echo '<p>Samostatný nástroj na /jak-vybrat-zavazeci-lodku/. Git update nic nepublikuje. '
            . 'Schválená V2 běží až do samostatného schválení V3. V3 obsahuje šest fotografií produktů z veřejných stránek Chyť a pusť; samotná veřejná stránka NEPROKAZUJE licenci. Schvalujte pouze po doložení oprávnění z CJ feedu nebo písemného svolení. Rollback V3 vrátí V2. '
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
            echo '<input type="hidden" name="action" value="nry_baitboat_v3_apply">';
            wp_nonce_field('nry_baitboat_v3_apply');
            echo '<p><label><input type="checkbox" name="nry_baitboat_v3_media_rights_confirmed" value="1" required> Potvrzuji opravneni pouzit sest fotografii produktu (schvaleny CJ feed pro na-ryby.eu nebo pisemne svoleni obchodnika); samotna verejna stranka neprokazuje licenci.</label></p>';
            submit_button('Apply V3: publikovat fotografie po potvrzení práv'); echo '</form>';
        }
        if (get_option(self::APPROVAL, false) !== false) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_baitboat_v3_disable">';
            wp_nonce_field('nry_baitboat_v3_disable');
            submit_button('Rollback V3: okamžitě vrátit schválenou V2', 'secondary'); echo '</form>';
        }
        if (!$state['ready'] || $state['approval_conflict']) {
            echo '<p><strong>BLOCKED:</strong> změna souboru, domény nebo kolize URL. '
                . 'Nikdy nepřepisujte stávající schválení bez rollbacku a nové kontroly.</p>';
        }
        echo '</div>';
    }

    public static function apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_baitboat_v3_apply');
        if (!isset($_POST['nry_baitboat_v3_media_rights_confirmed'])
            || (string) wp_unslash($_POST['nry_baitboat_v3_media_rights_confirmed']) !== '1') {
            update_option(self::RECEIPT, array('status'=>'blocked_media_rights_not_confirmed'), false);
            wp_safe_redirect(admin_url('tools.php?page=nry-baitboat-v3-release')); exit;
        }
        $state = self::inspection();
        if (!$state['ready'] || $state['approval_conflict']) {
            update_option(self::RECEIPT, array('status' => 'blocked_no_publication'), false);
        } else {
            $old = get_option(self::APPROVAL, false);
            if ($old === false) {
                $value = array('sha256' => $state['asset_sha'],
                    'approved_at_utc' => gmdate('c'), 'slug' => self::SLUG, 'image_rights_confirmed'=>true);
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
        wp_safe_redirect(admin_url('tools.php?page=nry-baitboat-v3-release')); exit;
    }

    public static function disable() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_baitboat_v3_disable');
        delete_option(self::APPROVAL);
        update_option(self::RECEIPT, array(
            'status' => get_option(self::APPROVAL, false) === false
                ? 'disabled_verified' : 'disable_failed',
            'at_utc' => gmdate('c'),
        ), false);
        wp_safe_redirect(admin_url('tools.php?page=nry-baitboat-v3-release')); exit;
    }

    /**
     * Read once, then verify and serve those same bytes. A deploy replacing
     * the asset between hash_file() and readfile() must never expose a body
     * different from the administrator-approved SHA.
     */
    public static function approved_asset_bytes($expected_sha) {
        if (!is_string($expected_sha) || !preg_match('/^[a-f0-9]{64}$/D', $expected_sha)) {
            return false;
        }
        $file = self::asset_path();
        if (!is_file($file) || !is_readable($file)) { return false; }
        $bytes = file_get_contents($file);
        if (!is_string($bytes) || strlen($bytes) < 15000 || strlen($bytes) > 100000) {
            return false;
        }
        return hash_equals($expected_sha, hash('sha256', $bytes)) ? $bytes : false;
    }

    public static function serve() {
        // An exact public path is the only supported route. Query parameters
        // remain available for the výběrového nástroje.
        // Only safe retrieval methods may serve this allowlisted static asset.
        // Never hijack a POST/PUT/DELETE request to the same WordPress path.
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : '';
        if ($method !== 'GET' && $method !== 'HEAD') { return; }
        // Never serve the admin-approved HTML over plain HTTP. A canonical
        // HTTPS home_url does not prove the incoming request used TLS.
        // WordPress is_ssl() must be correctly configured behind any proxy;
        // ambiguous proxy TLS state fails closed rather than downgrading.
        if (!is_ssl()) { return; }
        // A canonical WordPress home_url alone does not constrain the incoming
        // Host header: aliases and alternate virtual hosts must not serve this
        // SHA-approved asset. Allow only the canonical host or its :443 port.
        $host = isset($_SERVER['HTTP_HOST']) ? strtolower((string) $_SERVER['HTTP_HOST']) : '';
        if ($host !== 'na-ryby.eu' && $host !== 'na-ryby.eu:443') { return; }
        $request = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = parse_url($request, PHP_URL_PATH);
        if ($path !== '/' . self::SLUG && $path !== '/' . self::SLUG . '/') { return; }
        $state = self::inspection();
        if (!$state['approved']) { return; }
        // Verify and emit one immutable in-memory snapshot, not a second
        // disk read that could race a Deployer-for-Git asset replacement.
        $body = self::approved_asset_bytes($state['asset_sha']);
        if ($body === false) { return; }
        status_header(200);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data: https://i00.eu; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
        header('Cache-Control: no-store');
        header('X-NRY-Baitboat-Asset-SHA256: ' . $state['asset_sha']);
        // HEAD has the same verified headers as GET but no response body.
        if ($method === 'GET') { echo $body; }
        exit;
    }
}
add_action('admin_menu', array('NRY_Baitboat_Release_V3', 'admin_menu'));
add_action('admin_post_nry_baitboat_v3_apply', array('NRY_Baitboat_Release_V3', 'apply'));
add_action('admin_post_nry_baitboat_v3_disable', array('NRY_Baitboat_Release_V3', 'disable'));
add_action('template_redirect', array('NRY_Baitboat_Release_V3', 'serve'), -2);
