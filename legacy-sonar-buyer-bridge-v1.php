<?php
/**
 * Legacy echolot offer-trust bridge on two exact older public articles.
 * Render-only. Original post_content, merchant hrefs and SEO fields stay intact.
 * No WordPress writes, merchant HTTP requests, tracking or invented prices.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Legacy_Sonar_Buyer_Bridge_V1 {
    const HOST = 'https://na-ryby.eu';
    const GUIDE = 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';
    const MARKER = 'data-nry-legacy-sonar-bridge="v1"';

    private static function cases() {
        return array(
            'recenze-a-zkusenosti-lowrance-echolot-hds-live-7-se-sondou-active-imaging-3v1-baterie-nabijecka-zdarma' => array(
                'kind' => 'hds_live_7',
                'title' => 'Lowrance Echolot HDS LIVE 7',
                'needles' => array('HDS LIVE 7', 'ZDARMA'),
            ),
            'lowrance-echolot-hook-4-chirp-gps' => array(
                'kind' => 'hook_4',
                'title' => 'Lowrance Echolot Hook-4',
                'needles' => array('Hook-4', 'CHIRP'),
            ),
        );
    }

    public static function panel($kind) {
        if ($kind === 'hds_live_7') {
            $heading = 'Historická nabídka HDS LIVE 7: bonus a cenu ověřte znovu';
            $detail = 'Tento článek pochází z roku 2023. Uvedená cena, skladovost a baterie s nabíječkou „ZDARMA“ jsou historické údaje, nikoli potvrzená dnešní nabídka. Před nákupem porovnejte přesný kód sady, typ sondy, kompatibilní napájení, obsah balení a aktuální záruční podmínky přímo u prodejce.';
        } elseif ($kind === 'hook_4') {
            $heading = 'Starší generace HOOK-4: nekupujte jen podle historického hodnocení';
            $detail = 'Tato recenze popisuje starší model. Původní hodnocení a odkazy s tvrzením o nejnižší ceně nejsou aktuálním nezávislým srovnáním. Před koupí ověřte stav a stáří zařízení, přiloženou sondu, dostupnost mapových podkladů, aktualizací a náhradních dílů.';
        } else {
            return '';
        }
        return '<aside '.self::MARKER.' data-nry-legacy-sonar-kind="'.$kind.'" '
            .'style="border:1px solid #c5d6d1;border-left:5px solid #176a57;border-radius:10px;'
            .'padding:16px 18px;margin:18px 0 24px;background:#f3f8f6" '
            .'aria-label="Aktuální upozornění před nákupem echolotu">'
            .'<p style="margin:0 0 8px"><strong>'.$heading.'</strong></p>'
            .'<p style="margin:0 0 10px">'.$detail.'</p>'
            .'<p style="margin:0"><a href="'.self::GUIDE.'">'
            .'Vybrat echolot podle použití, sondy a rozpočtu →</a>'
            .' <small>(praktický nákupní průvodce Na-ryby.eu)</small></p>'
            .'</aside>';
    }

    public static function transform($html, $kind) {
        if (!is_string($html) || strlen($html) < 1200
            || strpos($html, self::MARKER) !== false) return $html;
        foreach (self::cases() as $case) {
            if ($case['kind'] !== $kind) continue;
            foreach ($case['needles'] as $needle) {
                if (stripos($html, $needle) === false) return $html;
            }
            $panel = self::panel($kind);
            if ($panel === '' || substr_count($panel, self::MARKER) !== 1
                || substr_count($panel, 'href="'.self::GUIDE.'"') !== 1) return $html;
            return $panel.$html;
        }
        return $html;
    }

    public static function render($html) {
        foreach (array('is_single','is_singular','get_queried_object_id','get_post',
            'get_permalink','home_url','is_admin','is_preview','is_main_query','in_the_loop') as $fn) {
            if (!function_exists($fn)) return $html;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || is_admin() || is_preview() || !is_singular('post')
            || !is_main_query() || !in_the_loop()
            || rtrim(home_url('/'), '/') !== self::HOST) return $html;
        $id = (int) get_queried_object_id();
        if ($id <= 0 || !is_single($id)) return $html;
        $post = get_post($id);
        if (!is_object($post) || (int) ($post->ID ?? 0) !== $id
            || ($post->post_type ?? '') !== 'post'
            || ($post->post_status ?? '') !== 'publish') return $html;
        $slug = (string) ($post->post_name ?? '');
        $cases = self::cases();
        if (!isset($cases[$slug])
            || get_permalink($id) !== self::HOST.'/'.$slug.'/') return $html;
        $case = $cases[$slug];
        if (stripos((string) ($post->post_title ?? ''), $case['title']) === false
            || !is_string($post->post_content ?? null)) return $html;
        foreach ($case['needles'] as $needle) {
            if (stripos($post->post_content, $needle) === false) return $html;
        }
        return self::transform($html, $case['kind']);
    }

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')
            && function_exists('is_singular') && function_exists('get_post')) {
            add_filter('the_content', array(__CLASS__, 'render'), 94);
        }
    }
}
NRY_Legacy_Sonar_Buyer_Bridge_V1::boot();
