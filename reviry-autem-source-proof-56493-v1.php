<?php
/**
 * Read-only raw source fingerprint for GSC #56493.
 * GET-only opt-in diagnostic returns identity, digests and anchor counts;
 * no raw content, WP writes, affiliate redirects, or synthetic buyer events.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Reviry_Autem_Source_56493_V1 {
    const ID = 56493;
    const SLUG = '1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem';
    const URL = 'https://na-ryby.eu/1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem/';
    const MODIFIED_GMT = '2023-06-28 11:29:01';
    const QUERY = 'nry_reviry_autem_source_v1';

    public static function boot() {
        if (function_exists('add_action')) {
            add_action('template_redirect', array(__CLASS__, 'handle'), 23);
        }
    }

    public static function snapshot() {
        $blocked = array('schema'=>1, 'state'=>'BLOCKED_SOURCE_DRIFT',
            'postId'=>self::ID, 'wordpressWrites'=>0,
            'merchantRequests'=>0, 'syntheticClicks'=>0);
        if (!function_exists('get_post') || !function_exists('get_permalink')) return $blocked;
        $p = get_post(self::ID);
        if (!is_object($p) || (int)($p->ID ?? 0) !== self::ID
            || ($p->post_type ?? '') !== 'post'
            || ($p->post_status ?? '') !== 'publish'
            || ($p->post_name ?? '') !== self::SLUG
            || ($p->post_modified_gmt ?? '') !== self::MODIFIED_GMT
            || !is_string($p->post_content ?? null)
            || strlen($p->post_content) < 2000
            || get_permalink(self::ID) !== self::URL) return $blocked;
        $raw = $p->post_content;
        return array('schema'=>1, 'state'=>'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
            'postId'=>self::ID, 'slug'=>self::SLUG, 'canonical'=>self::URL,
            'modifiedGmt'=>self::MODIFIED_GMT,
            'sourceSha256'=>hash('sha256', $raw),
            'sourceBytes'=>strlen($raw),
            'literalCounts'=>array(
                'wrongGeo'=>substr_count($raw, 'východně od Hradce Králové'),
                'podyji'=>substr_count($raw, 'Rybářský revír Podyjí'),
                'sumava'=>substr_count($raw, 'Rybářský revír Šumava'),
                'lipno'=>substr_count($raw, 'Rybářský revír Lipno'),
            ),
            'wordpressWrites'=>0, 'merchantRequests'=>0, 'syntheticClicks'=>0);
    }

    public static function handle() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
            || !isset($_GET[self::QUERY]) || $_GET[self::QUERY] !== '1'
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
NRY_Reviry_Autem_Source_56493_V1::boot();
