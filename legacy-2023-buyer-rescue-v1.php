<?php
/**
 * Six reviewed 2023 historical buyer posts -> conspicuous first-party
 * current decision guides. Frontend only, no stored WP/merchant URL changes.
 * Reuses existing green equipment cards, scoped only to exact post slugs.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Legacy_2023_Buyer_Rescue_V1 {
    const MARKER = 'data-nry-legacy-2023="v1"';
    private static $rendered = false;
    public static function routes() {
        return array(
            'nejlepsi-rybarske-lehatko-jak-vybrat-nejlepsi-lehatko-pro-rybare' => array(
                'title' => 'Nejlepší rybářské lehátko',
                'headline' => 'Nevybírejte lehátko podle starého žebříčku',
                'intro' => 'Článek vznikl v roce 2023. Než se rozhodnete, porovnejte rozměry, nosnost a pohodlí podle způsobu nocování; historická cena a pořadí nejsou aktuální nabídkou.',
                'links' => array(
                    array('bedchair_guide','Jak vybrat rybářské lehátko','Délka, šířka, nosnost, skladnost','https://na-ryby.eu/rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/'),
                    array('bedchair_5000','Lehátka do 5 000 Kč','Co nabízí běžný rozpočet','https://na-ryby.eu/nejlepsi-rybarska-lehatka-do-5000-kc/'),
                    array('bedchair_weight','Lehátko pro těžší postavu','Stabilita a rezerva nosnosti','https://na-ryby.eu/nejlepsi-rybarska-lehatka-pro-tezsi-postavy-jak-vybrat-spravne/'),
                ),
            ),
            'vysoce-hodnocena-rybarska-lehatka-podrobna-recenze-a-porovnani-popularnich-modelu' => array(
                'title' => 'Vysoce hodnocená rybářská lehátka',
                'headline' => 'Porovnejte lehátka podle účelu, ne hvězdiček',
                'intro' => 'Hodnocení starších modelů není nezávislým testem současné nabídky. Vyberte další krok podle počtu nocí, potřebného prostoru a rozpočtu.',
                'links' => array(
                    array('bedchair_comparison','Aktuálnější přehled lehátek','Vlastnosti a rozdíly konstrukcí','https://na-ryby.eu/nejlepsi-rybarska-lehatka-na-trhu-recenze-a-porovnani/'),
                    array('bedchair_trip','Lehátko na delší výpravy','Pohodlí při vícedenní kaprařině','https://na-ryby.eu/nejlepsi-rybarska-lehatka-pro-delsi-vypravy-top-vyber-a-srovnani/'),
                    array('bedchair_5000','Lehátka do 5 000 Kč','Kdy stačí základní rám a matrace','https://na-ryby.eu/nejlepsi-rybarska-lehatka-do-5000-kc/'),
                ),
            ),
            'nejlepsi-rybarske-pruty-s-navijakem-podle-zkusenych-rybaru' => array(
                'title' => 'Nejlepší rybářské pruty s navijákem',
                'headline' => 'Prut a naviják musí tvořit vhodnou sestavu',
                'intro' => 'Staré doporučení konkrétního setu není důkazem jeho současné dostupnosti. Začněte podle typu lovu, délky prutu, vrhací zátěže a velikosti navijáku.',
                'links' => array(
                    array('starter_set','První sestava do 3 000 Kč','Kolik vyčlenit na prut a naviják','https://na-ryby.eu/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/'),
                    array('rod_choice','Jak vybrat kaprový prut','Délka, akce a testovací křivka','https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'),
                    array('reel_choice','Jak vybrat naviják','Cívka, brzda, převod a kompatibilita','https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/'),
                ),
            ),
            'nejlepsi-rybarske-hlasice-a-signalizatory' => array(
                'title' => 'Nejlepší rybářské hlásiče a signalizátory',
                'headline' => 'Hlásiče vybírejte podle vody a příposlechu',
                'intro' => 'Názvy a stará doporučení v článku nemusí odpovídat dnešním sadám. Rozhoduje spolehlivost, citlivost, dosah přijímače a počasí, ne počet reklamních hvězdiček.',
                'links' => array(
                    array('alarm_choice','Výběr signalizátorů','Funkce hlásičů a jednotlivých sad','https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'),
                    array('alarm_3000','Sada 3+1 do 3 000 Kč','Co při nákupu příposlechu nepřehlédnout','https://na-ryby.eu/kompletni-sada-31-s-priposlechem-do-3-000-kc-co-opravdu-koupit/'),
                    array('alarm_troubleshoot','Hlásič pípá bez záběru','Nejdřív vyloučte nastavení a vítr','https://na-ryby.eu/proc-mi-signalizator-pipa-bez-zaberu-5-nejcastejsich-pricin-a-jak-je-vyresit/'),
                ),
            ),
            'recenze-nejlevnejsich-rybarskych-prutu-pro-zacatecniky-i-pokrocile' => array(
                'title' => 'Recenze nejlevnějších rybářských prutů',
                'headline' => 'Levný prut vybírejte podle rybolovu, ne jen ceny',
                'intro' => 'Historické modely v recenzi nemusí být dostupné a označení „nejlevnější“ nevypovídá o dnešní ceně. Pro začátečníka jsou důležitější ovladatelnost a správná sestava.',
                'links' => array(
                    array('beginner_fishing','Rybaření pro začátečníky','Co koupit a co zatím vynechat','https://na-ryby.eu/rybareni-pro-zacatecniky-jak-zacit-co-koupit-a-prvni-kroky/'),
                    array('starter_set','První sestava do 3 000 Kč','Prut, naviják a základní výbava','https://na-ryby.eu/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/'),
                    array('carp_3000','Kaprové pruty do 3 000 Kč','Výběr podle délky a použití','https://na-ryby.eu/nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber/'),
                ),
            ),
            'detske-rybarske-pruty-s-nejlepsi-cenu-a-kvalitou' => array(
                'title' => 'Dětské rybářské pruty s nejlepší cenu a kvalitou',
                'headline' => 'Dětský prut vybírejte hlavně podle dítěte',
                'intro' => 'Starý článek uvádí historické modely a cenové sliby. U dítěte je podstatná délka a hmotnost prutu, jednoduchý naviják, dohled dospělého a vhodný typ lovu.',
                'links' => array(
                    array('junior_rod','Kaprový prut pro dítě','Velikost a ergonomie pro mladého rybáře','https://na-ryby.eu/kaprove-pruty-pro-deti-a-mladez-jak-vybrat-spravne/'),
                    array('beginner_fishing','První rybaření s výbavou','Základní postupy pro nováčky','https://na-ryby.eu/rybareni-pro-zacatecniky-jak-zacit-co-koupit-a-prvni-kroky/'),
                    array('starter_set','Co pořídit do 3 000 Kč','Rozumná sestava bez zbytečných doplňků','https://na-ryby.eu/prvni-rybarska-sestava-do-3000-kc-co-ma-smysl-a-co-ne/'),
                ),
            ),
        );
    }
    private static function config($in_loop = false) {
        foreach (array('is_singular','is_single','is_admin','get_queried_object_id',
            'get_post','get_permalink','home_url') as $fn) {
            if (!function_exists($fn)) return null;
        }
        if (!is_singular('post') || is_admin()
            || (function_exists('is_preview') && is_preview())
            || (function_exists('is_feed') && is_feed())
            || (function_exists('is_customize_preview') && is_customize_preview())
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || rtrim(home_url('/'),'/') !== 'https://na-ryby.eu') return null;
        if ($in_loop && ((!function_exists('in_the_loop') || !in_the_loop())
            || (!function_exists('is_main_query') || !is_main_query()))) return null;
        $id = (int)get_queried_object_id();
        if ($id <= 0 || !is_single($id) || in_array($id,array(38064,42671),true)) return null;
        $post = get_post($id);
        if (!is_object($post) || (int)($post->ID ?? 0) !== $id
            || ($post->post_type ?? '') !== 'post'
            || ($post->post_status ?? '') !== 'publish'
            || !is_string($post->post_name ?? null)
            || !is_string($post->post_title ?? null)
            || !is_string($post->post_content ?? null)
            || strlen($post->post_content) < 1500
            || !is_string($post->post_date_gmt ?? null)
            || substr($post->post_date_gmt,0,4) !== '2023') return null;
        $all = self::routes();
        $slug = $post->post_name;
        if (!isset($all[$slug]) || get_permalink($id) !== 'https://na-ryby.eu/'.$slug.'/') return null;
        $actual = html_entity_decode(strip_tags($post->post_title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (strpos($actual, $all[$slug]['title']) !== 0) return null;
        return array('slug'=>$slug) + $all[$slug];
    }
    public static function block($cfg) {
        if (!is_array($cfg) || !isset($cfg['slug'],$cfg['headline'],$cfg['intro'],$cfg['links'])) return '';
        $out = '<section class="nry-gear-archive-entry nry-legacy-2023-buyer" '
            .self::MARKER.' data-nry-legacy-slug="'.$cfg['slug'].'"'
            .' aria-label="Rychlá pomoc s výběrem rybářského vybavení">'
            .'<p class="nry-gear-archive-entry__eyebrow">Aktuálnější rozhodovací návody</p>'
            .'<h2>'.$cfg['headline'].'</h2><p>'.$cfg['intro'].'</p>'
            .'<nav aria-label="Pokračovat podle potřeby">';
        foreach ($cfg['links'] as $link) {
            $out .= '<a class="nry-gear-archive-entry__choice" data-nry-legacy-guide="'.$link[0].'"'
                .' href="'.$link[3].'"><strong>'.$link[1].'</strong><span>'.$link[2].'</span></a>';
        }
        return $out.'</nav><p class="nry-gear-archive-entry__note">'
            .'Modely, ceny a bonusy ve starším článku mohou být neaktuální.'
            .' Před nákupem ověřte konkrétní variantu a podmínky přímo u prodejce.</p></section>';
    }
    public static function render($html) {
        $cfg = self::config(true);
        if (self::$rendered || !$cfg || !is_string($html)
            || strlen($html) < 1500 || strpos($html,self::MARKER)!==false) return $html;
        $block = self::block($cfg);
        if (!$block || substr_count($block,'data-nry-legacy-guide=')!==3) return $html;
        self::$rendered=true;
        return $block.$html;
    }
    public static function enqueue() {
        if (!self::config() || !function_exists('wp_enqueue_style')
            || !function_exists('wp_enqueue_script') || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-gear-archive-entry-v1',
            plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');
        wp_enqueue_script('nry-legacy-2023-buyer-click-v1',
            plugins_url('assets/nry-legacy-2023-buyer-click-v1.js',__FILE__),array(),'1.0.0',true);
    }
    public static function boot() {
        if (!function_exists('add_filter') || !function_exists('add_action')) return;
        add_filter('the_content',array(__CLASS__,'render'),80);
        add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),27);
    }
}
NRY_Legacy_2023_Buyer_Rescue_V1::boot();
