<?php
/**
 * Source-pinned, render-only correction of three 2023 carp unhooking-mat posts.
 * No stored WP changes, affiliate redirects, inventory or price assertions.
 * Undo by removing this require from na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Carp_Mat_Buyer_Trust_V1 {
    const MARKER = 'data-nry-carp-mat-buyer="v1"';
    const MAIN = 'https://na-ryby.eu/jake-jsou-klicove-faktory-pri-vyberu-rybarske-podlozky-pod-kapra-pro-zlepseni-ulovku/';
    const FISH_CARE = 'https://old.rybsvaz.cz/beta/index.php/119-vseobecne/849-vyprostovani-hacku-a-foceni-ulovku-2';
    const AFFILIATE = 'https://na-ryby.eu/podlozky';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 78);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 31);
        }
    }

    public static function cohorts() {
        return array(
            43541 => array(
                'slug'=>'jake-jsou-klicove-faktory-pri-vyberu-rybarske-podlozky-pod-kapra-pro-zlepseni-ulovku',
                'modified'=>'2023-05-02 00:00:47',
                'intro'=>'<h2>Jaké jsou klíčové faktory při výběru rybářské podložky pod kapra pro zlepšení úlovku?</h2>',
                'replacements'=>array(
                    'která zajistí správnou zátěž prutu, snížení rizika poškození ryby a zlepšení stability umístění kapra.'
                        => 'která má pomáhat chránit rybu při bezpečné manipulaci na břehu; se zátěží prutu nesouvisí.',
                    'vhodná tvrdost podložky pod kapra přispívá k efektivnímu přenosu vibrací a umožňuje rychlé a přesné reakce na záběr.'
                        => 'dostatečné polstrování pomáhá oddělit tělo ryby od tvrdého a nerovného podkladu; záběr nijak neovlivňuje.',
                    'vlhkost rybářské podložky působí jako ochrana proti poškození podložky a zároveň přispívá k větší stabilitě umístění kapra.'
                        => 'podložku před položením ryby řádně navlhčete a udržujte ji čistou, aby nedocházelo k poškozování ochranného slizu ryby.',
                    'správná komprese podložky pod kapra snižuje riziko poranění ryby a přispívá k lepšímu uchopení háčku v rybářském prutu.'
                        => 'polstrování má tlumit kontakt s podložím; uchopení háčku ani vlastnosti prutu tím neměníte.',
                    'správná velikost rybářské podložky zajišťuje pohodlnou manipulaci a zároveň umožňuje optimální uložení kapra v rybářském prutu.'
                        => 'podložka musí mít dost prostoru pro bezpečné položení celé ryby a manipulaci s ní nízko nad zemí.',
                    'zvyšuje pravděpodobnost úspěšného ulovení kapra.'
                        => 'sama o sobě však nezvyšuje pravděpodobnost záběru ani úlovku.',
                    'Správný tvar podložky by měl být co nejvíc podobný přirozenému prostředí ryby. Například, pokud ryby obvykle tráví čas u břehu, bude podložka ve tvaru náplavky dobrým výběrem. Pokud by však ryby měly tendenci pobývat uprostřed jezera, mohou být vhodnější podložky ve tvaru rohoží.'
                        => 'Tvar vybírejte podle břehu, velikosti ryby a manipulace: plochá polstrovaná podložka se snadno přenáší, kolébka s bočnicemi pomáhá bránit sklouznutí. Nezáleží na tom, zda kapr žije u břehu či uprostřed jezera.',
                    'budete mít mnohem větší šanci na úspěšný úlovek kapra.'
                        => 'zlepšíte především připravenost k šetrnému zacházení s již ulovenou rybou.',
                    'Podložka musí být dostatečně velká pro to, aby kapr mohl na ní bezpečně stát.'
                        => 'Podložka má být dostatečně velká, aby na ní ryba mohla bezpečně ležet.',
                    'Pokud se podložka hodně odlišuje od okolního prostředí, může to kapra odstrašit a snížit tak šance na úspěšný úlovek.'
                        => 'Barva podložky nemá pro záběr kapra zásadní význam, protože podložka slouží až k manipulaci s rybou na břehu.',
                    'Mnoho rybářů preferuje krajinovou podložku nebo podložku ze speciálních vláken jako je například Korda Dark Matter.'
                        => 'Sledujte především hladký povrch, snadné čištění, dostatečné polstrování a odolnost proti poškození.',
                    'Zvažte barvu podložky – některé barvy (např. tmavší odstíny zelené) mohou být přirozenější a méně rušivé pro ryby.'
                        => 'Barva je otázkou osobní preference, nikoli faktorem záběru; důležitější je bezpečná manipulace.',
                    'Zvažte materiál podložky – některé materiály (např. těžké kameny) mohou narušovat pH a teplotu vody, což může mít negativní vliv na ryby.'
                        => 'Zvažte materiál podložky – povrch má být hladký, omyvatelný, bez ostrých částí a dostatečně polstrovaný.',
                    'Zvažte umístění podložky vzhledem k proudění vody – ideální pozice podložky závisí na toku vody na daném místě.'
                        => 'Umístěte podložku na rovné bezpečné místo co nejblíže vodě, mimo ostré kameny a přímé slunce.',
                ),
            ),
            42643 => array(
                'slug'=>'testujeme-ruzne-druhy-rybarskych-podlozek-na-ryby-kterou-zvolit-pro-vase-potreby',
                'modified'=>'2023-04-29 02:21:26',
                'intro'=>'<h2>Testujeme různé druhy rybářských podložek na ryby: Kterou zvolit pro vaše potřeby?</h2>',
                'replacements'=>array(
                    'Proto jsme se rozhodli provést testování různých druhů rybářských podložek, abychom pomohli zjistit, která je nejlepší pro vaše potřeby.'
                        => 'Níže najdete praktické srovnání typů podle konstrukce a použití; historický text nedokládá žádný vlastní srovnávací test.',
                    'Během testování jsme používali různé druhy podložek, včetně gumových, pletených, textilních atd. Každá podložka byla testována na různých typech ryb, včetně kaprů, sumců a pstruhů.'
                        => 'Jde o redakční orientační přehled, nikoliv o výsledky měření nebo praktických testů na rybách.',
                    'Na základě našich výsledků jsme dospěli k závěru, že neexistuje univerzální podložka, která by byla nejlepší pro všechny druhy rybářských situací.'
                        => 'Podle konstrukce a velikosti neexistuje jediná podložka vhodná pro všechny podmínky.',
                    'Pokud plánujete chytat menší ryby v mělké vodě, může být pro vás nejvhodnější textilní podložka. Na druhé straně, pokud plánujete chytat větší ryby v hlubší vodě, může být lepší zvolit gumovou podložku, která poskytuje větší ochranu.'
                        => 'Hloubka vody neurčuje typ podložky: ta se používá na břehu. Na větší ryby a nerovný terén zvažte prostornou polstrovanou kolébku nebo podložku s bočnicemi; při častém přesunu lehčí skládací model.',
                    'Podložky z karimatky jsou vhodné pro rybářské zápasy, zejména na místech s nerovným terénem.'
                        => 'Obyčejná turistická karimatka nemusí mít dostatečnou ochranu ryby ani bezpečné okraje; vybírejte podložku přímo určenou k šetrné manipulaci s úlovkem.',
                    'Tyto podložky jsou navrženy tak, aby předcházely poškození ryby, což znamená, že můžete být klidnější, že vaše ryby nebudou utrpět.'
                        => 'Vhodná podložka pomáhá omezit kontakt ryby s tvrdým podkladem, ale sama nezaručuje, že rybě nevznikne zranění.',
                    'Testujeme podložky z různých materiálů, jako je například guma, pěna, nebo textil'
                        => 'Srovnáváme typy a materiály podle vlastností, nikoliv výsledků nedoložených testů',
                    'Porovnáváme účinnost podložek při lovu různých druhů ryb &#8211; kaprů, štik, línu a dalších'
                        => 'Při výběru sledujeme velikost ryby, polstrování a snadné čištění',
                    'Zkoušíme různé velikosti a tvary podložek a sledujeme jejich vliv na úspěšnost'
                        => 'Posuzujeme velikosti a tvary podle bezpečnosti manipulace, nikoli podle úspěšnosti',
                ),
                'replace_faq'=>true,
            ),
            36611 => array(
                'slug'=>'kde-nakupovat-rybarske-podlozky-pod-kapra-v-ceske-republice',
                'modified'=>'2023-04-09 22:32:40',
                // This older post has a much shorter unformatted source;
                // all exact content and affiliate guards remain mandatory.
                'minimum_html_bytes'=>3500,
                'intro'=>'<h2>Kde nakupovat rybářské podložky pod kapra v České republice</h2>',
                'replacements'=>array(
                    'Mezi nejznámější patří Rybářské potřeby'
                        => 'Výběr konkrétního prodejce se vyplatí ověřit podle dostupnosti, rozměrů, podmínek vrácení a skutečných fotografií. V nabídce rybářských potřeb najdete například',
                    'Rybářské potřeby Na Rybníčku, Rybářské potřeby Profi Rybář a mnoho dalších.'
                        => 'specializované e-shopy a kamenné prodejny s rybářským vybavením.',
                    'S dobrým vybavením se Vám bude lovit'
                        => 'S vhodnou podložkou lze na břehu šetrněji manipulovat s uloveným',
                    '>kapr</a> lépe a úspěšněji.'
                        => '>kaprem</a> bez kontaktu s tvrdým podkladem.',
                    '<p><b>Keywords:</b> rybářské podložky, kapr, Česká republika, rybářské obchody, kvalita materiálu, velikost, neopren, PVC, lovit kapr, vybavení, úspěšný lov.</p>'
                        => '<p>Před objednáním porovnejte vnitřní rozměr, výšku bočnic, tloušťku polstrování, možnost čištění a složené rozměry.</p>',
                    'Tyto podložky zrychlují proces hojení ran na křupavé kůži kapra a chrání ji před poškozením.'
                        => 'Navlhčená, čistá a polstrovaná podložka pomáhá omezit poranění při krátké manipulaci na břehu; hojení ran nezrychluje.',
                    'Kvalitním obchodem, který nabízí širokou škálu podložek, jsou například Rybářské ráje.'
                        => 'Před nákupem si ověřte nezávislé recenze prodejce a zda jsou uvedeny konkrétní vnitřní rozměry podložky.',
                    'Vybírat můžete z mnoha e-shopů, jako jsou například Rybářské potřeby Černý most nebo Rybářské potřeby DAP.'
                        => 'U online nabídky sledujte popis polstrování, skutečné fotografie a jasné informace o dopravě a vrácení.',
                    'Jde o hrubší podložku, kterou kryjeme zem a na ni položíme montáž se skládající se z háčku, plováku a nástrahy. Podložka pod kapra se využívá pro snazší manipulaci s montáží a také pro ochranu kapra, který leží na tvrdém povrchu, což může způsobit poranění ploutví.'
                        => 'Jde o polstrovanou a navlhčenou podložku určenou k bezpečné manipulaci s rybou na břehu, nikoli k odkládání montáže s háčkem a nástrahou.',
                    'Podložka by měla mít aspoň 80&#215;60 cm, aby byla dostatečně velká pro kapra. Co se týče tloušťky, ta by měla být minimálně 3 cm, aby kapr byl dobře izolován od tvrdého a zmrzlého podkladu.'
                        => 'Rozměr a polstrování zvolte podle reálné velikosti ryby, kterou očekáváte. U větších kaprů upřednostněte dostatečnou ložnou plochu a bezpečné bočnice; univerzální minimální rozměr pro všechny situace neexistuje.',
                    'Například obchod Rybářské potřeby Max, s.r.o. umožňuje nákup přes internetový obchod.'
                        => 'Vždy ověřte aktuální nabídku přímo u vybraného prodejce.',
                ),
                'replace_retailer_faq'=>true,
            ),
        );
    }

    private static function selected($in_loop = true) {
        if (!function_exists('get_queried_object_id')) return false;
        $id=(int)get_queried_object_id(); $sets=self::cohorts();
        if (!isset($sets[$id]) || !function_exists('is_single') || !is_single($id)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || ($in_loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('home_url') || rtrim(home_url('/'), '/')!=='https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink($id)!=='https://na-ryby.eu/'.$sets[$id]['slug'].'/'
            || !function_exists('get_post')) return false;
        $p=get_post($id);
        if (!is_object($p) || (int)($p->ID??0)!==$id
            || ($p->post_type??'')!=='post' || ($p->post_status??'')!=='publish'
            || ($p->post_name??'')!==$sets[$id]['slug']
            || ($p->post_modified_gmt??'')!==$sets[$id]['modified']) return false;
        $sets[$id]['is_main']=($id===43541);
        return $sets[$id];
    }

    public static function enqueue() {
        if (!self::selected(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-carp-mat-buyer-v1',
            plugins_url('assets/nry-carp-mat-buyer-v1.css',__FILE__),array(),'1.0.0');
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',$html,$m)===false) return null;
        return $m[2];
    }

    private static function faq_replace($html,$start,$end,$replacement) {
        if (substr_count($html,$start)!==1) return false;
        $a=strpos($html,$start);
        $b=strpos($html,$end,$a+strlen($start));
        if ($b===false || $b-$a>4000) return false;
        $original=substr($html,$a,$b+strlen($end)-$a);
        // The misleading FAQs carry no affiliate or editorial links.
        if (strpos($original,'href=')!==false) return false;
        return substr_replace($html,$replacement,$a,strlen($original));
    }

    private static function banner($set) {
        $lead=$set['is_main']
            ? 'Podložka chrání ulovenou rybu při manipulaci na břehu. Neovlivňuje záběr, montáž ani úspěšnost lovu.'
            : 'Tento článek pochází z roku 2023. Následující rozhodovací přehled opravuje nepřesnosti a neuvádí nedoložené testy, ceny ani skladové stavy.';
        $guide=$set['is_main'] ? '' : '<p class="nry-carp-mat__guide"><a href="'.self::MAIN.'">Podrobný průvodce: jak vybrat podložku pod kapra</a></p>';
        return '<aside class="nry-carp-mat" '.self::MARKER.' aria-label="Praktická volba podložky pod rybu">'
            .'<p class="nry-carp-mat__eyebrow">Praktický výběr a šetrná manipulace</p>'
            .'<h3>Jakou podložku pod kapra vybrat?</h3><p>'.$lead.'</p>'
            .'<div class="nry-carp-mat__grid">'
            .'<div><strong>Lehká skládací</strong><span>Časté přesuny a rovný břeh; ověřte polstrování a místo pro celou rybu.</span></div>'
            .'<div><strong>Kolébka s bočnicemi</strong><span>Delší výpravy a větší kapři; bočnice pomáhají omezit sklouznutí, počítejte s objemem.</span></div>'
            .'<div><strong>Nafukovací</strong><span>Úsporné balení; kontrolujte odolnost proti propíchnutí, stabilitu a nafukování.</span></div>'
            .'</div><p><strong>Před použitím:</strong> podložku řádně navlhčete, mějte vše připravené a rybu držte co nejníže nad ní. '
            .'<a href="'.self::FISH_CARE.'" rel="noopener noreferrer">Doporučení Českého rybářského svazu</a>.</p>'
            .$guide.'</aside>';
    }

    public static function transform($html,$set) {
        if (!is_string($html) || !is_array($set)
            || strlen($html)<($set['minimum_html_bytes']??6000)
            || strpos($html,self::MARKER)!==false
            || substr_count($html,'href="'.self::AFFILIATE.'"')!==4
            // Some 2023 articles repeat their exact H2 later in the body.
            // Inject after the FIRST occurrence only, without making all
            // corrections disappear when an old heading was duplicated.
            || substr_count($html,$set['intro'])<1
            || strpos($html,$set['intro'])>1500
            || strpos($html,self::FISH_CARE)!==false
            || strpos($html,self::MAIN)!==false) return $html;
        $before=self::hrefs($html);
        if ($before===null) return $html;
        $next=$html;
        foreach ($set['replacements'] as $old=>$new) {
            if (substr_count($next,$old)!==1) return $html;
            $next=str_replace($old,$new,$next);
        }
        if (!empty($set['replace_faq'])) {
            $faq='<div class="nry-carp-mat__faq"><h2>Časté otázky: jaké jsou typy podložek?</h2>'
                .'<h3>Je podložka součást montáže?</h3><p>Ne. Podložka slouží k ochraně ryby na břehu; potápivé a plovoucí montáže s ní nesouvisí.</p>'
                .'<h3>Rozhoduje hloubka vody?</h3><p>Ne. Rozhoduje velikost ryby, terén, polstrování, ochrana boků a způsob přepravy.</p>'
                .'<h3>Jak podložku používat?</h3><p>Před položením ryby ji navlhčete, očistěte od písku a mějte připravené náčiní pro rychlé šetrné vrácení ryby do vody.</p></div>';
            $next=self::faq_replace($next,"<div>\n<h2>FAQs &#8211;",'</div>',$faq);
            if ($next===false) return $html;
        }
        if (!empty($set['replace_retailer_faq'])) {
            $next=self::faq_replace($next,"<ul>\n<li>Rybářské potřeby Max, s.r.o.</li>",
                '</ul>', '<ul><li>Specializovaný prodejce s uvedenými rozměry a parametry.</li>'
                .'<li>Ověřené podmínky vrácení a dostupné fotografie konkrétního modelu.</li>'
                .'<li>Možnost poradit se o velikosti ryby a vhodnosti podložky pro terén.</li></ul>');
            if ($next===false) return $html;
            $next=str_replace('V těchto obchodech najdete širokou škálu rybářských podložek, včetně těch určených pro lov kaprů.',
                'Při nákupu u jakéhokoli prodejce ověřte konkrétní parametry a aktuální dostupnost.', $next);
        }
        $at=strpos($next,$set['intro'])+strlen($set['intro']);
        $next=substr_replace($next,"\n".self::banner($set)."\n",$at,0);
        $after=self::hrefs($next);
        $added=$set['is_main'] ? array(self::FISH_CARE) : array(self::FISH_CARE,self::MAIN);
        if ($after===null || count($after)!==count($before)+count($added)) return $html;
        foreach ($added as $url) {
            if (substr_count($next,'href="'.$url.'"')!==1) return $html;
        }
        $old_links=array_values(array_filter($after,function($href) use ($added) {
            return !in_array($href,$added,true);
        }));
        return $old_links===$before ? $next : $html;
    }

    public static function render($html) {
        $set=self::selected();
        // The older shop-guide template can render its full post body outside
        // the main-loop context. Its immutable post identity + four CTA hrefs
        // + all exact content anchors still gate the mutation, so a sidebar,
        // excerpt or newer editor revision cannot receive the replacement.
        if (!$set && function_exists('get_queried_object_id')
            && (int)get_queried_object_id()===36611) {
            $set=self::selected(false);
        }
        return $set ? self::transform($html,$set) : $html;
    }
}
NRY_Carp_Mat_Buyer_Trust_V1::boot();
