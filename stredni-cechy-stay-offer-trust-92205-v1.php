<?php
/**
 * Source-locked, frontend-only trust repair and one approved Hportal/Dognet
 * accommodation choice for the old Stredni Cechy article. Stored WP content,
 * metadata, cookies, redirects and tracking scripts remain untouched.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Stredni_Cechy_Stay_Offer_92205_V1 {
    const ID = 92205;
    const SLUG = 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach';
    const URL = 'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/';
    const MODIFIED_GMT = '2024-10-07 12:15:48';
    // Independently verified by the guarded, read-only public source proof.
    const RAW_SHA256 = 'ca79666566a68867a45eb6cb5504014813419e7c3055dc4880b04510a8560a74';
    // First 512 bytes of anonymous WordPress REST content.rendered on 18 Nov.
    const PREFIX_BYTES = 512;
    const PREFIX_SHA256 = '926f27c5dd1290e8d5816406fb82d4b68429cd98d930b5ca31efef2803696240';
    const ANCHOR = '<h1>Zážitkové rybaření s ubytováním ve středních Čechách</h1>';
    const MARKER = 'data-nry-sc-stay="v1"';
    const OFFER_ID = 'slapy-janata';
    const HPORTAL_SLAPY = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frabyne%2Fpenzion-janata%2F';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const LEGACY_BOOKING = 'https://na-ryby.eu/booking-cesko';
    const MISLOCATED_WIKI = 'https://cs.wikipedia.org/wiki/Jind%C5%99ichovice_pod_Smrkem';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__.'::enqueue', 28);
        }
    }

    private static function eligible($loop = true) {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || ($loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && hash_equals(self::RAW_SHA256, hash('sha256', $p->post_content));
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-sc-stay-92205-v1',
            plugins_url('assets/nry-stredni-cechy-stay-offer-v1.css', __FILE__), array(), '1.0.0');
    }

    public static function block() {
        return '<section class="nry-sc-stay" '.self::MARKER.' aria-label="Ubytování u Slap pro rybářský výlet">'
            .'<p class="nry-sc-stay__eyebrow">Konkrétní možnost ve středních Čechách</p>'
            .'<h2>Plánujete rybářský víkend u Slap?</h2>'
            .'<p>Ubytování je samostatná služba. Pro rybolov si zvolte konkrétní vodu a ověřte pravidla; '
            .'samotný pobyt automaticky nezahrnuje povolenku ani možnost lovu.</p>'
            .'<article class="nry-sc-stay__offer" data-nry-sc-offer="'.self::OFFER_ID.'">'
            .'<div><p class="nry-sc-stay__place">Slapy · Nová Rabyně</p>'
            .'<h3>Penzion Janata</h3>'
            .'<p>Hportal uvádí polohu u pláže a rybaření mezi aktivitami. '
            .'Dostupnost pokojů, vzdálenost k vybranému revíru a rybářské podmínky ověřte předem.</p></div>'
            .'<a class="nry-sc-stay__cta" href="'.htmlspecialchars(self::HPORTAL_SLAPY, ENT_QUOTES, 'UTF-8').'"'
            .' target="_blank" rel="sponsored nofollow noopener">Zobrazit ubytování u Slap</a>'
            .'</article>'
            .'<p class="nry-sc-stay__more">Hledáte i další lokality? '
            .'<a href="'.self::STAYS.'">Srovnat konkrétní rybářské pobyty</a>.</p>'
            .'<p class="nry-sc-stay__note"><small>Partnerský odkaz · ověřeno 18. 11. 2026. '
            .'Cena a dostupnost se mění. Na svazových vodách ověřte rybářský lístek a povolenku; '
            .'na soukromých vodách pravidla provozovatele.</small></p></section>';
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches) === false) return null;
        return array_map(function($url) { return html_entity_decode($url, ENT_QUOTES, 'UTF-8'); }, $matches[2]);
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 2500
            || hash('sha256', substr($html, 0, self::PREFIX_BYTES)) !== self::PREFIX_SHA256
            || substr_count($html, self::ANCHOR) !== 1
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::HPORTAL_SLAPY) !== false
            || strpos($html, self::STAYS) !== false) return $html;

        $original_hrefs = self::hrefs($html);
        if ($original_hrefs === null
            || count(array_filter($original_hrefs, function($u) { return $u === self::LEGACY_BOOKING; })) !== 1
            || count(array_filter($original_hrefs, function($u) { return $u === self::MISLOCATED_WIKI; })) !== 1) return $html;

        $changes = array(
            's možností ubytování přímo na břehu rybníka.'
                => 'a možností samostatně vybrat ubytování v okolí konkrétní vodní plochy.',
            'Mnoho rybníků nabízí i možnost výuky od zkušených instruktorů, kteří vám rádi poradí a pomohou s vaším lovem.'
                => 'Výuku, povolený způsob lovu a přístup k vodě si vždy ověřte přímo u příslušného správce.',
            '<p>Zážitkové rybaření s ubytováním zahrnuje profesionálního průvodce, rybářské vybavení, ubytování a stravování v krásné přírodě středních Čech.</p>'
                => '<p>Obsah služby záleží na konkrétní nabídce. Samotné ubytování neznamená zajištěného průvodce, vybavení, stravování ani rybolov v ceně.</p>',
            '<p>Ubytování je možné pro skupiny o velikosti od 2 do 6 osob, v závislosti na dostupnosti.</p>'
                => '<p>Kapacitu a volné termíny ověřte u vybraného ubytovatele; pro jednotlivé objekty se liší.</p>',
            '<p>Nabízíme nezapomenutelné zážitkové rybaření s možností ubytování ve středních Čechách&#8230;.</p>'
                => '<p>V tomto přehledu pomáháme vybrat lokalitu a ukazujeme samostatné možnosti ubytování. Neprovozujeme vlastní rybářský areál ani pobytový balíček.</p>',
            '<h3>Tip na zajímavý rybářský soukromý rybářský areál v Česku</h3>'
                => '<h3>Jak ověřit místo lovu před rezervací</h3>',
            '<p>Pokud hledáte ideální místo pro soukromé rybaření s ubytováním v České republice, doporučujeme navštívit <a href="'.self::MISLOCATED_WIKI.'">Jindřichovice pod Smrkem</a>. Tento malebný rybářský areál nabízí krásné ubytování u vody a možnost rybaření v soukromí.</p>'
                => '<p>Starší text zde zaměňoval Jindřichovice pod Smrkem s rybářským areálem ve středních Čechách. Pro pobyt u Slap si nejdřív ověřte konkrétní revír, podmínky lovu a vzdálenost ubytování od vody.</p>',
        );
        $changed = $html;
        foreach ($changes as $old => $new) {
            if (substr_count($changed, $old) !== 1) return $html;
            $changed = str_replace($old, $new, $changed);
        }
        $banner = '~<div style="display: flex; justify-content: center; align-items: center; height: 100vh;">\s*'
            .'<a href="'.preg_quote(self::LEGACY_BOOKING, '~').'">\s*<br\s*/>\s*'
            .'<img\b[^>]*\bsrc="https://na-ryby\.eu/wp-content/uploads/2023/08/Screenshot_960\.png"[^>]*>\s*<br\s*/>\s*'
            .'</a>\s*</div>~s';
        $changed = preg_replace($banner, '', $changed, 1, $banner_count);
        if (!is_string($changed) || $banner_count !== 1 || preg_match($banner, $changed)) return $html;
        $changed = str_replace(self::ANCHOR, self::ANCHOR."\n".self::block(), $changed);

        $new_hrefs = self::hrefs($changed);
        $allowed_removals = array(self::LEGACY_BOOKING, self::MISLOCATED_WIKI);
        $added = array(self::HPORTAL_SLAPY, self::STAYS);
        $preserved = array_values(array_filter($original_hrefs, function($u) use($allowed_removals) {
            return !in_array($u, $allowed_removals, true);
        }));
        $actual_preserved = array_values(array_filter($new_hrefs ?? array(), function($u) use($added) {
            return !in_array($u, $added, true);
        }));
        $new_counts = $new_hrefs === null ? array() : array_count_values($new_hrefs);
        return ($new_hrefs !== null && $preserved === $actual_preserved
            && count($new_hrefs) === count($original_hrefs)
            && substr_count($changed, self::MARKER) === 1
            && substr_count($changed, 'data-nry-sc-offer=') === 1
            && ($new_counts[self::HPORTAL_SLAPY] ?? 0) === 1
            && ($new_counts[self::STAYS] ?? 0) === 1
            && strpos($changed, 'height: 100vh') === false
            && strpos($changed, 'Nabízíme nezapomenutelné') === false)
            ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Stredni_Cechy_Stay_Offer_92205_V1::boot();
