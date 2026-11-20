<?php
/**
 * Frontend-only correction of an expired, prominent Slevomat offer on Orlik
 * stay post 119551. The live article, all unrelated links and WP storage stay
 * unchanged. No merchant requests, redirects or new tracking are made here.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Orlik_Expired_Stay_CTA_119551_V1 {
    const ID = 119551;
    const SLUG = 'kde-prespat-u-orliku-na-rybarsky-vikend';
    const URL = 'https://na-ryby.eu/kde-prespat-u-orliku-na-rybarsky-vikend/';
    const MODIFIED_GMT = '2026-05-10 09:23:09';
    // Public anonymous WP REST content.rendered, first 512 bytes, 19 Nov 2026.
    const PREFIX_SHA256 = 'b96cfa15bfa42365c2be2c9d47fa5e34b8bfc96772b8425ab01419d3c4f2b4a2';
    const MARKER = 'data-nry-orlik-stay="v1"';
    // Original Slevomat approved link is retained (not a specifically booked
    // property); its unsupported promise of exact dates is corrected.
    const SLEVOMAT_CATEGORY = 'https://www.slevomat.cz/cestovani/dovolena/u-vody?filtry%5Boblast%5D=cesko_stredni-cechy&#038;utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_terminoeu&#038;utm_content=NARYBY&#038;utm_medium=cpc&#038;utm_source=affiliate&#038;utm_term=j3d7tsgc';
    const EXPIRED_OFFER = 'https://www.slevomat.cz/akce/2337132-relax-na-orliku-neomezene-wellness-i-dobre-jidlo?utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_terminoeu&#038;utm_content=NARYBY&#038;utm_medium=cpc&#038;utm_source=affiliate&#038;utm_term=j3d7tsgc';
    // Current, named Milešov listing, verified directly from Hportal.
    const HPORTAL = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fkrasna-hora-nad-vltavou%2Fliskuv-mlyn-orlicka-prehrada%2F';
    const FIRST_STYLE = 'display:inline-block;background:#f59e0b;color:#111827;text-decoration:none;font-weight:700;padding:14px 18px;border-radius:12px;margin:0 10px 10px 0;';
    const SECOND_STYLE = 'display:inline-block;background:#ffffff;color:#0f172a;text-decoration:none;font-weight:700;padding:14px 18px;border-radius:12px;border:1px solid #cbd5e1;';

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
            && is_string($p->post_content ?? null)
            && strlen($p->post_content) > 20000;
    }

    public static function original_first() {
        return '<a href="'.self::SLEVOMAT_CATEGORY.'" style="'.self::FIRST_STYLE.'">Zjistit cenu a termín</a>';
    }
    public static function original_second() {
        return '<a href="'.self::EXPIRED_OFFER.'" style="'.self::SECOND_STYLE.'">Projít další možnost</a>';
    }
    public static function replacement_second() {
        return '<a href="'.htmlspecialchars(self::HPORTAL, ENT_QUOTES, 'UTF-8').'"'
            .' style="'.self::SECOND_STYLE.'" target="_blank" rel="sponsored nofollow noopener">'
            .'Liškův Mlýn u Orlíku – prověřit pobyt</a>';
    }
    public static function trust_note() {
        return '<span '.self::MARKER.' style="display:block;margin-top:14px;max-width:74ch;'
            .'font-size:13px;line-height:1.55;color:#e2e8f0;">Partnerské odkazy. '
            .'První vede na širší nabídku pobytů ve středních Čechách, druhý na konkrétní ubytování '
            .'Liškův Mlýn u Orlíku. Rezervace noclehu automaticky nezahrnuje rybářský lístek, '
            .'povolenku ani právo lovit; vzdálenost ke zvolenému lovišti, pravidla a volné termíny '
            .'ověřte samostatně. Ověřeno 19. 11. 2026.</span>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 20000
            || hash('sha256', substr($html, 0, 512)) !== self::PREFIX_SHA256
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::HPORTAL) !== false
            || substr_count($html, '<h1 style="margin:0 0 18px 0;font-size:34px;line-height:1.15;color:#1f2937;">Kde přespat u Orlíku na rybářský víkend</h1>') !== 1
            || substr_count($html, self::original_first()) !== 1
            || substr_count($html, self::original_second()) !== 1) return $html;

        $first = str_replace('Zjistit cenu a termín', 'Prohlédnout pobyty ve středních Čechách', self::original_first());
        $second = self::replacement_second();
        $note = self::trust_note();
        $changed = str_replace(self::original_first(), $first, $html);
        $changed = str_replace(self::original_second(), $second.$note, $changed);
        // Exact two CTA destinations: expired offer gone, first URL untouched.
        // No other article content, external links or storage are rewritten.
        return (substr_count($changed, self::MARKER) === 1
            && substr_count($changed, self::SLEVOMAT_CATEGORY) === 1
            && strpos($changed, self::EXPIRED_OFFER) === false
            && substr_count($changed, htmlspecialchars(self::HPORTAL, ENT_QUOTES, 'UTF-8')) === 1
            && substr_count($changed, 'Liškův Mlýn u Orlíku – prověřit pobyt') === 1)
            ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Orlik_Expired_Stay_CTA_119551_V1::boot();
