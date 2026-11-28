<?php
/** Practical two-way bivvy/bedchair sizing tool on three existing guides.
 * Render only, no stored writes or new merchant URLs. Measurements stay in browser.
 */
if (!defined('ABSPATH')) exit;
final class NRY_Bivak_Bedchair_Fit_V1 {
    const MARKER='data-nry-bivak-bedchair-fit="v1"';
    const BASE='https://na-ryby.eu/';
    const BIVAK='jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti';
    const PILLAR='rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare';
    const SMALL='jake-rybarske-lehatko-do-maleho-bivaku-prakticky-vyber';
    const SHORTLIST='nejlepsi-rybarska-lehatka-na-trhu-recenze-a-porovnani';
    const BUDGET='nejlepsi-rybarske-bivaky-do-10-000-kc';
    public static function routes(){
        return array(
            self::BIVAK=>array('id'=>118388,'date'=>'2026-03-19','modified'=>'2026-11-15 14:03:11','title'=>'Jak vybrat rybářský bivak: průvodce materiály, konstrukcí a velikostí','h1'=>'Jak vybrat rybářský bivak: průvodce materiály, konstrukcí a velikostí (2026)','anchor'=>'Pro koho je kvalitní bivak opravdu důležitý','role'=>'bivak','label'=>'Vejde se lehátko do bivaku? Spočítejte prostor před nákupem'),
            self::PILLAR=>array('id'=>118575,'date'=>'2026-03-21','modified'=>'2026-03-21 18:18:04','title'=>'Rybářská lehátka: kompletní průvodce výběrem pro rybáře','h1'=>'Rybářská lehátka: kompletní průvodce výběrem pro kapraře i víkendové rybáře','anchor'=>'Komu tenhle článek pomůže a v jaké situaci','role'=>'bedchair','label'=>'Ověřte rozměry lehátka, než objednáte'),
            self::SMALL=>array('id'=>118781,'date'=>'2026-03-24','modified'=>'2026-09-24 13:37:21','title'=>'Jaké rybářské lehátko do malého bivaku | Praktický výběr','h1'=>'Jaké rybářské lehátko do malého bivaku','anchor'=>'Co je v malém bivaku opravdu důležité','role'=>'compact','label'=>'Kalkulačka pro malé bivaky: délka, šířka a průchod'),
        );
    }
    private static function plain($s){
        $s=html_entity_decode(strip_tags($s),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $s=str_replace(array("\u{2010}","\u{2011}","\u{2013}","\u{2014}"),'-',$s);
        return trim(preg_replace('~[\s\x{00a0}\x{202f}]+~u',' ',$s));
    }
    private static function headings($html,$tag,$text){
        preg_match_all('~<'.$tag.'\b[^>]*>[\s\S]{1,1200}?</'.$tag.'\s*>~iu',$html,$all,PREG_OFFSET_CAPTURE);
        return array_values(array_filter($all[0],function($entry)use($text){return self::plain($entry[0])===self::plain($text);}));
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
        if(($r['id']&&$id!==$r['id'])||($r['modified']!==''&&$p->post_modified_gmt!==$r['modified'])
            ||substr($p->post_date,0,10)!==$r['date']
            ||html_entity_decode($p->post_title,ENT_QUOTES|ENT_HTML5,'UTF-8')!==$r['title']
            ||substr_count(self::plain($p->post_content),self::plain($r['anchor']))!==1||count(self::headings($p->post_content,'h1',$r['h1']))!==1)return null;
        return $r;
    }
    private static function e($s){return htmlspecialchars($s,ENT_QUOTES|ENT_HTML5,'UTF-8');}
    private static function field($key,$label,$value,$min,$max){
        return '<label class="nry-fit__field"><span>'.self::e($label).'</span><input type="number" inputmode="numeric" min="'.$min.'" max="'.$max.'" step="1" value="'.$value.'" data-nry-fit-input="'.$key.'"></label>';
    }
    public static function panel($r){
        $role=$r['role'];
        $out='<section class="nry-fit" '.self::MARKER.' data-nry-fit-role="'.$role.'" aria-label="Kalkulačka rozměrů bivaku a lehátka"><p class="nry-fit__eyebrow">Praktická pomůcka před nákupem</p><h2>'.self::e($r['label']).'</h2>';
        if($role==='bivak')$out.='<p>Nekupujte bivak jen podle označení 1-man nebo 2-man. Do výpočtu zadejte skutečný využitelný vnitřní obdélník v místě, kde bude stát lehátko, a vnější rozměry lehátka včetně spacáku a rámu. Počítejte s průchodem i rezervou u stěn.</p>';
        elseif($role==='bedchair')$out.='<p>Před porovnáním nosnosti a matrace vyřaďte rozměrově nevhodné modely. Změřte svůj bivak v plánované výšce lehátka. Rozhoduje celková délka a šířka s rámem a spacákem, nikoli pouze rozměr matrace.</p>';
        else $out.='<p>V malém bivaku bývá nejslabším místem šikmá stěna a výstup. Zadejte nejmenší použitelný vnitřní obdélník, nikoli maximální rozměr podlážky. Připočtěte vlastní prostor pro bezpečný výstup a obsluhu výbavy.</p>';
        $out.='<div class="nry-fit__grid">';
        $out.=self::field('shelterLength','Vnitřní délka (cm)',240,100,800);
        $out.=self::field('shelterWidth','Vnitřní šířka (cm)',180,100,800);
        $out.=self::field('bedLength','Délka lehátka se spacákem (cm)',210,100,300);
        $out.=self::field('bedWidth','Šířka lehátka se spacákem (cm)',80,40,180);
        $out.='<label class="nry-fit__field"><span>Počet stejných lehátek vedle sebe</span><select data-nry-fit-input="count"><option value="1">1</option><option value="2">2</option></select></label>';
        $out.=self::field('aisle','Vlastní požadovaný průchod (cm)',50,0,200);
        $out.=self::field('endGap','Rezerva u každého konce (cm)',10,0,100);
        $out.=self::field('wallGap','Rezerva u každé boční stěny (cm)',10,0,100);
        $out.='</div><div class="nry-fit__result" data-nry-fit-result role="status" aria-live="polite">Modelový příklad: potřebná délka 230 cm, šířka 150 cm. Zbývá 10 × 30 cm v daných osách.</div>';
        $out.='<p class="nry-fit__note">Výpočet je orientační geometrická kontrola rovnoběžného uspořádání. Nepotvrzuje bezpečný průchod, stabilitu ani kompatibilitu. Pro dvě různá lehátka použijte delší rozměr a pro šířku konzervativně širší z nich. Nezapočítává tvar stěn, dveře, tašky, kotevní tyče ani výškové změny.</p>';
        $out.='<p><strong>Bez kalkulačky:</strong> potřebná délka = délka lehátka + 2 × koncová rezerva; potřebná šířka = počet lehátek × šířka + průchod + 2 × boční rezerva.</p>';
        $out.='<h3>Tři modelové situace (rozměry v centimetrech)</h3><div class="nry-fit__table"><table><caption>Orientační plánovací rozměry; ne údaje konkrétních výrobků</caption><thead><tr><th scope="col">Sestava</th><th scope="col">Využitelný prostor</th><th scope="col">Potřebujete</th><th scope="col">Výsledek</th></tr></thead><tbody><tr><td>1 × 210 × 80</td><td>240 × 180</td><td>230 × 150</td><td>+10 / +30</td></tr><tr><td>1 × 220 × 100</td><td>230 × 155</td><td>240 × 170</td><td>−10 / −15</td></tr><tr><td>2 × 210 × 85</td><td>250 × 240</td><td>230 × 240</td><td>+20 / 0</td></tr></tbody></table></div><p>Příklady používají 10 cm na každé straně a konci a průchod 50 cm. Nulová rezerva navíc znamená přesnou shodu zadaných rozměrů, nikoli potvrzené pohodlí. Zvolte vlastní reálnou rezervu.</p>';
        if($role==='bivak'){
            $out.='<h3>Jak měřit bivak před objednáním</h3><p>Změřte půdorys u podlážky, potom opakujte měření ve výšce rámu a vrchní plochy spacáku. U šikmé stěny se využitelná plocha směrem nahoru zmenšuje. Použijte menší hodnotu a ověřte, že zvolený obdélník leží celý uvnitř prostoru. Uvnitř si vyznačte polohu dveří, tyčí, kšiltu a skutečnou trasu k východu.</p><p>Při dvou lehátkách počítejte i s odlišným rozestupem nohou a prostorem na vstávání. Nakreslete rozložení v měřítku 1:10 na papír; dva obdélníky 21 × 8,5 cm představují dvě lehátka 210 × 85 cm. Přidejte průchod, tašky a boty. Pokud se vše nevejde, nejprve změňte uspořádání, teprve potom uvažujte o větším bivaku.</p><h3>Rozpočet celé sestavy</h3><p>Modelově: bivak 6 500 Kč, lehátko 3 500 Kč, přehoz 1 800 Kč a spacák 2 000 Kč tvoří celkem 13 800 Kč. Jde o výpočet, ne nabídku. Porovnejte, zda jsou podlážka, kotvení a přepravní obal součástí konkrétního balení. Pokud už máte funkční lehátko, nekupujte druhé jen kvůli katalogové sestavě.</p>';
        }elseif($role==='bedchair'){
            $out.='<h3>Nejdřív prostor, potom nosnost a komfort</h3><p>Zapište si vlastní výšku a skutečně využitelnou délku ložné plochy. Při výběru pro vyšší postavu kontrolujte tvar hlavové a nožní části. Nosnost ověřte v dokumentaci konkrétní varianty; uvažujte i celkovou zátěž a pokyny pro použití. Samotný počet nohou není dokladem nosnosti. Zkontrolujte aretaci nohou, rozsah nastavení, patky, stabilitu na nerovném podkladu a dostupnost náhradních dílů.</p><h3>Přepravní rozměry a cena za noc</h3><p>V autě si předem změřte otvor kufru i prostor mezi ostatní výbavou. Složené lehátko může mít jiný tvar než rozložené. Zjistěte, zda se vejde se spacákem a kolik váží celá sestava. Jako jednoduchou pomůcku při porovnání lze pořizovací cenu vydělit očekávaným počtem nocí. Modelové lehátko za 4 800 Kč při 60 nocích vychází na 80 Kč za noc, bez údržby a případné zůstatkové hodnoty. Nejde o předpověď životnosti.</p>';
        }else{
            $out.='<h3>Ověření pomocí papírové šablony</h3><p>Pokud je bivak již doma, vyznačte rozměr zvažovaného lehátka na podlaze pomocí pásky nebo kartonu. Přidejte spacák, skutečnou šířku vyčnívajících kloubů a nohou a umístěte zavazadla tam, kde je budete používat. Vyzkoušejte zavření dveří a volnou cestu ven. Na nerovném břehu může nastavení nohou zvýšit lehátko, takže se opět přiblíží ke stěně.</p><h3>Kdy raději změnit bivak</h3><p>Pokud úzké lehátko neposkytuje vhodnou plochu pro váš spánek nebo nosnost modelu neodpovídá zatížení, není dobré slevit jen kvůli přístřešku. Porovnejte větší bivak se stávajícím lehátkem proti novému užšímu lehátku. Zohledněte hmotnost, cenu a prostor potřebný při přepravě. Výstup musí zůstat volný; v uzavřeném přístřešku nepoužívejte spalovací vařiče či topidla.</p>';
        }
        $out.='<div class="nry-gear-archive-entry"><p class="nry-gear-archive-entry__eyebrow">Další krok po ověření rozměrů</p><nav aria-label="Související nákupní průvodci">';
        $choices=$role==='bivak'?array(array(self::PILLAR,'Jak vybrat rybářské lehátko','Nosnost, matrace a přeprava'),array(self::BUDGET,'Bivaky do 10 000 Kč','Porovnejte konkrétní modely a jejich aktuální ceny')):array(array(self::BIVAK,'Jak vybrat velikost bivaku','Konstrukce a prostor'),array(self::SHORTLIST,'Srovnání rybářských lehátek','Konkrétní modely; ověřte rozměry a dostupnost'));
        foreach($choices as $a)$out.='<a class="nry-gear-archive-entry__choice" data-nry-fit-choice="'.self::e($a[0]).'" href="'.self::BASE.self::e($a[0]).'/"><strong>'.self::e($a[1]).'</strong><span>'.self::e($a[2]).'</span></a>';
        return $out.'</nav><p class="nry-gear-archive-entry__note">Nákupní průvodci mohou obsahovat partnerské odkazy. Zkontrolujte aktuální nabídky konkrétní varianty.</p></div></section>';
    }
    public static function transform($html,$r){
        if(!is_string($html)||strlen($html)<3000||strlen($html)>250000||strpos($html,self::MARKER)!==false)return $html;
        $matches=self::headings($html,'h1',$r['h1']);
        $anchorCount=substr_count(self::plain($html),self::plain($r['anchor']));
        if(count($matches)!==1||$anchorCount!==1){
            if(defined('NRY_FIT_PREFLIGHT'))fwrite(STDERR,"FIT_HEADING_MATCHES ".count($matches)." ANCHOR_COUNT ".$anchorCount."\n");
            return $html;
        }
        $original=$matches[0][0];$offset=$matches[0][1];
        // Preserve attributes and heading text; replace only heading level.
        $heading=preg_replace('~^<h1\b~i','<h2',$original);
        $heading=preg_replace('~</h1\s*>$~i','</h2>',$heading);
        $panel=self::panel($r);
        if(substr_count($panel,self::MARKER)!==1||substr_count($panel,'data-nry-fit-choice=')!==2)return $html;
        return substr($html,0,$offset).$heading."\n".$panel.substr($html,$offset+strlen($original));
    }
    public static function render($html){$r=self::eligible(true);return $r?self::transform($html,$r):$html;}
    public static function enqueue(){
        if(!self::eligible()||!function_exists('wp_enqueue_style')||!function_exists('wp_enqueue_script')||!function_exists('plugins_url'))return;
        wp_enqueue_style('nry-gear-archive-entry-v1',plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');
        wp_enqueue_style('nry-bivak-bedchair-fit-v1',plugins_url('assets/nry-bivak-bedchair-fit-v1.css',__FILE__),array(),'1.0.0');
        wp_enqueue_script('nry-bivak-bedchair-fit-v1',plugins_url('assets/nry-bivak-bedchair-fit-v1.js',__FILE__),array(),'1.0.0',true);
    }
    public static function boot(){
        if(!function_exists('is_single')||!function_exists('is_singular')||!function_exists('add_filter')||!function_exists('add_action'))return;
        add_filter('the_content',array(__CLASS__,'render'),80);
        add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),27);
    }
}
NRY_Bivak_Bedchair_Fit_V1::boot();


