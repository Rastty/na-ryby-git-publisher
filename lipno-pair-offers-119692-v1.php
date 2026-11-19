<?php
/**
 * Source-locked, frontend-only buyer choices on Lipno-for-two article #119692.
 * Places exactly two vetted Hauzi accommodation choices after its decision box,
 * before the original comparison table. No stored WP writes/cookies/tracking.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Lipno_Pair_Offers_119692_V1 {
    const ID = 119692;
    const SLUG = 'rybarsky-vikend-na-lipne-pro-dva-jak-ho-naplanovat-aby-to-davalo-smysl';
    const URL = 'https://na-ryby.eu/rybarsky-vikend-na-lipne-pro-dva-jak-ho-naplanovat-aby-to-davalo-smysl/';
    const MODIFIED_GMT = '2026-05-24 22:01:42';
    const PREFIX_SHA256 = '864eed266554f655484067ca749336089829d4fbce64e57561a07aab255b29a3';
    const MARKER = 'data-nry-lipno-pair="v1"';
    const DECISION = '<section id="decision-box" style="margin: 0 0 24px;">';
    const TABLE = '<section id="tabulka" style="margin: 0 0 24px;">';
    const JBX = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fjbx-resort-albatros';
    const VYHLIDKA = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fkomfortny-penzion-so-saunou-na-lipne';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), PHP_INT_MAX);
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
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post' && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null) && strlen($p->post_content) > 20000;
    }

    public static function block() {
        $button = 'display:inline-flex;align-items:center;justify-content:center;min-height:46px;'
            .'padding:12px 16px;border-radius:10px;background:#0f766e;color:#fff;'
            .'font-weight:700;line-height:1.3;text-decoration:none;margin-top:auto;';
        $card = 'display:flex;flex-direction:column;gap:9px;background:#fff;'
            .'border:1px solid #bcd9d3;border-radius:14px;padding:18px;min-width:0;';
        return '<section '.self::MARKER.' aria-label="Dvě možnosti ubytování pro víkend na Lipně"'
            .' style="box-sizing:border-box;margin:0 0 26px;padding:clamp(16px,3vw,25px);'
            .'border:1px solid #a7d4c6;border-radius:18px;background:#f0f9f6;color:#173c35;">'
            .'<p style="font-size:13px;font-weight:700;letter-spacing:.035em;margin:0 0 8px;">'
            .'TIP NA UBYTOVÁNÍ · LIPNO PRO DVA</p>'
            .'<h2 style="font-size:clamp(22px,2.8vw,28px);line-height:1.25;'
            .'margin:0 0 10px;color:#17483d;">Vybrali jste styl víkendu? Tady jsou dvě konkrétní možnosti</h2>'
            .'<p style="margin:0 0 17px;max-width:72ch;line-height:1.6;">Obě možnosti leží v Přední Výtoni '
            .'u Lipna. Jde o samostatné ubytování, nikoliv o rybářský pobyt s povolenkou. '
            .'Vyberte podle toho, zda je pro vás důležitější wellness nebo jednodušší zázemí.</p>'
            .'<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,230px),1fr));'
            .'gap:14px;margin:0 0 14px;">'
            .'<article data-nry-lipno-pair-offer="jbx" style="'.$card.'">'
            .'<p style="font-size:12px;letter-spacing:.025em;font-weight:700;color:#186c5b;margin:0;">'
            .'Přední Výtoň · wellness</p><h3 style="font-size:19px;color:#173c35;margin:0;">JBX Resort Albatros</h3>'
            .'<p style="margin:0 0 8px;line-height:1.55;">Pokoje a apartmány u jezera; '
            .'podle Hauzi je k dispozici sauna a vířivka. Ověřte rozsah služeb a volný termín.</p>'
            .'<a href="'.htmlspecialchars(self::JBX, ENT_QUOTES, 'UTF-8').'" style="'.$button.'"'
            .' target="_blank" rel="sponsored nofollow noopener">Prověřit JBX Resort Albatros</a></article>'
            .'<article data-nry-lipno-pair-offer="vyhlidka" style="'.$card.'">'
            .'<p style="font-size:12px;letter-spacing:.025em;font-weight:700;color:#186c5b;margin:0;">'
            .'Přední Výtoň · penzion a chaty</p><h3 style="font-size:19px;color:#173c35;margin:0;">'
            .'Pension Vyhlídka</h3><p style="margin:0 0 8px;line-height:1.55;">'
            .'Pokoje i chaty, možnost stravování a sauna dle Hauzi. '
            .'Skutečnou vzdálenost k zamýšlenému lovišti si ověřte předem.</p>'
            .'<a href="'.htmlspecialchars(self::VYHLIDKA, ENT_QUOTES, 'UTF-8').'" style="'.$button.'"'
            .' target="_blank" rel="sponsored nofollow noopener">Prověřit Pension Vyhlídka</a></article>'
            .'</div><p style="font-size:13px;line-height:1.55;margin:0;">'
            .'Partnerské odkazy · ověřeno 19. 11. 2026. Ubytování automaticky nezahrnuje '
            .'povolenku ani právo rybařit; ceny a dostupnost se mění. Na svazových vodách '
            .'ověřte rybářský lístek a povolenku, na soukromých pravidla provozovatele. '
            .'<a href="'.self::STAYS.'" style="color:#0c6554;font-weight:700;">'
            .'Další lokality a pobyty</a>.</p></section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 22000
            || hash('sha256', substr($html, 0, 512)) !== self::PREFIX_SHA256
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::JBX) !== false
            || strpos($html, self::VYHLIDKA) !== false
            || strpos($html, self::STAYS) !== false
            || substr_count($html, self::DECISION) !== 1
            || substr_count($html, self::TABLE) !== 1
            || strpos($html, self::DECISION) > strpos($html, self::TABLE)) return $html;
        $block = self::block();
        if (substr_count($block, self::MARKER) !== 1
            || substr_count($block, htmlspecialchars(self::JBX, ENT_QUOTES, 'UTF-8')) !== 1
            || substr_count($block, htmlspecialchars(self::VYHLIDKA, ENT_QUOTES, 'UTF-8')) !== 1
            || substr_count($block, self::STAYS) !== 1) return $html;
        $changed = str_replace(self::TABLE, $block."\n".self::TABLE, $html);
        // Subtracting precisely the new section must restore the old body,
        // including every prior anchor, text node and FAQ/structured data.
        return substr_count($changed, self::MARKER) === 1
            && str_replace($block."\n", '', $changed) === $html ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Lipno_Pair_Offers_119692_V1::boot();
