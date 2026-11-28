<?php
/** Technical comparison and direct in-page paths to existing product cards.
 * Exact source identity; no stored edits, new merchant links or tracking rewrites.
 */
if (!defined('ABSPATH')) exit;
final class NRY_Bedchair_Budget_5000_V1 {
    const SLUG='nejlepsi-rybarska-lehatka-do-5000-kc';
    const URL='https://na-ryby.eu/'.self::SLUG.'/';
    const TITLE='Nejlepší rybářská lehátka do 5000 Kč';
    const DATE='2026-01-21';
    const MARKER='data-nry-bedchair-budget-guide="v1"';
    const SOURCE='Rychlé srovnání doporučených variant';
    public static function models(){return array(
        'xl6'=>'Mivardi Lehátko Comfort XL6',
        'avenger'=>'Prologic Lehátko Avenger Bedchair 6 Leg',
        'xl8'=>'Mivardi Lehátko Comfort XL8',
        'gaube'=>'Giants Fishing Lehátko Gaube XT Bedchair 8leg',
        'hex8'=>'Mivardi Lehátko Professional Hex 8',
        'flat8'=>'Mivardi Lehátko CamoCODE Flat8',
        'eightlux'=>'Delphin Lehátko EightLux 8 C2G',
    );}
    private static function plain($s){return trim(preg_replace('~[\s\x{00a0}\x{202f}]+~u',' ',html_entity_decode(strip_tags($s),ENT_QUOTES|ENT_HTML5,'UTF-8')));}
    private static function headings($html,$tag,$text){
        preg_match_all('~<'.$tag.'\b[^>]*>[\s\S]{1,1200}?</'.$tag.'\s*>~iu',$html,$all,PREG_OFFSET_CAPTURE);
        return array_values(array_filter($all[0],function($m)use($text){return self::plain($m[0])===$text;}));
    }
    private static function eligible($loop=false){
        if(!is_singular('post')||is_admin()||is_preview()||is_feed()
          ||(function_exists('is_customize_preview')&&is_customize_preview())
          ||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
          ||($loop&&(!in_the_loop()||!is_main_query())))return false;
        $id=(int)get_queried_object_id();$p=get_post($id);
        return is_object($p)&&(int)$p->ID===$id&&is_single($id)&&$p->post_type==='post'&&$p->post_status==='publish'
          &&$p->post_name===self::SLUG&&get_permalink($id)===self::URL
          &&substr($p->post_date,0,10)===self::DATE&&self::plain($p->post_title)===self::TITLE
          &&is_string($p->post_content)&&substr_count(self::plain($p->post_content),self::SOURCE)===1;
    }
    private static function attrs($s){preg_match_all('~(?:href|src)\s*=\s*(["\x27])([^"\x27]*)\1~i',$s,$m);return $m[0];}
    public static function transform($html){
        if(!is_string($html)||strlen($html)<8000||strlen($html)>250000||strpos($html,self::MARKER)!==false
           ||substr_count(self::plain($html),self::SOURCE)!==1)return $html;
        $h=self::headings($html,'h1',self::TITLE);
        if(count($h)!==1){if(defined('NRY_BUDGET_PREFLIGHT'))fwrite(STDERR,"BUDGET_H1_DRIFT\n");return $html;}
        $out=$html;
        // Exact H3 model headings: add seven unique local fragment destinations
        // without modifying the existing links, images, text or heading IDs.
        $edits=array();
        foreach(self::models() as $key=>$name){
            $m=self::headings($html,'h3',$name);
            if(count($m)!==1||strpos($html,'nry-budget-'.$key)!==false){
                if(defined('NRY_BUDGET_PREFLIGHT'))fwrite(STDERR,"BUDGET_MODEL_DRIFT ".$key."\n");
                return $html;
            }
            $edits[]=array($m[0][1],0,'<span id="nry-budget-'.$key.'" aria-hidden="true" style="display:block;scroll-margin-top:100px"></span>');
        }
        usort($edits,function($a,$b){return $b[0]<=>$a[0];});
        foreach($edits as $edit)$out=substr_replace($out,$edit[2],$edit[0],$edit[1]);
        $old=$h[0][0];$offset=$h[0][1];
        $heading=preg_replace('~^<h1\b~i','<h2',$old);
        $heading=preg_replace('~</h1\s*>$~i','</h2>',$heading);
        $out=substr_replace($out,$heading,$offset,strlen($old));
        if(self::attrs($out)!==self::attrs($html))return $html;
        $guide=file_get_contents(__DIR__.'/editorial/bedchair-budget-5000-comparison-20261128.html');
        if(!is_string($guide)||substr_count($guide,self::MARKER)!==1||substr_count($guide,'data-nry-bedchair-detail=')!==7||substr_count($guide,'data-nry-bedchair-budget-path=')!==3)return $html;
        $at=$offset+strlen($heading);
        return substr($out,0,$at)."\n".$guide.substr($out,$at);
    }
    public static function render($html){return self::eligible(true)?self::transform($html):$html;}
    public static function enqueue(){
        if(self::eligible()&&function_exists('wp_enqueue_style')&&function_exists('plugins_url'))
            wp_enqueue_style('nry-gear-archive-entry-v1',plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');
    }
    public static function boot(){
        if(function_exists('is_single')&&function_exists('is_singular')&&function_exists('add_filter')&&function_exists('add_action')){
            add_filter('the_content',array(__CLASS__,'render'),80);
            add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),27);
        }
    }
}
NRY_Bedchair_Budget_5000_V1::boot();
