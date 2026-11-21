<?php
/**
 * Three source-reviewed indexed fishing-water journeys, no WordPress writes:
 * 46986 candát (70 GSC impressions 1 click; completed 2026-08-17..11-14),
 * 17820/17825 2018 archive pstruh listings (35/45 impressions, zero clicks).
 * RIS is the maintained source of rules, 2018 lists are NOT current advice.
 * All original text, links, canonicals, permits and merchant hrefs remain.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_GSC_Revir_Source_Paths_V1 {
    const MARKER='data-nry-gsc-revir-ris-paths="v1"';
    const HOST='https://na-ryby.eu';
    const RIS='https://ris.rybsvaz.cz/seznam-reviru';
    const RULES='https://ris.rybsvaz.cz/rybarsky-rad';
    const DYJE6='https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/';
    const STAYS='https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const SONAR='https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';
    const CANDAT_ANCHOR='<h2>Kde candáta v ČR hledat nejčastěji</h2>';
    const CANDAT_SOURCE='<h1>Kde v ČR chytit candáta obecného: revíry, místa a praktické tipy</h1>';
    const PRAHA_SOURCE='&nbsp;';
    const CENTRAL_SOURCE='<h3>413 001 BĚLÁ 1 (BĚLSKÝ POTOK)</h3>';
    const ROUTES=array(
        46986=>array(
            'slug'=>'rybarske-reviry-kde-muzete-ulovit-candata-obecneho-v-cr',
            'modified'=>'2026-04-15 17:01:43',
            'source'=>'<h1>Kde v ČR chytit candáta obecného: revíry, místa a praktické tipy</h1>',
            'marker'=>'candat'
        ),
        17820=>array(
            'slug'=>'svazove-rybarske-pstruhove-reviry-praha',
            'modified'=>'2018-07-17 19:33:38',
            'source'=>'&nbsp;',
            'marker'=>'praha'
        ),
        17825=>array(
            'slug'=>'svazove-rybarske-pstruhove-reviry-stredocesky-kraj',
            'modified'=>'2018-07-17 19:59:44',
            'source'=>'<h3>413 001 BĚLÁ 1 (BĚLSKÝ POTOK)</h3>',
            'marker'=>'stredocesky'
        )
    );

    private static function route() {
        foreach (array('is_single','is_admin','is_preview','is_main_query','in_the_loop',
            'home_url','get_queried_object_id','get_permalink','get_post') as $fn) {
            if (!function_exists($fn)) return null;
        }
        if (is_admin() || is_preview() || !is_main_query() || !in_the_loop()
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET')!=='GET'
            || (function_exists('is_feed') && is_feed())
            || (function_exists('is_customize_preview') && is_customize_preview())
            || rtrim(home_url('/'),'/')!==self::HOST) return null;
        $id=(int)get_queried_object_id();
        if (!isset(self::ROUTES[$id])) return null;
        $data=self::ROUTES[$id];
        if (!is_single($data['slug']) ||
            get_permalink($id)!==self::HOST.'/'.$data['slug'].'/') return null;
        $post=get_post($id);
        if (!is_object($post) || (int)($post->ID ?? 0)!==$id ||
            ($post->post_type ?? '')!=='post' ||
            ($post->post_status ?? '')!=='publish' ||
            ($post->post_name ?? '')!==$data['slug'] ||
            ($post->post_modified_gmt ?? '')!==$data['modified'] ||
            !is_string($post->post_content ?? null) ||
            strpos($post->post_content,$data['source'])!==0) return null;
        return array('id'=>$id,'data'=>$data);
    }

    public static function block($id) {
        if (!isset(self::ROUTES[$id])) return '';
        $type=self::ROUTES[$id]['marker'];
        if ($type==='candat') {
            return '<section class="nry-ris-choices" '.self::MARKER
                .' data-nry-ris-scope="candat" aria-labelledby="nry-ris-candat-title">'
                .'<p class="nry-ris-choices__eyebrow">Naplánovat výpravu</p>'
                .'<h2 id="nry-ris-candat-title">Nejdřív voda a pravidla, potom výbava</h2>'
                .'<ol><li><strong>Najděte konkrétní revír.</strong> Názvy přehrad jsou orientační, '
                .'ne garance výskytu candáta ani povoleného lovu. '
                .'<a href="'.self::RIS.'">Vyhledat revír v oficiálním RIS ČRS</a> '
                .'nebo si projít příklad <a href="'.self::DYJE6.'">Věstonická nádrž – Dyje 6</a>.</li>'
                .'<li><strong>Ověřte podmínky v den výpravy.</strong> '
                .'Druh povolenky, hájení, nejmenší lovnou míru, dobu lovu, '
                .'případná omezení člunu a přístupu zjistěte v '
                .'<a href="'.self::RULES.'">aktuálním rybářském řádu a soupisu revírů</a>.</li>'
                .'<li><strong>Teprve potom řešte zázemí a vybavení.</strong> '
                .'Pro delší cestu zvažte <a href="'.self::STAYS.'">rybářské pobyty u vody</a>; '
                .'pokud lovíte z lodě, pomůže <a href="'.self::SONAR.'">průvodce výběrem echolotu</a>.</li></ol>'
                .'<p class="nry-ris-choices__note">Jde o plánování, ne potvrzení dnešní obsádky, '
                .'povolenek, rezervací, cen nebo dostupnosti.</p></section>';
        }
        $place=$type==='praha'?'Praha':'Středočeský kraj';
        return '<section class="nry-ris-choices nry-ris-choices--archive" '
            .self::MARKER.' data-nry-ris-scope="'.$type.'" '
            .'aria-labelledby="nry-ris-archive-title">'
            .'<p class="nry-ris-choices__eyebrow">Důležité před výpravou</p>'
            .'<h2 id="nry-ris-archive-title">Původní soupis z roku 2018 – '
            .'aktuální pravidla zkontrolujte</h2>'
            .'<p>Seznam níže je historický podklad pro oblast <strong>'.$place.'</strong>, '
            .'nikoli aktuální potvrzení možnosti rybolovu, hranic revíru '
            .'nebo místních výjimek. Před lovem ověřte přesný kód revíru, '
            .'platnost povolenky, období lovu, hájení a bližší podmínky.</p>'
            .'<p><a href="'.self::RIS.'">Aktuální seznam a mapa revírů v RIS ČRS</a>'
            .' · <a href="'.self::RULES.'">Rybářský řád a soupisy pro 2026–2027</a></p>'
            .'<p class="nry-ris-choices__note">Podmínky se mohou změnit i '
            .'po vydání soupisu. Rozhodující je zveřejnění příslušného '
            .'uživatele revíru a aktuální pravidla ČRS.</p></section>';
    }

    public static function transform($html,$id) {
        if (!isset(self::ROUTES[$id]) || !is_string($html)
            || strlen($html)<500 || strpos($html,self::MARKER)!==false
            || strpos($html,'nry-ris-choices')!==false) return $html;
        if ($id===46986) {
            if (substr_count($html,self::CANDAT_ANCHOR)!==1 ||
                strpos($html,'Kde v ČR chytit candáta obecného')===false ||
                strpos($html,self::DYJE6)!==false ||
                strpos($html,self::STAYS)!==false) return $html;
            $offset=strpos($html,self::CANDAT_ANCHOR);
        } elseif ($id===17820) {
            if (strpos($html,'BOJOVSKÝ POTOK 1')===false ||
                strpos($html,'KOCÁBA 1')===false ||
                stripos($html,'<table')===false) return $html;
            $offset=0;
        } else {
            if (strpos($html,'413 001 BĚLÁ 1')===false ||
                strpos($html,'413 998 BOŽKOV')===false) return $html;
            $offset=0;
        }
        $block=self::block($id);
        if (!$block || substr_count($block,self::MARKER)!==1) return $html;
        $out=substr($html,0,$offset).$block.substr($html,$offset);
        return substr_count($out,self::MARKER)===1
            && str_replace($block,'',$out)===$html ? $out : $html;
    }

    public static function render($html) {
        $route=self::route();
        return $route ? self::transform($html,$route['id']) : $html;
    }
    public static function enqueue() {
        // Do not require the main loop at enqueue time; use source pin but
        // avoid is_main_query() because enqueue fires before the main loop.
        if (!function_exists('wp_enqueue_style') || !function_exists('plugins_url')
            || !function_exists('is_single') || !function_exists('get_post')
            || !function_exists('get_queried_object_id') || is_admin()) return;
        $id=(int)get_queried_object_id();
        if (!isset(self::ROUTES[$id])) return;
        $data=self::ROUTES[$id];
        $post=get_post($id);
        if (!is_single($data['slug']) ||
            !is_object($post) || ($post->post_modified_gmt ?? '')!==$data['modified']
            || !is_string($post->post_content ?? null)
            || strpos($post->post_content,$data['source'])!==0) return;
        wp_enqueue_style('nry-gsc-revir-ris-paths-v1',
            plugins_url('assets/nry-gsc-revir-ris-paths-v1.css',__FILE__),
            array(),'1.0.0');
    }
    public static function boot() {
        if (function_exists('add_filter') && function_exists('add_action')
            && function_exists('is_single') && function_exists('get_post')) {
            add_filter('the_content',array(__CLASS__,'render'),94);
            add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),25);
        }
    }
}
NRY_GSC_Revir_Source_Paths_V1::boot();
