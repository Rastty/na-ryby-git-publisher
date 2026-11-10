<?php
/**
 * Exact WordPress article #118455: existing-equipment first, before upgrading.
 * Rendered content only. No WordPress source writes, merchant requests or
 * modification of existing outbound destinations/telemetry.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Budget_Owner_First_118455_V1 {
    const ID=118455;
    const SLUG='nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz';
    const URL='https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/';
    const SOURCE_SHA='ff506044d0b7f494a901085c4fe01b5f44706afb69a03a8733329772b9ef8d82';
    const SOURCE_BYTES=39987;
    const MODIFIED_GMT='2026-11-08 10:21:01';
    const TRUST='<!-- nry-sonar-budget-trust-118455-v1 -->';
    const INTRO='Echolot do 15 000 Kč: jak vybrat celou funkční sestavu';
    const MARKER='data-nry-budget-owner-first="118455-20261110-v1"';
    const PRACTICAL='https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/';
    const PANEL='<aside data-nry-budget-owner-first="118455-20261110-v1" aria-label="Než koupíte nový echolot">'
        .'<p><strong>Máte už echolot a nevíte, zda potřebujete nový?</strong> '
        .'Než začnete porovnávat dražší displeje, prověřte polohu sondy, '
        .'citlivost, rozsah hloubky a napájení podle návodu ke své sestavě. '
        .'Projděte si <a href="https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/">'
        .'praktický návod k používání echolotu</a>. '
        .'Pokud vám potom stále chybí konkrétní funkce, vybírejte podle '
        .'kompatibilní sondy a ceny celého setu, nikoli jen názvu displeje.</p></aside>';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')
            && function_exists('get_queried_object_id')) {
            add_filter('the_content',array(__CLASS__,'render'),60);
        }
    }
    private static function eligible() {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id()!==self::ID
            || !function_exists('is_main_query') || !is_main_query()
            || !function_exists('in_the_loop') || !in_the_loop()
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink(self::ID)!==self::URL
            || !function_exists('get_post')) return false;
        $p=get_post(self::ID);
        return is_object($p) && (int)($p->ID??0)===self::ID
            && ($p->post_type??'')==='post'
            && ($p->post_status??'')==='publish'
            && ($p->post_name??'')===self::SLUG
            && ($p->post_modified_gmt??'')===self::MODIFIED_GMT
            && is_string($p->post_content??null)
            && strlen($p->post_content)===self::SOURCE_BYTES
            && hash_equals(self::SOURCE_SHA,hash('sha256',$p->post_content));
    }
    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',$html,$m)===false)
            return null;
        return $m[2];
    }
    public static function transform($html) {
        if (!is_string($html) || strlen($html)<3000
            || strpos($html,self::TRUST)!==0
            || substr_count($html,self::TRUST)!==1
            || strpos($html,self::MARKER)!==false
            || strpos($html,self::PRACTICAL)!==false
            || substr_count($html,self::INTRO)!==1
            || strpos($html,self::INTRO)>1800) return $html;
        $before=self::hrefs($html);
        if ($before===null) return $html;
        $at=strlen(self::TRUST);
        $updated=substr($html,0,$at)."\n".self::PANEL.substr($html,$at);
        $links=self::hrefs($updated);
        if ($links===null || substr_count($updated,self::MARKER)!==1) return $html;
        $original=array_values(array_filter($links,function($href) {
            return $href!==self::PRACTICAL;
        }));
        if ($original!==$before
            || count($links)!==count($before)+1) return $html;
        return $updated;
    }
    public static function render($html) {
        return self::eligible()?self::transform($html):$html;
    }
}
NRY_Budget_Owner_First_118455_V1::boot();
