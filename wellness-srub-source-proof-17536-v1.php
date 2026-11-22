<?php
/** GET-only raw WP source fingerprint for wellness/fishing cabin #17536. */
if (!defined('ABSPATH')) { exit; }

final class NRY_Wellness_Srub_Source_17536_V1 {
    const ID = 17536;
    const SLUG = 'chata-rybarsky-wellness-srub';
    const URL = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';
    const MODIFIED_GMT = '2023-04-11 13:48:48';

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
            || strlen($post->post_content) < 1000
            || get_permalink(self::ID) !== self::URL) return $blocked;
        $raw = $post->post_content;
        return array('schema' => 1, 'state' => 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
            'postId' => self::ID, 'canonical' => self::URL,
            'modifiedGmt' => self::MODIFIED_GMT,
            'sourceSha256' => hash('sha256', $raw), 'sourceBytes' => strlen($raw),
            'literalCounts' => array(
                'oldAgodaCjLinks' => substr_count($raw, 'anrdoezrs.net/click-8287231-15735403'),
                'oldAgodaBoxes' => substr_count($raw, 'agoda-booking-box'),
                'lowestPriceClaims' => substr_count($raw, 'Garantovaná nejnižší cena'),
                'oldBoatFeedingClaims' => substr_count($raw, 'stále prokrmovaná místa'),
                'verifiedHauziListingLinks' => substr_count($raw, 'hauzi.cz/rybarsky-srub'),
            ),
            'publicationAuthorized' => false, 'wordpressWrites' => 0,
            'merchantRequests' => 0, 'syntheticClicks' => 0);
    }

    public static function handle() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
            || !isset($_GET['nry_wellness_srub_source_proof'])
            || $_GET['nry_wellness_srub_source_proof'] !== '1'
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
NRY_Wellness_Srub_Source_17536_V1::boot();
