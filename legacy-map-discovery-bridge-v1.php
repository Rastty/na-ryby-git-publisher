<?php
/**
 * Give visitors an actionable, current route out of an old map-only page.
 * Preserve the historical map embed: the legacy Maps Engine URL currently
 * redirects to Google My Maps, so map failure must not be presumed.
 * Render-only, exact page and iframe; no stored WordPress changes.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Legacy_Map_Discovery_Bridge_V1 {
    const PAGE_SLUG = 'dovolena-u-vody';
    const PAGE_URL = 'https://na-ryby.eu/dovolena-u-vody/';
    const MAP_URL_TOKEN = 'mapsengine.google.com/map/embed?mid=z3EK_WPx5q6o.ksrmQWY0S3AE';
    const MARKER = 'data-nry-map-discovery="v1"';
    const REVIR_GUIDE = 'https://na-ryby.eu/category/soukrome-rybarske-reviry/';
    const RULES_GUIDE = 'https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/';
    const STAYS_GUIDE = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_page')) {
            add_filter('the_content', array(__CLASS__, 'render'), 92);
        }
    }

    private static function eligible() {
        foreach (array('home_url', 'is_admin', 'is_preview', 'is_front_page', 'is_main_query',
            'in_the_loop', 'get_queried_object_id', 'get_post', 'get_permalink') as $fn) {
            if (!function_exists($fn)) return false;
        }
        if (is_admin() || is_preview() || is_front_page() || !is_page(self::PAGE_SLUG)
            || !is_main_query() || !in_the_loop()
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return false;
        $id = (int)get_queried_object_id();
        if ($id <= 0 || get_permalink($id) !== self::PAGE_URL) return false;
        $post = get_post($id);
        return is_object($post) && (int)($post->ID ?? 0) === $id
            && ($post->post_type ?? '') === 'page'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::PAGE_SLUG;
    }

    public static function transform($html) {
        if (!is_string($html) || strpos($html, self::MARKER) !== false
            || substr_count($html, self::MAP_URL_TOKEN) !== 1
            || substr_count($html, '<iframe') !== 1
            || strpos($html, self::REVIR_GUIDE) !== false
            || strpos($html, self::RULES_GUIDE) !== false
            || strpos($html, self::STAYS_GUIDE) !== false) return $html;
        $urlAt = strpos($html, self::MAP_URL_TOKEN);
        $openAt = strpos($html, '<iframe');
        $tagEnd = strpos($html, '>', $openAt);
        $closeAt = strpos($html, '</iframe>', $urlAt);
        if ($openAt === false || $tagEnd === false || $closeAt === false
            || $openAt >= $urlAt || $urlAt >= $tagEnd
            || $tagEnd - $openAt > 1500 || $closeAt - $tagEnd > 1500) return $html;
        // Put the decision before the large map so useful first-party paths
        // remain visible above the fold; the historical embed stays byte-identical.
        $block = '<section class="nry-map-discovery" '.self::MARKER.'>'
            .'<h2>Jak ověřit revír a naplánovat pobyt u vody</h2>'
            .'<p>Mapa slouží k orientaci. Před výpravou ověřte aktuální hranice revíru, povolenku, místní pravidla a dostupnost ubytování.</p>'
            .'<ul><li><a href="'.self::REVIR_GUIDE.'">Prohlédnout soukromé revíry podle oblasti</a></li>'
            .'<li><a href="'.self::RULES_GUIDE.'">Jak najít a ověřit revír v RIS a soupisu</a></li>'
            .'<li><a href="'.self::STAYS_GUIDE.'">Rybářské pobyty a konkrétní možnosti ubytování</a></li></ul>'
            .'</section>';
        $insertAt = $openAt;
        $changed = substr($html, 0, $insertAt).$block.substr($html, $insertAt);
        return substr_count($changed, self::MARKER) === 1 ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Legacy_Map_Discovery_Bridge_V1::boot();
