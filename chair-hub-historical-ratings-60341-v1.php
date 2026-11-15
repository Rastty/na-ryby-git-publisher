<?php
/**
 * Render-only cleanup of three unsupported 2023 chair ratings and an exposed
 * keyword list in the existing chair buying hub (#60341).
 *
 * The existing product links, images, prices and WordPress post are untouched.
 * Source SHA and all reviewed text spans must match; otherwise fail closed.
 * Rollback: remove the require in na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Chair_Hub_Historical_Ratings_60341_V1 {
    const ID = 60341;
    const SLUG = 'nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby';
    const URL = 'https://na-ryby.eu/nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby/';
    const SOURCE_SHA = 'e7938af98def78057779f37ad6a88a31a5ffe83ed904dfb65e5a0657eef61ef4';
    const SOURCE_BYTES = 20997;
    const MODIFIED_GMT = '2026-11-04 10:06:35';
    const SOURCE_MARKER = 'data-nry-editorial-edit="chair-hub-trust-60341-v1"';
    const MARKER = '<!-- nry-chair-hub-historical-ratings-60341-v1 -->';

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')) {
            add_filter('the_content', array(__CLASS__, 'render'), 72);
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
        return is_object($post)
            && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($post->post_content ?? null)
            && strlen($post->post_content) === self::SOURCE_BYTES
            && hash_equals(self::SOURCE_SHA, hash('sha256', $post->post_content));
    }

    private static function replacements() {
        // All old strings were observed in anonymous public WordPress REST
        // #60341 on 2026-11-15. None contains an href, SKU, image or price.
        return array(
            '<p>Na-ryby.eu je rybářský informační magazín. Rybářská křesla nevyrábíme a nejsme jejich prodejcem. Nabízíme širokou škálu modelů, které splní všechny vaše požadavky a potřeby.</p>'
                => '<p>Na-ryby.eu je informační rybářský magazín, nikoli výrobce ani prodejce křesel. Níže najdete starší příklady modelů; jejich parametry, cenu a dostupnost vždy ověřte pro aktuální variantu.</p>',
            '<p>Ponoukáme křesla s ergonomickým designem, dobře polstrovaná a vybavená opěrkami pro záda a ruce. Jsou vyrobeny z kvalitních materiálů, které jsou odolné vůči vnějším vlivům, jako je voda, slunce nebo vítr.</p>'
                => '<p>Při výběru prověřte šířku sedáku, výšku opěradla, stabilitu nohou a odolnost konkrétního potahu podle údajů výrobce. Samotná fotografie pohodlí ani životnost neprokazuje.</p>',
            '<p>Výběr křesla opřete o správné rozměry, způsob rybaření, ověřenou nosnost a skutečné potřeby. Nechte se inspirovat naší širokou nabídkou a vyberte si tu nejlepší!</p>'
                => '<p>Výběr opřete o způsob lovu, rozměry, deklarovanou nosnost a přepravní hmotnost. Porovnejte aktuální nabídky a nepřiplácejte za funkce, které u vody nevyužijete.</p>',
            '<p>Pojďme se podívat na doporučené židličky:</p>'
                => '<p><strong>Historické příklady k porovnání konstrukcí:</strong> označení níže nejsou aktuálním žebříčkem ani výsledkem osobního testování.</p>',
            '<h2>Mivardi Křeslo Comfort Quattro &#8211; Doporučujeme &#8211; top poměr cena/výkon ⭐⭐⭐⭐⭐</h2>'
                => '<h2>Mivardi Comfort Quattro: co porovnat u konkrétní varianty</h2>',
            '<h2>Mivardi Křeslo Premium &#8211; ekonomická volba ⭐⭐⭐⭐</h2>'
                => '<h2>Mivardi Premium: konstrukce a přeprava</h2>',
            '<h2>Fox Sedačka Super Deluxe Recliner Highback Chair &#8211; nejlepší křeslo na trhu ⭐⭐⭐⭐⭐</h2>'
                => '<h2>Fox Super Deluxe Recliner Highback: polohování a stabilita</h2>',
            '<p>Toto křeslo nabízí maximální komfort a pohodlí. Sedadlo je vyrobeno z kvalitního materiálu, který je velmi příjemný na dotek. Zádová opěrka a područky jsou nastavitelné, takže si můžete sedátko přizpůsobit podle vašich potřeb. Díky robustní konstrukci je zajištěn dlouhodobý provoz bez problémů. Celkově se jedná o vynikající produkt, který vám poskytne relaxaci a pohodlí během vašich outdoorových aktivit.</p>'
                => '<p>U modelu Comfort Quattro ověřte u konkrétní generace rozměry sedáku, možnosti nastavení, výrobcem deklarovanou nosnost a dostupnost náhradních dílů. Starší popis není důkaz dnešních parametrů ani životnosti.</p>',
            '<p>Toto křeslo je skvělou volbou pro všechny outdoorové nadšence. Díky ergonomickému designu a kvalitnímu polstrování je zajištěno vysoké pohodlí při posedávání. Křeslo je vyrobeno z odolného materiálu, který je odolný vůči povětrnostním podmínkám. Jeho skladná konstrukce usnadňuje přenášení a uskladnění. Celkově se jedná o vynikající volbu pro ty, kteří hledají pohodlné a kvalitní křeslo pro své outdoorové aktivity.</p>'
                => '<p>U modelu Premium porovnejte přesnou hmotnost, rozměry po složení, pevnost aretace nohou, výšku sedáku a ověřenou nosnost. Údaj o odolnosti proti počasí hledejte v dokumentaci aktuální varianty.</p>',
            '<p>Tato luxusní sedačka je vybavena všemi funkcemi, které si můžete přát. Je vyrobena z vysoce kvalitních materiálů, které jsou odolné vůči opotřebení a povětrnostním podmínkám. Sedadlo lze snadno nastavit do různých poloh, včetně polohy na lehátko. Ergonomický design zajišťuje maximální pohodlí a podporu páteře. Tato sedačka je ideální volbou pro ty, kteří si chtějí užít dokonalý komfort a relaxaci během svých outdoorových aktivit.</p>'
                => '<p>U polohovacího křesla Fox prověřte přesný rozsah nastavení opěradla, stabilitu v každé povolené poloze, zámky konstrukce, transportní rozměry a nosnost. Samotný název Highback nebo Deluxe nedokazuje konkrétní funkce.</p>',
        );
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',
            $html, $matches) === false) return null;
        return $matches[2];
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 15000
            || substr_count($html, self::SOURCE_MARKER) !== 1
            || strpos($html, self::MARKER) !== false) return $html;
        $before = self::hrefs($html);
        if ($before === null) return $html;
        $updated = $html;
        foreach (self::replacements() as $old => $new) {
            if (substr_count($updated, $old) !== 1) return $html;
            $updated = str_replace($old, $new, $updated);
        }

        // Editorial keyword list is for SEO tooling, never for readers.
        $keywords = '~<p><strong>klíčová slova:</strong>[^<]{20,400}</p>~u';
        if (preg_match_all($keywords, $updated, $hits) !== 1) return $html;
        $updated = preg_replace($keywords, '', $updated, 1, $count);
        if ($count !== 1) return $html;

        // Exactly three star-only ratings and three unlabeled review sections.
        // Never touch any merchant links or schema outside this article.
        $ratings = '~<p>\s*<strong>Hodnocení:(?:\s|&nbsp;|&#160;|\x{00A0})*</strong>\s*⭐{4,5}\s*</p>~u';
        if (preg_match_all($ratings, $updated, $hits) !== 3) return $html;
        $updated = preg_replace($ratings,
            '<p><small>Historická zmínka bez ověřeného bodového hodnocení; aktuální parametry a dostupnost ověřte u prodejce.</small></p>',
            $updated, -1, $count);
        if ($count !== 3 || substr_count($updated, '<h3>Hodnocení:</h3>') !== 3)
            return $html;
        $updated = str_replace('<h3>Hodnocení:</h3>',
            '<h3>Co ověřit před koupí</h3>', $updated);

        if (strpos($updated, '⭐') !== false
            || strpos($updated, 'klíčová slova:') !== false
            || self::hrefs($updated) !== $before) return $html;
        return self::MARKER . $updated;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Chair_Hub_Historical_Ratings_60341_V1::boot();
