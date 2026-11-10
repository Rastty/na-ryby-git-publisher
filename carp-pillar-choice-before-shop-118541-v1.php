<?php
/**
 * Source-scoped, render-only buyer trust correction on the existing carp pillar.
 * Does not change WordPress post_content, affiliate hrefs, prices or metadata.
 * Rollback: remove the require_once from na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Carp_Pillar_Choice_118541_V1 {
    const ID = 118541;
    const SLUG = 'jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika';
    const URL = 'https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/';
    const SOURCE_MARKER = 'data-nry-carp-pillar-trust="118541-20261109-v1"';
    const NEW_MARKER = 'data-nry-carp-choice-before-shop="v1"';
    const OLD = 'Pokud už máte v hlavě základní směr a chcete si porovnat běžné kaprové pruty na jednom místě, dává smysl projít si hlavní kategorii a filtrovat podle délky, liber a ceny. Z obou mých ověřených obchodů mi tady aktuálně dává praktičtější smysl hlavně přehled kaprových dělených prutů na Chyť a pusť.';
    const NEW_BODY = '<span data-nry-carp-choice-before-shop="v1"></span><strong>Nejdřív vyberte typ prutu, potom obchod.</strong> Na menší vodě nebo úzkém břehu rozhoduje ovladatelnost a prostor pro nához; při lovu na větší vzdálenost správná délka a akce; ke krmení raketou slouží samostatný spodový prut. U přesné varianty porovnejte délku, testovací křivku, transportní rozměry a celkovou cenu. Odkaz níže vede do kategorie obchodu, není důkazem dnešní nejvýhodnější ceny ani dostupnosti.';

    public static function boot() {
        // Avoid activating the front-end hook in standalone publisher/KSES
        // contracts, which intentionally lack the WordPress query runtime.
        if (function_exists('add_filter') && function_exists('is_single')
            && function_exists('is_singular') && function_exists('get_queried_object_id')) {
            add_filter('the_content', array(__CLASS__, 'render'), 52);
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
        return is_object($post)
            && (int) ($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG;
    }

    public static function transform($content) {
        if (!is_string($content) || strlen($content) < 1000
            || substr_count($content, self::SOURCE_MARKER) !== 1
            || strpos($content, self::NEW_MARKER) !== false
            || !preg_match_all('~<p\b[^>]*>(.*?)</p>~isu', $content,
                $paragraphs, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return $content;
        }
        $matches = array();
        foreach ($paragraphs as $paragraph) {
            $body = $paragraph[1][0];
            if (strpos($body, 'aktuálně dává praktičtější smysl') === false) continue;
            $normalized = html_entity_decode($body, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $normalized = trim(preg_replace('/[\s\x{00A0}\x{202F}]+/u', ' ', $normalized));
            if ($normalized !== self::OLD || preg_match('~<[^>]+>~s', $body)) {
                // Unexpected editing or inline commercial links: never rewrite.
                return $content;
            }
            $matches[] = $paragraph;
        }
        if (count($matches) !== 1) return $content;
        $paragraph = $matches[0];
        $full = $paragraph[0][0];
        $body = $paragraph[1][0];
        $relative = strpos($full, $body);
        if ($relative === false) return $content;
        $revised = substr_replace($full, self::NEW_BODY, $relative, strlen($body));
        return substr_replace($content, $revised, $paragraph[0][1], strlen($full));
    }

    public static function render($content) {
        return self::eligible() ? self::transform($content) : $content;
    }
}
NRY_Carp_Pillar_Choice_118541_V1::boot();
