<?php
/**
 * Five reviewed Lipno budget guides with no direct booking exits.
 * Existing site-approved offers only; exact WP identity/revision and heading.
 * Render-only, original article preserved; no stored writes or new merchant URLs.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Lipno_Five_Reservation_Paths_V1 {
    const MARKER='data-nry-lipno-reservation="v1"';
    public static function routes() {
        return array(
            119686=>array('levne-ubytovani-u-lipna-pro-partu-kdy-vyjde-dobre-cena-na-osobu','2026-05-24 10:01:46','Levné ubytování u Lipna pro partu: kdy vyjde dobře cena na osobu','parta','Dvě možnosti pro rybářskou partu','U skupiny porovnejte celkovou cenu za všechny osoby a noci, nikoli jen nejnižší sazbu za lůžko. Například 6 000 Kč za dvě noci pro čtyři osoby znamená 750 Kč na osobu a noc před dalšími poplatky. Ověřte obsazenost, počet samostatných lůžek, parkování a úschovu výbavy.',array('vyhlidka','kohoutu')),
            119678=>array('levne-ubytovani-u-lipna-na-vikend-co-jeste-dava-smysl','2026-05-25 04:01:51','Levné ubytování u Lipna na víkend: co ještě dává smysl','vikend','Porovnejte dvě konkrétní možnosti na víkend','U dvou nocí rozhoduje i minimální délka pobytu, čas příjezdu a odjezdu a možnost bezpečně uschovat pruty. Levnější pokoj nemusí vyjít levněji, pokud zaplatíte další parkování nebo stravování. Porovnejte konečnou cenu pro stejný termín.',array('kohoutu','vyhlidka')),
            119672=>array('levna-chata-apartman-nebo-pohodlnejsi-pobyt-u-lipna-co-dava-rybari-vetsi-smysl','2026-05-23 04:01:33','Levná chata, apartmán, nebo pohodlnější pobyt u Lipna: co dává rybáři větší smysl','typ','Chata, pokoj, nebo apartmán? Prověřte dvě různé varianty','Chata může nabídnout více soukromí, apartmán zázemí pro vlastní stravování a pokoj jednodušší krátký pobyt. V Přední Výtoni můžete porovnat dva různé objekty. Než rezervujete, ověřte, co je skutečně v ceně a zda konkrétní typ ubytování vyhovuje vaší skupině.',array('vyhlidka','jbx')),
            119671=>array('na-cem-u-ubytovani-u-lipna-nesetrit-a-kde-naopak-klidne-ano','2026-05-22 22:01:17','Na čem u ubytování u Lipna nešetřit a kde naopak klidně ano','rozpocet','Srovnejte konečnou cenu, ne jen cenu za noc','Ptejte se na povinné příplatky, možnost zrušení rezervace, vytápění mimo sezonu a parkování. Pokud potřebujete dobíjet vlastní rybářskou elektroniku nebo uložit mokrou výbavu, potvrďte s ubytovatelem, zda je to možné. Služby nepovažujte za automaticky zahrnuté.',array('kohoutu','vyhlidka')),
            119661=>array('ubytovani-u-lipna-blizko-vody-pro-rybare-kdy-to-dava-smysl-a-kdy-ne','2026-05-26 04:02:05','Ubytování u Lipna blízko vody pro rybáře: kdy to dává smysl a kdy ne','u_vody','Vyberte správnou část Lipna podle loviště','Horní Planá a Přední Výtoň leží v různých částech Lipna. Na mapě si předem ověřte skutečnou cestu k místu lovu, možnost zaparkovat a pravidla přístupu k vodě. Ubytování u jezera není zárukou vlastního mola ani lovného místa.',array('kohoutu','vyhlidka')),
        );
    }
    public static function offers() {
        return array(
            'kohoutu'=>array('Pension U Kohoutů','Horní Planá · penzion; prověřte dostupný pokoj a cenu pro svůj termín.',NRY_Jihocesky_Lipno_Stays_13088_V1::HPORTAL),
            'vyhlidka'=>array('Pension Vyhlídka','Přední Výtoň · pokoje a chaty podle nabídky Hauzi; ověřte vhodný typ a kapacitu.',NRY_Lipno_Pair_Offers_119692_V1::VYHLIDKA),
            'jbx'=>array('JBX Resort Albatros','Přední Výtoň · pokoje a apartmány podle nabídky Hauzi; porovnejte rozsah služeb.',NRY_Lipno_Pair_Offers_119692_V1::JBX),
        );
    }
    private static function eligible($loop=false) {
        if (!is_singular('post') || is_admin() || is_preview() || is_feed()
            || (function_exists('is_customize_preview') && is_customize_preview())
            || ($_SERVER['REQUEST_METHOD']??'GET')!=='GET'
            || rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
            || ($loop && (!in_the_loop() || !is_main_query()))) return null;
        $id=(int)get_queried_object_id();$routes=self::routes();
        if (!isset($routes[$id]) || !is_single($id)) return null;
        $r=$routes[$id];$p=get_post($id);
        if (!is_object($p) || (int)$p->ID!==$id || $p->post_type!=='post'
            || $p->post_status!=='publish' || $p->post_name!==$r[0]
            || $p->post_modified_gmt!==$r[1] || get_permalink($id)!=='https://na-ryby.eu/'.$r[0].'/'
            || !is_string($p->post_content) || strlen($p->post_content)<10000
            || strpos($p->post_content,$r[2])===false) return null;
        return $r;
    }
    private static function esc($s) { return htmlspecialchars($s,ENT_QUOTES|ENT_HTML5,'UTF-8'); }
    public static function panel($r) {
        $offers=self::offers();
        $out='<section class="nry-gear-archive-entry nry-lipno-reservation" '.self::MARKER
            .' data-nry-lipno-role="'.self::esc($r[3]).'" aria-label="Konkrétní ubytování na Lipně">'
            .'<p class="nry-gear-archive-entry__eyebrow">Lipno · konkrétní možnosti ubytování</p>'
            .'<h2>'.self::esc($r[4]).'</h2><p>'.self::esc($r[5]).'</p>'
            .'<nav aria-label="Dvě možnosti ubytování na Lipně">';
        foreach($r[6] as $key) {
            if (!isset($offers[$key])) return '';
            $o=$offers[$key];
            $out.='<a class="nry-gear-archive-entry__choice" data-nry-lipno-offer="'.self::esc($key)
                .'" href="'.self::esc($o[2]).'" target="_blank" rel="sponsored nofollow noopener">'
                .'<strong>'.self::esc($o[0]).' – ověřit termín a cenu</strong><span>'.self::esc($o[1]).'</span></a>';
        }
        return $out.'</nav><p class="nry-gear-archive-entry__note">Partnerské odkazy Hauzi nebo Hportal. Nejde o garantované nejlevnější nabídky ani rybářské balíčky. Ověřte volný termín, konečnou cenu, přístup k vodě a potřebná oprávnění k lovu; povolenka není automaticky součástí pobytu.</p></section>';
    }
    public static function transform($html,$r) {
        if (!is_string($html) || strlen($html)<10000 || strpos($html,self::MARKER)!==false
            || preg_match_all('~<h1\b[^>]*>(.*?)</h1\s*>~isu',$html,$h)!==1
            || trim(html_entity_decode(strip_tags($h[1][0]),ENT_QUOTES|ENT_HTML5,'UTF-8'))!==$r[2]
            || preg_match('~(?:tracking\.affiliateport\.eu|go\.dognet\.com|dpbolvw\.net|tkqlhce\.com|jdoqocy\.com|anrdoezrs\.net|kqzyfj\.com)~i',$html)) return $html;
        $block=self::panel($r);
        if (substr_count($block,'data-nry-lipno-offer=')!==2) return $html;
        // Insert directly after the unique source H1; all prior bytes are preserved.
        $pos=strpos($html,$h[0][0])+strlen($h[0][0]);
        return substr($html,0,$pos)."\n".$block."\n".substr($html,$pos);
    }
    public static function render($html) { $r=self::eligible(true);return $r?self::transform($html,$r):$html; }
    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style') || !function_exists('wp_enqueue_script') || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-gear-archive-entry-v1',plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');
        wp_enqueue_script('nry-lipno-reservation-click-v1',plugins_url('assets/nry-lipno-reservation-click-v1.js',__FILE__),array(),'1.0.0',true);
    }
    public static function boot() {
        if (!function_exists('is_single') || !function_exists('is_singular') || !function_exists('add_filter') || !function_exists('add_action')) return;
        add_filter('the_content',array(__CLASS__,'render'),80);
        add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),27);
    }
}
NRY_Lipno_Five_Reservation_Paths_V1::boot();
