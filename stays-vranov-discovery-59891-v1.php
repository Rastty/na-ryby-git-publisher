<?php
/**
 * Two verified first-party Vranov stay choices from the existing stay buyer hub.
 * Read-only, no DB writes, merchant calls, event tracking or affiliate href rewrite.
 * A strict page, post and editorial-section guard fails closed on content drift.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Stays_Vranov_Discovery_59891_V1 {
    const ID = 59891;
    const SLUG = 'rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare';
    const URL = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const MARKER = 'data-nry-vranov-stays-59891="v1"';
    const EDIT_MARKER = 'data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"';
    const PREVIOUS = '<h4>Statek Jedraž – Jižní Čechy</h4>';
    const NEXT = '<h3>Další možnosti ubytování v rybářských lokalitách</h3>';
    const CYCLO = 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/';
    const WELLNESS = 'https://na-ryby.eu/chata-rybarsky-wellness-srub/';

    private static function eligible() {
        foreach (array('is_single','is_singular','get_queried_object_id',
            'is_admin','is_preview','home_url','get_permalink','get_post') as $f) {
            if (!function_exists($f)) return false;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || !is_single(self::ID) || !is_singular('post')
            || (int)get_queried_object_id() !== self::ID || is_admin() || is_preview()
            || (function_exists('is_feed') && is_feed())
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ID) !== self::URL) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG;
    }

    public static function block() {
        return '<nav '.self::MARKER.' aria-label="Dvě konkrétní chaty u Vranovské přehrady">'
            .'<h3>Vranovská přehrada: dvě konkrétní chaty</h3>'
            .'<p>Vybíráte pobyt u Vranova? Porovnejte i tyto dvě samostatné nabídky. '
            .'Cenu, volný termín, přístup k vodě a možnost rybaření si ověřte před rezervací.</p>'
            .'<ul>'
            .'<li><a href="'.self::CYCLO.'"><strong>Cyklochata Vranov</strong></a>'
            .' – zázemí pro skupinový pobyt. Povolenka a podmínky lovu se ověřují zvlášť.</li>'
            .'<li><a href="'.self::WELLNESS.'"><strong>Rybářský wellness srub</strong></a>'
            .' – sauna a koupací sud; možnost lovu může omezit nižší hladina přehrady.</li>'
            .'</ul></nav>';
    }


    /** Two reviewed, existing approved Hauzi destinations. No new offer identity,
     * no nested editorial article/section and no changes to the five v9 anchors. */
    public static function lipno() {
        if (!class_exists('NRY_Lipno_Pair_Offers_119692_V1')) return '';
        $offers=array(
            array('vyhlidka','Pension Vyhlídka','Penzion a chaty','Pro rodinu nebo skupinu: porovnejte pokoje a chaty, počet lůžek a možnosti stravování.',NRY_Lipno_Pair_Offers_119692_V1::VYHLIDKA),
            array('jbx','JBX Resort Albatros','Pokoje a apartmány','Pro dvojici nebo rodinu: zkontrolujte vhodný pokoj či apartmán, wellness služby a jejich cenu.',NRY_Lipno_Pair_Offers_119692_V1::JBX),
        );
        $out='<div data-nry-lipno-hub-offers="v1" role="region" aria-label="Dvě další ubytování u Lipna" style="margin:22px 0;padding:16px;border:1px solid #b9d9c9;border-radius:12px;background:#fff">'
            .'<h3 style="margin:0 0 8px;color:#183d2d">Lipno: porovnejte dvě nabídky v Přední Výtoni</h3>'
            .'<p>Oba objekty leží u jezera, ale nabízejí jiné zázemí. Vyberte nejprve podle počtu hostů a požadovaného typu ubytování; potom potvrďte cestu k povolenému lovišti.</p>'
            .'<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:12px">';
        foreach($offers as $o){
            $out.='<div style="display:flex;flex-direction:column;min-width:0;padding:15px;border:1px solid #c0d9cb;border-radius:10px;background:#f7faf8">'
                .'<p style="font-size:13px;font-weight:700;color:#276645;margin:0 0 5px">Přední Výtoň · '.$o[2].'</p>'
                .'<h4 style="margin:0 0 8px;color:#183d2d">'.$o[1].'</h4><p style="flex:1;margin:0 0 14px">'.$o[3].'</p>'
                .'<a data-nry-lipno-hub-offer="'.$o[0].'" href="'.htmlspecialchars($o[4],ENT_QUOTES|ENT_HTML5,'UTF-8').'" target="_blank" rel="sponsored nofollow noopener" style="display:block;min-height:44px;padding:13px 10px;background:#126747;color:#fff;text-align:center;border-radius:8px;font-weight:700;text-decoration:none">Ověřit termín a cenu – '.$o[1].'</a></div>';
        }
        return $out.'</div><p style="font-size:13px;margin:12px 0 0">Partnerské odkazy Hauzi. Uvedené objekty nejsou rybářské balíčky. Ověřte cenu pro stejný termín, povinné poplatky, přístup k vodě a potřebná oprávnění k lovu. Nabídky nejsou řazené podle ceny ani hodnocení.</p>'
            .'<p style="margin:9px 0 0">Jedete ve dvou? <a href="'.NRY_Lipno_Pair_Offers_119692_V1::URL.'">Praktický plán rybářského víkendu na Lipně</a>.</p></div>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 2500
            || substr_count($html, self::EDIT_MARKER) !== 1
            || substr_count($html, self::PREVIOUS) !== 1
            || substr_count($html, self::NEXT) !== 1
            || strpos($html, self::MARKER) !== false
            || strpos($html, 'data-nry-lipno-hub-offers=') !== false
            || strpos($html, 'sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fjbx-resort-albatros') !== false
            || strpos($html, 'sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fkomfortny-penzion-so-saunou-na-lipne') !== false
            || strpos($html, self::CYCLO) !== false
            || strpos($html, self::WELLNESS) !== false) return $html;
        $prev = strpos($html, self::PREVIOUS);
        $next = strpos($html, self::NEXT);
        $mark = strpos($html, self::EDIT_MARKER);
        if ($prev === false || $next === false || $mark === false
            || $mark >= $prev || $prev >= $next || ($next - $prev) > 5000) return $html;
        $lipno=self::lipno();
        if(substr_count($lipno,'data-nry-lipno-hub-offer=')!==2)return $html;
        $insert=self::block().$lipno;
        $result = substr($html, 0, $next).$insert.substr($html, $next);
        return substr_count($result, self::MARKER) === 1
            && substr_count($result, 'href="'.self::CYCLO.'"') === 1
            && substr_count($result, 'href="'.self::WELLNESS.'"') === 1
            && str_replace($insert, '', $result) === $html
            ? $result : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
    public static function buffer() {
        if (self::eligible() && function_exists('ob_start') && !headers_sent()) {
            ob_start(array(__CLASS__, 'render'));
        }
    }
    public static function boot() {
        if (function_exists('add_filter')) add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        if (function_exists('add_action')) add_action('template_redirect', __CLASS__.'::buffer', 26);
    }
}
NRY_Stays_Vranov_Discovery_59891_V1::boot();
