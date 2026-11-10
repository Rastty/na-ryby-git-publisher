<?php
/**
 * Render-only correction of stale "current offer" assertions and an exposed
 * editorial brief on one existing sonar buyer guide. No database or URL edits.
 * Removal: delete this require to restore unmodified WordPress output.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sonar_Budget_Trust_118455_V1 {
    const ID = 118455;
    const SLUG = 'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz';
    const URL = 'https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/';
    const MARKER = '<!-- nry-sonar-budget-trust-118455-v1 -->';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')) {
            add_filter('the_content', array(__CLASS__, 'render'), 32);
        }
    }

    private static function eligible() {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_main_query') || !is_main_query()
            || !function_exists('in_the_loop') || !in_the_loop()
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink')
            || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return $post && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG;
    }

    private static function corrections() {
        // Each exact string is grounded in the public 2026-11-10 rendered
        // buyer article. A moved/edited span is left untouched.
        return array(
            'Vybírám jen mezi dvěma mými preferovanými a důvěryhodnými obchody – OKfish a Chyť a pusť – a hlavní doporučení stavím na tom, kde aktuálně dává větší smysl nabídka pro čtenáře.'
                => 'Srovnání níže obsahuje starší příklady modelů a obchodů, nikoli dnes ověřenou nejlepší cenu nebo dostupnost. Před nákupem porovnejte přesnou variantu, sondu a cenu celé sestavy.',
            'Zadání sice zmiňuje Garmin Striker Vivid a Lowrance Hook Reveal 5, ale při přípravě článku jsem v rámci dvou preferovaných obchodů – OKfish a Chyť a pusť – našel jako nejrelevantnější a aktuálně nejlépe uchopitelné nabídky hlavně modely Lowrance. Garmin Striker Vivid beru v textu jako známý referenční benchmark téhle cenové třídy, ale hlavní doporučení stavím jen na tom, co je v těchto dvou shopech reálně dohledatelné a smysluplné pro čtenáře.'
                => 'Garmin Striker Vivid a Lowrance Hook Reveal 5 tu slouží jako příklady různých sestav této cenové třídy. Samotný starší přehled neprokazuje současnou nabídku: rozhodují funkce přesné varianty, kompatibilní sonda, montáž a cena celé sestavy.',
            'V tomhle případě vybírám jako hlavní odkaz lepší aktuální nabídku ze dvou mých preferovaných obchodů – OKfish a Chyť a pusť. Za nejvyváženější volbu mi teď vychází Lowrance Hook Reveal 5 z OKfish, hlavně kvůli poměru funkcí a ceny.'
                => 'Lowrance Hook Reveal 5 je zde starší příklad sestavy pro čtení dna a mapování. Odkaz níže nedokládá nejlepší současnou cenu ani sklad; ověřte konkrétní variantu a obsah balení.',
            'Tady vybírám jako hlavní odkaz lepší aktuální nabídku z OKfish a Chyť a pusť. Hook Reveal 5 je v této chvíli z těchto dvou obchodů nejzajímavější hlavně proto, že za rozumné peníze nabízí FishReveal i mapování Genesis Live.'
                => 'U vybraných variant Hook Reveal 5 stojí za prověření FishReveal a mapování Genesis Live. Dostupnost funkcí, kompatibilní sondy a aktuální obchodní nabídku ověřte podle přesného označení sestavy.',
            'Pokud chceš vytěžit maximum z limitu do 15 tisíc, tady už dává větší smysl připlatit hlavně za displej. Chyť a pusť tu má rozumnou aktuální nabídku včetně baterie a nabíječky.'
                => 'Větší displej může zlepšit čitelnost, ale hlídejte cenu celé sestavy. Obsah konkrétního balení včetně baterie, nabíječky a sondy ověřte v současné nabídce prodejce.',
            'Jednoduchý začátek pro člověka, který potřebuje hlavně hloubku, přehled a základní orientaci pod lodí. Oba obchody patří mezi moje ověřené top volby, ale tady aktuálně dává větší smysl nabídka z Chyť a pusť.'
                => 'Jednoduchý začátek pro rybáře, kteří potřebují hlavně hloubku a orientaci pod lodí. U konkrétní nabídky zkontrolujte cenu, vhodnou sondu a obsah balení.'
        );
    }

    /**
     * Two reviewed legacy paragraphs can have harmless inline formatting or
     * HTML entities that defeat byte-exact text matching. Require ONE whole
     * paragraph with text equal to the reviewed old claim after whitespace
     * normalization. Do not touch hrefs, nested blocks or editor-rewritten text.
     * Return null if there is no unique unambiguous candidate.
     */
    private static function guarded_paragraph_replacement($content, $old, $replacement) {
        if (!preg_match_all('~<p\b[^>]*>(.*?)</p>~isu', $content, $found,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) return null;

        $matches = array();
        $normalise = function($text) {
            $decoded = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            return trim(preg_replace('/[\s\x{00A0}\x{202F}]+/u', ' ', $decoded));
        };
        $expected = $normalise($old);
        foreach ($found as $match) {
            $body = $match[1][0];
            if ($normalise($body) !== $expected) continue;
            // Old sentences contain no links, images, shortcode or nested
            // block content. An editorial change introducing any is protected.
            if (preg_match('~<\s*/?\s*([a-z][a-z0-9]*)\b~i', $body, $tags)) {
                preg_match_all('~<\s*/?\s*([a-z][a-z0-9]*)\b~i', $body, $all_tags);
                foreach ($all_tags[1] as $tag) {
                    if (!in_array(strtolower($tag),
                        array('strong','b','em','i','span','u','small','br'), true)) {
                        continue 2;
                    }
                }
            }
            $matches[] = $match;
        }
        if (count($matches) !== 1) return null;
        $match = $matches[0];
        $full = $match[0][0];
        $body = $match[1][0];
        $start = strpos($full, $body);
        if ($start === false) return null;
        $updated = substr($full, 0, $start)
            . htmlspecialchars($replacement, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . substr($full, $start + strlen($body));
        return substr_replace($content, $updated, $match[0][1], strlen($full));
    }

    public static function render($content) {
        if (!is_string($content) || strlen($content) < 300
            || strpos($content, self::MARKER) !== false
            || !self::eligible()) return $content;

        $updated = $content;
        $changes = 0;
        foreach (self::corrections() as $index => $replacement) {
            // Respect editor changes; never replace an ambiguous occurrence.
            // Each key is the exact pre-reviewed legacy statement.
            $old = $index;
            if (substr_count($updated, $old) === 1) {
                $updated = str_replace($old, $replacement, $updated);
                ++$changes;
                continue;
            }
            // Only the two demonstrated non-matching paragraphs (#3 and #5)
            // are eligible for the safe whole-paragraph fallback.
            $position = array_search($old, array_keys(self::corrections()), true);
            if (!in_array($position, array(2, 4), true)) continue;
            $candidate = self::guarded_paragraph_replacement($updated, $old, $replacement);
            if ($candidate === null) continue;
            $updated = $candidate;
            ++$changes;
        }
        if ($changes === 0) return $content;
        // No link/href, scripts, prices, headings, tracking or WP post fields
        // are touched. This comment is a deterministic public readback marker.
        return self::MARKER . $updated;
    }
}
NRY_Sonar_Budget_Trust_118455_V1::boot();
