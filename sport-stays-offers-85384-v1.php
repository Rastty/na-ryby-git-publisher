<?php
/**
 * Three concrete, previously approved CZ accommodation affiliate routes on
 * one raw-source-pinned general fishing holiday guide. Frontend-only output:
 * never changes WP stored content, pricing, cookies or outbound URL values.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sport_Stays_Offers_85384_V1 {
    const ID = 85384;
    const SLUG = 'nejlepsi-mista-pro-sportovni-rybolov-s-ubytovanim-v-ceske-republice';
    const URL = 'https://na-ryby.eu/nejlepsi-mista-pro-sportovni-rybolov-s-ubytovanim-v-ceske-republice/';
    const MODIFIED_GMT = '2026-04-12 12:38:45';
    // Raw SHA returned independently by read-only source proof #273.
    const RAW_SHA256 = '6787d69c3c9040945a6a13df42b33fecdde4fba73fc2f279bb6e4145237a6969';
    // First 512 raw bytes of WordPress REST content.rendered, 18 Nov 2026.
    const PREFIX_BYTES = 512;
    const PREFIX_SHA256 = '506a49cd42f4223533b9ef36e40665fba49e3a60a26806cd82b162039a4e19c5';
    const ANCHOR = '<h1>Nejlepší místa pro sportovní rybolov s ubytováním v České republice</h1>';
    const MARKER = 'data-nry-sport-stays="v1"';
    const LEGACY_BOOKING = 'https://na-ryby.eu/booking-cesko';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const SOUTH_BOHEMIA = 'https://www.slevomat.cz/akce/2462030-statek-na-jihu-cech-snidane-i-privatni-sauna-ci-rybareni?utm_source=affiliate&utm_medium=cpc&utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_narybyCZ&utm_content=stay_59891_jedraz&utm_term=j3d7tsgc&a_box=j3d7tsgc';
    const SLAPY = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Frabyne%2Fpenzion-janata%2F';
    const PALAVA = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fchalupa-dolni-vestonice';

    public static function boot() {
        if (function_exists('add_filter')) add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        if (function_exists('add_action')) add_action('wp_enqueue_scripts', __CLASS__.'::enqueue', 28);
    }

    private static function eligible($loop = true) {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || ($loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($post->post_content ?? null)
            && hash_equals(self::RAW_SHA256, hash('sha256', $post->post_content));
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-sport-stays-85384-v1',
            plugins_url('assets/nry-sport-stays-85384-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function affiliate($url) {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    public static function block() {
        return '<section class="nry-sport-stays" '.self::MARKER.' aria-label="Konkrétní pobyty pro rybářský výlet v Česku">'
            .'<p class="nry-sport-stays__eyebrow">Tři konkrétní možnosti ubytování</p>'
            .'<h2>Vyberte lokalitu. Pak ověřte revír a termín.</h2>'
            .'<p>Pobyt a povolenka k lovu jsou dvě různé věci. Tyto příklady slouží k výběru ubytování; '
            .'vzdálenost od vybrané vody, možnosti rybaření, aktuální cenu a dostupnost potvrďte před rezervací.</p>'
            .'<div class="nry-sport-stays__grid">'
            .'<article data-nry-sport-offer="statek-jih-cech"><p class="nry-sport-stays__place">Jižní Čechy · statek</p>'
            .'<h3>Pobyt na statku na jihu Čech</h3>'
            .'<p>Slevomat mezi aktivitami zmiňuje také rybaření. Konkrétní podmínky pobytu i lovu ověřte u poskytovatele.</p>'
            .'<a class="nry-sport-stays__cta" href="'.self::affiliate(self::SOUTH_BOHEMIA).'" target="_blank" rel="sponsored nofollow noopener">Prohlédnout pobyt na Slevomatu</a></article>'
            .'<article data-nry-sport-offer="slapy-janata"><p class="nry-sport-stays__place">Střední Čechy · Slapy</p>'
            .'<h3>Penzion Janata</h3>'
            .'<p>Ubytování u Slap. Rybaření patří mezi uváděné aktivity v okolí; povolenku ani místo lovu pobyt nezajišťuje.</p>'
            .'<a class="nry-sport-stays__cta" href="'.self::affiliate(self::SLAPY).'" target="_blank" rel="sponsored nofollow noopener">Zobrazit Penzion Janata</a></article>'
            .'<article data-nry-sport-offer="palava-vestonice"><p class="nry-sport-stays__place">Jižní Morava · Pálava</p>'
            .'<h3>Chalupa Dolní Věstonice</h3>'
            .'<p>Ubytování na Pálavě přes Hauzi. Výpravu k vodě a oprávnění k rybolovu si naplánujte samostatně.</p>'
            .'<a class="nry-sport-stays__cta" href="'.self::affiliate(self::PALAVA).'" target="_blank" rel="sponsored nofollow noopener">Zobrazit chalupu na Hauzi</a></article>'
            .'</div><p class="nry-sport-stays__more">Chcete porovnat více lokalit? '
            .'<a href="'.self::STAYS.'">Další konkrétní pobyty pro rybáře</a>.</p>'
            .'<p class="nry-sport-stays__note"><small>Partnerské odkazy · aktualizováno 18. 11. 2026. '
            .'Ceny a volné termíny se mění. Na svazové vodě ověřte rybářský lístek a povolenku; '
            .'na soukromém revíru pravidla provozovatele.</small></p></section>';
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches) === false) return null;
        return array_map(function($link) {
            return html_entity_decode($link, ENT_QUOTES, 'UTF-8');
        }, $matches[2]);
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 10000
            || hash('sha256', substr($html, 0, self::PREFIX_BYTES)) !== self::PREFIX_SHA256
            || substr_count($html, self::ANCHOR) !== 1
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::STAYS) !== false
            || strpos($html, self::SOUTH_BOHEMIA) !== false
            || strpos($html, self::SLAPY) !== false
            || strpos($html, self::PALAVA) !== false) return $html;
        $before = self::hrefs($html);
        if ($before === null || count(array_filter($before, function($u) {
            return $u === self::LEGACY_BOOKING;
        })) !== 3) return $html;

        $updated = str_replace('href="'.self::LEGACY_BOOKING.'"', 'href="'.self::STAYS.'"', $html, $replaced);
        if ($replaced !== 3) return $html;
        $updated = str_replace(self::ANCHOR, self::ANCHOR."\n".self::block(), $updated);
        $after = self::hrefs($updated);
        if ($after === null) return $html;
        $expected = array_count_values(array_map(function($href) {
            return $href === self::LEGACY_BOOKING ? self::STAYS : $href;
        }, $before));
        foreach (array(self::SOUTH_BOHEMIA, self::SLAPY, self::PALAVA, self::STAYS) as $new) {
            $expected[$new] = ($expected[$new] ?? 0) + 1;
        }
        $actual = array_count_values($after);
        ksort($expected);
        ksort($actual);
        return $actual === $expected && count($after) === count($before) + 4
            && substr_count($updated, self::MARKER) === 1
            && substr_count($updated, 'data-nry-sport-offer=') === 3
            && strpos($updated, 'href="'.self::LEGACY_BOOKING.'"') === false
            ? $updated : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Sport_Stays_Offers_85384_V1::boot();
