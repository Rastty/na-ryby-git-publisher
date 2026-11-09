<?php
/**
 * Render-only navigation repair for exact published reel dashboard page #118015.
 * Fail closed if an editor supplies links or changes the source section.
 * No database writes, merchants, user tracking, redirects or AJAX.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Reel_Dashboard_Nextsteps_V1 {
    const ID = 118015;
    const SLUG = 'navijaky-dashboard';
    const PERMALINK = 'https://na-ryby.eu/navijaky-dashboard/';
    const MARKER = 'data-nry-reel-nextsteps="v1"';

    public static function boot() {
        // Isolated PHP publisher tests do not implement the full WordPress hook API.
        if (function_exists('is_page') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 35);
        }
        if (function_exists('is_page') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 26);
        }
    }

    public static function enqueue() {
        if (!function_exists('is_page') || !is_page(self::ID)
            || !function_exists('is_singular') || !is_singular('page')
            || !function_exists('get_queried_object_id')
            || !function_exists('get_permalink') || !function_exists('home_url')
            || !function_exists('wp_enqueue_script') || !function_exists('plugins_url'))
            return;
        $id = (int) get_queried_object_id();
        if ($id !== self::ID || get_permalink($id) !== self::PERMALINK
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return;
        wp_enqueue_script(
            'nry-reel-guide-handoff-v1',
            plugins_url('assets/nry-reel-handoff-v1.js', __FILE__),
            array(), '1.0.0', true
        );
    }

    public static function render($content) {
        if (!is_string($content) || strlen($content) < 300 || strpos($content, self::MARKER) !== false
            || !function_exists('is_page') || !is_page(self::ID)
            || is_admin() || !is_main_query() || !in_the_loop()
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ID) !== self::PERMALINK) return $content;
        $post = get_post(self::ID);
        if (!$post || (int)$post->ID !== self::ID || $post->post_type !== 'page'
            || $post->post_status !== 'publish' || $post->post_name !== self::SLUG) return $content;

        $heading = strpos($content, 'Pokračuj dál');
        if ($heading === false || substr_count($content, 'Pokračuj dál') !== 1) return $content;
        $end_heading = strpos($content, '</h2>', $heading);
        if ($end_heading === false || $end_heading - $heading > 160) return $content;
        $insert_at = $end_heading + strlen('</h2>');
        $next_h2 = stripos($content, '<h2', $insert_at);
        if ($next_h2 === false) return $content;
        $section = substr($content, $insert_at, $next_h2 - $insert_at);
        foreach (array('Jak vybrat naviják', 'Nejlepší přívlačové navijáky',
            'Nejlepší feederové navijáky', 'Údržba a servis navijáku') as $required) {
            if (strpos($section, $required) === false) return $content;
        }
        if (preg_match('~<a\b[^>]*\bhref\s*=~i', $section)) return $content;

        $links = array(
            array('Jak vybrat naviják: velikost, brzda a použití',
                'https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/'),
            array('Nejlepší feederové navijáky: praktické srovnání',
                'https://na-ryby.eu/nejlepsi-feederove-navijaky-srovnani/'),
            array('Jak pečovat o naviják po sezóně',
                'https://na-ryby.eu/pece-o-navijak-po-sezone-jak-ho-neznicit-spatnym-mazanim/'),
            array('Rybářský poradce: doporučení podle stylu lovu',
                'https://na-ryby.eu/poradce-vybavy/'),
        );
        $nav = '<nav class="nry-reel-nextsteps" '.self::MARKER
            .' aria-label="Další kroky pro výběr navijáku">'
            .'<p><strong>Vyberte další krok podle toho, co řešíte:</strong></p><ul>';
        foreach ($links as $link) {
            $nav .= '<li><a href="'.esc_url($link[1]).'">'.esc_html($link[0]).'</a></li>';
        }
        $nav .= '</ul></nav>';
        return substr($content, 0, $insert_at) . $nav . substr($content, $insert_at);
    }
}
NRY_Reel_Dashboard_Nextsteps_V1::boot();
