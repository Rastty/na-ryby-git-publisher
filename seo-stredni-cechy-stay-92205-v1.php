<?php
/**
 * One-post, render-only SERP description experiment from current GSC evidence:
 * 90d through 2026-11-14: 213 impressions / 1 click / average position 17.8.
 *
 * Publicly observed baseline on 2026-11-20, with the exact post identity and
 * modification date independently verified by the existing source-proof route.
 * No post body, Yoast metadata, affiliate href, price, title or WP writes.
 * Editor changes to the original snippet or post identity win automatically.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Stredni_Cechy_Stay_SERP_92205_V1 {
    const ID = 92205;
    const SLUG = 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach';
    const URL = 'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/';
    const MODIFIED_GMT = '2024-10-07 12:15:48';
    // Confirmed by anonymous source-proof route on 2026-11-20. Shared with
    // the already-released stay-offer trust guard for this exact post.
    const SOURCE_SHA256 = 'ca79666566a68867a45eb6cb5504014813419e7c3055dc4880b04510a8560a74';
    const SOURCE_BYTES = 5881;
    const BASELINE = 'Zážitkové rybaření s ubytováním ve středních Čechách. Přijďte si odpočinout a užít si rybaření s rodinou či přáteli.✔️';
    const CANDIDATE = 'Rybaření s ubytováním ve středních Čechách: konkrétní tip u Slap, co ověřit u revíru a povolenky před rezervací. Vyberte pobyt s rozmyslem.';

    public static function boot() {
        if (!function_exists('add_filter')) return;
        add_filter('wpseo_metadesc', array(__CLASS__, 'description'), 20);
        add_filter('wpseo_opengraph_desc', array(__CLASS__, 'description'), 20);
    }

    private static function eligible() {
        foreach (array('is_single', 'is_singular', 'get_queried_object_id', 'get_post',
            'home_url', 'get_permalink') as $fn) {
            if (!function_exists($fn)) return false;
        }
        if (!is_single(self::ID) || !is_singular('post')
            || (int)get_queried_object_id() !== self::ID
            || (function_exists('is_admin') && is_admin())
            || (function_exists('is_preview') && is_preview())
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ID) !== self::URL) return false;

        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && strlen($p->post_content) === self::SOURCE_BYTES
            && hash_equals(self::SOURCE_SHA256, hash('sha256', $p->post_content));
    }

    public static function description($current) {
        if (!is_string($current) || $current !== self::BASELINE
            || !self::eligible()) return $current;
        return self::CANDIDATE;
    }
}
NRY_Stredni_Cechy_Stay_SERP_92205_V1::boot();
