<?php
/** Exact legacy winter guide improvement; output-only, no affiliate mutations. */
if (!defined('ABSPATH')) exit;
final class NRY_Winter_Sleep_Buyer_V1 {
    const SLUG='jake-rybarske-lehatko-na-zimu-prakticky-vyber';
    const URL='https://na-ryby.eu/'.self::SLUG.'/';
    const TITLE='Jaké rybářské lehátko na zimu | Praktický výběr';
    const DATE='2026-03-24';
    const ID=118787;
    const MODIFIED='2026-03-24 10:13:30';
    const MARKER='data-nry-winter-sleep-guide="v1"';
    const H1='Jaké rybářské lehátko na zimu';
    const ANCHOR='Co je v zimě opravdu důležité';
    public static function changes(){
        return array(
            'Na mírnější zimu a kratší výpravy může stačit rozumný sleep system s 3 až 4 sezonním spacákem. Na opravdovou zimní kaprařinu ale většinou dává větší smysl lepší lehátko a poctivý 4–5 sezonní spacák, případně robustnější kompletní spánkový systém.'=>'Pro výběr spacího systému ověřte doložený tepelný komfort celé sestavy a očekávané podmínky. Označení 3–5 sezon samo o sobě nezaručuje použitelnost při konkrétní teplotě.',
            'Stačí rozumný 3–4 sezonní sleep system nebo lepší lehátko se slušným spacákem.'=>'Zvolte ověřený spací systém s odpovídající izolací a doloženým tepelným komfortem. Při pochybnostech nocování odložte.',
            'Na lehkou zimu nebo noci kolem nuly ještě ano, ale do opravdových mrazů už bych bez další vrstvy nebo jiného řešení nešel.'=>'Samotné označení tří sezon neprokazuje vhodnost kolem nuly ani pod bodem mrazu. Ověřte doložené parametry sestavy a podmínky použití.',
            'Tady vidím jako bezpečný základ Fox EOS Compact Sleep System nebo Nash Tackle Sleep System podle rozpočtu a očekávání.'=>'Fox EOS Compact Sleep System a Nash Tackle Sleep System lze porovnat jako nákupní kandidáty; bezpečnost nocování v chladu nelze odvodit z názvu výrobku ani počtu sezon.',
            'spacák určený i do teplot pod bodem mrazu'=>'u spacáku ověřte skutečnou teplotní deklaraci a metodu zkoušky',
        );
    }
    private static function plain($s){return trim(preg_replace('~[\s\x{00a0}\x{202f}]+~u',' ',html_entity_decode(strip_tags($s),ENT_QUOTES|ENT_HTML5,'UTF-8')));}
    private static function eligible($loop=false){
        if(!is_singular('post')||is_admin()||is_preview()||is_feed()
          ||(function_exists('is_customize_preview')&&is_customize_preview())
          ||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
          ||($loop&&(!in_the_loop()||!is_main_query())))return false;
        $id=(int)get_queried_object_id();$p=get_post($id);
        return $id===self::ID&&is_object($p)&&(int)$p->ID===$id&&$p->post_modified_gmt===self::MODIFIED&&is_single($id)&&$p->post_type==='post'&&$p->post_status==='publish'
          &&$p->post_name===self::SLUG&&get_permalink($id)===self::URL
          &&substr($p->post_date,0,10)===self::DATE&&self::plain($p->post_title)===self::TITLE
          &&is_string($p->post_content)&&substr_count(self::plain($p->post_content),self::ANCHOR)===1
          &&strpos($p->post_content,'Fox EOS Compact Sleep System')!==false
          &&strpos($p->post_content,'Nash Tackle Sleep System')!==false;
    }
    private static function attrs($s){preg_match_all('~(?:href|src)\s*=\s*(["\x27])([^"\x27]*)\1~i',$s,$m);return $m[0];}
    public static function transform($html){
        if(!is_string($html)||strlen($html)<3000||strlen($html)>250000||strpos($html,self::MARKER)!==false
           ||substr_count(self::plain($html),self::ANCHOR)!==1)return $html;
        $out=$html;
        // Replace only one bounded plain-text element per claim. Inline emphasis
        // is allowed, but any original link, image or unknown tag blocks.
        $pattern='~<(p|li|div|span)\b([^>]*)>((?:(?!</?(?:p|li|div|ul|ol|table|h[1-6])\b)[\s\S])*?)</\1\s*>~iu';
        foreach(self::changes() as $old=>$new){
            $count=0;
            $candidate=preg_replace_callback($pattern,function($m)use($old,$new,&$count){
                $plain=self::plain($m[3]);
                if(substr_count($plain,$old)!==1)return $m[0];
                if(self::attrs($m[3])||preg_match('~<(?!/?(?:br|strong|b|em|i|span)\b)[^>]+>~i',$m[3]))return $m[0];
                $count++;
                return '<'.$m[1].$m[2].'>'.htmlspecialchars(str_replace($old,$new,$plain),ENT_QUOTES|ENT_HTML5,'UTF-8').'</'.$m[1].'>';
            },$out);
            if(!is_string($candidate)||$count!==1){
                if(defined('NRY_WINTER_SOURCE_PREFLIGHT'))fwrite(STDERR,"WINTER_SOURCE_COPY_DRIFT ".substr($old,0,80)." count=".$count."\n");
                return $html;
            }
            $out=$candidate;
        }
        preg_match_all('~<h1\b[^>]*>[\s\S]{1,1200}?</h1\s*>~iu',$out,$all,PREG_OFFSET_CAPTURE);
        $matches=array_values(array_filter($all[0],function($m){return self::plain($m[0])===self::H1;}));
        if(count($matches)!==1)return $html;
        $original=$matches[0][0];$offset=$matches[0][1];
        $heading=preg_replace('~^<h1\b~i','<h2',$original);
        $heading=preg_replace('~</h1\s*>$~i','</h2>',$heading);
        $out=substr_replace($out,$heading,$offset,strlen($original));
        if(self::attrs($out)!==self::attrs($html))return $html;
        $guide=file_get_contents(__DIR__.'/editorial/winter-sleep-buyer-guide-20261128.html');
        if(!is_string($guide)||substr_count($guide,self::MARKER)!==1||substr_count($guide,'data-nry-winter-path=')!==3)return $html;
        // The entire original product list, media and unrelated links survive.
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
NRY_Winter_Sleep_Buyer_V1::boot();
