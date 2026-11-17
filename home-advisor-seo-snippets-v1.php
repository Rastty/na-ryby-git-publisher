<?php
/**
 * Two-page, render-only Yoast snippets. The baseline titles were independently
 * observed on the public site on 2026-11-17. No post/meta writes or redirects.
 * Any editor Yoast override, changed URL/identity or title drift wins.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Home_Advisor_SEO_Snippets_V1 {
    const HOME_ID = 118641;
    const ADVISOR_ID = 119865;

    private static function records() {
        return array(
            self::HOME_ID => array(
                'slug' => 'na-ryby-eu-vse-pro-rybare',
                'url' => 'https://na-ryby.eu/',
                'original' => 'Na-ryby.eu - Vše pro rybáře -',
                'title' => 'Rybářské vybavení a praktické návody | Na-Ryby.eu',
                'description' => 'Vyberte rybářský prut, naviják, echolot, zavážecí loďku nebo křeslo podle způsobu lovu a rozpočtu. Praktické návody, srovnání a poradce výběrem.',
            ),
            self::ADVISOR_ID => array(
                'slug' => 'poradce-vybavy',
                'url' => 'https://na-ryby.eu/poradce-vybavy/',
                'original' => 'Jakou rybářskou výbavu potřebuji? | Poradce Na-Ryby.eu -',
                'title' => 'Poradce výběrem prutu a navijáku | Na-Ryby.eu',
                'description' => 'Odpovězte na pět otázek a získejte orientační doporučení prutu a navijáku podle stylu rybaření, typu vody a rozpočtu. Vyberte vybavení s rozmyslem.',
            ),
        );
    }

    public static function boot() {
        if (!function_exists('add_filter') || !function_exists('is_page')) return;
        add_filter('wpseo_title', array(__CLASS__, 'title'), 20);
        add_filter('wpseo_metadesc', array(__CLASS__, 'description'), 20);
        add_filter('wpseo_opengraph_title', array(__CLASS__, 'og_title'), 20);
        add_filter('wpseo_opengraph_desc', array(__CLASS__, 'og_description'), 20);
    }

    private static function eligible($field) {
        foreach (array('get_queried_object_id', 'is_singular', 'home_url',
            'get_permalink', 'get_post', 'metadata_exists') as $fn) {
            if (!function_exists($fn)) return null;
        }
        if (!is_singular('page') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || (function_exists('is_admin') && is_admin())
            || (function_exists('is_preview') && is_preview())) return null;

        $id = (int)get_queried_object_id();
        $records = self::records();
        if (!isset($records[$id]) || !is_page($id)) return null;
        if ($id === self::HOME_ID) {
            if (!function_exists('is_front_page') || !is_front_page()) return null;
        } elseif (function_exists('is_front_page') && is_front_page()) return null;

        $record = $records[$id];
        if (get_permalink($id) !== $record['url']
            || metadata_exists('post', $id, $field)) return null;
        $post = get_post($id);
        if (!$post || (int)($post->ID ?? 0) !== $id
            || ($post->post_type ?? '') !== 'page'
            || ($post->post_status ?? '') !== 'publish'
            || ($post->post_name ?? '') !== $record['slug']) return null;
        return $record;
    }

    public static function title($current) {
        $record = self::eligible('_yoast_wpseo_title');
        return is_string($current) && $record && trim($current) === $record['original']
            ? $record['title'] : $current;
    }

    public static function description($current) {
        $record = self::eligible('_yoast_wpseo_metadesc');
        return is_string($current) && trim($current) === '' && $record
            ? $record['description'] : $current;
    }

    public static function og_title($current) {
        $record = self::eligible('_yoast_wpseo_opengraph-title');
        return is_string($current) && $record && trim($current) === $record['original']
            ? $record['title'] : $current;
    }

    public static function og_description($current) {
        $record = self::eligible('_yoast_wpseo_opengraph-description');
        return is_string($current) && trim($current) === '' && $record
            ? $record['description'] : $current;
    }
}
NRY_Home_Advisor_SEO_Snippets_V1::boot();
