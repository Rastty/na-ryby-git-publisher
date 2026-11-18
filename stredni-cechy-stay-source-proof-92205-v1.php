<?php
/**
 * Exact-post, read-only source fingerprint for the Stredni Cechy accommodation
 * guide. A later, separate release must pin the returned SHA before rendering
 * or editing any offers. No content, affiliate URLs, user data or writer state
 * leaves this endpoint. Removing the require fully rolls this probe back.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Stredni_Cechy_Stay_Source_92205_V1 {
    const ID = 92205;
    const SLUG = 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach';
    const URL = 'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/';
    const EXPECTED_MODIFIED_GMT = '2024-10-07 12:15:48';

    public static function boot() {
        if (function_exists('add_action')) {
            add_action('template_redirect', array(__CLASS__, 'handle'), 23);
        }
    }

    public static function snapshot() {
        $blocked = array('schema' => 1, 'state' => 'BLOCKED_SOURCE_DRIFT',
            'postId' => self::ID, 'publicationAuthorized' => false,
            'wordpressWrites' => 0, 'syntheticClicks' => 0);
        if (!function_exists('get_post') || !function_exists('get_permalink')) return $blocked;
        $p = get_post(self::ID);
        if (!is_object($p) || (int)($p->ID ?? 0) !== self::ID
            || ($p->post_type ?? '') !== 'post'
            || ($p->post_status ?? '') !== 'publish'
            || ($p->post_name ?? '') !== self::SLUG
            || ($p->post_modified_gmt ?? '') !== self::EXPECTED_MODIFIED_GMT
            || !is_string($p->post_content ?? null)
            || strlen($p->post_content) < 1200
            || get_permalink(self::ID) !== self::URL) return $blocked;
        $raw = $p->post_content;
        return array('schema' => 1, 'state' => 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
            'postId' => self::ID, 'canonical' => self::URL,
            'modifiedGmt' => self::EXPECTED_MODIFIED_GMT,
            'sourceSha256' => hash('sha256', $raw), 'sourceBytes' => strlen($raw),
            'literalCounts' => array(
                'weOfferExperience' => substr_count($raw, 'Nabízíme nezapomenutelné'),
                'jindrichovice' => substr_count($raw, 'Jindřichovice pod Smrkem'),
                'stredniCechyFishingSource' => substr_count($raw, 'rybareni-strednicesko.cz'),
            ),
            'publicationAuthorized' => false, 'wordpressWrites' => 0,
            'syntheticClicks' => 0);
    }

    public static function handle() {
        if (!isset($_GET['nry_stredni_cechy_stay_source_proof'])
            || $_GET['nry_stredni_cechy_stay_source_proof'] !== '1'
            || !function_exists('is_page') || !is_page('poradce-vybavy')
            || !function_exists('home_url')
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return;
        if (function_exists('nocache_headers')) nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        echo wp_json_encode(self::snapshot());
        exit;
    }
}
NRY_Stredni_Cechy_Stay_Source_92205_V1::boot();
