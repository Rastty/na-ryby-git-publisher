<?php
/**
 * P0 safety-only, reversible public-display correction for the exact winter
 * spinning post #45070. Does NOT mutate WordPress post_content, metadata,
 * canonicals, links, shortcodes, paid paths, or protected lifecycle records.
 *
 * Strong fail-closed identity: a future editor or other page is untouched.
 * Existing public text dangerously instructed readers to ignore ice warnings.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Winter_Ice_Safety_45070 {
    const ID=45070;
    const SLUG='jak-si-vybavit-vybavu-pro-zimni-privlac-na-ryby';
    const URL='https://na-ryby.eu/jak-si-vybavit-vybavu-pro-zimni-privlac-na-ryby/';
    const SOURCE_SHA='157c1443e5a0908896fafea014074abb5481e57b7df6b47032490bd43ee7e790';
    const SOURCE_BYTES=10099;
    const SOURCE_GMT='2023-05-07 04:10:20';
    const MARKER='data-nry-winter-ice-safety="45070-20261109-v1"';
    const OLD_ICE='Pamatujte, že zimní přívlač na ryby se provádí na zamrzlých jezerech a rybnících.';
    const NEW_ICE='Zimní přívlač se provozuje vláčením nástrahy v otevřené vodě, kde je lov touto metodou v daném období a revíru povolen; není důvod kvůli ní vstupovat na zamrzlou vodní plochu.';
    const OLD_WARNING='Je proto důležité nerespektovat varovné informace o nebezpečnosti';
    const NEW_WARNING='Je proto nutné respektovat všechna varování před nebezpečným ledem';
    const OLD_DEFINITION='Toto speciální rybaření probíhá na zamrzlých vodách, které jsou kryty ledem.';
    const NEW_DEFINITION='Zimní přívlač z bezpečně přístupného břehu se od lovu pod ledem liší; nejprve ověřte pravidla příslušného revíru, období lovu a hájení jednotlivých druhů.';
    public static function boot() {
        // Standalone PHP publisher regression harnesses do not define WP hooks.
        if (function_exists('add_filter'))
            add_filter('the_content',array(__CLASS__,'render'),8);
    }
    /** Pure presentation transform. A single missing marker leaves source unchanged. */
    public static function transform($html) {
        if (!is_string($html) || strlen($html)<1500 || strlen($html)>250000
            || strpos($html,self::MARKER)!==false) return $html;
        $pairs=array(
            self::OLD_ICE=>self::NEW_ICE,
            self::OLD_WARNING=>self::NEW_WARNING,
            self::OLD_DEFINITION=>self::NEW_DEFINITION
        );
        foreach($pairs as $old=>$new) {
            if(substr_count($html,$old)!==1 || strpos($html,$new)!==false)
                return $html;
        }
        $next=str_replace(array_keys($pairs),array_values($pairs),$html);
        // Visible before the first sentence, without hiding the historic post
        // or substituting an invented merchant product/price.
        $note='<aside '.self::MARKER.' role="note" aria-label="Bezpečnost při zimní přívlači">'
            .'<p><strong>Bezpečnost a upřesnění:</strong> Zimní přívlač na otevřené vodě není lov pod ledem. '
            .'Nevstupujte kvůli rybolovu na zamrzlé plochy, respektujte varování a zákazy vstupu. '
            .'Před výpravou ověřte aktuální pravidla a období lovu v konkrétním revíru. '
            .'Starší části tohoto článku směšují dvě odlišné techniky; vybavení pro lov pod ledem '
            .'nepovažujte za automatické doporučení pro přívlač ze břehu.</p></aside>';
        $next=$note.$next;
        if(substr_count($next,self::MARKER)!==1
            || strpos($next,self::OLD_WARNING)!==false
            || strpos($next,self::OLD_ICE)!==false
            || strpos($next,self::OLD_DEFINITION)!==false) return $html;
        // Preserve every pre-existing merchant/internal href, image src and
        // shortcode token, including their original order and exact bytes.
        foreach(array(
            '~\\bhref\\s*=\\s*([\"\\x27])(.*?)\\1~is',
            '~\\bsrc\\s*=\\s*([\"\\x27])(.*?)\\1~is',
            '~\\[[a-z][^\\]\\r\\n]*\\]~i'
        ) as $rx) {
            if(preg_match_all($rx,$html,$before)===false
               || preg_match_all($rx,$next,$after)===false
               || ($before[0]??array())!==($after[0]??array())) return $html;
        }
        return $next;
    }
    public static function render($content) {
        if(!function_exists('is_singular') || !is_singular('post')
            || !function_exists('in_the_loop') || !in_the_loop()
            || !function_exists('get_the_ID') || (int)get_the_ID()!==self::ID
            || !function_exists('home_url') || rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
            || !function_exists('get_post') || !function_exists('get_permalink'))
            return $content;
        $p=get_post(self::ID);
        if(!is_object($p) || (int)($p->ID??0)!==self::ID
            || ($p->post_type??'')!=='post'
            || ($p->post_status??'')!=='publish'
            || ($p->post_name??'')!==self::SLUG
            || get_permalink(self::ID)!==self::URL
            || !is_string($p->post_content??null)
            || strlen($p->post_content)!==self::SOURCE_BYTES
            || !hash_equals(self::SOURCE_SHA,hash('sha256',$p->post_content))
            || ($p->post_modified_gmt??'')!==self::SOURCE_GMT)
            return $content;
        return self::transform($content);
    }
}
NRY_Winter_Ice_Safety_45070::boot();
