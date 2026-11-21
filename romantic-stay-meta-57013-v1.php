<?php
/**
 * Exact-post, render-only Yoast description correction for the romantic stays guide.
 * Baseline: anonymous canonical + REST Yoast metadata readback on 2026-11-19.
 * Does not modify WordPress content, metadata, links, canonical or robots.
 * A different editor description or changed post identity wins automatically.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Romantic_Stay_Meta_57013_V1 {
    const ID = 57013;
    const SLUG = 'romanticky-pobyt-pro-dva-s-rybarenim-nezapomenutelne-chvile-plne-lasky-a-rybolovu';
    const URL = 'https://na-ryby.eu/romanticky-pobyt-pro-dva-s-rybarenim-nezapomenutelne-chvile-plne-lasky-a-rybolovu/';
    const MODIFIED_GMT = '2026-04-19 12:01:55';
    const ORIGINAL_DESCRIPTION = 'Romantický pobyt pro dva s rybařením - nezapomenutelné chvíle plné lásky a rybolovu. Zažijte dokonalou kombinaci odpočinku a vášně!✔️';
    const NEW_DESCRIPTION = 'Romantický pobyt s rybařením pro dva: zjistěte, jak ověřit přístup k vodě, pravidla lovu, soukromí i zázemí. Praktický výběr bez planých slibů.';

    private static function eligible() {
        foreach (array('get_queried_object_id', 'is_singular', 'home_url',
            'get_permalink', 'get_post') as $fn) {
            if (!function_exists($fn)) return false;
        }
        if (!is_singular('post')
            || (function_exists('is_admin') && is_admin())
            || (function_exists('is_preview') && is_preview())
            || (int)get_queried_object_id() !== self::ID
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ID) !== self::URL) return false;
        $post = get_post(self::ID);
        return $post && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED_GMT;
    }

    public static function description($current) {
        return is_string($current) && $current === self::ORIGINAL_DESCRIPTION
            && self::eligible() ? self::NEW_DESCRIPTION : $current;
    }

    public static function boot() {
        // Some independent publisher safety tests deliberately load the plugin
        // before any frontend query functions exist. Never register partial hooks.
        if (!function_exists('add_filter') || !function_exists('is_singular')) return;
        add_filter('wpseo_metadesc', array(__CLASS__, 'description'), 20);
        add_filter('wpseo_opengraph_desc', array(__CLASS__, 'description'), 20);
    }
}
NRY_Romantic_Stay_Meta_57013_V1::boot();
