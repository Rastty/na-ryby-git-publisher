<?php
/**
 * Exactly one source-pinned, render-only internal handoff from an existing
 * boat-buyer guide to the live independently verified baitboat selector.
 *
 * No WordPress post mutation, external calls, URL rewriting, affiliate change,
 * tracking endpoint, cron job or new auto-publisher. Editors retain ownership:
 * if post identity, modified time or anchor text drifts, fail closed.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Baitboat_Choice_Entry_61257_V1 {
    const ID = 61257;
    const SLUG = 'zavazeci-lodky-pro-rybare';
    const SOURCE_MODIFIED = '2026-05-13 10:22:17';
    const SOURCE_URL = 'https://na-ryby.eu/zavazeci-lodky-pro-rybare/';
    const PICKER_URL = 'https://na-ryby.eu/jak-vybrat-zavazeci-lodku/';
    const MARKER = 'data-nry-baitboat-choice-link="v1"';
    const HANDOFF = '<aside data-nry-baitboat-choice-link="v1"'
        .' style="border:1px solid #c8dfc9;border-left:5px solid #2e775a;'
        .'border-radius:12px;padding:16px 19px;margin:22px 0;background:#f1f8ed"'
        .' aria-label="Pomoc s výběrem zavážecí loďky">'
        .'<p style="margin:0 0 8px"><strong>Vybíráte konkrétní zavážecí loďku?</strong> '
        .'Porovnejte dosah, GPS, echolot, kapacitu a cenu kompletní sestavy '
        .'podle svých potřeb. Nástroj ukáže i modely, které se do rozpočtu nevejdou.</p>'
        .'<p style="margin:0"><a href="https://na-ryby.eu/jak-vybrat-zavazeci-lodku/">'
        .'<strong>Vybrat zavážecí loďku podle 5 otázek →</strong></a> '
        .'<small>Bez registrace a bez slibu neověřené skladové dostupnosti.</small></p>'
        .'</aside>';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')) {
            add_filter('the_content', array(__CLASS__, 'render'), 47);
        }
    }

    private static function eligible() {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_main_query') || !is_main_query()
            || !function_exists('in_the_loop') || !in_the_loop()
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink(self::ID) !== self::SOURCE_URL
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::SOURCE_MODIFIED;
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 5500
            || substr_count($html, self::MARKER) !== 0
            || strpos($html, self::PICKER_URL) !== false
            || substr_count($html, 'Kdy zavážecí loďka dává opravdu smysl') !== 1
            || strpos($html, 'Boatman Actor Plus Basic') === false
            || strpos($html, 'Deeper QUEST') === false) return $html;

        // Only augment after the first real introductory paragraph. If it is
        // too deep, absent or the article layout has drifted, do not guess.
        if (!preg_match('~<p\b[^>]*>[^<]{75,2000}</p>~u', $html, $m, PREG_OFFSET_CAPTURE)) {
            return $html;
        }
        $at = $m[0][1] + strlen($m[0][0]);
        $firstH = stripos($html, '<h2');
        if ($at > 4500 || $firstH === false || $at > $firstH) return $html;
        // Inserting a standalone aside does not edit any existing post text
        // or external/affiliate link, preserving all original destinations.
        return substr($html, 0, $at)."\n".self::HANDOFF."\n".substr($html, $at);
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Baitboat_Choice_Entry_61257_V1::boot();
