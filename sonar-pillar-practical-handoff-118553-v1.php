<?php
/**
 * One source-scoped, render-only internal path for fishermen who already own
 * an echo sounder. No WordPress writes, tracking, affiliate links or redirects.
 * Exact GSC live 2026-11-10: main pillar has 96 28d impressions / 3 clicks;
 * current public pillar already links to budget/boat/advisor but NOT to the
 * existing #49275 "how to use sonar" guide. This fills only that gap.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sonar_Pillar_Practical_Handoff_118553_V1 {
    const ID = 118553;
    const SLUG = 'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce';
    const URL = 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';
    const PRACTICAL = 'https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/';
    const SOURCE_MARKER = 'data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"';
    const MARKER = 'data-nry-sonar-owned-practical="v1"';
    const SOURCE_MODIFIED = '2026-11-04 11:27:17';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')) {
            add_filter('the_content', array(__CLASS__, 'render'), 44);
        }
    }

    private static function eligible() {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int) get_queried_object_id() !== self::ID
            || !function_exists('is_main_query') || !is_main_query()
            || !function_exists('in_the_loop') || !in_the_loop()
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return $post && (int) ($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::SOURCE_MODIFIED;
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 600
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::PRACTICAL) !== false
            || substr_count($html, self::SOURCE_MARKER) !== 1
            || substr_count($html, '<h2>Jak vybrat vhodný typ echolotu</h2>') !== 1)
            return $html;
        $start = strpos($html, '<section '.self::SOURCE_MARKER);
        if ($start === false) return $html;
        $close = strpos($html, '</section>', $start);
        if ($close === false || $close - $start > 4500) return $html;
        $before = substr($html, $start, $close - $start);
        // Source-proven existing four-scenario answer-first section; after
        // an editorial rewrite a maintainer must review a new exact match.
        if (substr_count($before, '<li>') !== 4
            || strpos($before, 'další nákup není nutný') === false
            || strpos($before, 'aktuální konečnou cenu kompletu') === false)
            return $html;
        foreach (array(
            'https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/',
            'https://na-ryby.eu/jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare/',
            'https://na-ryby.eu/poradce-vybavy/',
        ) as $link) {
            if (strpos($before, $link) === false) return $html;
        }
        $after = $close + strlen('</section>');
        $next = substr($html, $after, 400);
        if (!preg_match('~<h2\b[^>]*>\s*Jak vybrat echolot pro rybaření:~i', $next))
            return $html;

        $handoff = "\n".'<p '.self::MARKER.'>'
            .'<strong>Echolot už máte?</strong> Než pořídíte další výbavu, '
            .'zkontrolujte citlivost, umístění sondy a čtení dna podle '
            .'<a href="'.self::PRACTICAL.'">praktického návodu na používání echolotu z lodě</a>.'
            .'</p>'."\n";
        return substr($html, 0, $after).$handoff.substr($html, $after);
    }

    public static function render($html) {
        if (!self::eligible()) return $html;
        return self::transform($html);
    }
}
NRY_Sonar_Pillar_Practical_Handoff_118553_V1::boot();
