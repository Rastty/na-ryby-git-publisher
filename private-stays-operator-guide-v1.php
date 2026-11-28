<?php
/**
 * Exact legacy public text repair and a substantive operator-backed guide.
 * Render-only, retains existing WordPress URL, stored body, media and all
 * unrelated links; replaces only three explicitly identified historical
 * non-affiliate references and their unverified claims.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Private_Stays_Operator_Guide_V1 {
    const SLUG='nejlepsi-soukrome-reviry-s-ubytovanim-v-ceske-republice-zazitek-ktery-stoji-za-to';
    const TITLE='Nejlepší soukromé revíry s ubytováním v České republice: Zážitek, který stojí za to';
    const DATE='2024-05-04';
    const MARKER='data-nry-private-stays-operator-guide="v1"';
    const LATIN='Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis vehicula felis nunc, ac feugiat dui consequat sit amet. Proin vestibulum dapibus ante, ac ultricies nisi.';
    public static function pairs() {
        return array(
            self::LATIN=>'Při výběru soukromé vody s ubytováním je nejdůležitější potvrzené lovné místo, zázemí a pravidla. Níže najdete konkrétní možnosti a postup před rezervací.',
            'Revír Rybářství Pecka'=>'Rybník Velký – Chotilsko',
            'Revír Rybářství Pecka se nachází v malebném prostředí Podkrkonoší. Nabízí možnost rybaření na rybníku s bohatou rybí obsádkou a současně zde můžete využít ubytování v komfortních chatkách nebo penzionu.'=>'V areálu Velkého chotilského rybníka nabízí provozovatel na vybraných stanovištích chatky. Porovnejte konkrétní molo, vybavení a celkovou cenu rybolovu i pobytu.',
            'Revír U Rybníka Zlatník'=>'Grunty – okolí Brna',
            'Revír U Rybníka Zlatník se nachází v malebné krajině jižních Čech. Tato lokalita je ideální pro odpočinek a relaxaci v klidném prostředí. Rybáři zde najdou bohaté zásoby pstruhů a kaprů.'=>'Grunty jsou kaprařská voda mezi Jiříkovicemi a Ponětovicemi. Provozovatel uvádí možnost ubytování v chatce a zázemí pro bivaky; ověřte konkrétní místo a dostupnost.',
            'Revír Rybniční komplex Babín'=>'Rybník U Hráze – Vysoká Libyně',
            'Rybniční komplex Babín se nachází východně od Prahy a je ideálním místem pro rodinnou dovolenou. Zde si můžete užít rybaření, cyklistiku nebo procházky po okolních lesích.'=>'Farma Janko nabízí na rybníku U Hráze rekreační rybolov chyť a pusť a jednoduchou chatku bez lůžek na vybraném stanovišti. Ověřte vybavení a termín přímo s farmou.',
            'Soukromé revíry jsou místa v přírodě, kde si můžete pronajmout ubytování a strávit čas v klidu a pohodlí.'=>'Soukromě spravované vody mají různé právní režimy a pravidla. Ubytování není jejich automatickou součástí; předem ověřte oprávnění k lovu, podmínky rezervace a skutečné zázemí.',
            'V České republice jsou soukromé revíry s ubytováním velmi oblíbené, zejména u rybářů, kteří hledají klidné a přírodní prostředí pro své rybářské aktivity. Mezi populární revíry patří například revír Božanov, kde najdete rybník s bohatým rybím fondem a možností ubytování v pohodlných chatkách.'=>'V Česku lze porovnat různé typy pobytu u soukromě spravované vody: chatku s lůžky u vybraného stanoviště, jednodušší přístřešek bez lůžek nebo vlastní bivak. Podmínky se liší podle provozovatele; výše uvedené příklady mají dohledatelné vlastní stránky.',
        );
    }
    private static function eligible($loop=false) {
        if (!is_singular('post') || is_admin() || is_preview() || is_feed()
            || (function_exists('is_customize_preview') && is_customize_preview())
            || ($_SERVER['REQUEST_METHOD']??'GET')!=='GET'
            || rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
            || ($loop && (!in_the_loop() || !is_main_query()))) return false;
        $id=(int)get_queried_object_id();$p=get_post($id);
        if (!is_object($p) || (int)$p->ID!==$id || !is_single($id)
            || $p->post_type!=='post' || $p->post_status!=='publish'
            || $p->post_name!==self::SLUG || get_permalink($id)!=='https://na-ryby.eu/'.self::SLUG.'/'
            || !is_string($p->post_content) || substr($p->post_date,0,10)!==self::DATE
            || $p->post_title!==self::TITLE) return false;
        foreach(array(self::LATIN,'rybarstvipecka.cz','rybnikzlatnik.cz','rybnikbabincz','revír Božanov') as $a)
            if(stripos($p->post_content,$a)===false)return false;
        return true;
    }
    public static function transform($html) {
        if (!is_string($html)||strlen($html)<1000||strlen($html)>200000||strpos($html,self::MARKER)!==false)return $html;
        $pairs=self::pairs();
        // Longer exact source paragraphs must be matched before the headings
        // sharing their first words. Reject drift and ambiguous occurrences.
        uksort($pairs,function($a,$b){return strlen($b)<=>strlen($a);});
        $out=$html;
        foreach($pairs as $old=>$new) {
            if(substr_count($out,$old)!==1)return $html;
            $out=str_replace($old,$new,$out);
        }
        $heading='~<h1\b([^>]*)>\s*'.preg_quote(self::TITLE,'~').'\s*</h1\s*>~iu';
        if(preg_match_all($heading,$out)!==1)return $html;
        $out=preg_replace($heading,'<h2$1>Jak vybrat soukromou vodu s ubytováním</h2>',$out,1);
        $links=array(
            'www.rybarstvipecka.cz'=>array('rybarstvipecka.cz','https://www.rybnikvelky.cz/cz/sluzby-a-cenik/','Služby a ceník Rybníku Velký'),
            'www.rybnikzlatnik.cz'=>array('rybnikzlatnik.cz','https://www.grunty.cz/','Oficiální stránky Grunty'),
            'www.rybnikbabincz'=>array('rybnikbabincz','https://www.farmajanko.cz/rybnik/','Farma Janko – informace o rybníku'),
        );
        $counts=array_fill_keys(array_keys($links),0);
        $out=preg_replace_callback('~<a\b[^>]*>.*?</a\s*>~isu',function($m)use($links,&$counts){
            $a=$m[0];$label=trim(html_entity_decode(strip_tags($a),ENT_QUOTES|ENT_HTML5,'UTF-8'));
            if(!isset($links[$label]))return $a;
            $r=$links[$label];
            if(!preg_match('~\bhref\s*=\s*(["\x27])([^"\x27]*)\1~i',$a,$href)
                || stripos($href[2],$r[0])===false
                || preg_match('~affiliate|dognet|dpbolvw|tkqlhce|jdoqocy|anrdoezrs|kqzyfj|sponsored~i',$a))return $a;
            $counts[$label]++;
            return '<a href="'.$r[1].'">'.$r[2].'</a>';
        },$out);
        if(!is_string($out)||array_values($counts)!==array(1,1,1))return $html;
        // All image src and non-target href values must be identical.
        $collect=function($s){
            preg_match_all('~(?:href|src)\s*=\s*(["\x27])([^"\x27]*)\1~i',$s,$m);
            return $m[2];
        };
        $before=$collect($html);$after=$collect($out);
        foreach($before as &$url)foreach($links as $r)if(stripos($url,$r[0])!==false){$url=$r[1];break;}
        unset($url);
        if($before!==$after)return $html;
        foreach(array('Lorem ipsum dolor sit amet','rybarstvipecka.cz','rybnikzlatnik.cz','rybnikbabincz','revír Božanov') as $bad)
            if(stripos($out,$bad)!==false)return $html;
        $guide=file_get_contents(__DIR__.'/editorial/private-stays-operator-guide-20261128.html');
        if(!is_string($guide)||substr_count($guide,self::MARKER)!==1||substr_count($guide,'data-nry-private-operator=')!==3)return $html;
        return $guide."\n".$out;
    }
    public static function render($html){return self::eligible(true)?self::transform($html):$html;}
    public static function enqueue(){
        if(!self::eligible()||!function_exists('wp_enqueue_style')||!function_exists('plugins_url'))return;
        wp_enqueue_style('nry-gear-archive-entry-v1',plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');
    }
    public static function boot(){
        if(!function_exists('is_single')||!function_exists('is_singular')||!function_exists('add_filter')||!function_exists('add_action'))return;
        add_filter('the_content',array(__CLASS__,'render'),80);
        add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),27);
    }
}
NRY_Private_Stays_Operator_Guide_V1::boot();
