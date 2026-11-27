<?php
/**
 * Six high-intent stay guides receive 12 direct, already-site-approved affiliate
 * exits. Output-only, exact slugs and verified legacy source phrases.
 * No newly invented merchant listings, prices, stock, WP writes or redirects.
 * Reuses existing Hauzi/Cyklochata/Srub and Hportal/PoKohoutu deep links.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Six_Real_Stay_Offers_V1 {
    const MARKER = 'data-nry-real-stay-offers="v1"';
    private static $rendered = false;
    public static function approvedLinks() {
        return array(
            'cyklo' => 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcyklochata',
            'srub'  => 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub',
            'more'  => 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcz%2Fpro-dva%2Fpro-rybare%2Fwellness',
            'lipno' => 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fhorni-plana%2Fpenzion-u-kohoutu%2F',
        );
    }
    public static function routes() {
        return array(
            'rybarska-chata-bitov' => array(
                'title' => 'Rybářská chata Bítov',
                'source' => 'dvojchata',
                'cohort' => 'stay_bitov_alternatives',
                'eyebrow' => 'Bítov / Vranovská přehrada',
                'headline' => 'Chata Bítov nemá ověřený aktuální termín? Porovnejte dvě jiné chaty u Vranova',
                'intro' => 'Jde o jiné objekty, nikoli rezervaci původní Chaty Bítov. Ověřte cenu a termín přímo u poskytovatele; rybolov, povolenka ani přístup k vodě nejsou automaticky součástí pobytu.',
                'offers' => array(
                    array('cyklo','Cyklochata u Vranova','Ubytování pro skupiny ve Štítarech; ověřte volný termín.'),
                    array('srub','Rybářský wellness srub','Srub u Lančova se saunou; nižší hladina přehrady může omezit lov.'),
                ),
            ),
            'rybareni-a-romantika-tipy-na-pobyty-pro-dva-zamilovane-rybare' => array(
                'title' => 'Rybaření a romantika',
                'source' => 'rybaření',
                'cohort' => 'stay_romance_two',
                'eyebrow' => 'Pobyt pro dva',
                'headline' => 'Dva konkrétní způsoby, jak začít s výběrem pobytu',
                'intro' => 'Wellness srub je konkrétní ubytování u Vranovské přehrady; druhá možnost je katalog dalších pobytů. Rybaření ani lovné místo nejsou ubytováním zaručeny.',
                'offers' => array(
                    array('srub','Wellness srub u Vranova','Sauna a koupací sud; prověřte stav vody a možnost lovu.'),
                    array('more','Další rybářské pobyty pro dva','Srovnání různých lokalit a wellness pobytů na Hauzi.'),
                ),
            ),
            'wellness-pobyt-s-moznosti-soukromeho-rybareni-a-ubytovani-v-ceske-republice' => array(
                'title' => 'Wellness pobyt',
                'source' => 'rybaření',
                'cohort' => 'stay_wellness_private',
                'eyebrow' => 'Wellness a voda',
                'headline' => 'Nejdřív ověřte termín a skutečné možnosti rybolovu',
                'intro' => 'Soukromé rybaření není automaticky součástí wellness pobytu. U konkrétního rybářského srubu na Vranově může nižší hladina rybaření výrazně omezit; vždy ověřte podmínky s ubytovatelem.',
                'offers' => array(
                    array('srub','Rybářský wellness srub','Konkrétní srub, finská sauna a koupací sud.'),
                    array('more','Další pobyty s wellness','Porovnat jiné nabídky pro rybáře a páry.'),
                ),
            ),
            'rybarske-dobrodruzstvi-soukrome-rybareni-s-ubytovanim-v-ceske-prirode' => array(
                'title' => 'Rybářské dobrodružství',
                'source' => 'Nabízíme Vám možnost',
                'cohort' => 'stay_private_adventure',
                'eyebrow' => 'Skutečné nabídky místo neurčitých slibů',
                'headline' => 'Tento článek není nabídka vlastní chatky. Vyberte pobyt u skutečného poskytovatele',
                'intro' => 'Na-ryby.eu je redakční web a uvedenou chatku, vybavení ani půjčování prutů neprovozuje. Ověřujte konkrétní ubytování a pravidla lovu; žádné služby v historickém textu nejsou automaticky zahrnuty.',
                'offers' => array(
                    array('more','Rybářské pobyty s wellness','Porovnat různé možnosti ubytování na Hauzi.'),
                    array('cyklo','Cyklochata Vranov','Konkrétní jiný objekt ve Štítarech, nikoli chatka z původního článku.'),
                ),
            ),
            'rybareni-s-ubytovanim-v-ceske-republice-doporucene-lokality' => array(
                'title' => 'Rybaření s ubytováním v České republice',
                'source' => 'Orlická přehrada',
                'cohort' => 'stay_cz_regions',
                'eyebrow' => 'Konkrétní nabídky',
                'headline' => 'Od výběru revíru ke skutečné nabídce ubytování',
                'intro' => 'Místo neurčitého seznamu oblastí si ověřte konkrétní ubytování. Penzion U Kohoutů leží u Lipna v Horní Plané; další nabídky jsou z různých lokalit. Rybolov na konkrétní vodě a povolenku je třeba ověřit zvlášť.',
                'offers' => array(
                    array('lipno','Pension U Kohoutů u Lipna','Ubytování v Horní Plané, rybaření je uváděnou místní aktivitou.'),
                    array('more','Další možnosti pro rybáře','Více regionů a wellness pobytů na Hauzi.'),
                ),
            ),
            'tipy-na-skvele-mista-pro-rybareni-s-ubytovanim-u-vody-v-cesku' => array(
                'title' => 'Tipy na skvělé místa',
                'source' => 'Lipno',
                'cohort' => 'stay_tips_at_water',
                'eyebrow' => 'Jižní Čechy a další lokality',
                'headline' => 'Vyberte skutečné ubytování místo obecného seznamu míst',
                'intro' => 'Pension U Kohoutů je v Horní Plané u Lipna; na Hauzi můžete porovnat další ubytování. Nezaměňujte obecnou možnost rybaření s garantovaným lovným místem nebo zahrnutou povolenkou.',
                'offers' => array(
                    array('lipno','Pension U Kohoutů u Lipna','Konkrétní penzion v Horní Plané u Lipna.'),
                    array('more','Více pobytů pro rybáře','Prohlédnout další oblasti, termíny a vybavení.'),
                ),
            ),
        );
    }
    private static function selection($inLoop = false) {
        foreach(array('is_single','is_singular','is_admin','get_queried_object_id',
            'get_post','get_permalink','home_url') as $f) {
            if (!function_exists($f)) return null;
        }
        if (!is_singular('post') || is_admin()
            || (function_exists('is_preview') && is_preview())
            || (function_exists('is_feed') && is_feed())
            || (function_exists('is_customize_preview') && is_customize_preview())
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || rtrim(home_url('/'),'/') !== 'https://na-ryby.eu') return null;
        if ($inLoop && ((!function_exists('in_the_loop') || !in_the_loop())
            || (!function_exists('is_main_query') || !is_main_query()))) return null;
        $id = (int)get_queried_object_id();
        if ($id <= 0 || !is_single($id)) return null;
        $post=get_post($id);
        if (!is_object($post) || (int)($post->ID ?? 0) !== $id
            || ($post->post_type ?? '') !== 'post'
            || ($post->post_status ?? '') !== 'publish'
            || !is_string($post->post_name ?? null)
            || !is_string($post->post_title ?? null)
            || !is_string($post->post_content ?? null)
            || strlen($post->post_content) < 1200
            || !is_string($post->post_date_gmt ?? null)) return null;
        $rows=self::routes();$slug=$post->post_name;
        if (!isset($rows[$slug])
            || get_permalink($id) !== 'https://na-ryby.eu/'.$slug.'/') return null;
        $year=(int)substr($post->post_date_gmt,0,4);
        if ($year < 2017 || $year > 2024) return null;
        $title=html_entity_decode(strip_tags($post->post_title),ENT_QUOTES | ENT_HTML5,'UTF-8');
        if (strpos($title,$rows[$slug]['title']) !== 0
            || strpos($post->post_content,$rows[$slug]['source']) === false) return null;
        return array('slug'=>$slug) + $rows[$slug];
    }
    public static function panel($row) {
        if (!is_array($row) || !isset($row['offers'],$row['slug'])) return '';
        $urls=self::approvedLinks();
        $out='<section class="nry-gear-archive-entry nry-real-stays" '.self::MARKER
            .' data-nry-real-stay-slug="'.htmlspecialchars($row['slug'],ENT_QUOTES,'UTF-8').'"'
            .' aria-label="Ověření konkrétních ubytování pro rybáře">'
            .'<p class="nry-gear-archive-entry__eyebrow">'.htmlspecialchars($row['eyebrow'],ENT_QUOTES,'UTF-8').'</p>'
            .'<h2>'.htmlspecialchars($row['headline'],ENT_QUOTES,'UTF-8').'</h2>'
            .'<p>'.htmlspecialchars($row['intro'],ENT_QUOTES,'UTF-8').'</p>'
            .'<nav aria-label="Dvě ověřitelné partnerské nabídky">';
        foreach ($row['offers'] as $offer) {
            if (!isset($urls[$offer[0]])) return '';
            $href=htmlspecialchars($urls[$offer[0]],ENT_QUOTES,'UTF-8');
            $out.='<a class="nry-gear-archive-entry__choice" data-nry-real-stay-offer="'.$offer[0].'"'
                .' href="'.$href.'" target="_blank" rel="sponsored nofollow noopener">'
                .'<strong>'.htmlspecialchars($offer[1],ENT_QUOTES,'UTF-8').'</strong>'
                .'<span>'.htmlspecialchars($offer[2],ENT_QUOTES,'UTF-8').'</span></a>';
        }
        return $out.'</nav><p class="nry-gear-archive-entry__note">'
            .'Partnerské odkazy Hauzi nebo Hportal. Ceny, termíny, podmínky rybolovu a povolenky '
            .'ověřte u poskytovatele. Na-ryby.eu není provozovatelem ubytování.</p></section>';
    }
    public static function render($html) {
        $row=self::selection(true);
        if (self::$rendered || !$row || !is_string($html) || strlen($html)<1400
            || strpos($html,self::MARKER)!==false || strpos($html,$row['source'])===false) return $html;
        $block=self::panel($row);
        if ($block==='' || substr_count($block,'data-nry-real-stay-offer=')!==2) return $html;
        self::$rendered=true;
        return $block.$html;
    }
    public static function enqueue() {
        if (!self::selection() || !function_exists('wp_enqueue_style')
            || !function_exists('wp_enqueue_script') || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-gear-archive-entry-v1',
            plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');
        wp_enqueue_script('nry-six-real-stay-click-v1',
            plugins_url('assets/nry-six-real-stay-click-v1.js',__FILE__),array(),'1.0.0',true);
    }
    public static function boot() {
        if (!function_exists('is_single') || !function_exists('is_singular')
            || !function_exists('add_filter') || !function_exists('add_action')) return;
        add_filter('the_content',array(__CLASS__,'render'),80);
        add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),27);
    }
}
NRY_Six_Real_Stay_Offers_V1::boot();
