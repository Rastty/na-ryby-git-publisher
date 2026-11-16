<?php
/**
 * Three reader-first decision routes on the exact protected chair buyer hub.
 * Render-only and bound to original WordPress post SHA + reviewed cleanup.
 * No changes to source content, product claims, CJ URLs or stored metadata.
 * Rollback: remove this file's require from na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Chair_Hub_Decision_Paths_60341_V1 {
    const ID = 60341;
    const SLUG = 'nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby';
    const URL = 'https://na-ryby.eu/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/';
    const SOURCE_SHA = 'e7938af98def78057779f37ad6a88a31a5ffe83ed904dfb65e5a0657eef61ef4';
    const SOURCE_BYTES = 20997;
    const MODIFIED_GMT = '2026-11-04 10:06:35';
    const SOURCE_MARKER = 'data-nry-editorial-edit="chair-hub-trust-60341-v1"';
    const CLEANUP_MARKER = '<!-- nry-chair-hub-historical-ratings-60341-v1 -->';
    const MARKER = 'data-nry-chair-decision-paths="60341-20261116-v1"';
    const INSERT_BEFORE = '<p><strong>Historické příklady k porovnání konstrukcí:</strong>';
    const EVERGREEN = 'https://na-ryby.eu/jak-vybrat-rybarske-kreslo/';
    const CHAIR_120 = 'https://na-ryby.eu/rybarske-kreslo-pro-tezsi-rybare-s-nosnosti-120-kg/';
    const CHAIR_200 = 'https://na-ryby.eu/rybarske-kreslo-s-nosnosti-200-kg-pro-nadsence-lovu-na-tezkou-vahu/';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')) {
            add_filter('the_content', array(__CLASS__, 'render'), 78);
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
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($post->post_content ?? null)
            && strlen($post->post_content) === self::SOURCE_BYTES
            && hash_equals(self::SOURCE_SHA, hash('sha256', $post->post_content));
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',
            $html, $matches) === false) return null;
        return $matches[2];
    }

    private static function card($url, $label, $detail) {
        return '<a href="' . $url . '" style="display:block;flex:1 1 180px;'
            . 'padding:12px;background:#fff;border:1px solid #b8d1ca;border-radius:8px;'
            . 'color:#163d34;text-decoration:none"><strong style="display:block">'
            . $label . '</strong><span style="display:block;font-size:.9em;margin-top:5px">'
            . $detail . '</span></a>';
    }

    private static function panel() {
        return '<nav ' . self::MARKER . ' aria-label="Rychlý výběr rybářského křesla" '
            . 'style="padding:16px;margin:18px 0;background:#f3f8f5;'
            . 'border:1px solid #c9ded3;border-radius:12px">'
            . '<p style="margin:0 0 10px"><strong>Vyberte si další krok podle své situace:</strong> '
            . 'nejprve porovnejte rozměry, stabilitu a nosnost. Model i dostupnost ověřte před koupí.</p>'
            . '<div style="display:flex;flex-wrap:wrap;gap:10px">'
            . self::card(self::EVERGREEN, 'Nevím, jaké křeslo vybrat', 'Základy: přeprava, polohování a pohodlí')
            . self::card(self::CHAIR_120, 'Prověřuji nosnost 120 kg', 'Kontrola limitu výrobce, sedáku a nohou')
            . self::card(self::CHAIR_200, 'Prověřuji nosnost 200 kg', 'Na co se ptát u vysoce zatížené konstrukce')
            . '</div><p style="font-size:.85em;margin:10px 0 0">Nosnost je limit konkrétního modelu, '
            . 'nikoliv záruka bezpečnosti nebo doporučení pro určitou tělesnou hmotnost.</p></nav>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 1000
            || substr_count($html, self::SOURCE_MARKER) !== 1
            || substr_count($html, self::CLEANUP_MARKER) !== 1
            || strpos($html, self::MARKER) !== false
            || substr_count($html, self::INSERT_BEFORE) !== 1) return $html;
        $oldHrefs = self::hrefs($html);
        if ($oldHrefs === null) return $html;
        $panel = self::panel();
        $updated = str_replace(self::INSERT_BEFORE, $panel . self::INSERT_BEFORE, $html);
        $newHrefs = self::hrefs($updated);
        if ($newHrefs === null || count($newHrefs) !== count($oldHrefs) + 3
            || substr_count($updated, self::MARKER) !== 1
            || str_replace($panel, '', $updated) !== $html) return $html;
        foreach (array(self::EVERGREEN, self::CHAIR_120, self::CHAIR_200) as $target) {
            if (substr_count($panel, 'href="' . $target . '"') !== 1) return $html;
        }
        return $updated;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Chair_Hub_Decision_Paths_60341_V1::boot();
