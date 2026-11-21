<?php
/**
 * Exact-source, one-post render-only repair of obsolete fishing-by-car guide.
 * Never changes WP storage, booking availability, permits or merchant links.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Reviry_Autem_Trust_56493_V1 {
    const ID = 56493;
    const SLUG = '1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem';
    const URL = 'https://na-ryby.eu/1-nejlepsi-rybarske-reviry-v-cesku-kam-se-dostanete-autem/';
    const MODIFIED_GMT = '2023-06-28 11:29:01';
    // Disarmed until exact authenticated get_post()->post_content source SHA
    // is measured by the independent GET-only /poradce-vybavy/ proof.
    const RAW_SHA256 = '0000000000000000000000000000000000000000000000000000000000000000';
    const MARKER = 'data-nry-reviry-autem-trust="v1"';
    const ORIGINAL_H2 = 'Kam se dostanete autem';
    const BAD_ORLIK = 'Orlická přehrada se nachází východně od Hradce Králové a je dalším skvělým místem pro rybolov.';
    const GOOD_ORLIK = 'Orlická přehrada leží na Vltavě. Před cestou si ověřte konkrétní lovný úsek, povolenku, přístup k vodě a legální místo pro parkování.';
    const OLD_PODYJI = 'Rybářský revír Podyjí';
    const NEW_PODYJI = 'Podyjí: lov jen na konkrétně povolených úsecích';
    const OLD_SUMAVA = 'Rybářský revír Šumava';
    const NEW_SUMAVA = 'Šumava: nejprve ověřte konkrétní revír a ochranu přírody';
    const LIPNO = 'https://na-ryby.eu/rybareni-s-ubytovanim-jihocesky-kraj/';
    const ORLIK = 'https://na-ryby.eu/kde-prespat-u-orliku-na-rybarsky-vikend/';
    const DYJE = 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/';
    const RULES = 'https://na-ryby.eu/jak-cist-soupis-reviru-a-ris-cislo-reviru-podrevir-a-podminky/';

    public static function boot() {
        if (function_exists('add_filter')) {
            add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        }
        if (function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__.'::enqueue', 28);
        }
    }

    private static function eligible_post() {
        foreach (array('is_single','is_singular','get_queried_object_id',
            'is_admin','is_preview','home_url','get_permalink','get_post') as $f) {
            if (!function_exists($f)) return false;
        }
        if (is_admin() || is_preview() || !is_single(self::ID) || !is_singular('post')
            || (int)get_queried_object_id() !== self::ID
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ID) !== self::URL) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && strlen($p->post_content) > 2000
            && hash_equals(self::RAW_SHA256, hash('sha256', $p->post_content));
    }

    public static function block() {
        return '<section class="nry-autem-trust" '.self::MARKER.'>'
            .'<p class="nry-autem-trust__overline">Aktualizovaný výběr pro cestu autem</p>'
            .'<h3>Nejdřív zvolte konkrétní vodu, potom parkování a nocleh</h3>'
            .'<p>Název regionu nebo národního parku sám o sobě neznamená rybářský revír. '
            .'Před odjezdem zjistěte číslo konkrétního revíru, pravidla lovu, '
            .'zda smíte na daný břeh a kde lze skutečně zaparkovat. '
            .'Ubytování není automaticky povolenkou k rybolovu.</p>'
            .'<ul class="nry-autem-trust__grid">'
            .'<li><strong>Lipno a jižní Čechy</strong><span>Vyberte vodu a pobyt; '
            .'přístup autem a povolenku ověřte zvlášť.</span>'
            .'<a href="'.self::LIPNO.'">Rybářské pobyty v jižních Čechách</a></li>'
            .'<li><strong>Orlík na Vltavě</strong><span>Rozlehlá nádrž '
            .'s různými příjezdy; ne každé místo u břehu je parkoviště.</span>'
            .'<a href="'.self::ORLIK.'">Kde přespat u Orlíku</a></li>'
            .'<li><strong>Nové Mlýny – Dyje 6</strong><span>Nejdříve '
            .'zkontrolujte hranice revíru, bližší podmínky a přístup.</span>'
            .'<a href="'.self::DYJE.'">Praktický průvodce Dyje 6</a></li>'
            .'</ul>'
            .'<p class="nry-autem-trust__care">Podyjí a Šumava jsou chráněná území; '
            .'rybolov ani automobilový vjezd nejsou povoleny plošně. '
            .'Ověřte pravidla správ národních parků a uživatele zvoleného rybářského revíru.</p>'
            .'<p class="nry-autem-trust__rules"><a href="'.self::RULES.'">'
            .'Jak ověřit revír, podrevír, povolenku a bližší podmínky</a></p>'
            .'</section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 2000
            || strpos($html, self::MARKER) !== false
            || substr_count($html, self::BAD_ORLIK) !== 1
            || substr_count($html, self::OLD_PODYJI) !== 1
            || substr_count($html, self::OLD_SUMAVA) !== 1) return $html;
        foreach (array(self::LIPNO,self::ORLIK,self::DYJE,self::RULES) as $url) {
            // Do not inject duplicates into an editor-updated page.
            if (strpos($html, $url) !== false) return $html;
        }
        $matches = array();
        if (preg_match_all('~<h2\b[^>]*>\s*'.preg_quote(self::ORIGINAL_H2, '~').'\s*</h2>~u',
            $html, $matches, PREG_OFFSET_CAPTURE) !== 1) return $html;
        $h = $matches[0][0];
        $at = $h[1] + strlen($h[0]);
        $changed = substr($html,0,$at).self::block().substr($html,$at);
        $changed = str_replace(self::BAD_ORLIK,self::GOOD_ORLIK,$changed);
        $changed = str_replace(self::OLD_PODYJI,self::NEW_PODYJI,$changed);
        $changed = str_replace(self::OLD_SUMAVA,self::NEW_SUMAVA,$changed);
        return substr_count($changed,self::MARKER) === 1
            && substr_count($changed,self::GOOD_ORLIK) === 1
            && strpos($changed,self::BAD_ORLIK) === false
            && strpos($changed,self::OLD_PODYJI) === false
            && strpos($changed,self::OLD_SUMAVA) === false
            ? $changed : $html;
    }

    public static function render($html) {
        if (!self::eligible_post() || !function_exists('is_main_query')
            || !function_exists('in_the_loop') || !is_main_query() || !in_the_loop()) return $html;
        return self::transform($html);
    }

    public static function enqueue() {
        if (!self::eligible_post() || !function_exists('plugins_url')
            || !function_exists('wp_enqueue_style')) return;
        wp_enqueue_style('nry-reviry-autem-trust-56493',
            plugins_url('assets/nry-reviry-autem-trust-56493-v1.css', __FILE__),
            array(), '1.0.0');
    }
}
NRY_Reviry_Autem_Trust_56493_V1::boot();
