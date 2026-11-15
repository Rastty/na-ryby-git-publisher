<?php
/**
 * Optional V6: offer an explicit, user-confirmed lower-capacity compromise when
 * a stock-excluded baitboat would otherwise strand the visitor. Does not alter
 * approved V3/V4/V5 bytes, availability flags, affiliate targets or WP posts.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Baitboat_Compromise_Recovery_V1 {
    const APPROVAL = 'nry_baitboat_compromise_v1_approval';
    const RECEIPT = 'nry_baitboat_compromise_v1_receipt';

    private static function replace_once($html, $old, $new) {
        return substr_count($html, $old) === 1 ? str_replace($old, $new, $html) : false;
    }

    public static function selector_html($v5_html) {
        if (!is_string($v5_html) || strpos($v5_html, 'data-nry-baitboat-rc="v5-stock-guard"') === false
            || strpos($v5_html, 'const unavailableProducts=new Set(["vulgps","mini","vulcan"]);') === false
            || strpos($v5_html, NRY_Baitboat_Affiliate_Release_V1::CJ_PATH) === false) { return false; }
        $html = self::replace_once($v5_html, 'data-nry-baitboat-rc="v5-stock-guard"',
            'data-nry-baitboat-rc="v6-compromise-recovery"');
        if ($html === false) { return false; }
        // Preserve hard requirements except load and only propose a cheaper,
        // lower-capacity model the visitor can consciously accept. In no case
        // does a missing-stock model get a product-level buying CTA.
        $html = self::replace_once($html,
            ' if(!fits.length){',
            ' const lowerCapacityAlternatives=products.filter(p=>!unavailableProducts.has(p.id)&&p.max>=range&&p.load<load&&(!needSonar||p.sonar)&&(!needGps||p.gps))' . "\n" .
            '  .map(p=>priceFor(p,range,hasAdapter)).filter(o=>o.total<=budget)' . "\n" .
            '  .sort((a,b)=>b.p.load-a.p.load||a.total-b.total);' . "\n" .
            ' if(!fits.length){');
        if ($html === false) { return false; }
        $html = self::replace_once($html,
            '  nodes.breakdown.textContent="Nejprve upravte rozpočet nebo požadavky.";',
            '  nodes.breakdown.textContent="Nejprve upravte rozpočet nebo požadavky.";' . "\n" .
            '  if(unavailableMatch&&lowerCapacityAlternatives.length){' . "\n" .
            '   const alternative=lowerCapacityAlternatives[0];' . "\n" .
            '   const capacity=String(alternative.p.load).replace(".",",");' . "\n" .
            '   nodes.desc.textContent+=" Pokud vám stačí kapacita "+capacity+" kg, ostatním zadaným parametrům a rozpočtu vyhovuje "+alternative.p.name+". Sklad tohoto modelu není živě ověřen.";' . "\n" .
            '   const change=document.createElement("button");change.type="button";change.className="btn outline small";' . "\n" .
            '   change.textContent="Přepočítat s kapacitou "+capacity+" kg";' . "\n" .
            '   change.addEventListener("click",()=>{fields.load.value=String(alternative.p.load);recommendation();});' . "\n" .
            '   nodes.addons.append(change);' . "\n" .
            '  }');
        if ($html === false || strpos($html, 'data-nry-baitboat-rc="v5-stock-guard"') !== false) { return false; }
        return $html;
    }

    public static function inspection() {
        $v5 = NRY_Baitboat_Stock_Guard_V1::inspection();
        $source = !empty($v5['approved']) ? NRY_Baitboat_Release_V3::approved_asset_bytes($v5['source_sha']) : false;
        $v4 = $source !== false ? NRY_Baitboat_Affiliate_Release_V1::selector_html($source) : false;
        $v5_html = $v4 !== false ? NRY_Baitboat_Stock_Guard_V1::selector_html($v4) : false;
        $v5_matches = is_string($v5_html) && is_string($v5['sha256'])
            && hash_equals($v5['sha256'], hash('sha256', $v5_html));
        $html = $v5_matches ? self::selector_html($v5_html) : false;
        $sha = $html !== false ? hash('sha256', $html) : false;
        $saved = get_option(self::APPROVAL, false);
        $saved_sha = is_array($saved) && is_string($saved['sha256'] ?? null) ? $saved['sha256'] : '';
        $ready = $v5_matches && $sha !== false;
        return array('ready'=>$ready, 'sha256'=>$sha, 'source_sha'=>$v5['source_sha'],
            'approved'=>$ready && $saved_sha !== '' && hash_equals($sha, $saved_sha),
            'conflict'=>$saved_sha !== '' && ($sha === false || !hash_equals($sha, $saved_sha)));
    }

    public static function admin_menu() {
        add_management_page('Na-Ryby kompromisy', 'Na-Ryby kompromisy', 'manage_options',
            'nry-baitboat-compromise', array(__CLASS__, 'admin_page'));
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $s = self::inspection();
        echo '<div class="wrap"><h1>Na-Ryby — náhradní volba u nedostupných loděk</h1>';
        echo '<p>V6 nabídne jen po výslovné volbě návštěvníka přepočet na nižší kapacitu. Rozpočet, dosah, GPS, sonar, CJ odkazy, vyloučené produkty a V5 zůstávají beze změny. Neprokazuje skladovou dostupnost ani provize. Rollback okamžitě obnoví V5.</p>';
        echo '<p>Výsledný SHA-256: <code>' . esc_html($s['sha256'] ?: 'INVALID') . '</code></p>';
        echo '<p>Stav: <strong>' . ($s['approved'] ? 'approved — public readback required' :
            ($s['conflict'] ? 'BLOCKED: changed fingerprint' : ($s['ready'] ? 'ready' : 'BLOCKED: V5 unavailable'))) . '</strong></p>';
        $receipt = get_option(self::RECEIPT, array());
        if (is_array($receipt) && isset($receipt['status'])) { echo '<p>Poslední akce: ' . esc_html($receipt['status']) . '</p>'; }
        if ($s['ready'] && !$s['approved'] && !$s['conflict']) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_baitboat_compromise_apply">';
            wp_nonce_field('nry_baitboat_compromise_apply');
            submit_button('Apply V6 — volitelný kompromis'); echo '</form>';
        }
        if (get_option(self::APPROVAL, false) !== false) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_baitboat_compromise_disable">';
            wp_nonce_field('nry_baitboat_compromise_disable');
            submit_button('Rollback V6 → V5', 'secondary'); echo '</form>';
        }
        echo '</div>';
    }

    public static function apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_baitboat_compromise_apply');
        $s = self::inspection();
        if (!$s['ready'] || $s['conflict']) {
            $status = 'blocked_preflight';
        } else {
            $old = get_option(self::APPROVAL, false);
            $ok = $old === false ? add_option(self::APPROVAL,
                array('sha256'=>$s['sha256'], 'source_sha'=>$s['source_sha'], 'at_utc'=>gmdate('c')), '', false)
                : $s['approved'];
            $after = self::inspection();
            $status = $ok && $after['approved'] ? 'approved_pending_public_readback' : 'approval_failed';
        }
        update_option(self::RECEIPT, array('status'=>$status, 'at_utc'=>gmdate('c')), false);
        wp_safe_redirect(admin_url('tools.php?page=nry-baitboat-compromise')); exit;
    }

    public static function disable() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_baitboat_compromise_disable');
        delete_option(self::APPROVAL);
        update_option(self::RECEIPT, array('status'=>get_option(self::APPROVAL, false) === false
            ? 'disabled_verified' : 'disable_failed', 'at_utc'=>gmdate('c')), false);
        wp_safe_redirect(admin_url('tools.php?page=nry-baitboat-compromise')); exit;
    }

    public static function serve() {
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? '');
        if ($method !== 'GET' && $method !== 'HEAD') { return; }
        if (!is_ssl() || !in_array(strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')),
            array('na-ryby.eu', 'na-ryby.eu:443'), true)) { return; }
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($path !== '/jak-vybrat-zavazeci-lodku/' && $path !== '/jak-vybrat-zavazeci-lodku') { return; }
        $s = self::inspection();
        if (!$s['approved']) { return; }
        $source = NRY_Baitboat_Release_V3::approved_asset_bytes($s['source_sha']);
        $v4 = $source !== false ? NRY_Baitboat_Affiliate_Release_V1::selector_html($source) : false;
        $v5 = $v4 !== false ? NRY_Baitboat_Stock_Guard_V1::selector_html($v4) : false;
        $html = $v5 !== false ? self::selector_html($v5) : false;
        if ($html === false || !hash_equals($s['sha256'], hash('sha256', $html))) { return; }
        status_header(200);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data: https://i00.eu; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
        header('Cache-Control: no-store');
        header('X-NRY-Baitboat-Compromise-SHA256: ' . $s['sha256']);
        if ($method === 'GET') { echo $html; }
        exit;
    }
}
add_action('admin_menu', array('NRY_Baitboat_Compromise_Recovery_V1', 'admin_menu'));
add_action('admin_post_nry_baitboat_compromise_apply', array('NRY_Baitboat_Compromise_Recovery_V1', 'apply'));
add_action('admin_post_nry_baitboat_compromise_disable', array('NRY_Baitboat_Compromise_Recovery_V1', 'disable'));
add_action('template_redirect', array('NRY_Baitboat_Compromise_Recovery_V1', 'serve'), -5);
