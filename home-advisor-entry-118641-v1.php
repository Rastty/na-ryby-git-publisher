<?php
/**
 * One reversible, render-only homepage bridge to the existing safe advisor.
 * No WordPress writes, product offers, merchant href rewrites or analytics.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Home_Advisor_Entry_118641_V1 {
    const ID = 118641;
    const SOURCE_MARKER = 'data-nry-home-trust="source-bound-118641-20261109-v1"';
    const MARKER = 'data-nry-home-advisor-entry="v1"';
    const HERO = 'Průvodci výběrem vybavení, praktické rady, testy, srovnání i inspirace k vodě. Přehledně, vizuálně a bez zbytečného chaosu pro začátečníky i zkušené rybáře.';
    const ADVISOR = 'https://na-ryby.eu/poradce-vybavy/';
    const INSERT = '<p data-nry-home-advisor-entry="v1"><strong>Nevíte, čím začít?</strong> '
        .'Získejte orientační doporučení prutu a navijáku podle stylu rybaření, '
        .'typu vody a rozpočtu. <a href="https://na-ryby.eu/poradce-vybavy/">'
        .'Spustit průvodce výběrem výbavy</a>. Konkrétní produktové nabídky '
        .'zobrazujeme jen po ověření jejich aktuálnosti.</p>';

    public static function boot() {
        // Standalone publisher/KSES tests do not implement the front-end query.
        if (function_exists('add_filter') && function_exists('is_front_page')
            && function_exists('is_page') && function_exists('is_main_query')) {
            add_filter('the_content', array(__CLASS__, 'render'), 54);
        }
    }

    private static function eligible() {
        if (!is_front_page() || !is_page(self::ID)
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || !is_main_query() || !function_exists('in_the_loop')
            || !in_the_loop() || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink(self::ID) !== 'https://na-ryby.eu/'
            || !function_exists('get_post')) return false;
        $p=get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'page'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === 'na-ryby-eu-vse-pro-rybare';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 2500
            || substr_count($html, self::SOURCE_MARKER) !== 1
            || substr_count($html, self::MARKER) !== 0
            || substr_count($html, self::HERO) !== 1) return $html;
        $markerPos = strpos($html, self::SOURCE_MARKER);
        $at = strpos($html, self::HERO);
        $close = strpos($html, '</p>', $at);
        $category = strpos($html, 'Vybavení podle hlavních kategorií');
        if ($markerPos === false || $at === false || $close === false
            || $category === false || $at < $markerPos || $at > $markerPos + 5000
            || $category < $close || $close - $at > 300) return $html;
        // Expect plain introductory copy. Editorial markup and other changes
        // are protected rather than silently flattened or altered.
        if (strpos(substr($html,$at,$close-$at),'<') !== false) return $html;
        $insertAt=$close+strlen('</p>');
        return substr($html,0,$insertAt)."\n".self::INSERT.substr($html,$insertAt);
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Home_Advisor_Entry_118641_V1::boot();
