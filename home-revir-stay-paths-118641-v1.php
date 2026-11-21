<?php
/**
 * Homepage discovery bridge to already public fishing and stay content.
 * Render-only: the current homepage mentions waters and lodging but its
 * visible cards mostly point to equipment or a 2017 archive. No WP writes.
 * Exact source, unique inline anchors, one-page scope; remove one require to
 * roll back. Nothing here represents a verified hotel booking or permit.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Home_Revir_Stay_Paths_118641_V1 {
    const ID = 118641;
    // The immutable reviewed post_content created by the earlier homepage
    // cleanup. Any subsequent editor update wins and makes this a no-op.
    const RAW_SHA256 = '80e86d2c85d495a2502737243b4887b6ef48098906bc0cf958e08ec29dc6574f';
    const SOURCE_MARKER = 'data-nry-home-trust="source-bound-118641-20261109-v1"';
    const MARKER = 'data-nry-home-locality-paths="v1"';
    const COPY_MARKER = 'data-nry-home-visitor-copy="v1"';
    const REVIR_HEAD = '<h3>Kam na ryby</h3>';
    const REVIR_ANCHOR = '<li><a href="https://na-ryby.eu/category/soukrome-rybarske-reviry/">Přehled soukromých revírů</a></li>';
    const STAY_HEAD = '<h3>Výprava na více dní</h3>';
    const STAY_ANCHOR = '<li><a href="https://na-ryby.eu/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/">Výběr rybářského bivaku</a></li>';
    const STAY_INTRO = 'Chystáte se na více dní u vody? Pomůžeme vybrat přístřešek a předejít častým problémům při nocování.';
    const DYJE6 = 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/';
    const MORAVIA_REVIRS = 'https://na-ryby.eu/category/soukrome-rybarske-reviry/soukrome-rybarske-reviry-jihomoravsky-kraj/';
    const STAY_GUIDE = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_front_page')
            && function_exists('is_page') && function_exists('is_main_query')) {
            add_filter('the_content', array(__CLASS__, 'render'), 90);
            // Independent source-locked copy cleanup; never blocks main journeys.
            add_filter('the_content', array(__CLASS__, 'renderVisitorCopy'), 96);
        }
    }

    private static function eligible() {
        foreach (array('get_queried_object_id','in_the_loop','is_admin',
            'is_preview','home_url','get_permalink','get_post') as $fn) {
            if (!function_exists($fn)) return false;
        }
        if (!is_front_page() || !is_page(self::ID) || !is_main_query() || !in_the_loop()
            || is_admin() || is_preview() || (int)get_queried_object_id() !== self::ID
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ID) !== 'https://na-ryby.eu/') return false;
        $post=get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'page' && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === 'na-ryby-eu-vse-pro-rybare'
            && is_string($post->post_content ?? null)
            && hash_equals(self::RAW_SHA256,hash('sha256',$post->post_content));
    }

    public static function transform($html) {
        if (!is_string($html) || strpos($html,self::MARKER)!==false
            || substr_count($html,self::SOURCE_MARKER)!==1
            || substr_count($html,self::REVIR_HEAD)!==1
            || substr_count($html,self::STAY_HEAD)!==1
            || substr_count($html,self::REVIR_ANCHOR)!==1
            || substr_count($html,self::STAY_ANCHOR)!==1
            || substr_count($html,self::STAY_INTRO)!==1
            || strpos($html,self::DYJE6)!==false
            || strpos($html,self::MORAVIA_REVIRS)!==false
            || strpos($html,self::STAY_GUIDE)!==false) return $html;
        $rh=strpos($html,self::REVIR_HEAD);
        $ra=strpos($html,self::REVIR_ANCHOR);
        $sh=strpos($html,self::STAY_HEAD);
        $sa=strpos($html,self::STAY_ANCHOR);
        $si=strpos($html,self::STAY_INTRO);
        // Anchor must really belong to the corresponding homepage card.
        if ($rh===false || $ra<$rh || $ra-$rh>700
            || $sh===false || $si<$sh || $si-$sh>700
            || $sa<$si || $sa-$si>700 || $rh>=$sh) return $html;
        $revirLinks = '<li '.self::MARKER.'><a href="'.self::DYJE6.'">'
            .'Věstonická nádrž (Dyje 6): pravidla a přístup</a></li>'
            .'<li><a href="'.self::MORAVIA_REVIRS.'">Soukromé revíry na jižní Moravě</a></li>';
        $stayLinks = '<li><a href="'.self::STAY_GUIDE.'">'
            .'Rybářské pobyty: jak vybrat místo u vody</a></li>';
        $changed=str_replace(self::REVIR_ANCHOR,self::REVIR_ANCHOR.$revirLinks,$html);
        $changed=str_replace(self::STAY_ANCHOR,self::STAY_ANCHOR.$stayLinks,$changed);
        $changed=str_replace(self::STAY_INTRO,
            'Chystáte se na delší výpravu? Nejprve vyberte vodu a pobyt, pak řešte bivak a přespání. Před rezervací ověřte pravidla lovu i dostupnost.',
            $changed);
        return substr_count($changed,self::MARKER)===1
            && substr_count($changed,self::DYJE6)===1
            && substr_count($changed,self::MORAVIA_REVIRS)===1
            && substr_count($changed,self::STAY_GUIDE)===1
            && substr_count($changed,self::REVIR_ANCHOR)===1
            && substr_count($changed,self::STAY_ANCHOR)===1
            ? $changed : $html;
    }

    /**
     * Remove source-reviewed editorial/process wording from the public home.
     * These sentences still appear on the live homepage in November 2026.
     * No new links, offer data or claims of verified testing are introduced.
     * Separate from the three existing revír/stay link repairs.
     */
    public static function visitorCopyPairs() {
        return array(
            array(
                'Místo slabého archivního výpisu dostane návštěvník přehledný rozcestník podle záměru: výběr vybavení, nejlepší srovnání, praktické návody, začátky s rybařením i prostor pro budoucí revíry a pobyty.',
                'Vyberte si podle toho, co potřebujete: rybářskou výbavu, návod, revír nebo pobyt u vody. U konkrétních nabídek a pravidel lovu si vždy ověřte aktuální podmínky.'
            ),
            array(
                'Každá hlavní sekce je řešená jako samostatná vizuální karta s obrázkem, krátkým úvodem a top odkazy.',
                'Vyberte oblast, která vás zajímá, a začněte praktickým návodem nebo srovnáním.'
            ),
            array(
                'Top výběry, souboje značek a ručně vybrané články s vysokou klikatelností.',
                'Srovnání podle způsobu lovu, rozpočtu a důležitých vlastností výbavy.'
            ),
            array(
                'Silné vstupní průvodce, které pomůžou pochopit parametry, rozpočet i rozdíly mezi variantami.',
                'Průvodci, kteří vysvětlují parametry, rozpočet a rozdíly mezi variantami.'
            ),
            array(
                'Praktické průvodce, výběr vybavení a rybářský obsah stavěný tak, aby pomohl rychleji se zorientovat, vybrat správné řešení a nestrácet čas ve slabém obsahu.',
                'Praktické návody pro výběr výbavy, plánování výpravy a hledání vody. U konkrétních produktů a pobytů rozlišujeme rady od ověřovaných nabídek.'
            ),
            array(
                'Vedle hlavních průvodců tu postupně vzniká i prostor pro revíry, ubytování a další inspiraci k vodě.',
                'Najdete tu také rybářské revíry a tipy na pobyty u vody.'
            )
        );
    }

    public static function transformVisitorCopy($html) {
        if (!is_string($html) || strlen($html) < 2500
            || substr_count($html, self::SOURCE_MARKER) !== 1
            || substr_count($html, self::COPY_MARKER) !== 0) return $html;
        $sourceTag = '<span hidden '.self::SOURCE_MARKER.'></span>';
        if (substr_count($html, $sourceTag) !== 1) return $html;
        foreach (self::visitorCopyPairs() as $pair) {
            if (substr_count($html, $pair[0]) !== 1
                || strpos($html, $pair[1]) !== false) return $html;
        }
        $next=$html;
        foreach (self::visitorCopyPairs() as $pair)
            $next=str_replace($pair[0],$pair[1],$next);
        $copyTag='<span hidden '.self::COPY_MARKER.'></span>';
        $next=str_replace($sourceTag,$sourceTag.$copyTag,$next);
        if (substr_count($next,self::COPY_MARKER)!==1) return $html;
        // Prove that exactly the reviewed six sentences and the hidden marker
        // changed; every original link, media tag and other byte is preserved.
        $reversed=str_replace($copyTag,'',$next);
        foreach (self::visitorCopyPairs() as $pair)
            $reversed=str_replace($pair[1],$pair[0],$reversed);
        return $reversed===$html ? $next : $html;
    }

    public static function renderVisitorCopy($html) {
        return self::eligible() ? self::transformVisitorCopy($html) : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Home_Revir_Stay_Paths_118641_V1::boot();
