<?php
/**
 * Render-only experiment: give readers of the exact sonar pillar a rapid,
 * situation-based route to four existing, public buying/selection guides.
 * No product, merchant, price, stock, affiliate target or WordPress writes.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sonar_Pillar_Fast_Paths_118553_V1 {
    const ID = 118553;
    const URL = 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';
    const SLUG = 'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce';
    const MODIFIED = '2026-11-04 11:27:17';
    const SOURCE = 'data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"';
    const MARKER = 'data-nry-sonar-fast-paths="v1"';
    const BUDGET = 'https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/';
    const BOAT = 'https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/';
    const BANK = 'https://na-ryby.eu/deeper-pro-vs-pro-2-vs-chirp-2-ktery-se-vyplati/';
    const ADVISOR = 'https://na-ryby.eu/poradce-vybavy/';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')) {
            add_filter('the_content', array(__CLASS__, 'render'), 43);
        }
    }

    private static function eligible() {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_main_query') || !is_main_query()
            || !function_exists('in_the_loop') || !in_the_loop()
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED;
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 600
            || substr_count($html, self::SOURCE) !== 1
            || strpos($html, self::MARKER) !== false
            || substr_count($html, '<h2>Jak vybrat vhodný typ echolotu</h2>') !== 1) return $html;
        $start = strpos($html, '<section '.self::SOURCE);
        if ($start === false) return $html;
        $close = strpos($html, '</section>', $start);
        if ($close === false || $close - $start > 4500) return $html;
        $section = substr($html, $start, $close - $start);
        foreach (array(self::BUDGET, self::BOAT, self::ADVISOR) as $url) {
            if (strpos($section, $url) === false) return $html;
        }
        if (substr_count($section, '<li>') !== 4
            || strpos($section, 'další nákup není nutný') === false
            || strpos($section, 'aktuální konečnou cenu kompletu') === false) return $html;
        // Insert after the introductory answer, before the four technical bullets.
        // WordPress may preserve a newline (or other whitespace) between the
        // answer paragraph and its list; require exactly one adjacent pair.
        $pairs = array();
        if (preg_match_all('~</p>\s*<ul>~', $section, $pairs, PREG_OFFSET_CAPTURE) !== 1) return $html;
        $at = $start + $pairs[0][0][1] + strlen('</p>');
        $panel = "\n".'<nav '.self::MARKER.' aria-label="Rychlá cesta k výběru echolotu" '
            .'style="background:#f2f8f7;border:1px solid #cee4df;border-radius:12px;padding:16px;margin:18px 0">'
            .'<p style="margin:0 0 10px"><strong>Vyberte si cestu podle toho, jak chytáte.</strong> '
            .'Přejděte rovnou k výběru sestavy, která odpovídá vaší situaci.</p>'
            .'<div style="display:flex;flex-wrap:wrap;gap:10px">'
            .self::card(self::BANK, 'Chytám ze břehu', 'Srovnání nahazovacích sonarů Deeper')
            .self::card(self::BOAT, 'Chytám z člunu', 'Jak vybrat jednotku, sondu a montáž')
            .self::card(self::BUDGET, 'Mám rozpočet do 15 000 Kč', 'Sestavy podle použití a ceny celku')
            .self::card(self::ADVISOR, 'Zatím nevím', 'Otevřít poradce rybářským vybavením')
            .'</div><p style="font-size:.9em;margin:12px 0 0">'
            .'Konkrétní ceny a dostupnost ověřte vždy u prodejce; odkazy na návody nejsou potvrzením skladovosti.'
            .'</p></nav>' . "\n";
        return substr($html, 0, $at) . $panel . substr($html, $at);
    }

    private static function card($url, $title, $subtitle) {
        return '<a href="'.$url.'" style="display:block;flex:1 1 175px;min-width:0;'
            .'background:#fff;border:1px solid #bad7d2;border-radius:8px;padding:12px;'
            .'text-decoration:none;color:#123e38">'
            .'<strong style="display:block">'.$title.'</strong>'
            .'<span style="display:block;font-size:.9em;margin-top:5px">'.$subtitle.'</span></a>';
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Sonar_Pillar_Fast_Paths_118553_V1::boot();
