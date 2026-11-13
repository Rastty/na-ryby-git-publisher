<?php
/**
 * Fail-closed, owner-approved stock guard for the existing V4-CJ selector.
 * No mutation of the already-approved V3 source or V4 affiliate renderer.
 * This is a dated unavailability exclusion, NOT a live stock-feed claim.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Baitboat_Stock_Guard_V1 {
    const APPROVAL = 'nry_baitboat_stock_guard_v1_approval';
    const RECEIPT = 'nry_baitboat_stock_guard_v1_receipt';

    private static function replace_once($html, $old, $new) {
        if (substr_count($html, $old) !== 1) { return false; }
        return str_replace($old, $new, $html);
    }

    public static function selector_html($v4_html) {
        if (!is_string($v4_html) || strpos($v4_html, 'data-nry-baitboat-rc="v4-cj"') === false
            || strpos($v4_html, NRY_Baitboat_Affiliate_Release_V1::CJ_PATH) === false) { return false; }
        $html = $v4_html;
        $changes = array(
            'data-nry-baitboat-rc="v4-cj"' => 'data-nry-baitboat-rc="v5-stock-guard"',
            '];' . "\n" . 'const productPhotos=' => '];' . "\n" .
                '// Supplier detail checked 2026-11-13: no sale or temporarily unavailable.' . "\n" .
                'const unavailableProducts=new Set(["vulgps","mini","vulcan"]);' . "\n" . 'const productPhotos=',
            'const candidates=products.filter(p=>' => 'const candidates=products.filter(p=>!unavailableProducts.has(p.id)&&',
            'const fits=products.filter(p=>p.max>=range' => 'const fits=products.filter(p=>!unavailableProducts.has(p.id)&&p.max>=range',
            ' if(!fits.length){' => ' const unavailableMatch=products.filter(p=>p.max>=range&&p.load>=load&&(!needSonar||p.sonar)&&(!needGps||p.gps)).map(p=>priceFor(p,range,hasAdapter)).find(o=>o.total<=budget&&unavailableProducts.has(o.p.id));' . "\n" . ' if(!fits.length){',
            '  nodes.name.textContent="V této ceně nemáme poctivou shodu";' =>
                '  nodes.name.textContent=unavailableMatch?"Vhodná loďka je u prodejce nedostupná":"V této ceně nemáme poctivou shodu";',
            '  nodes.desc.textContent="Zadaný rozpočet nestačí na sestavu s požadovaným dosahem, GPS, sonarem a kapacitou. Upravte některý parametr; nechceme doporučit produkt, který zadání nesplňuje.";' =>
                '  nodes.desc.textContent=unavailableMatch?"Parametrům by vyhovoval model "+unavailableMatch.p.name+", ale při posledním ověření nebyl u prodejce dostupný. Zkuste upravit požadavky nebo prohlédnout další modely.":"Zadaný rozpočet nestačí na sestavu s požadovaným dosahem, GPS, sonarem a kapacitou. Upravte některý parametr; nechceme doporučit produkt, který zadání nesplňuje.";',
            '? "Předběžně "+candidates+" z 6 modelů"' => '? "Předběžně "+candidates+" ze 3 dostupnostně nevyloučených modelů"',
            'nodes.name.textContent=p.name;nodes.desc.textContent=p.desc;' =>
                'nodes.name.textContent="Parametricky vhodné: "+p.name;nodes.desc.textContent=p.desc;',
            'nodes.availability.textContent=p.id==="vulcan"?"V produktovém detailu se objevuje dostupnost u dodavatele; před objednávkou ji ověřte.":"Není zapojen živý skladový feed; dostupnost ověřte u prodejce.";' =>
                'nodes.availability.textContent="Skladová dostupnost není ověřena živým feedem. Před objednávkou ji zkontrolujte u prodejce; tato shoda zaručuje pouze parametry.";',
            'ceny a sklad jsou orientační, aktuální stav se zobrazí v e-shopu.' =>
                'ceny jsou orientační; zboží bez ověřené skladové dostupnosti nepovažujte za potvrzeně dostupné. Aktuální stav ověřte u prodejce.',
        );
        foreach ($changes as $old => $new) {
            $html = self::replace_once($html, $old, $new);
            if ($html === false) { return false; }
        }
        $category = NRY_Baitboat_Affiliate_Release_V1::cj_link('https://www.chytapust.cz/Zavazeci-lodky-2165/', true);
        $unavailable = array(
            array('name'=>'Boatman Vulcan GPS', 'label'=>'Vulcan GPS',
                'url'=>'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-GPS-Cerny'),
            array('name'=>'Boatman Vulcan Mini PRO', 'label'=>'Vulcan Mini PRO',
                'url'=>'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-Mini-PRO-s-GPS-a-Echolot-Cerna'),
            array('name'=>'Boatman Vulcan PRO', 'label'=>'Vulcan PRO',
                'url'=>'https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-Pro-s-GPS-a-Echolotem-Cerna'),
        );
        foreach ($unavailable as $item) {
            $html = self::replace_once($html, '<h3>'.$item['name'].'</h3>',
                '<h3>'.$item['name'].'</h3><span class="pill honest">U prodejce nedostupné při kontrole 13. 11. 2026</span>');
            if ($html === false) { return false; }
            $dead = NRY_Baitboat_Affiliate_Release_V1::cj_link($item['url']);
            $html = self::replace_once($html,
                '<a href="'.$dead.'" rel="nofollow sponsored noopener" target="_blank">'.$item['label'].' ↗</a>',
                '<a href="'.$category.'" rel="nofollow sponsored noopener" target="_blank">'.$item['label'].' – hledat alternativu ↗</a>');
            if ($html === false || substr_count($html, $dead . '"') !== 2) { return false; }
            // Remaining links are the product card and the (now blocked) JS entry.
            $html = str_replace($dead . '"', $category . '"', $html);
            if (strpos($html, $dead) !== false) { return false; }
        }
        if (substr_count($html, 'Zobrazit nabídku u Chyť a pusť ↗') !== 8) { return false; }
        $html = str_replace('Zobrazit nabídku u Chyť a pusť ↗', 'Ověřit dostupnost u Chyť a pusť ↗', $html);
        foreach ($unavailable as $item) {
            $start = strpos($html, '<h3>'.$item['name'].'</h3>');
            $end = $start !== false ? strpos($html, '</article>', $start) : false;
            if ($end === false) { return false; }
            $length = $end + strlen('</article>') - $start;
            $card = substr($html, $start, $length);
            if (substr_count($card, 'Ověřit dostupnost u Chyť a pusť ↗') !== 1) { return false; }
            $card = str_replace('Ověřit dostupnost u Chyť a pusť ↗', 'Hledat dostupnou alternativu ↗', $card);
            $html = substr_replace($html, $card, $start, $length);
        }
        $html = self::replace_once($html,
            '<h2 id="modely-title">Šest loďek pro šest různých potřeb</h2>',
            '<h2 id="modely-title">Srovnání šesti loděk: tři byly při kontrole nedostupné</h2>');
        if ($html === false) { return false; }
        return $html;
    }

    public static function inspection() {
        $v4 = NRY_Baitboat_Affiliate_Release_V1::inspection();
        $source = !empty($v4['approved']) ? NRY_Baitboat_Release_V3::approved_asset_bytes($v4['source_sha']) : false;
        $v4_html = $source !== false ? NRY_Baitboat_Affiliate_Release_V1::selector_html($source) : false;
        $v4_matches = is_string($v4_html) && hash_equals($v4['sha256'], hash('sha256', $v4_html));
        $html = $v4_matches ? self::selector_html($v4_html) : false;
        $sha = $html !== false ? hash('sha256', $html) : false;
        $saved = get_option(self::APPROVAL, false);
        $saved_sha = is_array($saved) && isset($saved['sha256']) && is_string($saved['sha256']) ? $saved['sha256'] : '';
        $ready = $sha !== false && $v4_matches;
        return array('ready'=>$ready, 'sha256'=>$sha, 'source_sha'=>$v4['source_sha'],
            'approved'=>$ready && $saved_sha !== '' && hash_equals($sha, $saved_sha),
            'conflict'=>$saved_sha !== '' && ($sha === false || !hash_equals($sha, $saved_sha)));
    }

    public static function admin_menu() {
        add_management_page('Na-Ryby Stock Guard', 'Na-Ryby Stock Guard', 'manage_options',
            'nry-baitboat-stock-guard', array(__CLASS__, 'admin_page'));
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $s = self::inspection();
        $receipt = get_option(self::RECEIPT, array());
        echo '<div class="wrap"><h1>Na-Ryby — kontrola dostupnosti zavážecích loděk</h1>';
        echo '<p>V5 vylučuje tři nedostupné modely Boatman Vulcan GPS, Vulcan Mini PRO a Vulcan PRO z doporučování a nákupních tlačítek, '
            . 'u ostatních modelů rozlišuje vhodnost parametrů od neověřeného stavu skladu. '
            . 'Nejde o živý skladový feed. Zachovává již schválené CJ odkazy a V3/V4 bez změny. '
            . 'Rollback vrátí V4.</p>';
        echo '<p>Otisk: <code>' . esc_html($s['sha256'] ?: 'INVALID') . '</code></p>';
        echo '<p>Stav: <strong>' . ($s['approved'] ? 'approved — ověřte veřejnou stránku' :
            ($s['conflict'] ? 'BLOCKED: changed fingerprint' : ($s['ready'] ? 'ready' : 'BLOCKED: V4 approval unavailable'))) . '</strong></p>';
        if (is_array($receipt) && isset($receipt['status'])) { echo '<p>Poslední akce: ' . esc_html($receipt['status']) . '</p>'; }
        if ($s['ready'] && !$s['approved'] && !$s['conflict']) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_baitboat_stock_guard_apply">';
            wp_nonce_field('nry_baitboat_stock_guard_apply');
            submit_button('Apply V5 — vyřadit nedostupné zboží'); echo '</form>';
        }
        if (get_option(self::APPROVAL, false) !== false) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_baitboat_stock_guard_disable">';
            wp_nonce_field('nry_baitboat_stock_guard_disable');
            submit_button('Rollback V5 → V4', 'secondary'); echo '</form>';
        }
        echo '</div>';
    }

    public static function apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_baitboat_stock_guard_apply');
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
        wp_safe_redirect(admin_url('tools.php?page=nry-baitboat-stock-guard')); exit;
    }

    public static function disable() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_baitboat_stock_guard_disable');
        delete_option(self::APPROVAL);
        update_option(self::RECEIPT, array('status'=>get_option(self::APPROVAL, false) === false
            ? 'disabled_verified' : 'disable_failed', 'at_utc'=>gmdate('c')), false);
        wp_safe_redirect(admin_url('tools.php?page=nry-baitboat-stock-guard')); exit;
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
        $v4_html = $source !== false ? NRY_Baitboat_Affiliate_Release_V1::selector_html($source) : false;
        $html = $v4_html !== false ? self::selector_html($v4_html) : false;
        if ($html === false || !hash_equals($s['sha256'], hash('sha256', $html))) { return; }
        status_header(200);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src data: https://i00.eu; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
        header('Cache-Control: no-store');
        header('X-NRY-Baitboat-Stock-Guard-SHA256: ' . $s['sha256']);
        if ($method === 'GET') { echo $html; }
        exit;
    }
}
add_action('admin_menu', array('NRY_Baitboat_Stock_Guard_V1', 'admin_menu'));
add_action('admin_post_nry_baitboat_stock_guard_apply', array('NRY_Baitboat_Stock_Guard_V1', 'apply'));
add_action('admin_post_nry_baitboat_stock_guard_disable', array('NRY_Baitboat_Stock_Guard_V1', 'disable'));
add_action('template_redirect', array('NRY_Baitboat_Stock_Guard_V1', 'serve'), -4);
