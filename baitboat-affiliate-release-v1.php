<?php
/**
 * Reviewed CJ routing for Na-ryby.eu. Does not alter the V3 SHA-approved asset,
 * WordPress content, existing affiliate links, or links to other merchants.
 * One reversible owner approval gates both the static selector and article links.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Baitboat_Affiliate_Release_V1 {
    const APPROVAL = 'nry_cj_baitboat_affiliate_v1_approval';
    const RECEIPT = 'nry_cj_baitboat_affiliate_v1_receipt';
    const PATH = 'jak-vybrat-zavazeci-lodku';
    const CJ_PATH = '/click-8287231-15734253';
    const CJ_ORIGIN = 'https://www.anrdoezrs.net';

    /** Fixed, human-reviewed source destinations already present in V3. */
    public static function selector_targets() {
        return array(
            'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Actor-Basic-s-vyklopnikem-10Ah-baterie-cerna' => 3,
            'https://www.chytapust.cz/Zavazeci-lodky-2165/Deeper-Zavazeci-lod-SPARK' => 3,
            'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-GPS-Cerny' => 3,
            'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Actor-PRO-s-GPS-Echolotem-10Ah-baterie-Carbon' => 3,
            'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-Mini-PRO-s-GPS-a-Echolot-Cerna' => 3,
            'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-Pro-s-GPS-a-Echolotem-Cerna' => 3,
            'https://www.chytapust.cz/Prislusenstvi-k-Zavazecim-lodkam/Deeper-Dalkovy-ovladac-Spark' => 1,
            'https://www.chytapust.cz/Prislusenstvi-k-Zavazecim-lodkam/Deeper-Nabijeci-adapter-65-W-pro-zavazeci-lodky-Deeper' => 1,
            'https://www.chytapust.cz/Zavazeci-lodky-2165/' => 1,
        );
    }

    /** Fail closed for unexpected domains, query strings, fragments and paths. */
    public static function merchant_url_eligible($url, $allow_category = false) {
        if (!is_string($url) || strlen($url) > 400) { return false; }
        $p = parse_url($url);
        if (!is_array($p) || ($p['scheme'] ?? '') !== 'https'
            || ($p['host'] ?? '') !== 'www.chytapust.cz'
            || isset($p['user']) || isset($p['pass']) || isset($p['port'])
            || isset($p['query']) || isset($p['fragment'])) { return false; }
        $path = $p['path'] ?? '';
        if (preg_match('~^/[A-Za-z0-9-]{2,100}/[A-Za-z0-9-]{2,180}$~D', $path)) { return true; }
        return $allow_category && $url === 'https://www.chytapust.cz/Zavazeci-lodky-2165/';
    }

    public static function cj_link($url, $allow_category = false) {
        return self::merchant_url_eligible($url, $allow_category)
            ? self::CJ_ORIGIN . self::CJ_PATH . '?url=' . rawurlencode($url) : false;
    }

    public static function selector_html($v3_html) {
        if (!is_string($v3_html) || strpos($v3_html, 'data-nry-baitboat-rc="v3"') === false) { return false; }
        $html = $v3_html;
        foreach (self::selector_targets() as $direct => $expected) {
            $actual = substr_count($html, $direct);
            if ($actual !== $expected) { return false; }
            $affiliate = self::cj_link($direct, true);
            if ($affiliate === false) { return false; }
            $html = str_replace($direct, $affiliate, $html);
        }
        $old = 'Po zapojení schválených provizních odkazů můžeme při nákupu získat provizi, která nezdražuje váš nákup.';
        $new = 'Odkazy na nabídky Chyť a pusť jsou affiliate. Pokud přes ně nakoupíte, můžeme získat provizi; cena pro vás zůstává stejná.';
        if (substr_count($html, $old) !== 1) { return false; }
        $html = str_replace($old, $new, $html);
        $html = str_replace('data-nry-baitboat-rc="v3"', 'data-nry-baitboat-rc="v4-cj"', $html);
        // Every source merchant URL must be absent from resulting buyer-facing
        // links and JS; source URLs are present only percent-encoded in CJ links.
        foreach (self::selector_targets() as $direct => $expected) {
            if (strpos($html, $direct) !== false) { return false; }
        }
        return $html;
    }

    public static function inspection() {
        $v3 = NRY_Baitboat_Release_V3::inspection();
        $source = !empty($v3['approved']) ? NRY_Baitboat_Release_V3::approved_asset_bytes($v3['asset_sha']) : false;
        $html = $source !== false ? self::selector_html($source) : false;
        $sha = $html !== false ? hash('sha256', $html) : false;
        $saved = get_option(self::APPROVAL, false);
        $saved_sha = is_array($saved) && isset($saved['sha256']) && is_string($saved['sha256']) ? $saved['sha256'] : '';
        $ready = $sha !== false && !empty($v3['origin_ok']) && empty($v3['collision']);
        return array('ready'=>$ready, 'sha256'=>$sha, 'source_sha'=>$v3['asset_sha'],
            'approved'=>$ready && $saved_sha !== '' && hash_equals($sha, $saved_sha),
            'conflict'=>$saved_sha !== '' && ($sha === false || !hash_equals($sha, $saved_sha)));
    }

    public static function admin_menu() {
        add_management_page('Na-Ryby CJ Affiliate', 'Na-Ryby CJ Affiliate', 'manage_options',
            'nry-cj-affiliate-release', array(__CLASS__, 'admin_page'));
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $s = self::inspection();
        $receipt = get_option(self::RECEIPT, array());
        echo '<div class="wrap"><h1>Na-Ryby: CJ affiliate odkazy</h1>';
        echo '<p>Jen CJ property Na-ryby.eu 8287231, schválená Chyť a pusť kreativa 15734253. '
            . 'Devět cílových adres ve výběru loďek (6 loděk, 2 příslušenství, 1 kategorie) '
            . 'a přímé produktové odkazy Chyť a pusť v renderovaných článcích. '
            . 'Stávající affiliate odkazy, jiní prodejci a databáze WordPress se nemění. '
            . 'Vypnutí okamžitě obnoví dosavadní V3 a původní odkazy v článcích.</p>';
        echo '<p>Otisk výsledné stránky: <code>' . esc_html($s['sha256'] ?: 'INVALID') . '</code></p>';
        echo '<p>Stav: <strong>' . ($s['approved'] ? 'approved — public readback required' : ($s['conflict'] ? 'BLOCKED: fingerprint changed' : ($s['ready'] ? 'ready' : 'BLOCKED'))) . '</strong></p>';
        if (is_array($receipt) && isset($receipt['status'])) { echo '<p>Poslední akce: ' . esc_html($receipt['status']) . '</p>'; }
        if ($s['ready'] && !$s['approved'] && !$s['conflict']) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_cj_affiliate_apply">';
            wp_nonce_field('nry_cj_affiliate_apply');
            submit_button('Apply CJ affiliate routing'); echo '</form>';
        }
        if (get_option(self::APPROVAL, false) !== false) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_cj_affiliate_disable">';
            wp_nonce_field('nry_cj_affiliate_disable');
            submit_button('Rollback CJ affiliate routing', 'secondary'); echo '</form>';
        }
        echo '</div>';
    }

    public static function apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_cj_affiliate_apply');
        $s = self::inspection();
        if (!$s['ready'] || $s['conflict']) {
            update_option(self::RECEIPT, array('status'=>'blocked_preflight'), false);
        } else {
            $old = get_option(self::APPROVAL, false);
            $ok = $old === false ? add_option(self::APPROVAL,
                array('sha256'=>$s['sha256'], 'source_sha'=>$s['source_sha'], 'at_utc'=>gmdate('c')), '', false)
                : $s['approved'];
            $after = self::inspection();
            update_option(self::RECEIPT, array('status'=>$ok && $after['approved']
                ? 'approved_pending_public_readback' : 'approval_failed', 'at_utc'=>gmdate('c')), false);
        }
        wp_safe_redirect(admin_url('tools.php?page=nry-cj-affiliate-release')); exit;
    }

    public static function disable() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_cj_affiliate_disable');
        delete_option(self::APPROVAL);
        update_option(self::RECEIPT, array('status'=>get_option(self::APPROVAL, false) === false
            ? 'disabled_verified' : 'disable_failed', 'at_utc'=>gmdate('c')), false);
        wp_safe_redirect(admin_url('tools.php?page=nry-cj-affiliate-release')); exit;
    }

    public static function serve() {
        $method = isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : '';
        if ($method !== 'GET' && $method !== 'HEAD') { return; }
        if (!is_ssl() || !in_array(strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')),
            array('na-ryby.eu', 'na-ryby.eu:443'), true)) { return; }
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($path !== '/' . self::PATH && $path !== '/' . self::PATH . '/') { return; }
        $s = self::inspection();
        if (!$s['approved']) { return; }
        $source = NRY_Baitboat_Release_V3::approved_asset_bytes($s['source_sha']);
        $html = $source !== false ? self::selector_html($source) : false;
        if ($html === false || !hash_equals($s['sha256'], hash('sha256', $html))) { return; }
        status_header(200);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data: https://i00.eu; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
        header('Cache-Control: no-store');
        header('X-NRY-Baitboat-Affiliate-SHA256: ' . $s['sha256']);
        if ($method === 'GET') { echo $html; }
        exit;
    }

    /** Runtime-only article routing: no DB writes or rewrap of tracked links. */
    public static function article_content($html) {
        if (!is_string($html) || strpos($html, 'chytapust.cz') === false
            || !class_exists('WP_HTML_Tag_Processor')) { return $html; }
        $s = self::inspection();
        if (!$s['approved'] || !is_singular()) { return $html; }
        $tags = new WP_HTML_Tag_Processor($html);
        $converted = 0;
        while ($tags->next_tag(array('tag_name'=>'A'))) {
            $direct = $tags->get_attribute('href');
            $affiliate = self::cj_link($direct);
            if ($affiliate === false) { continue; }
            $tags->set_attribute('href', $affiliate);
            $tokens = preg_split('/\s+/', strtolower(trim((string) $tags->get_attribute('rel'))));
            $tokens = array_filter($tokens, 'strlen');
            foreach (array('sponsored', 'nofollow', 'noopener', 'noreferrer') as $token) {
                if (!in_array($token, $tokens, true)) { $tokens[] = $token; }
            }
            $tags->set_attribute('rel', implode(' ', $tokens));
            $converted++;
        }
        if (!$converted) { return $html; }
        $out = $tags->get_updated_html();
        if (strpos($out, 'nry-cj-affiliate-disclosure-v1') === false) {
            $out .= '<p class="nry-cj-affiliate-disclosure-v1"><small>Transparentnost: Některé odkazy na produkty Chyť a pusť jsou affiliate. Pokud přes ně nakoupíte, můžeme získat provizi; cena pro vás zůstává stejná.</small></p>';
        }
        return $out;
    }
}
add_action('admin_menu', array('NRY_Baitboat_Affiliate_Release_V1', 'admin_menu'));
add_action('admin_post_nry_cj_affiliate_apply', array('NRY_Baitboat_Affiliate_Release_V1', 'apply'));
add_action('admin_post_nry_cj_affiliate_disable', array('NRY_Baitboat_Affiliate_Release_V1', 'disable'));
add_action('template_redirect', array('NRY_Baitboat_Affiliate_Release_V1', 'serve'), -3);
add_filter('the_content', array('NRY_Baitboat_Affiliate_Release_V1', 'article_content'), 99);
