<?php
/**
 * Three near-top navigation shortcuts to EXISTING buyer examples in article
 * 118455. Render-only and SHA-bound; no offer creation, merchant URL, product
 * feed, stock assertion, WP write or network request.
 * Rollback: remove the require in na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sonar_Budget_Scenario_Jumps_118455_V1 {
    const ID = 118455;
    const SLUG = 'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz';
    const URL = 'https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/';
    const SOURCE_SHA = 'ff506044d0b7f494a901085c4fe01b5f44706afb69a03a8733329772b9ef8d82';
    const SOURCE_BYTES = 39987;
    const MODIFIED_GMT = '2026-11-08 10:21:01';
    const OWNER_MARKER = 'data-nry-budget-owner-first="118455-20261110-v1"';
    const TRUST_MARKER = '<!-- nry-sonar-budget-trust-118455-v1 -->';
    const MARKER = 'data-nry-budget-scenario-jumps="118455-20261115-v1"';

    private static function scenarios() {
        return array(
            'Lowrance Eagle 4X se sondou Bullet (bonusy ověřte)' => array(
                'id'=>'nry-budget-eagle4x', 'label'=>'Jednoduchý základ: Eagle 4X'),
            'Lowrance Hook Reveal 5, 50/200 HDI ROW' => array(
                'id'=>'nry-budget-hook5', 'label'=>'Mapování: Hook Reveal 5'),
            'Lowrance Eagle 7 se sondou SplitShot HD (balení ověřte)' => array(
                'id'=>'nry-budget-eagle7', 'label'=>'Větší displej: Eagle 7'),
        );
    }

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')) {
            add_filter('the_content', array(__CLASS__, 'render'), 65);
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
        $post=get_post(self::ID);
        return is_object($post) && (int)($post->ID??0)===self::ID
            && ($post->post_type??'')==='post'
            && ($post->post_status??'')==='publish'
            && ($post->post_name??'')===self::SLUG
            && ($post->post_modified_gmt??'')===self::MODIFIED_GMT
            && is_string($post->post_content??null)
            && strlen($post->post_content)===self::SOURCE_BYTES
            && hash_equals(self::SOURCE_SHA,hash('sha256',$post->post_content));
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',
            $html,$matches)===false) return null;
        return $matches[2];
    }

    private static function nav() {
        $html='<nav '.self::MARKER.' aria-label="Rychlý výběr podle použití"'
            .' style="margin:12px 0 20px;padding:12px 14px;border:1px solid #d4e3da;border-radius:10px;background:#f7faf8">'
            .'<p style="margin:0 0 8px"><strong>Přejít rovnou k příkladu sestavy:</strong> '
            .'vyberte, co je pro vás důležité. Cenu a dostupnost vždy ověřte u prodejce.</p>'
            .'<ul style="list-style:none;margin:0;padding:0;display:flex;flex-wrap:wrap;gap:8px">';
        foreach (self::scenarios() as $option) {
            $html.='<li style="margin:0"><a href="#'.$option['id'].'"'
                .' style="display:inline-block;border:1px solid #a7c8b3;border-radius:6px;padding:7px 10px;background:#fff">'
                .htmlspecialchars($option['label'],ENT_QUOTES,'UTF-8').'</a></li>';
        }
        return $html.'</ul></nav>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html)<3000
            || substr_count($html,self::OWNER_MARKER)!==1
            || substr_count($html,self::TRUST_MARKER)!==1
            || strpos($html,self::MARKER)!==false) return $html;
        $existing=self::hrefs($html);
        if ($existing===null) return $html;
        if (preg_match_all('~<aside\b(?=[^>]*'.preg_quote(self::OWNER_MARKER,'~')
            .')[^>]*>.*?</aside>~is',$html,$asides,PREG_OFFSET_CAPTURE)!==1) return $html;
        if (preg_match_all('~<h3\b[^>]*>.*?</h3>~is',$html,$headings,
            PREG_OFFSET_CAPTURE)===false) return $html;
        $matches=array();
        foreach ($headings[0] as $heading) {
            $visible=trim(preg_replace('/[\s\x{00A0}\x{202F}]+/u',' ',
                html_entity_decode(strip_tags($heading[0]),ENT_QUOTES|ENT_HTML5,'UTF-8')));
            $scenarios=self::scenarios();
            if (!isset($scenarios[$visible])) continue;
            if (!preg_match('~^<h3\b[^>]*>~i',$heading[0],$opening)
                || preg_match('~\bid\s*=~i',$opening[0])) return $html;
            $matches[$visible][]=$heading;
        }
        $edits=array();
        foreach (self::scenarios() as $title=>$option) {
            if (count($matches[$title]??array())!==1
                || strpos($html,'id="'.$option['id'].'"')!==false) return $html;
            $heading=$matches[$title][0];
            $changed=preg_replace('~^<h3\b([^>]*)>~i',
                '<h3$1 id="'.$option['id'].'" style="scroll-margin-top:96px">',
                $heading[0],1,$count);
            if ($count!==1) return $html;
            $edits[]=array($heading[1],strlen($heading[0]),$changed);
        }
        // Rewrite from the end so each independently verified heading offset
        // remains exact. Only h3 opening tags acquire anchor IDs.
        usort($edits,function($a,$b) { return $b[0]-$a[0]; });
        $updated=$html;
        foreach ($edits as $edit) {
            $updated=substr_replace($updated,$edit[2],$edit[0],$edit[1]);
        }
        $aside=$asides[0][0][0];
        $at=strpos($updated,$aside);
        if ($at===false || substr_count($updated,$aside)!==1 || $at>12000)
            return $html;
        $updated=substr_replace($updated,self::nav(),$at+strlen($aside),0);
        $new=self::hrefs($updated);
        if ($new===null || substr_count($updated,self::MARKER)!==1
            || count($new)!==count($existing)+3) return $html;
        $anchors=array_map(function($x) {return '#'.$x['id'];},array_values(self::scenarios()));
        $preserved=array_values(array_filter($new,function($href) use ($anchors) {
            return !in_array($href,$anchors,true);
        }));
        if ($preserved!==$existing) return $html;
        foreach ($anchors as $anchor) {
            if (substr_count($updated,'href="'.$anchor.'"')!==1) return $html;
        }
        return $updated;
    }

    public static function render($html) {
        return self::eligible()?self::transform($html):$html;
    }
}
NRY_Sonar_Budget_Scenario_Jumps_118455_V1::boot();
