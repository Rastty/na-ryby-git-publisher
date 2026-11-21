<?php
/**
 * Clear first choice on the existing private-waters category archive.
 * Read-only frontend rendering, no post/term edits, redirects, tracking or
 * third-party requests. Scoped to a verified root category and main loop.
 * Removal of one require_once restores the original archive.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Revir_Archive_Decision_Entry_V1 {
    const ROOT_URL = 'https://na-ryby.eu/category/soukrome-rybarske-reviry/';
    const REGION_URL = 'https://na-ryby.eu/category/soukrome-rybarske-reviry/soukrome-rybarske-reviry-jihomoravsky-kraj/';
    const WATER_URL = 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/';
    const STAY_URL = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const RULES_URL = 'https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/';
    const MARKER = 'data-nry-revir-archive-entry="v1"';
    private static $done = false;

    private static function eligible() {
        foreach (array('is_category','is_admin','is_feed','is_paged',
            'get_queried_object','get_term_link','home_url') as $fn) {
            if (!function_exists($fn)) return false;
        }
        if (!is_category('soukrome-rybarske-reviry') || is_admin()
            || is_feed() || is_paged()
            || (function_exists('is_preview') && is_preview())
            || (function_exists('wp_doing_ajax') && wp_doing_ajax())
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return false;
        $term = get_queried_object();
        return is_object($term)
            && ($term->taxonomy ?? '') === 'category'
            && ($term->slug ?? '') === 'soukrome-rybarske-reviry'
            && (int)($term->term_id ?? 0) > 0
            && (int)($term->parent ?? -1) === 0
            && get_term_link($term) === self::ROOT_URL;
    }

    public static function block() {
        return '<section class="nry-revir-archive-entry" '.self::MARKER
            .' aria-label="Rychlý výběr revíru a rybářské výpravy">'
            .'<p class="nry-revir-archive-entry__eyebrow">Kam na ryby</p>'
            .'<h2>Najděte vhodnou vodu bez zbytečného hledání</h2>'
            .'<p>V archivu jsou i starší články. Než vyrazíte, ověřte u provozovatele '
            .'aktuální podmínky lovu, povolenky a přístup k vodě.</p>'
            .'<nav class="nry-revir-archive-entry__choices" aria-label="Začít výběr revíru">'
            .'<a href="'.self::REGION_URL.'"><strong>Vybrat podle oblasti</strong>'
            .'<span>Soukromé revíry na jižní Moravě</span></a>'
            .'<a href="'.self::WATER_URL.'"><strong>Prozkoumat konkrétní vodu</strong>'
            .'<span>Dyje 6 – místní povolenka a pravidla</span></a>'
            .'<a href="'.self::STAY_URL.'"><strong>Plánuji pobyt u vody</strong>'
            .'<span>Ubytování a rybářská dovolená</span></a></nav>'
            .'<p class="nry-revir-archive-entry__rules">Nevíte, jak číst povolenky? '
            .'<a href="'.self::RULES_URL.'">Průvodce pravidly revírů</a>.</p>'
            .'</section>';
    }

    public static function render_on_main_loop($query) {
        if (self::$done || !is_object($query)
            || !method_exists($query,'is_main_query')
            || !$query->is_main_query() || !self::eligible()) return;
        self::$done = true;
        echo self::block();
    }

    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-revir-archive-entry-v1',
            plugins_url('assets/nry-revir-archive-entry-v1.css', __FILE__),
            array(), '1.0.0');
    }

    public static function boot() {
        if (function_exists('add_action')) {
            add_action('loop_start', array(__CLASS__,'render_on_main_loop'), 8, 1);
            add_action('wp_enqueue_scripts', array(__CLASS__,'enqueue'), 25);
        }
    }
}
NRY_Revir_Archive_Decision_Entry_V1::boot();