/** Related bedchair content only: explicit exact-page registry, no loader change.
 * Existing merchant URLs and all original media remain untouched.
 */
final class NRY_Bedchair_Load_Decision_V1 {
    const MARKER='data-nry-bedchair-load-guide="v1"';
    const BASE='https://na-ryby.eu/';
    const SCENARIO='jake-rybarske-lehatko-pro-tezsi-rybare-jak-vybrat-spravne';
    const LIST='nejlepsi-rybarska-lehatka-pro-tezsi-postavy-jak-vybrat-spravne';
    const TRIP='nejlepsi-rybarska-lehatka-pro-delsi-vypravy-top-vyber-a-srovnani';
    public static function routes(){return array(
        self::SCENARIO=>array('id'=>118779,'modified'=>'2026-04-25 14:37:52','date'=>'2026-03-24','title'=>'Jaké rybářské lehátko pro těžší rybáře | Jak vybrat správně','h1'=>'Jaké rybářské lehátko pro těžší rybáře','anchor'=>'Co je v této situaci opravdu důležité','role'=>'scenario'),
        self::LIST=>array('id'=>118569,'modified'=>'2026-03-21 17:37:33','date'=>'2026-03-21','title'=>'Nejlepší rybářská lehátka pro těžší postavy | Jak vybrat správně','h1'=>'Nejlepší rybářská lehátka pro těžší postavy','anchor'=>'Podle čeho jsem vybíral lehátka pro těžší postavy','role'=>'shortlist'),
        self::TRIP=>array('id'=>0,'modified'=>'','date'=>'2026-03-21','title'=>'Nejlepší rybářská lehátka pro delší výpravy: top výběr a srovnání','h1'=>'Nejlepší rybářská lehátka pro delší výpravy','anchor'=>'Podle čeho jsem vybíral','role'=>'trip'),
    );}
    private static function plain($s){return trim(preg_replace('~[\s\x{00a0}\x{202f}]+~u',' ',html_entity_decode(strip_tags($s),ENT_QUOTES|ENT_HTML5,'UTF-8')));}
    private static function headings($html,$tag,$text){
        preg_match_all('~<'.$tag.'\b[^>]*>[\s\S]{1,1200}?</'.$tag.'\s*>~iu',$html,$all,PREG_OFFSET_CAPTURE);
        return array_values(array_filter($all[0],function($m)use($text){return self::plain($m[0])===$text;}));
    }
    private static function attrs($s){preg_match_all('~(?:href|src)\s*=\s*(["\x27])([^"\x27]*)\1~i',$s,$m);return $m[0];}
    private static function eligible($loop=false){
        if(!is_singular('post')||is_admin()||is_preview()||is_feed()
          ||(function_exists('is_customize_preview')&&is_customize_preview())
          ||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
          ||($loop&&(!in_the_loop()||!is_main_query())))return null;
        $id=(int)get_queried_object_id();$p=get_post($id);
        if(!is_object($p)||(int)$p->ID!==$id||!is_single($id)||$p->post_type!=='post'||$p->post_status!=='publish'||!is_string($p->post_content))return null;
        $all=self::routes();$slug=$p->post_name;
        if(!isset($all[$slug])||get_permalink($id)!==self::BASE.$slug.'/')return null;
        $r=$all[$slug];
        if(($r['id']&&$id!==$r['id'])||($r['modified']!==''&&$p->post_modified_gmt!==$r['modified'])
          ||substr($p->post_date,0,10)!==$r['date']||self::plain($p->post_title)!==$r['title']
          ||substr_count(self::plain($p->post_content),$r['anchor'])!==1||count(self::headings($p->post_content,'h1',$r['h1']))!==1)return null;
        return $r;
    }
    public static function changes(){
        return array(
            'Nejvyšší rezervu nosnosti nabízí Carpers Specialist – dává smysl tam, kde je prioritou opravdu robustní konstrukce.'=>'Carpers Specialist je historický kandidát s uváděnou nosností 220 kg; OKfish jej při kontrole 28. 11. 2026 nenabízel k prodeji.',
            'Nejlepší kombinaci prostoru a praktičnosti vidím u modelů Delphin ST8 a Chyť a pusť Thunder 8 leg.'=>'Porovnejte rozměry a nosnost Delphinu ST8; dřívější set Thunder 8 leg s taškou nebyl při kontrole dostupný.',
            'Chci hlavně jistotu a rezervu: Carpers Specialist.'=>'Při vyšším zatížení hledejte dostupný model s dostatečnou doloženou nosností; historický Carpers Specialist nyní není nákupní doporučení.',
            'Chci něco rozumného za solidní peníze: Thunder 8 leg nebo Mivardi Comfort XL8.'=>'Při odpovídajícím zatížení a rozměrech prověřte Mivardi Comfort XL8; historický set Thunder 8 leg je nedostupný.',
            'Produktová stránka Chyť a pusť uvádí nosnost 170 kg a akci s taškou zdarma.'=>'Historický detail Chyť a pusť uvádí 170 kg; dne 28. 11. 2026 byl tento set označen jako nedostupný. Tašku zdarma nelze slibovat jako aktuální bonus.',
            '170 kg, akční set s taškou'=>'170 kg; historický set, nedostupný při kontrole',
            'Produktová stránka uvádí nosnost 220 kg a rozměry 90 × 210 cm.'=>'Historický detail OKfish uvádí 220 kg a 90 × 210 cm, ale při kontrole 28. 11. 2026 již produkt nebyl v prodeji.',
        );
    }
    private static function replaceVisible(&$out,$old,$new){
        // Prefer exact literal when it exists outside attributes. Otherwise
        // allow a bounded plain-text element with benign inline formatting.
        $pattern='~<(p|li|td|div|span)\b([^>]*)>((?:(?!</?(?:p|li|td|div|ul|ol|table|h[1-6])\b)[\s\S])*?)</\1\s*>~iu';
        $count=0;
        $candidate=preg_replace_callback($pattern,function($m)use($old,$new,&$count){
            $plain=self::plain($m[3]);
            if(substr_count($plain,$old)!==1)return $m[0];
            if(self::attrs($m[3])||preg_match('~<(?!/?(?:br|strong|b|em|i|span)\b)[^>]+>~i',$m[3]))return $m[0];
            $count++;
            return '<'.$m[1].$m[2].'>'.htmlspecialchars(str_replace($old,$new,$plain),ENT_QUOTES|ENT_HTML5,'UTF-8').'</'.$m[1].'>';
        },$out);
        if(!is_string($candidate)||$count!==1){
            if(defined('NRY_FIT_PREFLIGHT'))fwrite(STDERR,"LOAD_COPY_DRIFT ".substr($old,0,100)." count=".$count."\n");
            return false;
        }
        $out=$candidate;
        return true;
    }
    private static function scenario(){
        return <<<'HTML'
<section data-nry-bedchair-load-guide="v1" data-nry-load-role="scenario" aria-label="Jak vybrat nosnost lehátka pro vyšší zatížení">
<p><strong>Rychlé pravidlo:</strong> U lehátka pro těžšího rybáře nejprve ověřte deklarovanou nosnost konkrétní varianty a celkové zatížení podle návodu. Počet nohou, šířka ani reklamní označení XL samy o sobě nosnost neprokazují. Potom řešte pohodlí, přenášení a kompatibilitu s bivakem.</p>
<h2>Jak spočítat potřebnou nosnost rybářského lehátka</h2>
<p>Sečtěte vlastní hmotnost v oblečení, spací vybavení a další zátěž, kterou podle návodu na lehátku skutečně nesete. Nezapočítávejte hmotnost celého bivaku ani tašek uložených na zemi. Pokud není jasné, zda deklarace výrobce zahrnuje jen uživatele nebo veškerou zátěž, vyžádejte si vysvětlení. Do rozhodnutí použijte konzervativní celkový součet a nepřekračujte limit. Rezerva v následujícím modelu je pouze aritmetický rozdíl, nikoli odhad pevnosti nebo bezpečnosti.</p>
<div style="overflow-x:auto"><table><caption>Modelová zbývající nosnost v kg podle celkového zatížení</caption><thead><tr><th scope="col">Celkové zatížení</th><th scope="col">Nosnost 140 kg</th><th scope="col">Nosnost 160 kg</th><th scope="col">Nosnost 170 kg</th><th scope="col">Nosnost 200 kg</th></tr></thead><tbody>
<tr><td>125 kg</td><td>15 kg</td><td>35 kg</td><td>45 kg</td><td>75 kg</td></tr>
<tr><td>145 kg</td><td>Nevyhoví</td><td>15 kg</td><td>25 kg</td><td>55 kg</td></tr>
<tr><td>170 kg</td><td>Nevyhoví</td><td>Nevyhoví</td><td>0 kg</td><td>30 kg</td></tr>
<tr><td>185 kg</td><td>Nevyhoví</td><td>Nevyhoví</td><td>Nevyhoví</td><td>15 kg</td></tr></tbody></table></div>
<p>Příklad: rybář v oblečení váží 137 kg, spací vybavení a ostatní nesená zátěž 8 kg. Celkem je to 145 kg. U lehátka deklarovaného na 160 kg zbývá 15 kg podle tohoto jednoduchého součtu. Není to univerzální doporučená rezerva; sedání na kraj, nerovný terén, opotřebení a jiné použití nemusí odpovídat podmínkám výrobce. Lehátko přesně na deklarovaném limitu neberte jako doporučenou sestavu bez potvrzení výrobce.</p>
<h2>Co kontrolovat při nákupu pro vyšší zatížení</h2>
<p>Vyžádejte si skutečný rozměr lehací plochy, vnější rozměr rámu, způsob aretace a povolenou polohu při sedání. Širší matrace pomůže jen tehdy, když je pod ní odpovídající opora a lehátko se vejde do přístřešku. U výškově nastavitelných noh si zjistěte, zda výrobce připouští použití v dané výšce a na zvoleném povrchu. Na kluzkém, nestabilním nebo strmém břehu nepoužívejte lehátko jen proto, že má osm nohou. Nikdy nezvyšujte nosnost neautorizovanými výztuhami.</p>
<p>Při porovnání dvou modelů stejné nosnosti vybírejte podle prostoru pro spánek, skutečné hmotnosti při přepravě a servisovatelnosti. U delších výprav si zvlášť započítejte spacák a izolační vrstvu. Pokud chybí spolehlivý údaj o zatížení, model z užšího výběru vyřaďte, dokud jej výrobce nepotvrdí.</p>
<div class="nry-gear-archive-entry"><p class="nry-gear-archive-entry__eyebrow">Vyberte podle ověřených parametrů</p><nav aria-label="Nákupní průvodci pro vyšší zatížení">
<a class="nry-gear-archive-entry__choice" data-nry-load-path="models" href="https://na-ryby.eu/nejlepsi-rybarska-lehatka-pro-tezsi-postavy-jak-vybrat-spravne/"><strong>Konkrétní modely pro těžší postavy</strong><span>Porovnejte doloženou nosnost a historické nabídky odlište od dostupných alternativ.</span></a>
<a class="nry-gear-archive-entry__choice" data-nry-load-path="fit" href="https://na-ryby.eu/rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/"><strong>Ověřit rozměry v kalkulačce bivaku</strong><span>Vnější rám, spacák a prostor pro průchod.</span></a></nav></div>
</section>
HTML;
    }
    private static function shortlist(){
        return <<<'HTML'
<section data-nry-bedchair-load-guide="v1" data-nry-load-role="shortlist" aria-label="Porovnání nosnosti konkrétních modelů a dostupnosti">
<p><strong>Aktualizace nabídek 28. 11. 2026:</strong> Carpers Specialist byl na původní stránce OKfish označen jako neprodávaný a set Chyť a pusť Thunder 8 leg s taškou jako nedostupný. Nejde proto o aktuální nákupní tipy. U stávajícího odkazu na Mivardi Comfort XL8 prodejce při kontrole uváděl dostupnost; před objednáním ji vždy potvrďte. U ostatních modelů neuvádíme neověřenou skladovost. Nosnost žádného modelu zde nepředstavuje nezávislý zátěžový test.</p>
<h2>Které lehátko zvolit při celkovém zatížení 145 nebo 170 kg?</h2>
<p>Celkové zatížení zahrnuje podle podmínek výrobce uživatele a nesené vybavení. V následující tabulce počítáme rozdíl deklarované nosnosti a modelového celkového zatížení. Pokud rozdíl vyjde záporný, model nesplňuje ani číselný požadavek; nula není doporučenou rezervou. Kladné číslo není potvrzením bezpečné stability ani vhodnosti pro každého uživatele.</p>
<div style="overflow-x:auto"><table><caption>Doložené nebo historické nosnosti a prostý aritmetický rozdíl</caption><thead><tr><th scope="col">Model</th><th scope="col">Nosnost</th><th scope="col">Při 145 kg</th><th scope="col">Při 170 kg</th><th scope="col">Zdroj a nákupní stav</th></tr></thead><tbody>
<tr><td><a data-nry-load-detail="xl8" href="#nry-load-xl8">Mivardi Comfort XL8</a></td><td>160 kg</td><td>15 kg</td><td>Nevyhoví</td><td><a href="https://www.mivardi.cz/lehatko-comfort-xl8">Výrobce</a>; nabídku ověřte v původní kartě</td></tr>
<tr><td><a data-nry-load-detail="st8" href="#nry-load-st8">Delphin ST8 XXL</a></td><td>170 kg</td><td>25 kg</td><td>0 kg</td><td><a href="https://eshop.moss.sk/?moss=produkt&amp;oc=410095909">Výrobce MOSS.SK</a>; skladovost neověřena</td></tr>
<tr><td><a data-nry-load-detail="air8" href="#nry-load-air8">Mivardi New Dynasty AIR8</a></td><td>160 kg</td><td>15 kg</td><td>Nevyhoví</td><td><a href="https://www.mivardi.cz/mivardi-svet/clanky/testy-a-recenze/lehatko-new-dynasty-air8-recenze">Podklady výrobce</a>; skladovost neověřena</td></tr>
<tr><td>ZFISH Camo Condor 8-Leg</td><td>150 kg</td><td>5 kg</td><td>Nevyhoví</td><td><a href="https://www.zfish.cz/zfish-lehatko-camo-condor-bedchair-8-leg-p10811/?cid=3489">Výrobce</a>; skladovost konkrétní původní nabídky neověřena</td></tr>
<tr><td>Carpers Specialist (historický)</td><td>220 kg</td><td>75 kg</td><td>50 kg</td><td>Starý detail OKfish; při kontrole mimo prodej</td></tr>
<tr><td>Thunder 8 leg (historický set)</td><td>170 kg</td><td>25 kg</td><td>0 kg</td><td>Starý detail Chyť a pusť; při kontrole nedostupný</td></tr></tbody></table></div>
<p><strong>Nejste-li v rozsahu doložené nosnosti dostupných modelů, nekupujte slabší model jen kvůli ceně nebo dostupnosti.</strong> Vyžádejte si od prodejce přesnou variantu s vyšší deklarovanou nosností a návodem. Historický model s 220 kg nelze nahradit lehátkem s 160 nebo 170 kg, pokud by jeho limit neodpovídal skutečné zátěži.</p>
<h2>Praktické rozdíly mezi kandidáty</h2>
<p>Comfort XL8 a New Dynasty AIR8 mají podle podkladů Mivardi shodnou deklarovanou nosnost 160 kg. New Dynasty má deklarovaný rozměr 215 × 98 cm oproti 213 × 86 cm u Comfort XL8. Přidaná šířka 12 cm může znamenat více prostoru, nikoli vyšší povolenou zátěž. Je-li vaším hlavním omezením nosnost, příplatek za širší matraci problém s limitem nevyřeší. Delphin ST8 XXL má v podkladech výrobce matraci 210 × 95 cm, hmotnost 10 kg a deklarovanou nosnost 170 kg; ověřte přesné produktové označení, protože jiné varianty ST8 mohou mít jiné údaje.</p>
<p>Při výběru zohledněte skutečný rám včetně spacáku, nikoli jen deklarovanou lehací plochu. Nechte volný výstup a zkontrolujte podklad, aretaci a pokyny pro sedání a vstávání. O nosnosti rozhoduje dokumentace konkrétního výrobku, ne počet opěrných bodů nebo subjektivní dojem z robustnosti.</p>
<div class="nry-gear-archive-entry"><p class="nry-gear-archive-entry__eyebrow">Pokračujte k vhodné variantě</p><nav aria-label="Srovnání rozměrů a rozpočtu">
<a class="nry-gear-archive-entry__choice" data-nry-load-path="scenario" href="https://na-ryby.eu/jake-rybarske-lehatko-pro-tezsi-rybare-jak-vybrat-spravne/"><strong>Jak určit vlastní potřebnou nosnost</strong><span>Modelová zatížení, omezení a otázky na prodejce.</span></a>
<a class="nry-gear-archive-entry__choice" data-nry-load-path="budget" href="https://na-ryby.eu/nejlepsi-rybarska-lehatka-do-5000-kc/"><strong>Porovnat lehátka do 5 000 Kč</strong><span>Další modely, technické parametry a rozpočet; ověřte nosnost.</span></a></nav></div>
</section>
HTML;
    }
    private static function trip(){
        return <<<'HTML'
<section data-nry-bedchair-load-guide="v1" data-nry-load-role="trip" aria-label="Výběr lehátka na vícedenní rybářskou výpravu">
<p><strong>Na třídenní nebo týdenní výpravu vybírejte celou spací sestavu, nejen lehátko.</strong> Rozhoduje hmotnost při přesunu, rozměr v bivaku, doložená nosnost a izolace. Pokud je auto vedle lovného místa, může být těžší integrovaný systém praktický. Jestliže jdete stovky metrů pěšky, každý kilogram se projeví i při návratu s mokrou výbavou. Následující srovnání pomůže vybrat z modelů, které jsou již podrobně představené v článku. Nejde o nový vlastní test ani o záruku skladovosti.</p>
<h2>Lehátka na delší výpravy: porovnání samostatného rámu a kompletní sestavy</h2>
<div style="overflow-x:auto"><table><caption>Technické parametry podle dokumentace výrobců dohledané 28. 11. 2026</caption><thead><tr><th scope="col">Model</th><th scope="col">Co porovnáváme</th><th scope="col">Hmotnost</th><th scope="col">Deklarované zatížení</th><th scope="col">Zdroj</th></tr></thead><tbody>
<tr><td><a data-nry-trip-detail="xl6" href="#nry-trip-xl6">Mivardi Comfort XL6</a></td><td>Samostatné lehátko 210 × 86 cm</td><td>9,8 kg</td><td>140 kg</td><td><a href="https://www.mivardi.cz/lehatko-comfort-xl6">Mivardi</a></td></tr>
<tr><td><a data-nry-trip-detail="ergo" href="#nry-trip-ergo">Delphin ErgoCARP</a></td><td>Samostatné lehátko, kód 101006080</td><td>12 kg</td><td>Přibližně 200 kg za ideálních podmínek</td><td><a href="https://eshop.moss.sk/?moss=produkt&amp;oc=101006080">MOSS.SK</a></td></tr>
<tr><td><a data-nry-trip-detail="flat6" href="#nry-trip-flat6">Mivardi CamoCODE Flat6</a></td><td>Samostatné lehátko 205 × 80 cm</td><td>8,6 kg</td><td>140 kg</td><td><a href="https://www.mivardi.cz/bedchair-camocode-flat6">Mivardi</a></td></tr>
<tr><td><a data-nry-trip-detail="els" href="#nry-trip-els">Trakker Levelite ELS-MF Bed System</a></td><td>Standardní systém se spacákem, cca 215 × 90 cm</td><td>cca 19,4 kg</td><td>225 kg při běžném použití dle výrobce</td><td><a href="https://www.trakkerproducts.com/uk/product/levelite-els-mf-bed-system">Trakker</a></td></tr>
</tbody></table></div>
<p>U ErgoCARP výrobce výslovně rozlišuje statické a dynamické zatížení; orientační hodnota 200 kg v ideálních podmínkách není garantovaná nosnost při libovolném použití. U Trakkeru nezaměňujte standardní a Wide variantu. Samotné lehátko není hmotnostně srovnatelné s kompletní sestavou, dokud nepřipočtete spacák, potřebnou izolaci a transportní obal. U zbylých modelů níže ověřte přesné parametry konkrétní varianty v dokumentaci.</p>
<h2>Kolik opravdu ponesete od auta k vodě?</h2>
<p>Modelový samostatný spacák o hmotnosti 3 kg a obal o hmotnosti 0,5 kg zvýší hmotnost Flat6 z 8,6 kg na 12,1 kg. U Comfort XL6 by stejný příklad vyšel na 13,3 kg a u ErgoCARP na 15,5 kg. Integrovaný Trakker už spacák zahrnuje; s modelovým obalem 0,5 kg počítejte přibližně s 19,9 kg. Nejde o vážení hotových sestav ani o doporučení konkrétního spacáku. Skutečné hmotnosti zjistíte zvážením svého vybavení; potřebnou izolaci připočtěte zvlášť, pokud ji zvolená sestava nezahrnuje.</p>
<p>Pro představu při 400 m od auta a cestě tam i zpět představuje rozdíl 7,8 kg mezi modelovým Flat6 a Trakkerem výraznou změnu neseného nákladu. Pokud používáte vozík, zkontrolujte šířku, rozložení nákladu, stabilitu na cestě a jeho povolené zatížení. Pokud k lovišti vedou schody nebo úzké pěšiny, zvažte raději lehčí sestavu nebo více bezpečných cest. V zimě přitom nesnižujte izolaci pouze kvůli hmotnosti.</p>
<h2>Kdy se vyplatí investovat do dražšího spacího systému?</h2>
<p>Pro rozhodování porovnejte očekávaný počet nocí, ne jen pořizovací cenu. U modelové modulární sestavy za 6 000 Kč a 60 nocí vychází jednoduchý náklad na 100 Kč za noc. U kompletní sestavy za 12 000 Kč a stejných 60 nocí je to 200 Kč; při 120 nocích 100 Kč. Jde o účetní příklad bez údržby a zůstatkové hodnoty, nikoli odhad životnosti nebo skutečné ceny uvedených produktů. Zvažte, zda lze samostatně vyměnit spacák, potah, nohy či další díly a zda je servis dostupný.</p>
<h2>Výběr podle tří konkrétních výprav</h2>
<ul><li><strong>Víkend s dlouhým přístupem:</strong> porovnejte lehčí samostatné rámy, které odpovídají vaší postavě a povolenému zatížení. Zvažte hmotnost s vlastním spacákem.</li><li><strong>Tři až sedm nocí poblíž auta:</strong> větší prostor, možnost sušení a vhodná matrace mohou mít větší význam než rozdíl několika kilogramů. Prověřte vnější rozměr celé sestavy a volný východ.</li><li><strong>Chladné vícedenní výpravy:</strong> nejdřív ověřte teplotní parametry spacáku, izolaci pod tělem a plán ukončení výpravy. Označení počtem sezon nestačí k určení bezpečné teploty.</li></ul>
<h3>Kontrola před nákupem a před cestou</h3><p>Změřte kufr auta, šířku přístupové cesty a využitelný prostor bivaku ve výšce matrace. V obchodě si ověřte, co balení opravdu obsahuje. Doma vyzkoušejte lehátko, aretaci nohou, složení se spacákem a možnost rychlého výstupu. Mokrou výbavu při balení oddělte od suchých vrstev. U nerovného podkladu se řiďte návodem výrobce; počet nohou není náhradou za bezpečné usazení.</p>
<div class="nry-gear-archive-entry"><p class="nry-gear-archive-entry__eyebrow">Vyřešte celou sestavu</p><nav aria-label="Další rozhodovací průvodci"><a class="nry-gear-archive-entry__choice" data-nry-load-path="fit" href="https://na-ryby.eu/rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/"><strong>Kalkulačka rozměrů lehátka a bivaku</strong><span>Ověřte vlastní rozměry a průchod.</span></a><a class="nry-gear-archive-entry__choice" data-nry-load-path="winter" href="https://na-ryby.eu/jake-rybarske-lehatko-na-zimu-prakticky-vyber/"><strong>Zimní spací sestava</strong><span>Izolace, teplotní údaje a rozpočet.</span></a><a class="nry-gear-archive-entry__choice" data-nry-load-path="budget" href="https://na-ryby.eu/nejlepsi-rybarska-lehatka-do-5000-kc/"><strong>Lehátka do 5 000 Kč</strong><span>Porovnejte sedm konkrétních modelů.</span></a></nav><p class="nry-gear-archive-entry__note">Původní produktové karty níže mohou obsahovat partnerské odkazy. Před objednáním ověřte cenu, variantu a dostupnost u prodejce.</p></div>
</section>
HTML;
    }
    public static function transform($html,$r){
        if(!is_string($html)||strlen($html)<3000||strlen($html)>250000||strpos($html,self::MARKER)!==false
          ||substr_count(self::plain($html),$r['anchor'])!==1)return $html;
        $out=$html;
        if($r['role']==='shortlist'){
            foreach(self::changes() as $old=>$new)if(!self::replaceVisible($out,$old,$new))return $html;
            $edits=array();
            foreach(array('xl8'=>'Mivardi Comfort XL8','st8'=>'Delphin ST8','air8'=>'Mivardi New Dynasty AIR8') as $key=>$name){
                $m=self::headings($out,'h3',$name);
                if(count($m)!==1||strpos($out,'nry-load-'.$key)!==false){
                    if(defined('NRY_FIT_PREFLIGHT'))fwrite(STDERR,"LOAD_MODEL_DRIFT ".$key."\n");
                    return $html;
                }
                $edits[]=array($m[0][1],'<span id="nry-load-'.$key.'" aria-hidden="true" style="display:block;scroll-margin-top:100px"></span>');
            }
            foreach(array('Carpers Specialist'=>'Historický model: původní nabídka OKfish nebyla 28. 11. 2026 v prodeji.','Chyť a pusť Thunder 8 leg + taška'=>'Historický set: při kontrole 28. 11. 2026 nedostupný, bonus s taškou není aktuální příslib.') as $name=>$note){
                $m=self::headings($out,'h3',$name);
                if(count($m)!==1)return $html;
                $edits[]=array($m[0][1],'<p data-nry-load-historical="v1"><strong>'.$note.'</strong></p>');
            }
            usort($edits,function($a,$b){return $b[0]<=>$a[0];});
            foreach($edits as $edit)$out=substr_replace($out,$edit[1],$edit[0],0);
        }
        if($r['role']==='trip'){
            $edits=array();
            foreach(array('xl6'=>'Mivardi Lehátko Comfort XL6','ergo'=>'Delphin Lehátko ErgoCarp','flat6'=>'Lehátko MIVARDI CamoCODE Flat6','els'=>'Trakker Lehátko Levelite ELS-MF Bed System') as $key=>$name){
                $m=self::headings($out,'h3',$name);
                if(count($m)!==1||strpos($out,'nry-trip-'.$key)!==false){
                    if(defined('NRY_FIT_PREFLIGHT'))fwrite(STDERR,"TRIP_MODEL_DRIFT ".$key."\n");
                    return $html;
                }
                $edits[]=array($m[0][1],'<span id="nry-trip-'.$key.'" aria-hidden="true" style="display:block;scroll-margin-top:100px"></span>');
            }
            usort($edits,function($a,$b){return $b[0]<=>$a[0];});
            foreach($edits as $edit)$out=substr_replace($out,$edit[1],$edit[0],0);
        }
        $h=self::headings($out,'h1',$r['h1']);if(count($h)!==1)return $html;
        $old=$h[0][0];$offset=$h[0][1];
        $heading=preg_replace('~^<h1\b~i','<h2',$old);
        $heading=preg_replace('~</h1\s*>$~i','</h2>',$heading);
        $out=substr_replace($out,$heading,$offset,strlen($old));
        if(self::attrs($out)!==self::attrs($html))return $html;
        $panel=$r['role']==='scenario'?self::scenario():($r['role']==='trip'?self::trip():self::shortlist());
        if(substr_count($panel,self::MARKER)!==1||substr_count($panel,'data-nry-load-path=')!==($r['role']==='trip'?3:2))return $html;
        $at=$offset+strlen($heading);
        return substr($out,0,$at)."\n".$panel.substr($out,$at);
    }
    public static function render($html){$r=self::eligible(true);return $r?self::transform($html,$r):$html;}
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
NRY_Bedchair_Load_Decision_V1::boot();
