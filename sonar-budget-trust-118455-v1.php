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

    public static function render($content) {
        if (!is_string($content) || strlen($content) < 300
            || strpos($content, self::MARKER) !== false
            || !self::eligible()) return $content;

        $updated = $content;
        $changes = 0;
        foreach (self::corrections() as $old => $replacement) {
            // Respect editor changes; never replace an ambiguous occurrence.
            if (substr_count($updated, $old) !== 1) continue;
            $updated = str_replace($old, $replacement, $updated);
            ++$changes;
        }
        if ($changes === 0) return $content;
        // No link/href, scripts, prices, headings, tracking or WP post fields
        // are touched. This comment is a deterministic public readback marker.
        return self::MARKER . $updated;
    }
}
NRY_Sonar_Budget_Trust_118455_V1::boot();
