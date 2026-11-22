<?php
/**
 * Two verified first-party Vranov stay choices from the existing stay buyer hub.
 * Read-only, no DB writes, merchant calls, event tracking or affiliate href rewrite.
 * A strict page, post and editorial-section guard fails closed on content drift.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Stays_Vranov_Discovery_59891_V1 {
    const ID = 59891;
    const SLUG = 'rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare';
    const URL = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const MARKER = 'data-nry-vranov-stays-59891="v1"';
    const EDIT_MARKER = 'data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"';
    const PREVIOUS = '<h4>Statek Jedraž – Jižní Čechy</h4>';
    const NEXT = '<h3>Další možnosti ubytování v rybářských lokalitách</h3>';
    const CYCLO = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/';
    const WELLNESS = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';

    private static function eligible() {
        foreach (array('is_single','is_singular','get_queried_object_id',
            'is_admin','is_preview','home_url','get_permalink','get_post') as $f) {
            if (!function_exists($f)) return false;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || !is_single(self::ID) || !is_singular('post')
            || (int)get_queried_object_id() !== self::ID || is_admin() || is_preview()
            || (function_exists('is_feed') && is_feed())
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ID) !== self::URL) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG;
    }

    public static function block() {
        return '<nav '.self::MARKER.' aria-label="Dvě konkrétní chaty u Vranovské přehrady">'
            .'<h3>Vranovská přehrada: dvě konkrétní chaty</h3>'
            .'<p>Vybíráte pobyt u Vranova? Porovnejte i tyto dvě samostatné nabídky. '
            .'Cenu, volný termín, přístup k vodě a možnost rybaření si ověřte před rezervací.</p>'
            .'<ul>'
            .'<li><a href="'.self::CYCLO.'"><strong>Cyklochata Vranov</strong></a>'
            .' – zázemí pro skupinový pobyt. Povolenka a podmínky lovu se ověřují zvlášť.</li>'
            .'<li><a href="'.self::WELLNESS.'"><strong>Rybářský wellness srub</strong></a>'
            .' – sauna a koupací sud; možnost lovu může omezit nižší hladina přehrady.</li>'
            .'</ul></nav>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 2500
            || substr_count($html, self::EDIT_MARKER) !== 1
            || substr_count($html, self::PREVIOUS) !== 1
            || substr_count($html, self::NEXT) !== 1
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::CYCLO) !== false
            || strpos($html, self::WELLNESS) !== false) return $html;
        $prev = strpos($html, self::PREVIOUS);
        $next = strpos($html, self::NEXT);
        $mark = strpos($html, self::EDIT_MARKER);
        if ($prev === false || $next === false || $mark === false
            || $mark >= $prev || $prev >= $next || ($next - $prev) > 5000) return $html;
        $result = substr($html, 0, $next).self::block().substr($html, $next);
        return substr_count($result, self::MARKER) === 1
            && substr_count($result, 'href="'.self::CYCLO.'"') === 1
            && substr_count($result, 'href="'.self::WELLNESS.'"') === 1
            && str_replace(self::block(), '', $result) === $html
            ? $result : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
    public static function buffer() {
        if (self::eligible() && function_exists('ob_start') && !headers_sent()) {
            ob_start(array(__CLASS__, 'render'));
        }
    }
    public static function boot() {
        if (function_exists('add_filter')) add_filter('the_content', array(__CLASS__, 'render'), PHP_INT_MAX);
        if (function_exists('add_action')) add_action('template_redirect', array(__CLASS__, 'buffer'), 26);
    }
}
NRY_Stays_Vranov_Discovery_59891_V1::boot();
