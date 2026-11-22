<?php
/**
 * Three evidence-selected indexed regional guides: grounded visitor correction
 * and first-party water/stay handoff above legacy 2023–24 lists.
 *
 * Render-only, exact canonical + published WordPress source identity. Original
 * article HTML, affiliate hrefs, media and WordPress DB remain byte-identical.
 * Historical GSC demand is not a promise of uplift/commission.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_GSC_Regional_Accuracy_V1 {
    const MARKER = 'data-nry-region-accuracy="v1"';
    const HOST = 'https://na-ryby.eu';
    const RIS = 'https://ris.rybsvaz.cz/seznam-reviru';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const USTI_CITY = 'https://www.usti.cz/cz/volny-cas/turistum/turisticke-zajimavosti-cile/prirodni-zajimavosti/jezero-milada.html';
    const CANDAT = 'https://na-ryby.eu/rybarske-reviry-kde-muzete-ulovit-candata-obecneho-v-cr/';
    const HRA_RULES = 'https://www.crsplzen.cz/inpage/431-028-mze-4/';
    const HRA_DETAIL = 'https://na-ryby.eu/bungalov-u-prehrady-hracholusky/';
    const PRIVATE_WATERS = 'https://na-ryby.eu/category/soukrome-rybarske-reviry/';

    const ROUTES = array(
        'rybareni-v-usteckem-kraji-oblibene-reviry-a-rybi-bohatstvi' => array(
            'role'=>'usti',
            'post_id'=>56255,
            'year_month'=>'2023-06',
            'phrases'=>array(
                'Rybaření v Ústeckém kraji',
                'Povrlyský rybník',
                'Lambrovy rybníky',
                'Duchcovský rybník',
                'Milada'
            )
        ),
        '3-kam-na-ryby-autem-v-zapadnich-cechach-5-oblibenych-lokalit' => array(
            'role'=>'west',
            'post_id'=>null,
            'year_month'=>'2023-07',
            'phrases'=>array(
                'Kam na ryby autem v západních Čechách',
                'Lužická Nisa',
                'Pilsenský rybník',
                'Hracholusky'
            )
        ),
        'kam-na-kapry-v-okoli-prahy-nejlepsi-reviry-pro-milovniky-rybareni' => array(
            'role'=>'prague',
            'post_id'=>null,
            'year_month'=>'2024-04',
            'phrases'=>array(
                'Kam na kapry v okolí Prahy',
                'Ovčáry',
                'Štěchovice',
                'Hostivařská přehrada'
            )
        )
    );

    private static function route($in_loop) {
        foreach (array('is_single','is_admin','is_preview','home_url',
            'get_queried_object_id','get_permalink','get_post') as $fn)
            if (!function_exists($fn)) return null;
        if ($_SERVER['REQUEST_METHOD'] ?? 'GET' !== 'GET') return null;
        if (is_admin() || is_preview() ||
            (function_exists('is_feed') && is_feed()) ||
            (function_exists('is_customize_preview') && is_customize_preview()) ||
            rtrim(home_url('/'),'/')!==self::HOST) return null;
        if ($in_loop && (!function_exists('is_main_query') ||
            !function_exists('in_the_loop') || !is_main_query() || !in_the_loop()))
            return null;
        $id=(int)get_queried_object_id();
        if ($id<=0) return null;
        $post=get_post($id);
        if (!is_object($post) ||
            (int)($post->ID ?? 0)!==$id ||
            ($post->post_type ?? '')!=='post' ||
            ($post->post_status ?? '')!=='publish')
            return null;
        $slug=$post->post_name ?? '';
        if (!isset(self::ROUTES[$slug]) || !is_single($slug) ||
            get_permalink($id)!==self::HOST.'/'.$slug.'/') return null;
        $data=self::ROUTES[$slug];
        if ($data['post_id']!==null && $data['post_id']!==$id) return null;
        if (!is_string($post->post_date_gmt ?? null) ||
            strpos($post->post_date_gmt,$data['year_month'])!==0 ||
            !is_string($post->post_content ?? null) ||
            strlen($post->post_content)<600) return null;
        foreach ($data['phrases'] as $phrase) {
            if (strpos($post->post_content,$phrase)===false) return null;
        }
        return $data;
    }

    public static function block($role) {
        if ($role==='usti') {
            return '<section class="nry-ris-choices nry-ris-choices--archive" '
                .self::MARKER.' data-nry-region="usti" aria-labelledby="nry-accuracy-usti">'
                .'<p class="nry-ris-choices__eyebrow">Aktuální ověření před výpravou</p>'
                .'<h2 id="nry-accuracy-usti">Pozor na starší údaje o ústeckých vodách</h2>'
                .'<p>Článek pochází z roku 2023 a názvy vod, možnosti lovu nebo odkazy '
                .'na soukromé provozovatele nemusí být aktuální. '
                .'<strong>Jezero Milada leží u Ústí nad Labem, ne u Chomutova.</strong> '
                .'Umístění uvádí <a href="'.self::USTI_CITY.'">oficiální web města</a>; '
                .'ani to samo nepotvrzuje povolený rybolov.</p>'
                .'<p>Nejdřív <a href="'.self::RIS.'">ověřte konkrétní svazový revír v RIS ČRS</a> '
                .'nebo aktuální pravidla soukromého správce. Zajímají-li vás dravci, '
                .'pomůže <a href="'.self::CANDAT.'">průvodce výběrem vody na candáta</a>. '
                .'Pro delší cestu projděte <a href="'.self::STAYS.'">pobyty u vody</a> '
                .'a dostupnost ověřte přímo u poskytovatele.</p>'
                .'<p class="nry-ris-choices__note">Nejde o potvrzení rybí obsádky, '
                .'povolenek, cen nebo volných míst.</p></section>';
        }
        if ($role==='west') {
            return '<section class="nry-ris-choices nry-ris-choices--archive" '
                .self::MARKER.' data-nry-region="west" aria-labelledby="nry-accuracy-west">'
                .'<p class="nry-ris-choices__eyebrow">Než vyrazíte autem</p>'
                .'<h2 id="nry-accuracy-west">Opravte si mapu: ne každá zmíněná voda je na západě Čech</h2>'
                .'<p>Seznam níže vznikl v roce 2023 a má zeměpisné nepřesnosti. '
                .'<strong>Lužická Nisa protéká Libereckým krajem, ne západními Čechami.</strong> '
                .'U některých popsaných rybníků není dostupnost rybolovu doložená. '
                .'Neberte tento přehled jako ověřenou pětici revírů.</p>'
                .'<p>Pro konkrétní západočeskou vodu začněte u nádrže '
                .'<a href="'.self::HRA_RULES.'">Hracholusky – oficiální revír Mže 4</a>, '
                .'poté <a href="'.self::RIS.'">ověřte aktuální podmínky v RIS ČRS</a>. '
                .'K plánování přespání se hodí starší '
                .'<a href="'.self::HRA_DETAIL.'">profil zázemí u Hracholusek</a> '
                .'nebo <a href="'.self::STAYS.'">přehled rybářských pobytů</a>.</p>'
                .'<p class="nry-ris-choices__note">Před cestou zvlášť ověřte přístup, '
                .'povolenku, omezení lovu a skutečně dostupné ubytování.</p></section>';
        }
        if ($role==='prague') {
            return '<section class="nry-ris-choices nry-ris-choices--archive" '
                .self::MARKER.' data-nry-region="prague" aria-labelledby="nry-accuracy-prague">'
                .'<p class="nry-ris-choices__eyebrow">Rychlé rozhodnutí pro rybáře z Prahy</p>'
                .'<h2 id="nry-accuracy-prague">Nejdřív ověřte povolenku, přístup a typ vody</h2>'
                .'<p>Výčet níže je starší redakční inspirace z roku 2024, '
                .'nikoli potvrzení možnosti stanování, půjčení loďky, rybí obsádky '
                .'nebo platných podmínek lovu na uvedených vodách. '
                .'Na každé místo mohou platit jiná pravidla.</p>'
                .'<p><a href="'.self::RIS.'">Najděte přesný kód svazového revíru v RIS ČRS</a>, '
                .'nebo vybírejte z <a href="'.self::PRIVATE_WATERS.'">profilů soukromých revírů</a> '
                .'a ověřte jejich nabídku u správce. '
                .'Na víkend pomůže <a href="'.self::STAYS.'">průvodce rybářskými pobyty</a>.</p>'
                .'<p class="nry-ris-choices__note">Nezaručujeme povolený lov, '
                .'volná místa ani ceny. Rozhodují aktuální pravidla provozovatele.</p></section>';
        }
        return '';
    }

    public static function transform($html,$role,$phrases) {
        if (!in_array($role,array('usti','west','prague'),true) ||
            !is_string($html) || strlen($html)<600 ||
            strpos($html,self::MARKER)!==false) return $html;
        foreach ($phrases as $phrase)
            if (strpos($html,$phrase)===false) return $html;
        $section=self::block($role);
        if ($section==='' || substr_count($section,self::MARKER)!==1 ||
            strpos($html,'nry-accuracy-'.$role)!==false) return $html;
        $out=$section.$html;
        return substr_count($out,self::MARKER)===1 &&
            substr($out,strlen($section))===$html ? $out : $html;
    }

    public static function render($html) {
        $route=self::route(true);
        return $route ? self::transform($html,$route['role'],$route['phrases']) : $html;
    }

    public static function enqueue() {
        $route=self::route(false);
        if ($route && function_exists('wp_enqueue_style') &&
            function_exists('plugins_url')) {
            // Shared, production-verified accessible RIS style. No new CSS runtime.
            wp_enqueue_style('nry-gsc-revir-ris-paths-v1',
                plugins_url('assets/nry-gsc-revir-ris-paths-v1.css',__FILE__),
                array(),'1.0.0');
        }
    }

    public static function boot() {
        if (function_exists('add_filter') && function_exists('add_action') &&
            function_exists('is_single') && function_exists('get_post')) {
            add_filter('the_content',array(__CLASS__,'render'),95);
            add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),25);
        }
    }
}
NRY_GSC_Regional_Accuracy_V1::boot();
