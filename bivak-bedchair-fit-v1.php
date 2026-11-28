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
            self::BIVAK=>array('id'=>118388,'date'=>'2026-03-19','modified'=>'2026-11-15 14:03:11','title'=>'Jak vybrat rybářský bivak: průvodce materiály, konstrukcí a velikostí','h1'=>'Jak vybrat rybářský bivak: průvodce materiály, konstrukcí a velikostí (2026)','anchor'=>'1-man vs. 2-man: kde lidi dělají největší chyby','role'=>'bivak','label'=>'Vejde se lehátko do bivaku? Spočítejte prostor před nákupem'),
            self::PILLAR=>array('id'=>0,'date'=>'2026-03-21','modified'=>'','title'=>'Rybářská lehátka: kompletní průvodce výběrem pro rybáře','h1'=>'Rybářská lehátka: kompletní průvodce výběrem pro kapraře i víkendové rybáře','anchor'=>'Komu tenhle článek pomůže a v jaké situaci','role'=>'bedchair','label'=>'Ověřte rozměry lehátka, než objednáte'),
            self::SMALL=>array('id'=>0,'date'=>'2026-03-24','modified'=>'','title'=>'Jaké rybářské lehátko do malého bivaku | Praktický výběr','h1'=>'Jaké rybářské lehátko do malého bivaku','anchor'=>'Co je v malém bivaku opravdu důležité','role'=>'compact','label'=>'Kalkulačka pro malé bivaky: délka, šířka a průchod'),
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
        if(($r['id']&&$id!==$r['id'])||($r['modified']!==''&&$p->post_modified_gmt!==$r['modified'])
            ||substr($p->post_date,0,10)!==$r['date']
            ||html_entity_decode($p->post_title,ENT_QUOTES|ENT_HTML5,'UTF-8')!==$r['title']
            ||strpos($p->post_content,$r['anchor'])===false||strpos($p->post_content,$r['h1'])===false)return null;
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
        $pat='~<h1\b([^>]*)>\s*'.preg_quote($r['h1'],'~').'\s*</h1\s*>~u';
        if(preg_match_all($pat,$html,$m,PREG_OFFSET_CAPTURE)!==1||substr_count($html,$r['anchor'])!==1)return $html;
        $original=$m[0][0][0];$offset=$m[0][0][1];
        // Preserve attributes and heading text; replace only heading level.
        $heading=preg_replace('~^<h1\b~','<h2',$original);
        $heading=preg_replace('~</h1\s*>$~','</h2>',$heading);
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
