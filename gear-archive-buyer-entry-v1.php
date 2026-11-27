<?php
/**
 * Six existing high-intent equipment archives -> owned buyer decision guides.
 * Render-only, exact current category and canonical URL on first archive page.
 * Reuses the source-guarded H1/loop_start pattern of revir-archive-decision-entry.
 * No WordPress DB/term writes, redirects, merchant URLs or affiliate rewrites.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Gear_Archive_Buyer_Entry_V1 {
    const MARKER = 'data-nry-gear-archive="v1"';
    private static $printed = false;

    public static function routes() {
        return array(
                        'pruty' => array(
                'title' => 'Kaprové pruty',
                'url' => 'https://na-ryby.eu/category/vybaveni-pro-rybare/pruty/',
                'headline' => 'Kaprový prut vybírejte podle vody, délky a rozpočtu',
                'intro' => 'Nejdřív si ujasněte způsob lovu a transport, potom délku, testovací křivku a rozpočet. Dřívější pořadí modelů a ceny nejsou dnešní skladová nabídka.',
                'links' => array(
                    array('carp_rod_guide', 'Jak vybrat kaprový prut', 'Od prvního prutu po delší odhozy', 'https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'),
                    array('carp_rod_budget', 'Kaprový prut do 3 000 Kč', 'Srovnání kompromisů v běžném rozpočtu', 'https://na-ryby.eu/nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber/'),
                    array('carp_rod_youth', 'Prut pro dítě nebo juniora', 'Délka, hmotnost a pohodlné ovládání', 'https://na-ryby.eu/kaprove-pruty-pro-deti-a-mladez-jak-vybrat-spravne/'),
                ),
            ),
            'signalizatory' => array(
                'title' => 'Signalizátory',
                'url' => 'https://na-ryby.eu/category/vybaveni-pro-rybare/signalizatory/',
                'headline' => 'Signalizátor vybírejte podle lovu a příposlechu',
                'intro' => 'Jinou sadu potřebujete na krátkou položenou a jinou na velkou přehradu. Zvažte dosah v terénu, falešné záběry, napájení a ovládání; historické nabídky nemusí platit.',
                'links' => array(
                    array('signal_guide', 'Jak vybrat hlásiče záběru', 'Základní funkce a sady podle použití', 'https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'),
                    array('signal_budget', 'Sada 3+1 do 3 000 Kč', 'Co kontrolovat u cenově dostupného příposlechu', 'https://na-ryby.eu/kompletni-sada-31-s-priposlechem-do-3-000-kc-co-opravdu-koupit/'),
                    array('signal_range', 'Signalizátory na velké vody', 'Dosah příposlechu, překážky a spolehlivost', 'https://na-ryby.eu/jak-vybrat-signalizator-pro-lov-na-velkych-prehradach-a-rekach/'),
                ),
            ),
            'navijaky' => array(
                'title' => 'Navijáky',
                'url' => 'https://na-ryby.eu/category/vybaveni-pro-rybare/navijaky/',
                'headline' => 'Nejdřív vyberte správný naviják, potom čtěte recenze',
                'intro' => 'Výběr začíná typem lovu, velikostí cívky a brzdou. V archivu je mnoho starších článků; níže jsou tři přímé návody podle vašeho záměru.',
                'links' => array(
                    array('reel_guide', 'Výběr navijáku od začátku', 'Velikost, převod, brzda a správná sestava', 'https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/'),
                    array('feeder_reel', 'Naviják na feeder', 'Jak porovnat feederové modely', 'https://na-ryby.eu/nejlepsi-feederove-navijaky-srovnani/'),
                    array('spinning_reel', 'Naviják na přívlač', 'Na co se soustředit u lehké i těžší přívlače', 'https://na-ryby.eu/nejlepsi-privlacove-navijaky-jak-vybrat/'),
                ),
            ),
            'echoloty-na-lod' => array(
                'title' => 'Echoloty a sonary',
                'url' => 'https://na-ryby.eu/category/vybaveni-pro-rybare/echoloty-na-lod/',
                'headline' => 'Echolot vyberte podle vody a způsobu lovu',
                'intro' => 'Nenechte se zahltit starými modely. Nejdřív vyberte technologii, ověřte rozpočet a naučte se číst obraz. Konkrétní modely, balíčky a dostupnost se mění.',
                'links' => array(
                    array('sonar_guide', 'Jak vybrat echolot', 'Sonda, obraz, GPS a kompatibilita', 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'),
                    array('sonar_budget', 'Echolot do 15 000 Kč', 'Co v dané cenové hladině reálně uvidíte', 'https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/'),
                    array('sonar_setup', 'Echolot už mám', 'Praktické nastavení a vyhodnocení obrazu', 'https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/'),
                ),
            ),
            'bivaky' => array(
                'title' => 'Bivaky',
                'url' => 'https://na-ryby.eu/category/vybaveni-pro-rybare/bivaky/',
                'headline' => 'Vyberte bivak podle výpravy a počtu lidí',
                'intro' => 'Na jednu noc, víkend nebo delší pobyt potřebujete rozdílný prostor, konstrukci a ochranu proti počasí. V recenzích mohou být historické ceny a nabídky.',
                'links' => array(
                    array('bivak_guide', 'Jak vybrat rybářský bivak', 'Materiály, velikost, stavba a odolnost', 'https://na-ryby.eu/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'),
                    array('fishing_stays', 'Raději chatu u vody?', 'Ubytování, odkud můžete plánovat rybaření', 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/'),
                ),
            ),
            'zavazeci-lodky' => array(
                'title' => 'Zavážecí loďky',
                'url' => 'https://na-ryby.eu/category/vybaveni-pro-rybare/zavazeci-lodky/',
                'headline' => 'Zavážecí loďka: GPS, dosah a sonar rozhodují',
                'intro' => 'Zavážecí nebo zakrmovací loďka je dálkově ovládaný prostředek pro montáž a krmení, nikoli nafukovací člun pro přepravu osob. Starší texty v archivu tyto typy mohou zaměňovat.',
                'links' => array(
                    array('baitboat_choice', 'Vybrat zavážecí loďku', 'Praktický výběr podle dosahu, GPS a rozpočtu', 'https://na-ryby.eu/zavazeci-lodky-pro-rybare/'),
                    array('baitboat_sonar', 'Potřebuji sonar?', 'Jak porovnat zobrazovací technologie', 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'),
                ),
            ),
        );
    }

    private static function config() {
        foreach (array('is_category','is_admin','is_feed','is_paged',
            'get_queried_object','get_term_link','home_url') as $fn) {
            if (!function_exists($fn)) return null;
        }
        if (is_admin() || is_feed() || is_paged()
            || (function_exists('is_preview') && is_preview())
            || (function_exists('is_customize_preview') && is_customize_preview())
            || (function_exists('wp_doing_ajax') && wp_doing_ajax())
            || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return null;
        $term = get_queried_object();
        if (!is_object($term) || ($term->taxonomy ?? '') !== 'category'
            || !is_string($term->slug ?? null)
            || (int)($term->term_id ?? 0) <= 0
            || (int)($term->parent ?? 0) <= 0) return null;
        $all = self::routes();
        $slug = $term->slug;
        if (!isset($all[$slug]) || !is_category($slug)
            || get_term_link($term) !== $all[$slug]['url']) return null;
        return array('slug' => $slug) + $all[$slug];
    }

    public static function block($cfg) {
        if (!is_array($cfg) || !isset($cfg['slug'], $cfg['headline'], $cfg['intro'], $cfg['links'])) return '';
        $out = '<section class="nry-gear-archive-entry" '.self::MARKER
            .' data-nry-gear-category="'.$cfg['slug'].'"'
            .' aria-label="Rychlá cesta k výběru rybářského vybavení">'
            .'<p class="nry-gear-archive-entry__eyebrow">Praktický výběr před recenzemi</p>'
            .'<h2>'.$cfg['headline'].'</h2>'
            .'<p>'.$cfg['intro'].'</p>'
            .'<nav aria-label="Vybrat návod podle potřeby">';
        foreach ($cfg['links'] as $link) {
            $out .= '<a class="nry-gear-archive-entry__choice" data-nry-gear-guide="'.$link[0].'"'
                .' href="'.$link[3].'"><strong>'.$link[1].'</strong>'
                .'<span>'.$link[2].'</span></a>';
        }
        return $out.'</nav><p class="nry-gear-archive-entry__note">'
            .'Dřívější recenze a prodejní nabídky nemusí být aktuální. '
            .'Před nákupem ověřte variantu, cenu, dostupnost a podmínky u prodejce.</p>'
            .'</section>';
    }

    /**
     * Newspaper prints part of the archive cards before loop_start. Relocate
     * only the exact block after one reviewed archive H1, preserving all other
     * frontend bytes and original term description. Editor/H1 drift wins.
     */
    public static function place_after_archive_h1($html) {
        $cfg = self::config();
        if (!$cfg || !is_string($html) || strlen($html) < 5000
            || substr_count($html, self::MARKER) !== 1) return $html;
        $block = self::block($cfg);
        if ($block === '' || substr_count($html, $block) !== 1) return $html;
        if (preg_match_all('~<h1\b[^>]{0,800}>([\s\S]{1,400}?)</h1>~u', $html,
                $matches, PREG_OFFSET_CAPTURE) !== 1
            || !isset($matches[0][0][0],$matches[0][0][1],$matches[1][0][0])) return $html;
        $rawTitle = trim($matches[1][0][0]);
        $span = array();
        if (preg_match('~\A<span>([^<>]{1,300})</span>\z~u', $rawTitle, $span)) {
            $rawTitle = $span[1];
        }
        if (strpos($rawTitle, '<') !== false || strpos($rawTitle, '>') !== false) return $html;
        $decoded = html_entity_decode($rawTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = preg_replace('~[\s\x{00A0}\x{202F}]+~u', ' ', $decoded);
        if (!is_string($title) || trim($title) !== $cfg['title']) return $html;
        $heading = $matches[0][0][0];
        $end = $matches[0][0][1] + strlen($heading);
        $blockAt = strpos($html, $block);
        if ($blockAt === false || $blockAt <= $end) return $html;
        $without = substr_replace($html, '', $blockAt, strlen($block));
        $changed = substr($without, 0, $end).$block.substr($without, $end);
        return strlen($changed) === strlen($html)
            && substr_count($changed, self::MARKER) === 1
            && strpos($changed, $heading.$block) !== false
            ? $changed : $html;
    }

    public static function start_buffer() {
        if (self::config() && function_exists('ob_start')) {
            ob_start(array(__CLASS__, 'place_after_archive_h1'));
        }
    }

    public static function render_on_main_loop($query) {
        if (self::$printed || !is_object($query)
            || !method_exists($query, 'is_main_query') || !$query->is_main_query()) return;
        $cfg = self::config();
        if (!$cfg) return;
        self::$printed = true;
        echo self::block($cfg);
    }

    public static function enqueue() {
        if (!self::config() || !function_exists('plugins_url')) return;
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style('nry-gear-archive-entry-v1',
                plugins_url('assets/nry-gear-archive-entry-v1.css', __FILE__),
                array(), '1.0.0');
        }
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script('nry-gear-archive-click-v1',
                plugins_url('assets/nry-gear-archive-click-v1.js', __FILE__),
                array(), '1.1.0', true);
        }
    }

    public static function boot() {
        if (function_exists('add_action')) {
            add_action('template_redirect', array(__CLASS__, 'start_buffer'), 1);
            add_action('loop_start', array(__CLASS__, 'render_on_main_loop'), 8, 1);
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 25);
        }
    }
}
NRY_Gear_Archive_Buyer_Entry_V1::boot();
