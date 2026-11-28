<?php
/**
 * Exact-source, render-only boat payload check for one existing buyer guide.
 * No stored content mutation, merchant routing, visitor storage or external calls.
 * Rollback: remove this file's require from the main plugin and redeploy.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Boat_Load_Check_119598_V1 {
    const ID = 119598;
    const SLUG = 'nejlepsi-nafukovaci-cluny-pro-rybare';
    const MODIFIED = '2026-05-15 12:17:30';
    const MARKER = 'data-nry-boat-load="v1"';
    const CJ = 'click-100927011-12347575';
    const ANCHOR = '<h2 style="font-size: 30px; line-height: 1.3; margin: 36px 0 16px 0; color: #0f172a;">Nosnost: proč nestačí počítat jen lidi</h2>';

    public static function eligible() {
        if (!function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_post') || !function_exists('get_permalink')
            || get_permalink(self::ID) !== 'https://na-ryby.eu/'.self::SLUG.'/') return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post' && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED;
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches) === false) return null;
        return $matches[2];
    }

    private static function form() {
        return '<section '.self::MARKER.' aria-label="Kontrola celkové zátěže rybářského člunu"'
            .' style="border:2px solid #0369a1;border-radius:12px;background:#f0f9ff;padding:18px;margin:20px 0;color:#0c4a6e">'
            .'<h3 style="margin:0 0 8px;color:#075985;font-size:21px">Vejde se vaše sestava do nosnosti člunu?</h3>'
            .'<p>Sečtěte hmotnost lidí, motoru, baterie a všech věcí, které povezete. Do prvního pole přepište'
            .' <strong>nejvyšší doporučenou celkovou zátěž</strong> přímo ze štítku nebo návodu konkrétního člunu.'
            .' Nezaměňujte ji s hmotností samotného člunu ani s limitem jen pro osoby.</p>'
            .'<form data-nry-boat-load-form="v1" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:12px;align-items:end">'
            .'<label>Maximální celková zátěž (kg)<input name="capacity" type="number" min="1" max="2500" step="0.5" required'
            .' placeholder="Údaj výrobce" inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Všichni lidé dohromady (kg)<input name="people" type="number" min="1" max="1000" step="0.5" required'
            .' placeholder="Součet hmotností" inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Motor a držák (kg)<input name="motor" type="number" min="0" max="800" step="0.5" required value="0"'
            .' inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Baterie (kg)<input name="battery" type="number" min="0" max="800" step="0.5" required value="0"'
            .' inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Rybářská výbava (kg)<input name="gear" type="number" min="0" max="800" step="0.5" required value="0"'
            .' inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<label>Ostatní náklad (kg)<input name="other" type="number" min="0" max="800" step="0.5" required value="0"'
            .' inputmode="decimal" style="display:block;width:100%;padding:9px;box-sizing:border-box;margin-top:5px"></label>'
            .'<button type="submit" style="cursor:pointer;border:0;border-radius:8px;padding:11px 15px;background:#0369a1;color:#fff;font-weight:bold">Spočítat zátěž</button>'
            .'</form><output data-nry-boat-load-result="v1" role="status" aria-live="polite"'
            .' style="display:block;font-size:17px;font-weight:bold;margin:14px 0 7px">Vyplňte údaje ze štítku a vaši sestavu.</output>'
            .'<p style="font-size:14px;margin:8px 0 0"><strong>Důležité:</strong> I když součet nepřekročí limit,'
            .' nejde o potvrzení bezpečné plavby. Samostatně ověřte maximální počet osob, povolený motor,'
            .' rozložení zátěže, doporučení výrobce, počasí, záchranné vesty a pravidla revíru.'
            .' Údaje a dostupnost konkrétního člunu vždy ověřte před nákupem.</p></section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 18000
            || strpos($html, self::MARKER) !== false
            || substr_count($html, self::ANCHOR) !== 1
            || substr_count($html, self::CJ) !== 13
            || strpos($html, 'Rychlý orientační výpočet zátěže') === false) return $html;
        $before = self::hrefs($html);
        if ($before === null || count($before) < 13) return $html;
        $next = str_replace(self::ANCHOR, self::ANCHOR.self::form(), $html);
        $after = self::hrefs($next);
        return ($before === $after && substr_count($next, self::MARKER) === 1) ? $next : $html;
    }

    public static function render($html) { return self::eligible() ? self::transform($html) : $html; }

    public static function enqueue() {
        if (self::eligible() && function_exists('wp_enqueue_script') && function_exists('plugins_url')) {
            wp_enqueue_script('nry-boat-load-v1', plugins_url('assets/nry-boat-load-v1.js', __FILE__),
                array(), '1.0.0', true);
        }
    }

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_singular')) {
            add_filter('the_content', array(__CLASS__, 'render'), 79);
        }
        if (function_exists('add_action') && function_exists('is_singular')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'));
        }
    }
}
NRY_Boat_Load_Check_119598_V1::boot();


/** Related source-pinned buying content; leaves the calculators and old links intact. */
final class NRY_Boat_System_Buyer_V1 {
    const MARKER='data-nry-boat-system-guide=';
    public static function routes(){return array(
        119598=>array('slug'=>'nejlepsi-nafukovaci-cluny-pro-rybare','modified'=>'2026-05-15 12:17:30','role'=>'boat','anchor'=>NRY_Boat_Load_Check_119598_V1::ANCHOR),
        119594=>array('slug'=>'nejlepsi-lodni-elektromotory-pro-rybare','modified'=>'2026-05-15 10:37:45','role'=>'motor','anchor'=>'<h3 style="font-size: 24px; margin: 24px 0 10px 0; color: #0f172a;">Jakou kapacitu baterie vybrat?</h3>'),
    );}
    private static function plain($s){return trim(preg_replace('~[\s\x{00a0}\x{202f}]+~u',' ',html_entity_decode(strip_tags($s),ENT_QUOTES|ENT_HTML5,'UTF-8')));}
    public static function panel($role){
        if($role==='boat')return <<<'HTML'
<section data-nry-boat-system-guide="boat" aria-label="Srovnání konkrétních nafukovacích člunů a rozpočet sestavy">
<p><strong>Rychlá volba před nákupem:</strong> porovnejte konkrétní člun, nikoli pouze kategorii 240, 280 nebo 320 cm. Délka sama neurčuje povolený náklad, šířku uvnitř, počet osob ani přípustný motor. Následující příklady vycházejí z produktových údajů výrobců, nejsou výsledkem vlastního jízdního testu.</p>
<h2>Čtyři konkrétní rybářské čluny: nosnost, velikost a podlaha</h2>
<div style="overflow-x:auto"><table><caption>Porovnání produktových listů výrobců, 28. 11. 2026</caption><thead><tr><th scope="col">Model</th><th scope="col">Délka</th><th scope="col">Max. deklarovaná zátěž</th><th scope="col">Podlaha</th><th scope="col">Zdroj parametrů</th></tr></thead><tbody>
<tr><td>Mivardi M-Boat 240AWB MK2</td><td>240 cm</td><td>320 kg</td><td>Nafukovací</td><td><a href="https://www.mivardi.cz/m-boat-240awb-mk2">Mivardi, M-MB240AWB2</a></td></tr>
<tr><td>Mivardi M-Boat 280AWB MK2</td><td>280 cm dle produktové stránky</td><td>372,5 kg</td><td>Nafukovací</td><td><a href="https://www.mivardi.cz/clun-m-boat-280awb-mk2">Mivardi, M-MB280AWB2</a></td></tr>
<tr><td>Fox 290 X Boat</td><td>290 cm</td><td>438 kg</td><td>Varianty Air Deck / hliník</td><td><a href="https://www.foxint.com/home/product/fox-290-x-boat">Fox</a></td></tr>
<tr><td>Mivardi M-BOAT 320P MK2</td><td>320 cm</td><td>481 kg</td><td>Pevná</td><td><a href="https://www.mivardi.cz/rybarsky-clun-m-boat-320p-mk2">Mivardi</a></td></tr>
</tbody></table></div>
<p>U všech variant si před nákupem nechte potvrdit údaje na štítku a v návodu, včetně povoleného počtu osob, rozměrů uvnitř a limitu hmotnosti motoru. Čísla na produktové stránce se mohou lišit od dokumentace konkrétního výrobního provedení. Maximální nosnost není zárukou bezpečného provozu při větru, vlnách nebo nevhodném rozložení nákladu. Pokud se údaje rozcházejí, nepřebírejte vyšší hodnotu bez potvrzení výrobce.</p>
<h2>Modelový náklad pro jednoho a dva rybáře</h2>
<div style="overflow-x:auto"><table><caption>Ilustrační hmotnost ve člunu, nikoli skutečná hmotnost uvedených výrobků</caption><thead><tr><th scope="col">Zátěž</th><th scope="col">Jeden rybář</th><th scope="col">Dva rybáři</th></tr></thead><tbody><tr><td>Posádka v oblečení</td><td>95 kg</td><td>180 kg</td></tr><tr><td>Motor a držák</td><td>12 kg</td><td>12 kg</td></tr><tr><td>Baterie a box</td><td>15 kg</td><td>24 kg</td></tr><tr><td>Výbava, voda a ostatní náklad</td><td>35 kg</td><td>65 kg</td></tr><tr><td>Celkem</td><td>157 kg</td><td>281 kg</td></tr></tbody></table></div>
<p>U druhého příkladu zbývá proti deklarovaným 320 kg aritmeticky 39 kg, u 372,5 kg je rozdíl 91,5 kg. Výsledek neřeší prostor, povolený počet osob, bezpečnou plavbu ani doporučené zatížení v konkrétních podmínkách. Změřte i vnitřní prostor mezi válci a zrcadlem; dvě tašky, baterie a sedící posádka mohou zabrat podstatně více místa, než napovídá vnější délka. Vlastní sestavu zadejte do kalkulačky níže.</p>
<h2>Rozpočet kompletní sestavy člun + motor + baterie</h2>
<div style="overflow-x:auto"><table><caption>Modelové rozpočty, nikoli nabídky prodejců ani doporučené kombinace konkrétních výrobků</caption><thead><tr><th scope="col">Položka</th><th scope="col">Úspornější sestava</th><th scope="col">Rozšířená sestava</th></tr></thead><tbody><tr><td>Člun</td><td>18 000 Kč</td><td>28 000 Kč</td></tr><tr><td>Elektromotor</td><td>5 000 Kč</td><td>10 000 Kč</td></tr><tr><td>Kompatibilní baterie a nabíječka</td><td>7 000 Kč</td><td>12 000 Kč</td></tr><tr><td>Vesty, schválené příslušenství a další výbava</td><td>3 000 Kč</td><td>5 000 Kč</td></tr><tr><td>Celkem</td><td>33 000 Kč</td><td>55 000 Kč</td></tr></tbody></table></div>
<p>Rozpočet musí vycházet z konkrétní kompatibilní sestavy. Nejdřív si ověřte, co už je v ceně člunu (podlaha, vesla, pumpa, opravná sada, taška), a následně cenu baterie s odpovídajícím proudovým limitem a nabíječkou. Nepoužívejte tah motoru v librách jako náhradu údaje o povoleném výkonu v kW. Nechte si potvrdit vhodné uchycení, délku nohy motoru a požadovanou elektroinstalaci. Na povinné a bezpečnostní výbavě nešetřete jen proto, abyste splnili modelový rozpočet.</p>
<h2>Před objednáním: šest otázek na prodejce</h2>
<ol><li>Jaký je přesný kód varianty a co obsahuje balení?</li><li>Jaká je celková zátěž, počet osob a povolený motor podle štítku?</li><li>Jak velký je využitelný prostor uvnitř a kolik váží jednotlivé přepravní balíky?</li><li>Jakou podlahu, pumpu a postup nafouknutí výrobce předepisuje?</li><li>Jaké jsou doporučené uchycení motoru, baterie a příslušenství?</li><li>Kde je dostupný servis, náhradní ventily a návod?</li></ol>
<p>Před první plavbou ověřte místní pravidla pro lov a pohon, počasí, vybavení posádky a možnost návratu. Na vodu nevyjíždějte při podmínkách mimo limity člunu, zkušeností nebo vybavení. Větší rozměr sám o sobě nezaručuje stabilitu.</p>
<div style="border:1px solid #bdd5c7;background:#f3faf5;border-radius:10px;padding:16px;margin:18px 0"><h3>Další krok: vyberte jednotlivé části sestavy</h3><p><a data-nry-boat-system-path="motor" href="https://na-ryby.eu/nejlepsi-lodni-elektromotory-pro-rybare/"><strong>Elektromotory a kalkulačka baterie</strong></a> – ověřte napětí, odběr a dobu provozu.</p><p><a data-nry-boat-system-path="kayak" href="https://na-ryby.eu/rybarsky-kajak-pro-koho-dava-smysl-a-jak-vybrat/"><strong>Rybářský kajak jako alternativa</strong></a> – pokud potřebujete jiný způsob přepravy a lovu.</p><p>Pro aktuální ceny a dostupnost použijte stávající nabídky člunů v původním výběru níže; nejde o vlastní test ani o garantovanou kompatibilitu.</p></div></section>
HTML;
        if($role==='motor')return <<<'HTML'
<section data-nry-boat-system-guide="motor" aria-label="Nákupní kontrola motoru, baterie a člunu">
<h3>Porovnejte motor a baterii jako jeden systém</h3>
<p>Ještě před výpočtem kapacity si připravte údaje z dokumentace konkrétního motoru: jmenovité napětí, maximální proud, případná omezení baterie, povolenou montáž a délku nohy. Hodnota tahu 55 lb sama o sobě neudává spotřebu proudu ani povolený výkon motoru. Není z ní možné spolehlivě vypočítat dojezd nebo určit vhodnou baterii.</p>
<div style="overflow-x:auto"><table><caption>Modelové výpočty pro ilustraci kapacity, nikoli údaje o konkrétních motorech</caption><thead><tr><th scope="col">Scénář</th><th scope="col">Předpokládaný odběr a čas</th><th scope="col">Využitelný podíl a rezerva</th><th scope="col">Potřebná jmenovitá kapacita</th></tr></thead><tbody><tr><td>Krátký přesun</td><td>20 A × 1 h</td><td>80 % / 30 %</td><td>32,5 Ah</td></tr><tr><td>Delší provoz</td><td>30 A × 2 h</td><td>80 % / 30 %</td><td>97,5 Ah</td></tr><tr><td>Vysoký odběr</td><td>50 A × 2 h</td><td>80 % / 30 %</td><td>162,5 Ah</td></tr></tbody></table></div>
<p>Výpočet používá stejný vzorec jako kalkulačka: spotřeba v Ah krát 1,3 děleno 0,8. Rezerva v tomto příkladu je přirážka k předpokládané spotřebě, ne garantovaných 30 % zbývající kapacity. Skutečně použitelný podíl určuje výrobce baterie a podmínky provozu; zohledněte stáří, teplotu, další zařízení a možné vyšší odběry. Proudový limit BMS musí vyhovovat požadavkům konkrétního motoru včetně podmínek rozběhu. Nepřipojujte baterii jiného napětí ani neimprovizujte jištění, kabely či nabíjení.</p>
<p><strong>12 V a 24 V nejsou zaměnitelné:</strong> kapacita 100 Ah při 12 V odpovídá nominálně 1 200 Wh, stejných 100 Ah při 24 V 2 400 Wh. Jde o výpočet nominální energie, nikoli využitelné energie nebo dojezdu. Napětí, proudovou zatížitelnost a vhodný typ baterie ověřte v návodech motoru i baterie. Neodvozujte kompatibilitu jen z údaje Ah.</p>
<p>Při výběru motoru a baterie zvažte i jejich společnou hmotnost, povolené uchycení, celkovou zátěž člunu a dostupnost servisu. Počítejte s návratem a změnou podmínek, neplánujte plavbu na úplné vybití. <a data-nry-boat-system-path="boat" href="https://na-ryby.eu/nejlepsi-nafukovaci-cluny-pro-rybare/"><strong>Porovnat konkrétní nafukovací čluny a použít kalkulačku nosnosti</strong></a>.</p></section>
HTML;
        return '';
    }
    public static function transform($html,$r){
        if(!is_string($html)||strlen($html)<18000||strlen($html)>300000||strpos($html,self::MARKER)!==false
          ||substr_count($html,$r['anchor'])!==1||substr_count($html,'click-100927011-12347575')!==13)return $html;
        $panel=self::panel($r['role']);if(!$panel)return $html;
        if($r['role']==='boat'){
            preg_match_all('~<h1\b[^>]*>[\s\S]{1,1200}?</h1\s*>~iu',$html,$all,PREG_OFFSET_CAPTURE);
            $matches=array_values(array_filter($all[0],function($m){return self::plain($m[0])==='Nejlepší nafukovací čluny pro rybáře: jak vybrat velikost, podlážku, nosnost a motor';}));
            if(count($matches)!==1)return $html;
            $old=$matches[0][0];$offset=$matches[0][1];
            $heading=preg_replace('~^<h1\b~i','<h2',$old);
            $heading=preg_replace('~</h1\s*>$~i','</h2>',$heading);
            return substr_replace($html,$heading."\n".$panel,$offset,strlen($old));
        }
        // Insert before the existing calculator heading: retain original calculator position.
        return str_replace($r['anchor'],$panel."\n".$r['anchor'],$html);
    }
    public static function render($html){
        foreach(array('is_single','is_singular','is_admin','is_preview','is_feed','in_the_loop','is_main_query','get_queried_object_id','get_post','get_permalink','home_url') as $f)if(!function_exists($f))return $html;
        if(!is_singular('post')||is_admin()||is_preview()||is_feed()||!in_the_loop()||!is_main_query()
          ||(function_exists('is_customize_preview')&&is_customize_preview())
          ||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'||rtrim(home_url('/'),'/')!=='https://na-ryby.eu')return $html;
        $id=(int)get_queried_object_id();$all=self::routes();if(!isset($all[$id])||!is_single($id))return $html;
        $r=$all[$id];$p=get_post($id);
        if(!is_object($p)||(int)$p->ID!==$id||$p->post_type!=='post'||$p->post_status!=='publish'
           ||$p->post_name!==$r['slug']||$p->post_modified_gmt!==$r['modified']||get_permalink($id)!=='https://na-ryby.eu/'.$r['slug'].'/'
           ||!is_string($p->post_content)||substr_count($p->post_content,'click-100927011-12347575')!==13
           ||substr_count($p->post_content,$r['anchor'])!==1)return $html;
        return self::transform($html,$r);
    }
}
if(function_exists('add_filter')&&function_exists('is_single')&&function_exists('in_the_loop'))
    add_filter('the_content',array('NRY_Boat_System_Buyer_V1','render'),80);
