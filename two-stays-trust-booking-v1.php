<?php
/** Two bounded repairs of legacy stay articles; preserve all existing links/media.
 * No WP database writes. Existing approved booking routes only. */
if (!defined('ABSPATH')) { exit; }
final class NRY_Two_Stays_Trust_20261128_V1 {
    const BASE='https://na-ryby.eu/';
    const MARKER='data-nry-stay-reader-guide=';
    public static function routes(){
        return array(
            'rybarske-dobrodruzstvi-s-ubytovanim-u-vody-vyber-nejlepsich-mist'=>array(
                'date'=>'2023-09-23','title'=>'Rybářské dobrodružství s ubytováním u vody: Výběr nejlepších míst',
                'h1'=>'Rybářské dobrodružství s ubytováním u vody','role'=>'destinations-v1',
                'file'=>'stay-destinations-practical-20261128.html','source'=>array('Rybník Šumava','Rybník Morava','Jezero Zlatá voda','Potok Lesní'),
                'eyebrow'=>'Lipno a Vranov','headline'=>'Porovnejte dvě konkrétní ubytování','intro'=>'Jde o samostatná ubytování v okolí různých nádrží, nikoli rezervaci lovného místa nebo povolenku.',
                'offers'=>array(array('lipno','Pension U Kohoutů – Lipno','Horní Planá; prověřte volný termín a cenu.'),array('cyklo','Cyklochata – Vranov','Štítary; ověřte kapacitu skupiny a skutečnou cestu k vodě.')),
            ),
            'naplnete-sve-rybarske-sny-v-soukromych-revirech-s-ubytovanim'=>array(
                'date'=>'2024-08-12','title'=>'Naplněte své rybářské sny v soukromých revírech s ubytováním',
                'h1'=>'Naplněte své rybářské sny v soukromých revírech s ubytováním','role'=>'private-booking-v1',
                'file'=>'stay-private-booking-practical-20261128.html','source'=>array('Naše revíry jsou bohaté','Lorem ipsum dolor sit amet','našeho online rezervačního systému','V našich ubytovacích zařízeních'),
                'eyebrow'=>'Alternativní pobyt bez garantovaného soukromého loviště','headline'=>'Pokud chcete jiné ubytování u vody, porovnejte tyto možnosti',
                'intro'=>'Soukromé lovné místo rezervujte přímo u provozovatele vody; tato dvě ubytování jsou samostatné alternativy, nikoli potvrzené balíčky se soukromým rybolovem.',
                'offers'=>array(array('srub','Wellness srub Vranov','Lančov; ověřte hladinu, termín a možnost lovu.'),array('more','Další rybářské pobyty','Katalog Hauzi; podmínky ověřte u konkrétního objektu.')),
            ),
        );
    }
    private static function eligible($loop=false){
        if(!is_singular('post')||is_admin()||is_preview()||is_feed()
            ||(function_exists('is_customize_preview')&&is_customize_preview())
            ||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||rtrim(home_url('/'),'/').'/'!==self::BASE
            ||($loop&&(!in_the_loop()||!is_main_query())))return null;
        $id=(int)get_queried_object_id();$p=get_post($id);
        if(!is_object($p)||(int)$p->ID!==$id||!is_single($id)||$p->post_type!=='post'||$p->post_status!=='publish'||!is_string($p->post_content))return null;
        $routes=self::routes();$slug=$p->post_name;
        if(!isset($routes[$slug])||get_permalink($id)!==self::BASE.$slug.'/')return null;
        $r=$routes[$slug];
        if(substr($p->post_date,0,10)!==$r['date']||html_entity_decode($p->post_title,ENT_QUOTES|ENT_HTML5,'UTF-8')!==$r['title'])return null;
        foreach($r['source'] as $s)if(strpos($p->post_content,$s)===false)return null;
        return array('slug'=>$slug)+$r;
    }
    private static function replaceText(&$html,$old,$new){
        // Exact visible phrase; never alter attributes. Existing links remain.
        $quoted=preg_quote($old,'~');
        $pattern='~(?<=>|\s)'.$quoted.'(?=[\s<.,;:!?]|$)~u';
        if(preg_match_all($pattern,$html)!==1){if(defined('NRY_TWO_STAYS_PREFLIGHT'))fwrite(STDERR,'SOURCE_TEXT_GUARD '.$old."\n");return false;}
        $html=preg_replace($pattern,$new,$html,1);
        return is_string($html);
    }
    private static function block(&$html,$tag,$start,$new){
        // WordPress may wrap plain historical text with inline formatting
        // or hard line breaks. Match normalized visible text exactly at
        // the start of one bounded element. Never remove a link or image.
        $pattern='~<'.$tag.'\b([^>]*)>((?:(?!</?'.$tag.'\b).)*?)</'.$tag.'\s*>~isu';
        $n=0;
        $candidate=preg_replace_callback($pattern,function($m)use($tag,$start,$new,&$n){
            $plain=trim(preg_replace('~[\s\x{00a0}]+~u',' ',html_entity_decode(strip_tags($m[2]),ENT_QUOTES|ENT_HTML5,'UTF-8')));
            if(strpos($plain,$start)!==0)return $m[0];
            if(preg_match('~<(?!/?(?:br|strong|b|em|i|span)\b)[^>]+>~i',$m[2])||self::attrs($m[2]))return $m[0];
            $n++;
            return '<'.$tag.$m[1].'>'.$new.'</'.$tag.'>';
        },$html);
        if($n!==1||!is_string($candidate)){
            if(defined('NRY_TWO_STAYS_PREFLIGHT'))fwrite(STDERR,"SOURCE_BLOCK_GUARD ".$tag." ".$start." matched=".$n."\n");
            return false;
        }
        $html=$candidate;
        return true;
    }
    private static function attrs($html){
        preg_match_all('~(?:href|src)\s*=\s*(["\x27])([^"\x27]*)\1~i',$html,$m);
        return $m[0];
    }
    public static function transform($html,$r){
        if(!is_string($html)||strlen($html)<1000||strlen($html)>200000||strpos($html,self::MARKER)!==false)return $html;
        foreach($r['source'] as $s)if(strpos($html,$s)===false)return $html;
        $out=$html;
        if($r['role']==='destinations-v1'){
            $blocks=array(
                array('li','Rybník Šumava','Údolní nádrž Lipno – ověřte revír a konkrétní břeh.'),
                array('li','Rybník Morava','Vranovská přehrada – ověřte přístup a hladinu.'),
                array('li','Jezero Zlatá voda','Lipno: pro delší výpravu vybírejte konkrétní část nádrže a přístup k lovišti.'),
                array('li','Řeka Divoká','Vranov: porovnejte zázemí a skutečné možnosti lovu před rezervací.'),
                array('li','Potok Lesní','Hracholusky: zkontrolujte aktuální pravidla Mže 4 a zvolte vhodný úsek.'),
                array('p','Na těchto lokalitách si můžete pronajmout','V některých oblastech najdete chaty, penziony nebo kempy. Ubytování přímo u vody a oprávnění k lovu nejsou zaručené.'),
                array('p','Mezi známé rybářské destinace v Krkonoších','Pec pod Sněžkou je turistická destinace. Bez určení konkrétní vody a potvrzení pravidel ji nelze doporučit jako ověřený rybářský revír.'),
                array('p','V okolí Bedřichova v Jizerských horách','Bedřichov je turistická oblast. Možnost rybolovu ověřte u konkrétní vody a správce, nikoli podle samotné blízkosti rybníka.'),
                array('p','Pokud hledáte rybářské dobrodružství s ubytováním u vody, v Česku','Pro rybářský pobyt volte konkrétní vodu, její pravidla a přístupné stanoviště. Turistické destinace samy o sobě nejsou dokladem povoleného lovu.'),
                array('p','Na těchto rybářských lokalitách si můžete vybrat','Dostupné typy ubytování se liší podle lokality. Vždy ověřte konkrétní poskytovatele, vybavení a podmínky.'),
                array('p','Nejlepší doba na rybolov na těchto místech','Termín volte podle cílové ryby, povolené metody, hájení, počasí a aktuálních pravidel revíru.'),
                array('p','Tyto místa nabízejí nejen možnost rybaření','Uvedené oblasti nejsou garantované balíčky ubytování s rybolovem. Ověřte konkrétní vodu, správce a samostatnou rezervaci pobytu.'),
            );
            foreach($blocks as $b)if(!self::block($out,$b[0],$b[1],$b[2]))return $html;
            if(!self::replaceText($out,'a štikozubec','a další druhy podle konkrétního revíru'))return $html;
        }else{
            $blocks=array(
                array('p','Lorem ipsum dolor sit amet','Při plánování pobytu je nutné oddělit rezervaci ubytování od oprávnění k lovu. Využijte konkrétní postup a srovnání výše.'),
                array('p','Součástí nabídky je i možnost ubytování','Někteří provozovatelé nabízejí ubytování přímo u lovného místa. Jeho vybavení, kapacitu a cenu si nechte potvrdit u konkrétního areálu.'),
                array('p','Rezervaci je možné provést prostřednictvím našeho','Rezervaci provádějte prostřednictvím oficiálních kontaktů konkrétního provozovatele. Na-ryby.eu vlastní rezervační systém pro tyto pobyty nemá.'),
                array('p','V našich ubytovacích zařízeních','Vybavení je závislé na vybrané chatce nebo ubytování. Ověřte lůžka, hygienické zázemí, elektřinu, vytápění a případné doplatky.'),
                array('p','Navštivte soukromé revíry s ubytováním','Porovnejte konkrétní vody s dohledatelným provozovatelem, pravidly a podmínkami pobytu. Na-ryby.eu nabízí informace, nikoli vlastní rybářské areály.'),
            );
            foreach($blocks as $b)if(!self::block($out,$b[0],$b[1],$b[2]))return $html;
            $pairs=array(
                'Vítejte na našem webu, kde si můžete užít nezapomenutelné rybářské dobrodružství v nádherných soukromých revírech s možností ubytování'=>'Tento informační průvodce vám pomůže naplánovat rybaření na soukromé vodě s ubytováním',
                'Naše revíry jsou bohaté na rybí populaci a nabízejí skvělé podmínky pro'=>'Vhodnost konkrétní vody a její pravidla si ověřte před cestou na',
                'Pro více informací a rezervace si prohlédněte naši'=>'Starší externí informační odkaz:',
                'Veškeré rybářské vybavení je k dispozici na místě, avšak pokud preferujete své vlastní vybavení, můžete si ho samozřejmě přinést'=>'Možnost zapůjčení vybavení závisí na konkrétním provozovateli a není automaticky součástí pobytu',
            );
            foreach($pairs as $a=>$b)if(!self::replaceText($out,$a,$b))return $html;
        }
        $heading='~<h1\b([^>]*)>\s*'.preg_quote($r['h1'],'~').'\s*</h1\s*>~u';
        if(preg_match_all($heading,$out)!==1){if(defined('NRY_TWO_STAYS_PREFLIGHT'))fwrite(STDERR,"SOURCE_HEADING_GUARD\n");return $html;}
        $out=preg_replace_callback($heading,function($m)use($r){return '<h2'.$m[1].'>'.($r['role']==='destinations-v1'?'Další plánování pobytu u vody':'Další informace k výběru revíru a pobytu').'</h2>';},$out,1);
        if(self::attrs($out)!==self::attrs($html))return $html;
        foreach($r['source'] as $s)if(strpos($out,$s)!==false)return $html;
        $guide=file_get_contents(__DIR__.'/editorial/'.$r['file']);
        if(!is_string($guide)||substr_count($guide,self::MARKER)!==1||!class_exists('NRY_Six_Real_Stay_Offers_V1'))return $html;
        $panel=NRY_Six_Real_Stay_Offers_V1::panel($r);
        if(substr_count($panel,'data-nry-real-stay-offer=')!==2)return $html;
        return $panel."\n".$guide."\n".$out;
    }
    public static function render($html){$r=self::eligible(true);return $r?self::transform($html,$r):$html;}
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
NRY_Two_Stays_Trust_20261128_V1::boot();
