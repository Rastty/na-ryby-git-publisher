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


/** Two additional 2024 booking-intent articles. Reuse existing approved links.
 * No new merchant destination, WP mutation or change to the shared loader.
 */
final class NRY_Cabin_Relax_Stays_V1 {
    const BASE='https://na-ryby.eu/';
    const MARKER='data-nry-cabin-relax-guide=';
    public static function routes(){return array(
        'rybareni-a-odpocinek-v-jednom-ubytovani-na-rybarske-chate'=>array(
            'id'=>92376,'modified'=>'2024-10-21 05:00:01','date'=>'2024-10-21','title'=>'Rybaření a odpočinek v jednom – ubytování na rybářské chatě','role'=>'cabin',
            'eyebrow'=>'Dvě skutečné chaty u Vranovské přehrady','headline'=>'Porovnejte termín a podmínky pobytu u vody',
            'intro'=>'Jde o dva nezávislé objekty; Na-ryby.eu žádnou z těchto chat neprovozuje. Lovné místo, povolenku a přístup potvrďte zvlášť.',
            'offers'=>array(array('cyklo','Cyklochata – Štítary','Ověřte termín, kapacitu a cestu k lovišti.'),array('srub','Wellness srub – Vranov','Samostatný objekt; nízká hladina může rybolov omezit.')),
        ),
        'proc-je-rybareni-s-ubytovanim-u-vody-vyhodnou-volbou-pro-vas-relaxacni-pobyt'=>array(
            'id'=>91503,'modified'=>'2024-09-12 12:15:29','date'=>'2024-09-12','title'=>'Proč je rybaření s ubytováním u vody výhodnou volbou pro váš relaxační pobyt','role'=>'relax',
            'eyebrow'=>'Pobyt u Lipna nebo Vranova','headline'=>'Porovnejte dvě různé možnosti odpočinku',
            'intro'=>'Penzion v Horní Plané a wellness srub u Vranova jsou samostatná ubytování. Rybaření ani lov přímo z objektu není zaručený.',
            'offers'=>array(array('lipno','Pension U Kohoutů – Horní Planá','Zázemí v obci u Lipna; zkontrolujte termín a cestu k vodě.'),array('srub','Wellness srub – Vranov','Ověřte obsazenost, wellness podmínky a hladinu vody.')),
        ),
    );}
    private static function plain($s){return trim(preg_replace('~[\s\x{00a0}\x{202f}]+~u',' ',html_entity_decode(strip_tags($s),ENT_QUOTES|ENT_HTML5,'UTF-8')));}
    private static function attrs($s){preg_match_all('~(?:href|src)\s*=\s*(["\x27])([^"\x27]*)\1~i',$s,$m);return $m[0];}
    public static function corrections($role){
        if($role==='cabin')return array(
            'Rybářská chata je vybavena veškerým potřebným vybavením'=>'Vybavení konkrétní chaty se liší. Ověřte kuchyň, koupelnu, lůžka a prostor pro výbavu před rezervací.',
            'Ubytování na rybářské chatě je možnost strávit dovolenou na místě'=>'Chata u vody poskytuje zázemí; lov a případné ponechání úlovku se řídí pravidly konkrétní vody.',
            'Soukromé rybaření s ubytováním je skvělá možnost'=>'Některé soukromé vody nabízejí i ubytování. Rezervace chaty sama o sobě nezaručuje lov kdykoli ani výhradní lovné místo.',
            'Užijte si perfektní kombinaci rybaření a relaxace na naší rybářské chatě'=>'Vyberte skutečné ubytování podle termínu, kapacity a pravidel konkrétní vody. Na-ryby.eu žádnou vlastní rybářskou chatu nenabízí.',
        );
        return array(
            'Další výhodou je možnost chytání ryb přímo z vašeho ubytování'=>'Blízkost vody může zkrátit cestu k lovišti, ale neznamená automaticky právo lovit z objektu ani bezplatný rybolov.',
            'Ubytování u vody vám umožní rybařit kdykoli se vám zachce'=>'Povolenou dobu a místo lovu určuje konkrétní revír. Ubytování může usnadnit přístup, nikoli nahradit oprávnění k lovu.',
            'Jedním z oblíbených míst pro rybaření u vody v České republice je rybářská chata Na Rybníčku'=>'Historickou zmínku o objektu Na Rybníčku nelze považovat za ověřenou současnou nabídku. Porovnejte konkrétní poskytovatele výše.',
            'Naše zařízení s ubytováním u vody se nachází'=>'Na-ryby.eu vlastní ubytovací zařízení neprovozuje. Polohu ověřujte u konkrétního poskytovatele.',
            'Nabízíme moderní a pohodlné ubytování'=>'Vybavení a případná půjčovna prutů jsou závislé na vybraném objektu; potvrďte si služby před rezervací.',
            'Rybaření s ubytováním u vody je oblíbenou aktivitou mezi rybáři'=>'Ubytování u vody je zázemím pro výpravu, nikoli potvrzením možnosti lovit z vlastního pokoje nebo chaty.',
            'Na takových místech bývají k dispozici rybářské chatky či penziony'=>'Před cestou potvrďte vybavení, úschovu prutů, kuchyň, koupelnu a případnou půjčovnu přímo u ubytovatele.',
            'Rybaření s ubytováním u vody je skvělou volbou pro ty'=>'Při rezervaci posuzujte přístup k vodě, pravidla lovu a program pro všechny účastníky, nikoli pouze polohu objektu.',
        );
    }
    private static function replaceBlock(&$out,$old,$new){
        $n=0;
        $candidate=preg_replace_callback('~<p\b([^>]*)>((?:(?!</?p\b)[\s\S])*?)</p\s*>~iu',function($m)use($old,$new,&$n){
            if(strpos(self::plain($m[2]),$old)!==0||self::attrs($m[2])||preg_match('~<(?!/?(?:br|strong|b|em|i|span)\b)[^>]+>~i',$m[2]))return $m[0];
            $n++;return '<p'.$m[1].'>'.htmlspecialchars($new,ENT_QUOTES|ENT_HTML5,'UTF-8').'</p>';
        },$out);
        if($n!==1||!is_string($candidate)){if(defined('NRY_TWO_STAYS_PREFLIGHT'))fwrite(STDERR,"CABIN_BLOCK_DRIFT ".$old." count=".$n."\n");return false;}
        $out=$candidate;return true;
    }
    public static function guide($role){
        if($role==='cabin')return <<<'HTML'
<section data-nry-cabin-relax-guide="cabin" aria-label="Jak vybrat rybářskou chatu pro vlastní výpravu">
<p><strong>Rybářská chata není automaticky soukromý revír.</strong> Na-ryby.eu je informační web, nikoli majitel chaty nebo rybníka. Nabídky výše vedou k samostatným ubytovatelům. Než zaplatíte zálohu, zjistěte, zda se smí lovit na konkrétní vodě a jaké podmínky platí pro váš termín.</p>
<h2>Chata na ryby: co rozhoduje o pohodlí při delším pobytu</h2>
<p>U výpravy na více dní oceníte nejen vzdálenost od vody, ale také možnost nechat vyschnout mokrý podběrák, přístup k autu, bezpečné uložení prutů a skutečnou dostupnost kuchyně a koupelny. Je rozdíl mezi ubytováním u veřejného revíru, chatou přímo na soukromém rybníku a objektem s výhledem na vodu bez možnosti lovu. Každý z těchto typů má jiné podmínky a cenu.</p>
<div style="overflow-x:auto"><table><caption>Rozhodnutí podle způsobu výpravy</caption><thead><tr><th scope="col">Co chcete</th><th scope="col">Vhodný typ zázemí</th><th scope="col">Potvrdit před rezervací</th></tr></thead><tbody><tr><td>Celý víkend u kaprů</td><td>Chata s praktickým přístupem k lovišti</td><td>Povolenka, cesta s výbavou, doba lovu</td></tr><tr><td>Parta rybářů na týden</td><td>Celý objekt s dostatečnou kapacitou</td><td>Počet lůžek, parkování, sušení a doplatky</td></tr><tr><td>Pár nebo rodina</td><td>Chata či penzion s nezávislým programem</td><td>Bezpečný přístup, soukromí, možnosti pro nerybáře</td></tr></tbody></table></div>
<h2>Vranov: porovnání dvou konkrétních ubytování</h2>
<p>Cyklochata ve Štítarech a Wellness srub u Vranovské přehrady jsou dvě různé nabídky. U srubu je v katalogu uvedeno sedm lůžek ve třech ložnicích a sauna s koupací kádí; popis zároveň upozorňuje na zhoršené podmínky rybolovu při nízké hladině. Nepovažujte jej za potvrzené lovné místo ani za nejlepší nabídku podle recenzí. U Cyklochaty prověřte přesný počet hostů a cestu z objektu na vybrané stanoviště. Zvolte termín až po potvrzení ceny, podmínek rybolovu a skutečného přístupu.</p>
<h2>Jak posoudit cenu celé chaty</h2>
<p>Pokud je modelová cena objektu 3 600 Kč za noc, dvě noci vyjdou na 7 200 Kč. Při šesti hostech jde o 1 200 Kč na osobu za pobyt, při čtyřech o 1 800 Kč. Doplňte skutečnou dopravu, povolenky, stravu a případné doplatky. Tento příklad není ceníkem uvedených objektů. Při porovnávání nabídek sledujte také minimální délku pobytu, vratnou kauci a podmínky zrušení. Kauce není běžný náklad, pokud se vrátí celá, ale ovlivňuje částku, kterou potřebujete zaplatit předem.</p>
<h2>Co si potvrdit písemně</h2><p>Pošlete ubytovateli termín a přesný počet všech osob. Zeptejte se na cestu k vodě, parkování, úschovu vybavení, přístup v noci, možnost sušení a cenu včetně všech povinných poplatků. Oprávnění k lovu a podmínky konkrétního revíru ověřte samostatně u jeho správce. Na svazových vodách lze začít v <a href="https://ris.rybsvaz.cz/">Rybářském informačním systému ČRS</a>; u soukromých vod použijte pravidla jejich provozovatele.</p>
<p>Pokud je cesta k lovišti nevhodná pro těžkou výbavu nebo termín nesedí, změňte lokalitu před zaplacením zálohy. Využijte <a href="https://na-ryby.eu/nejlepsi-soukrome-reviry-s-ubytovanim-v-ceske-republice-zazitek-ktery-stoji-za-to/">průvodce soukromými revíry s ubytováním</a>, hledáte-li skutečně soukromé loviště, nikoli pouze chatu v okolí přehrady.</p></section>
HTML;
        return <<<'HTML'
<section data-nry-cabin-relax-guide="relax" aria-label="Plán relaxačního rybářského víkendu ve dvou">
<p><strong>Víkend u vody má smysl i bez celodenního rybaření.</strong> Pokud jedete ve dvou a jeden z vás nechytá, vybírejte zázemí podle obou lidí. Na-ryby.eu není ubytovatel a nemá vlastní rybářská zařízení; výše uvedené odkazy vedou k nezávislým poskytovatelům.</p>
<h2>Penzion, wellness srub, nebo chata?</h2>
<div style="overflow-x:auto"><table><caption>Výběr podle potřeb obou účastníků pobytu</caption><thead><tr><th scope="col">Typ</th><th scope="col">Kdy dává smysl</th><th scope="col">Ověřit</th></tr></thead><tbody><tr><td>Penzion</td><td>Krátký pobyt, jednodušší stravování a výlety</td><td>Cestu na loviště, parkování, čas snídaně</td></tr><tr><td>Wellness srub</td><td>Soukromí, vlastní kuchyň a odpočinek po lovu</td><td>Provoz wellness, doplatky, stav vody</td></tr><tr><td>Samostatná chata</td><td>Více nocí, vlastní program a výbava</td><td>Kapacitu, minimální délku pobytu, vytápění</td></tr></tbody></table></div>
<p>Pension U Kohoutů najdete v Horní Plané u Lipna. Jde o zázemí v obci, nikoli garantovaný lov přímo před pokojem. Wellness srub u Vranova nabízí podle katalogu samostatný objekt se saunou a koupací kádí; popis upozorňuje na sezónní omezení rybolovu při poklesu hladiny. Nejdříve porovnejte volný termín a potvrďte skutečné podmínky u vody. Ani jedno ubytování zde nehodnotíme jako osobně vyzkoušené.</p>
<h2>Modelový víkend pro dva: rybaření i odpočinek</h2>
<p>V pátek po příjezdu si projděte bezpečnou přístupovou cestu, ověřte aktuální pravidla revíru a připravte pouze potřebnou výbavu. V sobotu lze naplánovat ranní lov podle povolené doby a společný program odpoledne. V neděli počítejte s odjezdem v čase, který stanoví ubytovatel. Nechte rezervu na počasí. V zimě i mimo hlavní sezónu si předem ověřte provoz služeb, přístup k vodě a případná omezení lovu; pobyt není důvodem k porušení pravidel.</p>
<h2>Kolik zaplatíte za pohodlný krátký pobyt?</h2>
<div style="overflow-x:auto"><table><caption>Ilustrační náklady pro dva na dvě noci, nikoli skutečné ceny nabídek</caption><thead><tr><th scope="col">Položka</th><th scope="col">Jednodušší zázemí</th><th scope="col">Pohodlnější zázemí</th></tr></thead><tbody><tr><td>Ubytování</td><td>3 000 Kč</td><td>5 000 Kč</td></tr><tr><td>Doprava</td><td>800 Kč</td><td>800 Kč</td></tr><tr><td>Strava a společný program</td><td>1 600 Kč</td><td>1 600 Kč</td></tr><tr><td>Modelový poplatek za lov</td><td>600 Kč</td><td>600 Kč</td></tr><tr><td>Celkem</td><td>6 000 Kč</td><td>8 000 Kč</td></tr><tr><td>Na osobu</td><td>3 000 Kč</td><td>4 000 Kč</td></tr></tbody></table></div>
<p>Poplatek za lov je pouze ilustrační: potřebný druh oprávnění i cena se liší podle revíru a rybáře. Připočtěte reálné wellness doplatky a další povinné služby. Pokud chcete ušetřit, volte raději kratší cestu nebo jednodušší zázemí než neověřenou nabídku s údajným bezplatným rybolovem.</p>
<h2>Kdy se pobyt u vody nevyplatí?</h2>
<p>Jestliže je hlavním cílem lov, ale termín nebo přístup k revíru nejsou potvrzené, vyčkejte s rezervací. Pokud hledáte klid, ověřte hluk, společné prostory a storno podmínky. Ubytování s wellness není automaticky soukromá voda, povolenka ani půjčovna prutů. Pro konkrétní pravidla svazového revíru využijte <a href="https://ris.rybsvaz.cz/">RIS ČRS</a>; soukromé vody mají vlastní provozní podmínky.</p><p>Při volbě mezi Lipnem a Vranovem navazuje <a href="https://na-ryby.eu/rybarske-dobrodruzstvi-s-ubytovanim-u-vody-vyber-nejlepsich-mist/">průvodce výběrem rybářské destinace</a>. Zvolte místo podle skutečné dostupnosti lovu a programu pro všechny účastníky.</p></section>
HTML;
    }
    public static function transform($html,$r){
        if(!is_string($html)||strlen($html)<1000||strlen($html)>200000||strpos($html,self::MARKER)!==false||strpos($html,NRY_Six_Real_Stay_Offers_V1::MARKER)!==false)return $html;
        $out=$html;
        if($r['role']==='cabin'){
            // The introduction contains a link, so replace only its exact trailing plain text.
            $old='Ubytování na naší rybářské chatě vám poskytne vše, co potřebujete k relaxaci a vyhlášenému rybaření na naší soukromé rybníku.';
            if(substr_count($out,$old)!==1){if(defined('NRY_TWO_STAYS_PREFLIGHT'))fwrite(STDERR,"CABIN_INTRO_DRIFT\n");return $html;}
            $out=str_replace($old,'Tento průvodce vám pomůže porovnat nezávislé nabídky chat a ověřit pravidla konkrétní vody.',$out);
        }
        foreach(self::corrections($r['role']) as $old=>$new)if(!self::replaceBlock($out,$old,$new))return $html;
        preg_match_all('~<h1\b[^>]*>[\s\S]{1,1200}?</h1\s*>~iu',$out,$all);
        $matches=array_values(array_filter($all[0],function($m)use($r){return self::plain($m)===$r['title'];}));
        if(count($matches)!==1){if(defined('NRY_TWO_STAYS_PREFLIGHT'))fwrite(STDERR,"CABIN_H1_DRIFT\n");return $html;}
        $h=preg_replace('~^<h1\b~i','<h2',$matches[0]);$h=preg_replace('~</h1\s*>$~i','</h2>',$h);
        $out=str_replace($matches[0],$h,$out);
        if(self::attrs($html)!==self::attrs($out))return $html;
        $panel=NRY_Six_Real_Stay_Offers_V1::panel($r);
        if(substr_count($panel,'data-nry-real-stay-offer=')!==2)return $html;
        return $panel."\n".self::guide($r['role'])."\n".$out;
    }
    private static function eligible($loop=false){
        foreach(array('is_single','is_singular','is_admin','is_preview','is_feed','in_the_loop','is_main_query','get_queried_object_id','get_post','get_permalink','home_url') as $f)if(!function_exists($f))return null;
        if(!is_singular('post')||is_admin()||is_preview()||is_feed()||($loop&&(!in_the_loop()||!is_main_query()))
          ||(function_exists('is_customize_preview')&&is_customize_preview())||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||rtrim(home_url('/'),'/').'/'!==self::BASE)return null;
        $id=(int)get_queried_object_id();$p=get_post($id);
        if(!is_object($p)||(int)$p->ID!==$id||!is_single($id)||$p->post_type!=='post'||$p->post_status!=='publish'||!is_string($p->post_content))return null;
        $routes=self::routes();$slug=$p->post_name;if(!isset($routes[$slug]))return null;$r=$routes[$slug];
        if($id!==$r['id']||($r['modified']!==''&&$p->post_modified_gmt!==$r['modified'])||substr($p->post_date,0,10)!==$r['date']||!in_array(self::plain($p->post_title),$r['role']==='cabin'?array($r['title'],str_replace(' – ',' - ',$r['title'])):array($r['title']),true)||get_permalink($id)!==self::BASE.$slug.'/')return null;
        foreach(self::corrections($r['role']) as $old=>$new)if(strpos(self::plain($p->post_content),$old)===false)return null;
        return array('slug'=>$slug)+$r;
    }
    public static function render($html){$r=self::eligible(true);return $r?self::transform($html,$r):$html;}
    public static function enqueue(){if(self::eligible()&&function_exists('wp_enqueue_style')&&function_exists('plugins_url'))wp_enqueue_style('nry-gear-archive-entry-v1',plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');}
    public static function boot(){if(function_exists('is_single')&&function_exists('is_singular')&&function_exists('add_filter')&&function_exists('add_action')){add_filter('the_content',array(__CLASS__,'render'),80);add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),27);}}
}
NRY_Cabin_Relax_Stays_V1::boot();
