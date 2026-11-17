<?php
/**
 * Source-guarded, render-only correction of a misleading 2023 baitboat guide.
 * The archived post conflated unmanned RC baitboats with passenger vessels and
 * presented unverified star ratings as a current ranking. No stored post,
 * affiliate target, price, image, metadata or redirect is changed.
 * Rollback: remove the require from the plugin entrypoint.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Legacy_Baitboat_Trust_61251_V1 {
    const ID = 61251;
    const SLUG = 'jak-vybrat-zavazeci-lodku-pro-rybare';
    const URL = 'https://na-ryby.eu/jak-vybrat-zavazeci-lodku-pro-rybare/';
    const MODIFIED_GMT = '2023-09-23 08:44:03';
    const GUIDE = 'https://na-ryby.eu/zavazeci-lodky-pro-rybare/';
    const PICKER = 'https://na-ryby.eu/jak-vybrat-zavazeci-lodku/';
    const MARKER = 'data-nry-legacy-baitboat-trust="v1"';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 75);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 28);
        }
    }

    private static function eligible() {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_main_query') || !is_main_query()
            || !function_exists('in_the_loop') || !in_the_loop()
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT;
    }

    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-legacy-baitboat-trust-61251-v1',
            plugins_url('assets/nry-legacy-baitboat-trust-v1.css', __FILE__),
            array(), '1.0.0');
    }

    private static function replacements() {
        // Literal anchors observed on the public WordPress REST post #61251.
        // No existing a[href], price, image, SKU or merchant CTA is touched.
        return array(
            'Zavážecí loďka je ideálním doplňkem pro každého rybáře, který chce získat přístup k místům na vodě, kam se jinak dostat nedokáže.'
                => 'Dálkově ovládaná zavážecí loďka převáží krmení a montáže na vybrané místo; není určena pro přepravu lidí. Na krátkou vzdálenost, kam pohodlně dohodíte, ji možná nepotřebujete.',
            'Existuje několik různých typů zavážecích lodí, včetně nafukovacích, plastových a dřevěných. Vyberte si ten, který vyhovuje vašim potřebám a požadavkům.'
                => 'U RC zavážecí loďky vybírejte základní provedení, GPS pro opakovaný návrat na bod, případně sonar pro orientaci v hloubce a reliéfu dna.',
            'Zvažte, kolik lidí a věcí budete převážet a vyberte loďku s dostatečným prostorem.'
                => 'Porovnejte kapacitu zásobníku na krmení, počet výklopných komor, velikost sestavy a její přepravní hmotnost.',
            'Rybolov může být zdlouhavý a stabilní loďka je důležitá, abyste se cítili pohodlně a bezpečně.'
                => 'Sledujte stabilitu RC loďky při větru a vlnách, ochranu pohonu a možnost bezpečného návratu ke břehu.',
            '<li>Ocelový trup pro maximální pevnost</li>'
                => '<li>Materiál a konstrukci trupu ověřte v dokumentaci konkrétní varianty.</li>',
            'Pojďme se podívat na nejlepší loďky v jednotlivých cenových kategoriích. Vybíráme nejlepší loďku v poměru cena/výkon, ekonomickou alternativu a nejlepší produkt na trhu.'
                => 'Následují historické příklady z původního přehledu z roku 2023. Nejde o aktuálně ověřený žebříček, test ani potvrzení dnešní dostupnosti.',
            'Devict Zavážecí Loďka Tanker Mono &#8211; NEJLEPŠÍ VOLBA POMĚR CENA/VÝKON ⭐⭐⭐⭐⭐'
                => 'Devict Tanker Mono – historický model k ověření parametrů',
            'Sportcarp Zavážecí Lodička Baiting 500 &#8211; EKONOMICKÁ VOLBA'
                => 'Sportcarp Baiting 500 – historický model k ověření nabídky',
            'Mivardi Zavážecí loď Mivardi Carp Scout &#8211; LA 10 &#8211; NEJLEPŠÍ NA TRHU <strong>⭐</strong><strong>⭐</strong><strong>⭐</strong><strong>⭐</strong><strong>⭐</strong>'
                => 'Mivardi Carp Scout LA 10 – historický model k porovnání funkcí',
            'Tyto faktory zahrnují velikost, materiál, nosnost, stabilitu a pohodlí.'
                => 'Rozhodují zejména dosah ovládání, kapacita na krmení, výdrž baterie, GPS, sonar, stabilita a dostupnost servisu.',
            'Musíte zvážit, zda potřebujete menší, lehčí loďku pro jednoho rybáře nebo větší loďku pro více rybářů a větší vybavení.'
                => 'Porovnejte rozměry při přepravě, hmotnost s akumulátory a kapacitu komor; žádný z těchto RC modelů není určen k přepravě rybáře.',
            'Loďka by měla mít dostatečnou nosnost, aby vás a váš náklad udržela na hladině bezpečně.'
                => 'Kapacita udává hmotnost krmení a montáže, nikoli nosnost člověka; ověřte zatížení deklarované výrobcem.',
            'Zkontrolujte, zda loďka nabízí dostatek místa pro sedění a uskladnění vybavení. Přítomnost polstrovaných sedadel a dalšího vybavení může zvýšit komfort vašeho rybaření.'
                => 'U dálkově ovládaného modelu řešte ergonomii ovladače, viditelnost světel, snadné plnění komor a výměnu baterie. Sedadla do RC zavážecí loďky nepatří.',
            'Výběr velikosti zavážecí lodě závisí na počtu rybářů, kterým slouží. Pokud jste sami, menší loďka bude dostačující. Pro více rybářů je vhodné zvolit větší variantu.'
                => 'Velikost vybírejte podle kapacity návnady, rozměrů pro přepravu a podmínek vody. Počet rybářů, kteří budou model ovládat, není kritériem nosnosti.',
            'Nosnost zavážecí lodě by měla být dostatečná pro váhu rybáře a veškerého vybavení, které se na ní bude nacházet. Je důležité zvolit loďku s vyšší nosností, abyste se vyvarovali případným problémům.'
                => 'U bezposádkové RC loďky ověřte výrobcem uváděnou hmotnost krmení a maximální zatížení komor. Rybář zůstává na břehu.',
            'Většinou se pohybuje v rozmezí 3000 Kč &#8211; 10000 Kč.'
                => 'Historické cenové rozpětí zde není aktuální; ověřte cenu celé sestavy včetně baterie, nabíječky a případného GPS či sonaru.',
            '<h2>Typy zavážecích lodí pro rybáře</h2>'
                => '<h2>Typy RC zavážecích loděk podle výbavy</h2>',
            'Existuje mnoho různých typů zavážecích lodí, které jsou vhodné pro rybáře. Zde si představíme několik nejpopulárnějších možností.'
                => 'Nafukovací člun a kajak převážejí člověka; dálkově ovládaná zavážecí loďka převáží pouze montáž a krmení. Praktické varianty RC vybavení jsou tyto:',
            '<h3>Nafukovací loď</h3>' => '<h3>Základní RC zavážecí loďka</h3>',
            'Nafukovací loď je jedním z nejčastěji používaných typů zavážecích lodí mezi rybáři. Jsou lehké, snadno přenosné a nabízí slušnou stabilitu na vodě. Navíc jsou relativně cenově dostupné. Nafukovací loď je ideální pro rybáře, kteří cestují častěji, protože ji lze snadno sbalit a převést do různých lokalit.'
                => 'Základní RC model se ovládá ze břehu a hodí se pro jednoduché zavážení krmení a montáže. Před koupí ověřte dosah, kapacitu, výdrž a chování při větru.',
            '<h3>Kajak</h3>' => '<h3>RC loďka s GPS</h3>',
            'Kajak je další populární zavážecí loďka, kterou mnoho rybářů volí. Kajak je malý, ovladatelný a ideální pro rybaření na menších vodních tocích, jezerech nebo pstruhových řekách. Rybáři, kteří preferují rybaření v těsném kontaktu s vodou, si oblíbí kajak pro jeho blízkost povrchu a tichý pohyb.'
                => 'GPS varianta umožňuje ukládat krmná místa a u podporovaných modelů zjednodušuje návrat na zvolený bod. Konkrétní způsob automatického návratu ověřte u výrobce.',
            '<h3>Motorová loď</h3>' => '<h3>RC loďka se sonarem</h3>',
            'Pro ty rybáře, kteří potřebují více výkonu a rychlosti, jsou motorové lodě tou správnou volbou. Motorová loď nabízí větší kapacitu pro nářadí, vybavení a dokonce i více pasažérů. Jsou skvělé pro rybolov na větších jezerech nebo na moři, kde je potřeba překonávat vzdálenosti a rychleji se dostat na vybrané místo.'
                => 'Model se sonarem může ukázat hloubku a orientační profil dna. Za tuto funkci má smysl připlácet, pokud vodu neznáte; srovnejte obrazovku, kompatibilitu a cenu kompletního setu.',
        );
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $m) === false) return null;
        return $m[2];
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 18000
            || strpos($html, self::MARKER) !== false) return $html;
        $intro = '<h2>Jak vybrat zavážecí loďku pro rybáře</h2>';
        $at = strpos($html, $intro);
        if ($at === false || $at > 2000 || substr_count($html, $intro) !== 3
            || substr_count($html, 'https://na-ryby.eu/zavazeci-lodky-parys') !== 4
            || strpos($html, self::GUIDE) !== false || strpos($html, self::PICKER) !== false) return $html;
        $old_hrefs = self::hrefs($html);
        if ($old_hrefs === null) return $html;

        $changed = $html;
        foreach (self::replacements() as $old => $new) {
            if (substr_count($changed, $old) !== 1) return $html;
            $changed = str_replace($old, $new, $changed);
        }
        if (substr_count($changed, '<p><strong>HODNOCENÍ: ⭐⭐⭐⭐⭐</strong></p>') !== 2
            || substr_count($changed, '<p><strong>HODNOCENÍ: ⭐⭐⭐⭐</strong></p>') !== 1) return $html;
        $changed = str_replace(array('<p><strong>HODNOCENÍ: ⭐⭐⭐⭐⭐</strong></p>',
            '<p><strong>HODNOCENÍ: ⭐⭐⭐⭐</strong></p>'),
            '<p><small>Historické hodnocení nebylo doloženo nezávislým testem; aktuální parametry a dostupnost ověřte u prodejce.</small></p>', $changed);

        $intro_end = strpos($changed, $intro);
        $after = $intro_end + strlen($intro);
        $notice = '<aside class="nry-legacy-baitboat-trust" '.self::MARKER
            .' aria-label="Aktuální pomoc s výběrem zavážecí loďky">'
            .'<p class="nry-legacy-baitboat-trust__eyebrow">Aktualizovaný výběr • 2026</p>'
            .'<h3>Hledáte dálkově ovládanou zavážecí loďku?</h3>'
            .'<p>Jde o bezposádkové RC plavidlo pro krmení a montáže, nikoli člun pro rybáře. '
            .'Tento starší článek obsahuje historické modely; jejich někdejší pořadí, cenu ani dostupnost nepotvrzujeme.</p>'
            .'<div class="nry-legacy-baitboat-trust__choices">'
            .'<a href="'.self::GUIDE.'">Porovnat loďky podle rozpočtu, GPS a sonaru</a>'
            .'<a href="'.self::PICKER.'">Vybrat loďku pomocí 5 otázek</a>'
            .'</div></aside>';
        $changed = substr_replace($changed, "\n".$notice."\n", $after, 0);
        // All existing affiliate and editorial href targets must be byte-for-byte
        // unchanged, in the same order. Only two verified internal links are new.
        $new_hrefs = self::hrefs($changed);
        if ($new_hrefs === null || count($new_hrefs) !== count($old_hrefs) + 2) return $html;
        $filter = array_values(array_filter($new_hrefs, function($href) {
            return $href !== self::GUIDE && $href !== self::PICKER;
        }));
        if ($filter !== $old_hrefs) return $html;
        return $changed;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Legacy_Baitboat_Trust_61251_V1::boot();
