<?php
/**
 * Source-bounded, output-only 2023 archive + catfish-location decision paths.
 * Never rewrites WordPress post_content, merchant hrefs, images or metadata.
 * Official 2026-27 rules: RIS ČRS; Orlík 481501 is on the Vltava, not Orlice.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_2023_Rules_Sumec_Decisions_V1 {
    const HOST='https://na-ryby.eu';
    const MARKER='data-nry-legacy-decision="v1"';
    const RULES='https://ris.rybsvaz.cz/rybarsky-rad';
    const RIS='https://ris.rybsvaz.cz/seznam-reviru';
    const ORLIK='https://ris.rybsvaz.cz/karta-reviru/-/481501-vltava-16-19-udolni-nadrz-orlik';
    const HOW='https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/';
    const SHORE='https://na-ryby.eu/kde-najit-nejlepsi-reviry-pro-lov-sumcu-ze-brehu-v-ceske-republice/';
    const STAYS='https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const SONAR='https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';

    const ROUTES=array(
        'seznam-nejlepsich-reviru-pro-lov-sumce-na-polozenou-v-cesku'=>array(
            'role'=>'sumec','month'=>'2023-06',
            'source'=>array('Orlická přehrada','Lužnice','Sázava','Pilín','Vápno')
        ),
        'rybarsky-rad-2023'=>array(
            'role'=>'rules2023','month'=>'2023-06',
            'source'=>array('2022','2023','99/2004','197/2004')
        )
    );

    private static function route($need_loop) {
        foreach (array('is_single','is_admin','is_preview','home_url','get_queried_object_id',
            'get_permalink','get_post') as $fn) if (!function_exists($fn)) return null;
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='GET'
            || is_admin() || is_preview()
            || (function_exists('is_feed') && is_feed())
            || (function_exists('is_customize_preview') && is_customize_preview())
            || rtrim(home_url('/'), '/')!==self::HOST) return null;
        if ($need_loop && (!function_exists('is_main_query')
            || !function_exists('in_the_loop') || !is_main_query() || !in_the_loop()))
            return null;
        $id=(int)get_queried_object_id();
        if ($id<=0) return null;
        $post=get_post($id);
        if (!is_object($post) || (int)($post->ID ?? 0)!==$id
            || ($post->post_type ?? '')!=='post' || ($post->post_status ?? '')!=='publish')
            return null;
        $slug=$post->post_name ?? '';
        if (!isset(self::ROUTES[$slug]) || !is_single($slug)
            || get_permalink($id)!==self::HOST.'/'.$slug.'/') return null;
        $route=self::ROUTES[$slug];
        if (!is_string($post->post_date_gmt ?? null)
            || strpos($post->post_date_gmt,$route['month'])!==0
            || !is_string($post->post_content ?? null)
            || strlen($post->post_content)<1300) return null;
        foreach ($route['source'] as $word)
            if (strpos($post->post_content,$word)===false) return null;
        return $route;
    }

    public static function block($role) {
        if ($role==='sumec') {
            return '<section class="nry-ris-choices nry-ris-choices--archive" '
                .self::MARKER.' data-nry-legacy-role="sumec" aria-labelledby="nry-sumec-2026">'
                .'<p class="nry-ris-choices__eyebrow">Aktuální orientace před výpravou</p>'
                .'<h2 id="nry-sumec-2026">Nejdřív přesný revír, potom lov sumce</h2>'
                .'<p>Tento seznam vznikl v roce 2023 a není žebříčkem ověřených úlovků '
                .'ani potvrzením pronájmu lodí, přístupu nebo povolení lovu. '
                .'<strong>Orlická přehrada leží na Vltavě, ne na řece Orlici.</strong> '
                .'Konkrétní svazový revír je '
                .'<a href="'.self::ORLIK.'">481 501 Vltava 16–19, ÚN Orlík v RIS ČRS</a>. '
                .'Na různých úsecích platí jiné zákazy a podmínky.</p>'
                .'<ol><li><a href="'.self::RIS.'">Najděte přesný revír a hranice v RIS</a>; '
                .'název celé řeky nestačí.</li>'
                .'<li><a href="'.self::RULES.'">Ověřte aktuální rybářský řád 2026–2027</a>, '
                .'povolenku, období lovu, nástražní rybku a omezení plavidel; '
                .'historické rady nejsou dnešní povolení.</li>'
                .'<li>Jdete ze břehu? Projděte '
                .'<a href="'.self::SHORE.'">praktický výběr míst pro sumce ze břehu</a>. '
                .'Pro delší výpravu zvažte '
                .'<a href="'.self::STAYS.'">rybářské pobyty u vody</a> '
                .'a při výběru vybavení '
                .'<a href="'.self::SONAR.'">průvodce echoloty</a>.</li></ol>'
                .'<p class="nry-ris-choices__note">Neověřujeme aktuální obsádku ryb, '
                .'ceny, volné termíny, dostupnost člunů ani úlovky.</p></section>';
        }
        if ($role==='rules2023') {
            return '<section class="nry-ris-choices nry-ris-choices--archive" '
                .self::MARKER.' data-nry-legacy-role="rules2023" '
                .'aria-labelledby="nry-rules-archive-2023">'
                .'<p class="nry-ris-choices__eyebrow">Pozor: archiv pravidel</p>'
                .'<h2 id="nry-rules-archive-2023">Text níže platil pro roky 2022–2023</h2>'
                .'<p>Jde o historické znění rybářského řádu, <strong>ne o návod '
                .'podle pravidel pro dnešní rybolov</strong>. Pro roky 2026–2027 '
                .'použijte <a href="'.self::RULES.'">oficiální rybářský řád ČRS v RIS</a> '
                .'a <a href="'.self::RIS.'">aktuální seznam a podmínky konkrétního revíru</a>. '
                .'Bližší podmínky, dodatky, povolenky, lovné míry a zákazy '
                .'mohou být jiné než v archivu.</p>'
                .'<p>Nejste si jistí číslem vody nebo podrevírem? '
                .'<a href="'.self::HOW.'">Praktický návod, jak číst soupis revírů a RIS</a>. '
                .'Původní dokument níže ponecháváme pouze pro historické srovnání.</p>'
                .'</section>';
        }
        return '';
    }

    public static function transform($html,$route) {
        if (!is_array($route)||!isset($route['role'],$route['source'])
            || !is_string($html)||strlen($html)<1000
            || strpos($html,self::MARKER)!==false) return $html;
        foreach($route['source'] as $word)
            if (strpos($html,$word)===false) return $html;
        $section=self::block($route['role']);
        if ($section==='' || substr_count($section,self::MARKER)!==1
            || strpos($html,'nry-sumec-2026')!==false
            || strpos($html,'nry-rules-archive-2023')!==false) return $html;
        $out=$section.$html;
        return substr_count($out,self::MARKER)===1
            && substr($out,strlen($section))===$html ? $out:$html;
    }
    public static function render($html) {
        $route=self::route(true);
        return $route ? self::transform($html,$route):$html;
    }
    public static function enqueue() {
        $route=self::route(false);
        if ($route && function_exists('wp_enqueue_style') && function_exists('plugins_url'))
            wp_enqueue_style('nry-gsc-revir-ris-paths-v1',
                plugins_url('assets/nry-gsc-revir-ris-paths-v1.css',__FILE__),array(),'1.0.0');
    }
    public static function boot() {
        if (function_exists('add_filter') && function_exists('add_action')
            && function_exists('get_post') && function_exists('is_single')) {
            add_filter('the_content',array(__CLASS__,'render'),96);
            add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),25);
        }
    }
}
NRY_2023_Rules_Sumec_Decisions_V1::boot();
