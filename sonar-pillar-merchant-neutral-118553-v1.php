<?php
/**
 * Exact sonar pillar #118553: replace one unproven merchant "broader and
 * clearer offer right now" claim. No WordPress writes, no changed merchant
 * destinations, prices, analytics, tracking redirects, or sitewide scope.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Sonar_Pillar_Merchant_Neutral_118553_V1 {
    const ID=118553;
    const SLUG='jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce';
    const URL='https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';
    const GMT='2026-11-04 11:27:17';
    const SOURCE_MARKER='data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"';
    const MARKER='data-nry-sonar-merchant-neutral="v1"';
    const OLD='Tady aktuálně dává smysl širší a přehlednější nabídka z Chyť a pusť.';
    const REPLACEMENT='<span data-nry-sonar-merchant-neutral="v1"></span>'
        .' Odkaz do kategorie je možností prohlédnout si modely, nikoli důkazem '
        .'dnešní nejširší nabídky nebo nejvýhodnější ceny. '
        .'Před nákupem porovnejte přesnou variantu, sondu, cenu celé sestavy a dostupnost.';

    public static function boot(){
        if (function_exists('add_filter')&&function_exists('is_single')&&
            function_exists('get_queried_object_id')){
            add_filter('the_content',array(__CLASS__,'render'),56);
        }
    }
    private static function eligible(){
        if (!function_exists('is_single')||!is_single(self::ID)||
            !function_exists('is_singular')||!is_singular('post')||
            (int)get_queried_object_id()!==self::ID||
            !function_exists('is_main_query')||!is_main_query()||
            !function_exists('in_the_loop')||!in_the_loop()||
            !function_exists('is_admin')||is_admin()||
            !function_exists('is_preview')||is_preview()||
            !function_exists('home_url')||
            rtrim(home_url('/'),'/')!=='https://na-ryby.eu'||
            !function_exists('get_permalink')||get_permalink(self::ID)!==self::URL||
            !function_exists('get_post'))return false;
        $p=get_post(self::ID);
        return is_object($p)&&(int)($p->ID??0)===self::ID&&
            ($p->post_type??'')==='post'&&($p->post_status??'')==='publish'&&
            ($p->post_name??'')===self::SLUG&&
            ($p->post_modified_gmt??'')===self::GMT;
    }
    private static function hrefs($html){
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',$html,$m)===false)
            return null;
        return $m[2];
    }
    public static function transform($html){
        if (!is_string($html)||strlen($html)<600||
            substr_count($html,self::SOURCE_MARKER)!==1||
            substr_count($html,self::OLD)!==1||
            strpos($html,self::MARKER)!==false||
            strpos($html,'Jak vybrat vhodný typ echolotu')===false||
            strpos($html,'Jak vybrat echolot pro rybaření: Kompletní průvodce funkcemi')===false)
            return $html;
        $position=strpos($html,self::OLD);
        $head=strpos($html,'Jak vybrat echolot pro rybaření: Kompletní průvodce funkcemi');
        if ($position===false||$head===false||$position<$head) return $html;
        $open=strrpos(substr($html,0,$position),'<p');
        $close=strpos($html,'</p>',$position);
        if ($open===false||$close===false||$position-$open>1200||
            $close-$position>400)return $html;
        // Do not rewrite embedded hyperlinks, HTML-heavy promo paragraphs or
        // an anchor containing the claim. Preserve every preexisting href.
        $p=substr($html,$open,$close+4-$open);
        $openEnd=strpos($p,'>');
        if ($openEnd===false)return $html;
        $body=substr($p,$openEnd+1,-4);
        // The original sentence must be in a plain paragraph. An editor
        // wrapping it in a link, emphasis or shortcode forces re-review.
        if (preg_match('~<[^>]+>~s',$body))return $html;
        $before=self::hrefs($html);
        if ($before===null)return $html;
        $updated=substr_replace($html,self::REPLACEMENT,$position,strlen(self::OLD));
        if (substr_count($updated,self::MARKER)!==1||
            self::hrefs($updated)!==$before||
            str_replace(self::REPLACEMENT,self::OLD,$updated)!==$html)
            return $html;
        return $updated;
    }
    public static function render($html){
        return self::eligible()?self::transform($html):$html;
    }
}
NRY_Sonar_Pillar_Merchant_Neutral_118553_V1::boot();
