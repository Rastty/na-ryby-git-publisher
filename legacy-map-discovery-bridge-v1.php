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
    const CYCLO = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/';
    const WELLNESS = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';
    const STAY_MARKER = 'data-nry-map-stay-picks="v1"';

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
            || strpos($html, self::STAYS_GUIDE) !== false
            || strpos($html, self::CYCLO) !== false
            || strpos($html, self::WELLNESS) !== false
            || strpos($html, self::STAY_MARKER) !== false) return $html;
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
            .'<div '.self::STAY_MARKER.' aria-label="Konkrétní rybářská ubytování"'
            .' style="padding:18px;margin:17px 0 22px;background:#eff8f2;'
            .'border:2px solid #a9d8bd;border-radius:16px;box-shadow:0 6px 20px rgba(15,77,43,.08)">'
            .'<p style="font-size:13px;font-weight:700;color:#236545;letter-spacing:.04em;'
            .'text-transform:uppercase;margin:0 0 6px">Vyberte skutečné ubytování</p>'
            .'<h3 style="margin:0 0 10px;color:#143f2b">Dvě možnosti u Vranovské přehrady</h3>'
            .'<p style="margin:0 0 15px;color:#344b3b">Už nemusíte začínat starou mapou. '
            .'Tady jsou dvě konkrétní chaty s odkazy na ověření pobytu. '
            .'Cenu, volný termín, povolenku a možnost rybaření ověřte před rezervací.</p>'
            .'<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(225px,1fr));gap:13px">'
            .'<article style="border:1px solid #cadccc;padding:16px;background:#fff;'
            .'border-radius:12px"><p style="margin:0 0 5px;font-size:13px;color:#566c5d">'
            .'Štítary · pro větší skupinu</p>'
            .'<h4 style="margin:0 0 7px;color:#143f2b">Cyklochata Vranov</h4>'
            .'<p style="margin:0 0 14px">Chata se 7 ložnicemi, uváděná kapacita až 28 hostů. '
            .'Ubytování samo o sobě nezahrnuje povolenku.</p>'
            .'<a data-nry-map-stay="cyklochata" href="'.self::CYCLO.'"'
            .' style="display:inline-block;padding:11px 15px;background:#216b46;color:#fff;'
            .'font-weight:700;border-radius:8px;text-decoration:none">'
            .'Prohlédnout Cyklochatu</a></article>'
            .'<article style="border:1px solid #cadccc;padding:16px;background:#fff;'
            .'border-radius:12px"><p style="margin:0 0 5px;font-size:13px;color:#566c5d">'
            .'Lančov · wellness pro menší skupinu</p>'
            .'<h4 style="margin:0 0 7px;color:#143f2b">Rybářský wellness srub</h4>'
            .'<p style="margin:0 0 14px">Sauna a koupací sud, uváděná kapacita až 7 hostů. '
            .'Nižší hladina přehrady může omezit možnosti lovu.</p>'
            .'<a data-nry-map-stay="wellness" href="'.self::WELLNESS.'"'
            .' style="display:inline-block;padding:11px 15px;background:#216b46;color:#fff;'
            .'font-weight:700;border-radius:8px;text-decoration:none">'
            .'Prohlédnout wellness srub</a></article></div>'
            .'<p style="margin:12px 0 0;font-size:13px;color:#4b6252">'
            .'Oba odkazy vedou nejprve na Na-Ryby.eu a následně ke konkrétním '
            .'nabídkám. Údaje o objektu pocházejí od ubytovatelů a mohou se měnit.</p></div>'
            .'<ul><li><a href="'.self::REVIR_GUIDE.'">Prohlédnout soukromé revíry podle oblasti</a></li>'
            .'<li><a href="'.self::RULES_GUIDE.'">Jak najít a ověřit revír v RIS a soupisu</a></li>'
            .'<li><a href="'.self::STAYS_GUIDE.'">Rybářské pobyty a konkrétní možnosti ubytování</a></li></ul>'
            .'</section>';
        $insertAt = $openAt;
        $changed = substr($html, 0, $insertAt).$block.substr($html, $insertAt);
        return substr_count($changed, self::MARKER) === 1
            && substr_count($changed, self::STAY_MARKER) === 1
            && substr_count($changed, 'href="'.self::CYCLO.'"') === 1
            && substr_count($changed, 'href="'.self::WELLNESS.'"') === 1
            && substr_count($changed, 'data-nry-map-stay="') === 2
            && str_replace($block, '', $changed) === $html
            ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Legacy_Map_Discovery_Bridge_V1::boot();
