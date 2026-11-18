<?php
/**
 * Exact-source, read-only presentation of verified Nové Mlýny stays on the
 * historical South Moravia holiday article. No WordPress content mutation,
 * affiliate redirect fetch or new tracking channel.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Jizni_Morava_Stays_12166_V1 {
    const ID = 12166;
    const SLUG = 'rybarska-dovolena-jizni-morava';
    const URL = 'https://na-ryby.eu/rybarska-dovolena-jizni-morava/';
    // Never enable without a fresh authenticated WordPress raw source and
    // exact metadata verification. Pending values must remain fail-closed.
    const MODIFIED_GMT = '2017-06-28 21:26:22';
    const SOURCE_SHA256 = '2bdb8599825facdb6bd90393136074ea5af40efe3b265e58ab75da99e0ff9587';
    const MARKER = 'data-nry-jm-stays="v1"';
    const ARCHIVE_MARKER = 'data-nry-jm-archive="v1"';
    const ARCHIVE_ID = 'nry-jm-stays-12166';
    const INTRO = '<p><strong>Rybářská dovolená jižní morava</strong> &#8211;';
    const LEGACY_AGODA = 'https://www.anrdoezrs.net/click-8287231-15735403?url=https%3A%2F%2Fwww.agoda.com%2Fsearch%3Fcity%3D1418%26textToSearch%3DOlomouc%26ds%3DNVXXV6AsTxxuHYjr';
    const LEGACY_FOOTER = '<div style="margin-top: 10px; font-size: 11px; color: #999;">* Garantovaná nejnižší cena a možnost zrušení zdarma u většiny objektů.</div>';
    const DYJE6 = 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/';
    const OFFICIAL = 'https://www.rybnikarstvipohorelice.cz/dyje-6/';
    // Owner-supplied Nov 18 Slevomat CZ campaign domain: na-ryby.eu. This
    // new placement has its own utm_content and is not the Dyje6 CTA copy.
    const SLEVOMAT = 'https://www.slevomat.cz/akce/2464086-dovolena-na-palave-polopenze-i-vstup-do-wellness?utm_source=affiliate&utm_medium=cpc&utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_na-ryby.eu&utm_content=stay_12166_termal_musov&utm_term=j3d7tsgc&a_box=j3d7tsgc';
    const HPORTAL = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fpasohlavky%2Fpenzion-pasohlavky%2F';
    const HAUZI = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fchalupa-dolni-vestonice';

    public static function boot() {
        // Match the established Dyje 6 module's WordPress-runtime gate.
        // Publisher/KSES contract tests intentionally stub add_filter without
        // a public-query API and must see no frontend hook registrations.
        if (function_exists('is_single') && function_exists('add_filter')) {
            // WP accepts Class::method string callbacks. Using this form also
            // keeps the publisher's legacy KSES filter contracts compatible:
            // their minimal add_filter stubs index callbacks by string.
            add_filter('the_content', __CLASS__ . '::render', PHP_INT_MAX);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__ . '::enqueue', 28);
        }
    }

    private static function eligible() {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post' && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($post->post_content ?? null)
            && hash('sha256', $post->post_content) === self::SOURCE_SHA256;
    }

    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-jizni-morava-stays-12166-v1',
            plugins_url('assets/nry-jizni-morava-stays-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function offer($id, $title, $description, $url, $cta) {
        return '<article data-nry-offer-id="'.$id.'">'
            .'<h3>'.$title.'</h3><p>'.$description.'</p>'
            .'<p><a class="nry-jm-stays__cta" href="'.htmlspecialchars($url, ENT_QUOTES, 'UTF-8').'"'
            .' target="_blank" rel="sponsored nofollow noopener">'.$cta.'</a></p></article>';
    }

    public static function block() {
        return '<section id="'.self::ARCHIVE_ID.'" class="nry-jm-stays" '.self::MARKER.' aria-label="Aktuální tipy na pobyty u Nových Mlýnů">'
            .'<p class="nry-jm-stays__eyebrow">Ověřené nabídky u Nových Mlýnů · listopad 2026</p>'
            .'<h2>Rybářská dovolená na jižní Moravě: nejdřív revír, potom nocleh</h2>'
            .'<p>Chystáte se na Nové Mlýny? Vyberte si ubytování v Pasohlávkách nebo Dolních Věstonicích, '
            .'ale nejprve si ověřte, <strong>na které vodě a na kterém úseku smíte lovit</strong>. '
            .'Například Dyje 6 vyžaduje vlastní místní povolenku od správce a lov pouze na určené části hráze.</p>'
            .'<p class="nry-jm-stays__revir"><strong>Začněte u revíru:</strong> '
            .'<a href="'.self::DYJE6.'">Věstonická nádrž – Dyje 6: podmínky, povolenky a místa lovu</a> '
            .'· <a href="'.self::OFFICIAL.'" target="_blank" rel="noopener">aktuální pravidla správce</a></p>'
            .'<div class="nry-jm-stays__grid">'
            .self::offer('2464086', 'Hotel Termal Mušov – Pasohlávky',
                'Pobyt na Pálavě s polopenzí a možností wellness. Jde o hotelovou nabídku, nikoli o povolenku na Dyji 6; volné termíny a variantu ověřte při výběru.',
                self::SLEVOMAT, 'Ověřit pobyt na Slevomatu')
            .self::offer('hportal-penzion-pasohlavky', 'Penzion Pasohlávky – Pasohlávky 206',
                'Ubytování v obci Pasohlávky; Hportal uvádí rybaření mezi okolními aktivitami. Konkrétní lovné místo ani povolenka nejsou potvrzenou součástí pobytu.',
                self::HPORTAL, 'Ověřit ubytování na Hportalu')
            .self::offer('hauzi-chalupa-dolni-vestonice', 'Chalupa Dolní Věstonice – až 11 osob',
                'Samostatná chalupa v Dolních Věstonicích se třemi ložnicemi. Nabídka sama nezaručuje přístup na povolený lovný úsek ani volnou kapacitu ve vašem termínu.',
                self::HAUZI, 'Ověřit chalupu na Hauzi')
            .'</div><p class="nry-jm-stays__disclaimer"><small>Partnerské odkazy. Údaje o pobytech byly ověřeny 18. 11. 2026; '
            .'ceny, varianty a termíny se mění. Každá voda může mít odlišného správce, pravidla a povolenky.</small></p>'
            .'</section>'
            .'<p class="nry-jm-stays__archive" '.self::ARCHIVE_MARKER.'><strong>Pozor na starší katalog níže:</strong> '
            .'Následující tipy pocházejí převážně z let 2016–2018. Původní popisy, ceny ani dostupnost ubytování '
            .'nepovažujte za aktuálně ověřenou nabídku.</p>';
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $m) === false) return null;
        return array_map(function($h) { return html_entity_decode($h, ENT_QUOTES, 'UTF-8'); }, $m[2]);
    }

    public static function transform($html) {
        if (!is_string($html) || strpos($html, self::MARKER) !== false
            || strpos($html, self::ARCHIVE_MARKER) !== false) return $html;
        // Source-pinned legacy article contains a provably wrong Olomouc
        // Agoda CTA in a SOUTH MORAVIA guide and an unsupported lowest-price
        // guarantee. Replace only that exact source-visible box, preserving
        // every other original href and all 46 historical archive entries.
        if (substr_count($html, self::INTRO) !== 1
            || substr_count($html, 'Chata Brněnská přehrada') < 1
            || substr_count($html, 'Rybářský wellness srub') < 1
            || substr_count($html, '<div class="agoda-booking-box"') !== 1
            || substr_count($html, self::LEGACY_AGODA) !== 1
            || substr_count($html, self::LEGACY_FOOTER) !== 1) return $html;
        $start = strpos($html, '<div class="agoda-booking-box"');
        $footer = strpos($html, self::LEGACY_FOOTER, $start);
        if ($footer === false) return $html;
        $after_footer = $footer + strlen(self::LEGACY_FOOTER);
        if (substr($html, $after_footer, strlen("\n</div>")) !== "\n</div>") return $html;
        $end = $after_footer + strlen("\n</div>");
        $legacy = substr($html, $start, $end - $start);
        if (substr_count($legacy, self::LEGACY_AGODA) !== 1
            || substr_count($legacy, '<strong>Olomouc</strong>') !== 1
            || substr_count($legacy, 'Ubytování u revíru Morava') !== 1) return $html;
        $old_hrefs = self::hrefs($html);
        if ($old_hrefs === null || substr_count(implode(' ', $old_hrefs), self::LEGACY_AGODA) !== 1
            || strpos($html, self::SLEVOMAT) !== false || strpos($html, self::HPORTAL) !== false
            || strpos($html, self::HAUZI) !== false) return $html;
        $footer_link = '<p class="nry-jm-stays__archive-footer">Plánujete pobyt u Nových Mlýnů? '
            .'<a href="#'.self::ARCHIVE_ID.'">Podívejte se na ověřené možnosti ubytování výše</a>.</p>';
        $without_wrong_box = substr($html,0,$start).$footer_link.substr($html,$end);
        $intro_at = strpos($without_wrong_box, self::INTRO);
        if ($intro_at === false) return $html;
        $changed = substr($without_wrong_box,0,$intro_at).self::block()."\n"
            .substr($without_wrong_box,$intro_at);
        $new_hrefs = self::hrefs($changed);
        $new_only = array(self::DYJE6, self::OFFICIAL, self::SLEVOMAT, self::HPORTAL,
            self::HAUZI, '#'.self::ARCHIVE_ID);
        $preserved = array_values(array_filter($old_hrefs, function($h) {
            return $h !== self::LEGACY_AGODA;
        }));
        $remaining = array_values(array_filter($new_hrefs ?? array(), function($h) use($new_only) {
            return !in_array($h, $new_only, true);
        }));
        return ($new_hrefs !== null && $preserved === $remaining
            && substr_count($changed, self::MARKER) === 1
            && substr_count($changed, self::ARCHIVE_MARKER) === 1
            && substr_count($changed, 'data-nry-offer-id=') === 3
            && strpos($changed, self::LEGACY_AGODA) === false
            && strpos($changed, 'Garantovaná nejnižší cena') === false)
            ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Jizni_Morava_Stays_12166_V1::boot();
