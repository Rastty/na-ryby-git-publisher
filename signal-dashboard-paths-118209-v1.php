<?php
/**
 * Read-only repair of dead buyer CTAs on the published signalizatory dashboard.
 * Exact page + original-URL counts are mandatory. No post/meta writes, no
 * affiliate URL manipulation and no redirects or external requests.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Signal_Dashboard_Paths_118209_V1 {
    const ID = 118209;
    const SLUG = 'signalizatory-dashboard';
    const URL = 'https://na-ryby.eu/signalizatory-dashboard/';
    const MARKER = 'data-nry-signal-decision-paths="v1"';
    const PILLAR = 'https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/';
    const START = 'https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/';
    const THREE = 'https://na-ryby.eu/kompletni-sada-31-s-priposlechem-do-3-000-kc-co-opravdu-koupit/';

    public static function boot() {
        // Legacy isolated publisher tests expose add_filter but not WordPress
        // query functions; never register an array callback in those stubs.
        if (function_exists('is_page') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 36);
        }
        if (function_exists('is_page') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 27);
        }
    }

    private static function eligible() {
        if (!function_exists('is_page') || !is_page(self::ID)
            || !function_exists('is_singular') || !is_singular('page')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_admin') || is_admin()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'page'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG;
    }

    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-signal-dashboard-paths-118209-v1',
            plugins_url('assets/nry-signal-dashboard-paths-v1.css', __FILE__),
            array(), '1.0.0');
    }

    public static function render($content) {
        if (!is_string($content) || strlen($content) < 500
            || strpos($content, self::MARKER) !== false
            || !self::eligible() || !function_exists('is_main_query')
            || !is_main_query() || !function_exists('in_the_loop') || !in_the_loop()) return $content;

        $dead = array(
            'https://na-ryby.eu/signalizator-zaberov-do-100-eur-najlepsie-modely/' => array(2, '#top-modely'),
            'https://na-ryby.eu/sada-signalizatorov-do-150-eur-co-kupit-a-comu-sa-vyhnut/' => array(1, '#srovnani'),
            'https://na-ryby.eu/signalizatory-zaberov-kompletny-sprievodca-vyberom/' => array(1, self::PILLAR),
        );
        // An editor has changed the underlying page: fail closed as a unit.
        foreach ($dead as $old => $rule) {
            if (substr_count($content, 'href="'.$old.'"') !== $rule[0]) return $content;
        }
        foreach (array('id="top-modely"', 'id="srovnani"',
            'Projít levnější modely', 'Projít doporučené sety',
            'Rychlý rozcestník', 'Jsem začátečník', 'Řeším nižší rozpočet',
            'Chci nejlepší poměr cena / výkon', 'Nevím, jestli 2+1 nebo 3+1') as $token) {
            if (substr_count($content, $token) !== 1) return $content;
        }
        $head_at = strpos($content, 'Rychlý rozcestník');
        $head_end = stripos($content, '</h2>', $head_at);
        $next_h2 = $head_end === false ? false : stripos($content, '<h2', $head_end + 5);
        $section_end = $head_end === false ? false : stripos($content, '</section>', $head_end + 5);
        if ($head_end === false || $head_end - $head_at > 160 || $next_h2 === false
            || $next_h2 - $head_end > 6500 || $section_end === false
            || $section_end > $next_h2 || $section_end - $head_end > 6000) return $content;
        $section = substr($content, $head_end + 5, $section_end - $head_end - 5);
        if (preg_match('~<a\b[^>]*\bhref\s*=~i', $section)) return $content;

        $links = array(
            array('Začínám: jak nastavit hlásič bez chyb', self::START),
            array('Mám omezený rozpočet: levnější modely', '#top-modely'),
            array('Hledám poměr cena / výkon: srovnání', '#srovnani'),
            array('Zvažuji 3+1: sady do 3 000 Kč', self::THREE),
        );
        $nav = '<nav class="nry-signal-decision-paths" '.self::MARKER
            .' aria-label="Další krok při výběru signalizátoru">'
            .'<p><strong>Vyber si cestu podle toho, co řešíš:</strong></p><ul>';
        foreach ($links as $item) {
            $nav .= '<li><a href="'.esc_url($item[1]).'">'.esc_html($item[0]).'</a></li>';
        }
        $nav .= '</ul></nav>';

        // Put the navigation after all four explanatory cards at section width,
        // not inside the narrow heading column of the flex layout.
        $after = substr_replace($content, $nav, $section_end, 0);
        foreach ($dead as $old => $rule) {
            $after = str_replace('href="'.$old.'"', 'href="'.esc_url($rule[1]).'"', $after);
        }
        return $after;
    }
}
NRY_Signal_Dashboard_Paths_118209_V1::boot();
