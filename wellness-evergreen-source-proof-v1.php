<?php
/**
 * Read-only identity/fingerprint evidence for two existing GSC-indexed wellness
 * editorial articles. No raw WordPress content, email, merchant URL, visitor
 * data, DB write or synthetic click is exposed or mutated.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Wellness_Evergreen_Source_V1 {
    const QUERY = 'nry_wellness_evergreen_source_v1';
    const SITE = 'https://na-ryby.eu';
    const TARGETS = array(
        'wellness-a-rybolov-v-jednom-luxusni-ubytovani-pro-narocne-rybare',
        'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice',
    );
    public static function snapshot($slug) {
        $blocked = array('slug'=>$slug, 'state'=>'BLOCKED_SOURCE_IDENTITY',
            'wordpressWrites'=>0, 'merchantRequests'=>0, 'syntheticClicks'=>0);
        if (!in_array($slug, self::TARGETS, true)
            || !function_exists('get_page_by_path') || !function_exists('get_permalink')) return $blocked;
        $post = get_page_by_path($slug, OBJECT, 'post');
        if (!is_object($post) || (int)($post->ID ?? 0) <= 0
            || ($post->post_type ?? '') !== 'post'
            || ($post->post_status ?? '') !== 'publish'
            || ($post->post_name ?? '') !== $slug
            || !is_string($post->post_content ?? null)
            || strlen($post->post_content) < 1000
            || strlen($post->post_content) > 300000
            || !is_string($post->post_modified_gmt ?? null)
            || !preg_match('/^20[0-9]{2}-[01][0-9]-[0-3][0-9] [0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/D', $post->post_modified_gmt)
            || get_permalink((int)$post->ID) !== self::SITE.'/'.$slug.'/') return $blocked;
        return array(
            'slug'=>$slug, 'state'=>'SOURCE_SHA_AVAILABLE_NO_PUBLICATION',
            'postId'=>(int)$post->ID, 'canonical'=>self::SITE.'/'.$slug.'/',
            'modifiedGmt'=>$post->post_modified_gmt,
            'sourceBytes'=>strlen($post->post_content),
            'sourceSha256'=>hash('sha256', $post->post_content),
            'wordpressWrites'=>0, 'merchantRequests'=>0, 'syntheticClicks'=>0,
        );
    }
    public static function report() {
        return array('schema'=>1, 'site'=>self::SITE,
            'publicationAuthorized'=>false,
            'targets'=>array_map(array(__CLASS__, 'snapshot'), self::TARGETS));
    }
    public static function handle() {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET'
            || ($_GET[self::QUERY] ?? null) !== '1'
            || !function_exists('is_page') || !is_page('poradce-vybavy')
            || !function_exists('is_admin') || is_admin()
            || !function_exists('home_url')
            || rtrim(home_url('/'), '/') !== self::SITE) return;
        if (function_exists('nocache_headers')) nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        echo wp_json_encode(self::report());
        exit;
    }
    public static function boot() {
        if (function_exists('add_action')) {
            add_action('template_redirect', __CLASS__.'::handle', 23);
        }
    }
}
NRY_Wellness_Evergreen_Source_V1::boot();
