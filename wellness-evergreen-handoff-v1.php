<?php
/**
 * Exact-source-locked, editorial first-party buyer handoffs from two indexed
 * wellness fishing guides. No new merchant offers or WP writes are introduced.
 * Source IDs, modified GMT and SHA-256 fingerprints independently verified
 * in GitHub public GET run 45013057853; never use rendered REST HTML as source.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Wellness_Evergreen_Handoff_V1 {
    const ORIGIN = 'https://na-ryby.eu';
    const MARKER = 'data-nry-wellness-evergreen-handoff="v1"';
    const SRUB = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const ROUTES = array(
        'wellness-a-rybolov-v-jednom-luxusni-ubytovani-pro-narocne-rybare' => array(
            'id'=>61382, 'modified'=>'2026-04-12 11:03:12', 'sha'=>'99948a7a555de0ff91d6caa55ef66992fcef90d7e88dc6a15b73710598dfc1ae', 'role'=>'wellness',
            'sourceAnchor'=>'Když se řekne rybářský pobyt s wellness',
        ),
        'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice' => array(
            'id'=>69482, 'modified'=>'2026-04-17 07:34:03', 'sha'=>'c1384208bd584bb72326fbd4684133ffb3252b626c4e08087eb45de8c052f495', 'role'=>'private',
            'sourceAnchor'=>'Chcete si odpočinout, ale nechcete jen ležet u bazénu?',
        ),
    );

    private static function eligible() {
        foreach (array('is_single','is_singular','get_queried_object_id','get_post',
            'get_permalink','home_url','is_admin','is_preview') as $f) {
            if (!function_exists($f)) return null;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='GET'
            || !is_singular('post') || is_admin() || is_preview()
            || (function_exists('is_feed') && is_feed())
            || (function_exists('is_customize_preview') && is_customize_preview())
            || rtrim(home_url('/'), '/')!==self::ORIGIN) return null;
        $id=(int)get_queried_object_id();
        if ($id<=0) return null;
        $post=get_post($id);
        if (!is_object($post) || (int)($post->ID ?? 0)!==$id
            || ($post->post_type ?? '')!=='post'
            || ($post->post_status ?? '')!=='publish'
            || !is_string($post->post_content ?? null)) return null;
        $slug=$post->post_name ?? '';
        if (!is_string($slug) || !isset(self::ROUTES[$slug])) return null;
        $set=self::ROUTES[$slug];
        if ($id!==$set['id']
            || !is_string($set['sha']) || !preg_match('/^[a-f0-9]{64}$/D', $set['sha'])
            || !is_string($set['modified']) || strlen($set['modified'])!==19
            || !is_single($slug) || get_permalink($id)!==self::ORIGIN.'/'.$slug.'/'
            || ($post->post_modified_gmt ?? null)!==$set['modified']
            || !hash_equals($set['sha'],hash('sha256',$post->post_content))) return null;
        return $set;
    }

    public static function panel($role) {
        if ($role==='wellness') {
            $heading='Konkrétní wellness srub: ověřte pobyt u Vranova';
            $copy='Hledáte saunu a odpočinek po pobytu u vody? Samostatně popsaný Rybářský wellness srub má podle nabídky saunu a koupací sud. Aktuální termín a vybavení je třeba ověřit. Nižší hladina Vranovské přehrady může výrazně omezit rybolov; povolenka, soukromé lovné místo ani loď nejsou automaticky zajištěny.';
            $links=array(
                array('srub',self::SRUB,'Podrobnosti o konkrétním wellness srubu'),
                array('stays',self::STAYS,'Porovnat další rybářské pobyty'),
            );
        } elseif ($role==='private') {
            $heading='Chcete skutečně rybařit? Ověřte konkrétní variantu pobytu';
            $copy='Ne každé wellness ubytování zahrnuje soukromé rybaření. V našem přehledu najdete i vybranou variantu pobytu se soukromým lovem chyť a pusť; oprávnění, rozsah a volný termín se liší podle zvolené nabídky. Alternativní Wellness srub u Vranova má saunu, nikoli potvrzenou garanci soukromého lovu, a na přehradě může být rybaření omezeno hladinou vody.';
            $links=array(
                array('stays',self::STAYS,'Zobrazit rybářské varianty pobytů'),
                array('srub',self::SRUB,'Alternativa: Wellness srub u Vranova'),
            );
        } else return '';
        $actions='';
        foreach ($links as $link) {
            $actions.='<li><a data-nry-wellness-path="'.$link[0].'" href="'.$link[1].'">'
                .htmlspecialchars($link[2],ENT_QUOTES,'UTF-8').'</a></li>';
        }
        return '<section class="nry-wellness-handoff" '.self::MARKER
            .' data-nry-wellness-role="'.$role.'" aria-label="Ověřit konkrétní rybářský pobyt">'
            .'<p class="nry-wellness-handoff__label">Z obecného průvodce ke konkrétní nabídce</p>'
            .'<h2>'.$heading.'</h2><p>'.$copy.'</p>'
            .'<ul class="nry-wellness-handoff__actions">'.$actions.'</ul>'
            .'<p class="nry-wellness-handoff__note">Odkazy vedou na naše podrobné stránky. '
            .'Aktuální cenu, volné termíny, pravidla lovu a povolenku ověřte před rezervací. '
            .'Některé následné nabídky používají označený partnerský odkaz.</p></section>';
    }
    private static function hrefs($html) {
        if (preg_match_all('~<a\\b[^>]*\\bhref\\s*=\\s*(["\\x27])([^"\\x27]*)\\1~is',$html,$m)===false) return null;
        return $m[2];
    }
    public static function transform($html,$set) {
        if (!is_string($html) || !is_array($set)
            || strlen($html)<1500 || strlen($html)>350000
            || strpos($html,self::MARKER)!==false
            || !isset($set['sourceAnchor'],$set['role'])
            || substr_count($html,$set['sourceAnchor'])!==1
            || strpos($html,'href="'.self::SRUB.'"')!==false
            || strpos($html,'href="'.self::STAYS.'"')!==false) return $html;
        $old=self::hrefs($html); $block=self::panel($set['role']);
        if ($old===null || $block==='' || substr_count($block,self::MARKER)!==1) return $html;
        $new=$block."\n".$html;
        $newHrefs=self::hrefs($new);
        $expected=$set['role']==='private' ? array(self::STAYS,self::SRUB) : array(self::SRUB,self::STAYS);
        return $newHrefs!==null && array_slice($newHrefs,0,2)===$expected
            && array_slice($newHrefs,2)===$old
            && substr_count($new,self::MARKER)===1
            && str_replace($block."\n",'',$new)===$html ? $new : $html;
    }
    public static function render($html) {
        $set=self::eligible();
        return $set ? self::transform($html,$set) : $html;
    }
    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-wellness-evergreen-handoff-v1',
            plugins_url('assets/nry-wellness-evergreen-handoff-v1.css',__FILE__),
            array(),'1.0.0');
    }
    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')
            && function_exists('get_post')) {
            add_filter('the_content',__CLASS__.'::render',90);
        }
        if (function_exists('add_action') && function_exists('is_single')
            && function_exists('get_post')) {
            add_action('wp_enqueue_scripts',__CLASS__.'::enqueue',30);
        }
    }
}
NRY_Wellness_Evergreen_Handoff_V1::boot();
