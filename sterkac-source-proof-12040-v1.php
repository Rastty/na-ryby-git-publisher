<?php
/**
 * GET-only raw WordPress source SHA for the published Štěrkáč Zaječí guide.
 * Prepares a separately reviewed correction of stale permit price and season.
 * Never returns article text or authorizes publication.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sterkac_Source_12040_V1 {
    const ID = 12040;
    const SLUG = 'soukromy-revir-sterkac-zajeci';
    const URL = 'https://na-ryby.eu/soukromy-revir-sterkac-zajeci/';
    const MODIFIED_GMT = '2026-04-14 10:33:15';

    public static function boot() {
        if (function_exists('add_action')) {
            add_action('template_redirect', array(__CLASS__, 'handle'), 23);
        }
    }

    public static function snapshot() {
        $blocked = array('schema' => 1, 'state' => 'BLOCKED_SOURCE_DRIFT',
            'postId' => self::ID, 'publicationAuthorized' => false,
            'wordpressWrites' => 0, 'merchantRequests' => 0, 'syntheticClicks' => 0);
        if (!function_exists('get_post') || !function_exists('get_permalink')) return $blocked;
        $post = get_post(self::ID);
        if (!is_object($post) || (int)($post->ID ?? 0) !== self::ID
            || ($post->post_type ?? '') !== 'post'
            || ($post->post_status ?? '') !== 'publish'
            || ($post->post_name ?? '') !== self::SLUG
            || ($post->post_modified_gmt ?? '') !== self::MODIFIED_GMT
            || !is_string($post->post_content ?? null)
            || strlen($post->post_content) < 5000
            || get_permalink(self::ID) !== self::URL) return $blocked;
        $raw = $post->post_content;
        return array('schema' => 1, 'state' => 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
            'postId' => self::ID, 'canonical' => self::URL,
            'modifiedGmt' => self::MODIFIED_GMT,
            'sourceSha256' => hash('sha256', $raw), 'sourceBytes' => strlen($raw),
            'literalCounts' => array(
                'oldPermit300Mentions' => substr_count($raw, '300 Kč'),
                'novemberMentions' => substr_count($raw, 'listopad'),
                'oldBookingCategoryLinks' => substr_count($raw, 'https://na-ryby.eu/booking-cesko'),
                'officialOperatorLinks' => substr_count($raw, 'vinarstviukaplicky.cz/rybarina'),
            ),
            'publicationAuthorized' => false, 'wordpressWrites' => 0,
            'merchantRequests' => 0, 'syntheticClicks' => 0);
    }

    public static function handle() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
            || !isset($_GET['nry_sterkac_source_proof'])
            || $_GET['nry_sterkac_source_proof'] !== '1'
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
NRY_Sterkac_Source_12040_V1::boot();
