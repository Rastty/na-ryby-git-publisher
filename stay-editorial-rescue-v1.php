<?php
/**
 * Legacy 2024 fishing-stay article: bounded visible editorial repair.
 * Source phrases and exact public identity guard all changes. No DB writes.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Stay_Editorial_Rescue_V1 {
    const SLUG='tipy-pro-perfektni-rybareni-a-ubytovani-u-vody';
    const TITLE='Tipy pro perfektní rybaření a ubytování u vody';
    const DATE='2024-05-01';
    const MARKER='data-nry-stay-editorial-rescue="v1"';
    const STAYS='https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const RIS='https://ris.rybsvaz.cz/';
    const LIPNO='https://www.rybsvaz.cz/karta-reviru/-/421200-vltava-30-32-udolni-nadrz-lipno';
    const OLD_HEADING='Dobrovolník českého jazyka';
    const OLD_INTRO='Tady napsat text o tipy na';
    const OLD_FAQ1='Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed ultricies, eros a porta facilisis, velit turpis rutrum felis, et efficitur eros lorem in ipsum.';
    const OLD_FAQ2='Nulla facilisi. Nam faucibus lacus ut dolor porttitor, at tincidunt lacus varius. Curabitur vitae lacus non tortor aliquam tristique in vel felis.';
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
        foreach(array(self::OLD_HEADING,self::OLD_INTRO,self::OLD_FAQ1,self::OLD_FAQ2) as $a)
            if(substr_count($p->post_content,$a)!==1)return false;
        return true;
    }
    private static function e($s){return htmlspecialchars($s,ENT_QUOTES|ENT_HTML5,'UTF-8');}
    private static function offer($key,$title,$copy,$href){
        return '<a class="nry-gear-archive-entry__choice" data-nry-stay-editorial-offer="'.self::e($key).'" href="'.self::e($href).'" target="_blank" rel="sponsored nofollow noopener"><strong>'.self::e($title).'</strong><span>'.self::e($copy).'</span></a>';
    }
    public static function guide() {
        $out='<section '.self::MARKER.' aria-label="Praktický průvodce rybářskou dovolenou">'
        .'<p><strong>Rychlá odpověď:</strong> Nejdřív si vyberte konkrétní vodu, kde smíte v plánovaném termínu a zvoleným způsobem lovit. Až potom hledejte ubytování s přístupem k vhodnému břehu nebo s krátkým dojezdem. Samotné označení chata u vody nezaručuje možnost lovit z pozemku.</p>'
        .'<div class="nry-gear-archive-entry"><p class="nry-gear-archive-entry__eyebrow">Lipno a Vranov · konkrétní možnosti ubytování</p>'
        .'<h2>Kam na ryby s ubytováním: tři možnosti k porovnání</h2>'
        .'<p>Vyberte podle oblasti, kde skutečně plánujete chytat. Následující objekty jsou samostatná ubytování, ne garantované balíčky s povolenkou.</p><nav aria-label="Partnerské možnosti pobytu">';
        $links=NRY_Six_Real_Stay_Offers_V1::approvedLinks();
        $out.=self::offer('kohoutu','Pension U Kohoutů – Lipno','Horní Planá; ověřte volný pokoj, termín a cenu.',NRY_Jihocesky_Lipno_Stays_13088_V1::HPORTAL);
        $out.=self::offer('cyklo','Cyklochata – Vranov','Štítary; ověřte kapacitu skupiny a přístup k vodě.',$links['cyklo']);
        $out.=self::offer('srub','Rybářský wellness srub – Vranov','Lančov; ověřte termín, služby a stav vody.',$links['srub']);
        $out.='</nav><p class="nry-gear-archive-entry__note">Partnerské odkazy Hauzi a Hportal. Nejde o garantované rybářské balíčky ani ověřené volné termíny. Ceny, podmínky a povolenky zjistěte zvlášť. Na-Ryby.eu ubytování ani revír neprovozuje. <a href="'.self::STAYS.'">Další možnosti pobytu</a>.</p></div>'
        .'<h2>Jak naplánovat rybaření s ubytováním u vody</h2>'
        .'<h3>1. Vyberte typ výpravy</h3><ul><li><strong>Víkend s partou:</strong> řešte počet samostatných lůžek, parkování, sušení oblečení a celkovou cenu za skupinu. Pokud každý chytá jinou technikou, ověřte, že zvolený revír umožňuje všechny plánované metody.</li><li><strong>Výprava ve dvou:</strong> rozumný kompromis je pokoj či apartmán s pohodlným zázemím a samostatným programem pro doprovod. Ptejte se na čas příjezdu a odjezdu, abyste nepřišli o velkou část krátkého víkendu.</li><li><strong>Rodina:</strong> zkontrolujte bezpečný přístup k vodě, vzdálenost od rušné silnice a možnosti programu mimo rybaření. U vody zajistěte nepřetržitý dohled nad dětmi.</li></ul>'
        .'<h3>2. Ověřte oprávnění a přístup</h3><p>V rybářském revíru si předem ověřte požadovaný rybářský lístek a povolenku, aktuální podmínky, hájení, povolené metody a případná omezení plavidel. U soukromých vod zjistěte právní režim a podmínky přímo od provozovatele. Nestačí, že máte zaplacenou chatu. Pro svazové vody začněte v <a href="'.self::RIS.'">oficiálním informačním systému ČRS</a>. Například Lipno má vlastní bližší podmínky na <a href="'.self::LIPNO.'">kartě revíru 421 200 – Vltava 30–32 (ÚN Lipno)</a>. Zkontrolujte aktuální pravidla před každou cestou.</p>'
        .'<h3>3. Srovnejte konečnou cenu</h3><p>Při nabídce 4 800 Kč za dvě noci pro čtyři osoby vychází modelová cena na 600 Kč za osobu a noc před dalšími poplatky. Připočtěte dopravu, povolenky, stravu, parkování a případný úklid. Porovnávejte stejný počet osob a stejný termín. Nejde o aktuální ceník žádného ubytování.</p>'
        .'<div style="overflow-x:auto"><table><caption>Modelové srovnání nákladů na dvě noci pro čtyři osoby</caption><thead><tr><th>Položka</th><th>Chata A</th><th>Apartmán B</th></tr></thead><tbody><tr><td>Ubytování</td><td>4 800 Kč</td><td>5 200 Kč</td></tr><tr><td>Úklid a další poplatky</td><td>800 Kč</td><td>0 Kč</td></tr><tr><td>Místní přesuny</td><td>400 Kč</td><td>200 Kč</td></tr><tr><td>Celkem za skupinu</td><td>6 000 Kč</td><td>5 400 Kč</td></tr><tr><td>Za osobu a noc</td><td>750 Kč</td><td>675 Kč</td></tr></tbody></table></div>'
        .'<p>Ilustrační výpočet ukazuje, proč nejnižší základní cena nemusí znamenat nejlevnější výpravu. Stravu a oprávnění k lovu zde předpokládáme stejné pro obě varianty; skutečné položky si zjistěte u poskytovatelů.</p>'
        .'<h2>Jak vybrat chatu, penzion nebo kemp pro rybáře</h2><p>Chata dává smysl, když chcete mít společné zázemí pro partu a vlastní režim. U penzionu zvažte pohodlí, dostupnost snídaně a možnost bezpečně uschovat výbavu. Apartmán bývá praktický při vlastním vaření a více nocích. Kemp vybírejte podle povoleného způsobu nocování, sociálního zázemí, provozní doby a podmínek při nepříznivém počasí. Nocování přímo na lovném místě není automaticky povoleno.</p>'
        .'<h3>Před rezervací položte šest otázek</h3><ol><li>Která konkrétní voda je dostupná a jak dlouho trvá skutečná cesta k lovišti?</li><li>Smí se lovit z pozemku, nebo musíte na jiný veřejně přístupný břeh?</li><li>Jaká oprávnění potřebuji a kde je mohu získat?</li><li>Je možné bezpečně uložit pruty, baterie a mokrou výbavu?</li><li>Jaká je celková cena včetně povinných poplatků?</li><li>Co se stane při změně termínu nebo nepříznivém počasí?</li></ol>'
        .'<h2>Výbava na rybářský víkend a delší dovolenou</h2><p>Na feeder zvolte prut a krmítka odpovídající proudu a vzdálenosti. Pro přívlač počítejte s častým pohybem, lehkým podběrákem a vhodnou obuví. Kaprař potřebuje také zázemí pro delší pobyt u vody. Vždy přibalte pomůcky pro šetrné zacházení s úlovkem, ochranu před počasím, osvětlení, lékárničku a případně záchrannou vestu pro lov z plavidla.</p>'
        .'<p>Pokud plánujete chytat více dní, rozdělte výbavu na část, kterou berete k vodě, a zásoby ponechané v ubytování. Usnadníte si přesuny a snížíte množství drahého vybavení ponechaného bez dozoru. Při lovu ze břehu si předem zjistěte, zda se k místu dostanete s vozíkem nebo zda budete vše nosit v ruce. Při delším pobytu si nechte rezervní den na déšť či silný vítr.</p>'
        .'<h2>Nejčastější chyby při rezervaci rybářského pobytu</h2><ul><li>Rezervujete podle vzdálenosti od hladiny, nikoli od povoleného místa lovu.</li><li>Předpokládáte, že ubytování zahrnuje povolenku nebo vyhrazené lovné místo.</li><li>Porovnáváte cenu za pokoj s cenou za osobu bez přepočtu.</li><li>Zapomenete na mokrou výbavu, nabíjení elektroniky a bezpečné uložení prutů.</li><li>Plánujete celý pobyt bez rezervy pro počasí a program doprovodu.</li></ul>'
        .'<p><strong>Doporučení:</strong> Před platbou si nechte potvrdit podstatné podmínky písemně. Ubytování vybírejte až po ověření revíru. Jestliže chcete lovit přímo z pozemku, potvrďte si tuto možnost i oprávnění ke vstupu a lovu.</p></section>';
        return $out;
    }
    public static function transform($html) {
        if (!is_string($html)||strlen($html)<1000||strpos($html,self::MARKER)!==false)return $html;
        $pairs=array(
            self::OLD_INTRO=>'Při plánování výpravy spojte',
            self::OLD_FAQ1=>'Výbavu přizpůsobte revíru a způsobu lovu. Základ tvoří vhodný prut a naviják, montáže, podběrák a pomůcky pro šetrné zacházení s rybami. Na delší výpravu přidejte ochranu před deštěm, osvětlení, lékárničku a potřebná oprávnění k lovu.',
            self::OLD_FAQ2=>'Začněte konkrétním revírem a ověřte jeho pravidla, přístupné břehy a podmínky v plánovaném termínu. Ubytování vybírejte podle skutečné dostupnosti loviště, nikoli jen podle fotografie jezera.',
        );
        foreach($pairs as $old=>$new) if(substr_count($html,$old)!==1)return $html;
        $pattern='~<h1\b([^>]*)>\s*'.preg_quote(self::OLD_HEADING,'~').'\s*</h1\s*>~iu';
        if(preg_match_all($pattern,$html)!==1)return $html;
        $out=str_replace(array_keys($pairs),array_values($pairs),$html);
        $out=preg_replace($pattern,'<h2$1>Plán výpravy krok za krokem</h2>',$out,1);
        // Keep every original link and image unchanged.
        preg_match_all('~(?:href|src)\s*=\s*(["\x27])([^"\x27]*)\1~i',$html,$before);
        preg_match_all('~(?:href|src)\s*=\s*(["\x27])([^"\x27]*)\1~i',$out,$after);
        if($before[0]!==$after[0])return $html;
        return self::guide()."\n".$out;
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
NRY_Stay_Editorial_Rescue_V1::boot();
