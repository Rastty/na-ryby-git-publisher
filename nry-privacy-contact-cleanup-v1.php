<?php
/**
 * Render-only correction of three public editing mistakes on privacy page #115409.
 *
 * The page is NOT certified legally complete by this copy repair. WordPress
 * source, controller identity, cookies, affiliate terms and external links are
 * not rewritten. Roll back by removing this plugin include.
 *
 * Public source preflight: 2026-11-11, exact identity, modified GMT,
 * two visible wrong contact strings / four REST-rendered occurrences, and one
 * literal publisher prompt. Homepage independently displays contact to use.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Privacy_Contact_Copy_115409_V1 {
    const ID = 115409;
    const SLUG = 'ochrana-osobnich-udaju-gdpr';
    const URL = 'https://na-ryby.eu/ochrana-osobnich-udaju-gdpr/';
    const MODIFIED_GMT = '2026-02-16 07:29:16';
    const OLD_CONTACT = 'xfit.redakce@gmail.com';
    const NEW_CONTACT = 'na.ryby.eu@gmail.com';
    const DRAFT = '(Volitelně: IČO, sídlo / adresa podnikání – pokud máte)';
    const MARKER = '<span hidden data-nry-privacy-contact-proof="20261111-v1"></span>';
    const HEAD = 'Kdo je správce osobních údajů';
    const RIGHTS = 'Jak nás můžete kontaktovat';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_page')
            && function_exists('get_queried_object_id')) {
            add_filter('the_content', array(__CLASS__,'render'), 56);
        }
    }
    private static function eligible() {
        if (!is_page(self::ID)
            || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_main_query') || !is_main_query()
            || !function_exists('in_the_loop') || !in_the_loop()
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'),'/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $post=get_post(self::ID);
        return is_object($post)
            && (int)($post->ID ?? 0)===self::ID
            && ($post->post_type ?? '')==='page'
            && ($post->post_status ?? '')==='publish'
            && ($post->post_name ?? '')===self::SLUG
            && ($post->post_modified_gmt ?? '')===self::MODIFIED_GMT;
    }

    private static function links($html) {
        if (preg_match_all('~<a\\b[^>]*\\bhref\\s*=\\s*(["\\x27])([^"\\x27]*)\\1~is',
            $html, $matches) === false) return null;
        return $matches[2];
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html)<3200
            || substr_count($html,self::MARKER)!==0
            || substr_count($html,self::OLD_CONTACT)!==4
            || substr_count($html,self::NEW_CONTACT)!==0
            || substr_count($html,self::DRAFT)!==1
            || substr_count($html,self::HEAD)!==1
            || substr_count($html,self::RIGHTS)!==1) return $html;
        $original=self::links($html);
        if ($original === null) return $html;
        $updated=str_replace(self::OLD_CONTACT,self::NEW_CONTACT,$html);
        $updated=str_replace(self::DRAFT,self::MARKER,$updated);
        $links=self::links($updated);
        if ($links === null || substr_count($updated,self::MARKER)!==1
            || substr_count($updated,self::OLD_CONTACT)!==0
            || substr_count($updated,self::NEW_CONTACT)!==4
            || count($links)!==count($original)) return $html;
        foreach($original as $i=>$old) {
            // Only original mailto links to the publicly wrong contact may
            // change. All existing external, internal and affiliate hrefs
            // must remain byte-for-byte identical.
            if ($links[$i]!==str_replace(self::OLD_CONTACT,self::NEW_CONTACT,$old))
                return $html;
        }
        return $updated;
    }
    public static function render($html) {
        return self::eligible()?self::transform($html):$html;
    }
}
NRY_Privacy_Contact_Copy_115409_V1::boot();
