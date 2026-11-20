<?php
/**
 * Static, source-pinned Czech fishing-energy guide and one optional internal
 * discoverability link. No WordPress post/option writes, affiliate URLs,
 * merchant requests, tracking events, forms or external JS dependencies.
 * Publication requires the existing exact-SHA guarded FTPS code release.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Powerbank_Solar_Fishing_Guide_V1 {
    const SLUG = 'powerbanka-nebo-solarni-panel-na-ryby';
    const URL = 'https://na-ryby.eu/powerbanka-nebo-solarni-panel-na-ryby/';
    const ASSET_SHA256 = '6a8823edae2ed37f041ec0b800afc548a144d4bedd3f4756956393c8a53671ee';
    const ENTRY_POST = 61257;
    const ENTRY_SLUG = 'zavazeci-lodky-pro-rybare';
    const ENTRY_MODIFIED_GMT = '2026-05-13 10:22:17';
    const ENTRY_MARKER = 'data-nry-power-energy-entry="v1"';
    const ENTRY_HTML = '<aside data-nry-power-energy-entry="v1"'
        .' style="border:1px solid #bfd6c4;border-left:5px solid #27674b;'
        .'border-radius:11px;padding:16px 18px;margin:24px 0;background:#eff6ee"'
        .' aria-label="Energie pro vícedenní rybaření">'
        .'<p style="margin:0 0 6px"><strong>Telefon, echolot a světla na více nocí?</strong> '
        .'Před koupí další baterie si spočítejte energii ve Wh, '
        .'rezervu a kdy má solární panel skutečně smysl.</p>'
        .'<p style="margin:0"><a href="https://na-ryby.eu/powerbanka-nebo-solarni-panel-na-ryby/">'
        .'Powerbanka nebo solární panel na ryby: kalkulačka Wh →</a> '
        .'<small>Bez slibů skladovosti nebo univerzální kompatibility.</small></p>'
        .'</aside>';

    public static function asset_path() {
        return __DIR__ . '/assets/nry-powerbank-solar-fishing-v1.html';
    }

    public static function approved_bytes() {
        $p = self::asset_path();
        if (!is_file($p) || !is_readable($p)) { return false; }
        $bytes = filesize($p);
        if (!is_int($bytes) || $bytes < 9000 || $bytes > 45000) { return false; }
        $html = file_get_contents($p);
        if (!is_string($html) || strlen($html) !== $bytes
            || strpos($html, '<body data-nry-power-guide="v1">') === false
            || substr_count($html, '<script type="module">') !== 1
            || substr_count($html, 'data-nry-whcalc="v1"') !== 1
            || substr_count($html, '<link rel="canonical" href="' . self::URL . '">') !== 1
            || preg_match('~<iframe\b|<form\b|<script[^>]+src\s*=|\bfetch\s*\(|\bXMLHttpRequest\b|\bsendBeacon\s*\(~i', $html)
            || !hash_equals(self::ASSET_SHA256, hash('sha256', $html))) { return false; }
        return $html;
    }

    public static function collision() {
        if (get_page_by_path(self::SLUG, OBJECT, 'page')) { return true; }
        $types = array_values(get_post_types(array(), 'names'));
        $statuses = array_values(get_post_stati(array(), 'names'));
        if (!$types || !$statuses) { return true; }
        $items = get_posts(array('name' => self::SLUG, 'post_type' => $types,
            'post_status' => $statuses, 'numberposts' => 1, 'fields' => 'ids',
            'no_found_rows' => true, 'suppress_filters' => true));
        return !empty($items);
    }

    public static function ready() {
        return rtrim(home_url('/'), '/') === 'https://na-ryby.eu'
            && !self::collision() && self::approved_bytes() !== false;
    }

    public static function serve() {
        $method = (string) ($_SERVER['REQUEST_METHOD'] ?? '');
        if ($method !== 'GET' && $method !== 'HEAD') { return; }
        if (!is_ssl() || strtolower((string) ($_SERVER['HTTP_HOST'] ?? '')) !== 'na-ryby.eu') { return; }
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        if ($path !== '/' . self::SLUG && $path !== '/' . self::SLUG . '/') { return; }
        if (rtrim(home_url('/'), '/') !== 'https://na-ryby.eu' || self::collision()) { return; }
        $html = self::approved_bytes();
        if ($html === false) { return; }
        status_header(200);
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'none'; script-src 'unsafe-inline'; style-src 'unsafe-inline'; img-src 'none'; connect-src 'none'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'");
        header('Cache-Control: public, max-age=300, must-revalidate');
        header('X-NRY-Power-Guide-SHA256: ' . self::ASSET_SHA256);
        if ($method === 'GET') { echo $html; }
        exit;
    }

    /** Existing verified buyer guide can link to the new tool without DB edits. */
    public static function entry($html) {
        if (!is_string($html) || !function_exists('is_single') || !is_single(self::ENTRY_POST)
            || !is_singular('post') || is_admin() || is_preview()
            || !is_main_query() || !in_the_loop()
            || get_queried_object_id() !== self::ENTRY_POST
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ENTRY_POST) !== 'https://na-ryby.eu/' . self::ENTRY_SLUG . '/') {
            return $html;
        }
        $p = get_post(self::ENTRY_POST);
        if (!is_object($p) || (int) ($p->ID ?? 0) !== self::ENTRY_POST
            || ($p->post_name ?? '') !== self::ENTRY_SLUG
            || ($p->post_status ?? '') !== 'publish' || ($p->post_type ?? '') !== 'post'
            || ($p->post_modified_gmt ?? '') !== self::ENTRY_MODIFIED_GMT
            || strlen($html) < 5000
            || substr_count($html, 'data-nry-baitboat-choice-link="v1"') !== 1
            || strpos($html, self::ENTRY_MARKER) !== false
            || strpos($html, self::URL) !== false) { return $html; }
        // Does not touch a single existing anchor, affiliate URL or article byte.
        return $html . self::ENTRY_HTML;
    }

    public static function register_entry() {
        add_filter('the_content', array(__CLASS__, 'entry'), 60);
    }
}
add_action('template_redirect', array('NRY_Powerbank_Solar_Fishing_Guide_V1', 'serve'), -4);
add_action('init', array('NRY_Powerbank_Solar_Fishing_Guide_V1', 'register_entry'));
