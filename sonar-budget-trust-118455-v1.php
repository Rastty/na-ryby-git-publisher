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
            // The original post's FAQPage JSON-LD is appended by a later
            // content filter; only repair that exact schema after it exists.
            add_filter('the_content', array(__CLASS__, 'render_faq_schema'), 999999);
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
                => 'Jednoduchý začátek pro rybáře, kteří potřebují hlavně hloubku a orientaci pod lodí. U konkrétní nabídky zkontrolujte cenu, vhodnou sondu a obsah balení.',
            // 2026-11-10: independent public buyer journey confirmed four
            // additional time-sensitive assertions below the first-screen
            // corrections. Preserve all original merchant hrefs and prices.
            'Jenže v rámci dvou preferovaných obchodů, se kterými v článku pracuju, mi v danou chvíli vychází lépe reálně dostupné nabídky Lowrance. Proto jsem článek postavil na nich – ne kvůli shopu, ale kvůli aktuální nabídce pro čtenáře.'
                => 'Modely Lowrance v tomto starším přehledu slouží jako příklady různých sestav, nikoli jako dnes nezávisle ověřená nejvýhodnější nabídka. Před nákupem porovnejte také vhodné varianty Garmin podle sondy, funkcí a konečné ceny.',
            'Za mě jsou dnes v rámci dvou preferovaných obchodů nejlepší tři scénáře tyto:'
                => 'Tři praktické příklady podle způsobu použití (nikoli ověřené dnešní pořadí podle ceny a dostupnosti):',
            'Ano jako obecně známý referenční model ano. V tomto článku ale hlavní doporučení stavím jen na aktuálně dohledatelných nabídkách z OKfish a Chyť a pusť.'
                => 'Garmin Striker Vivid může být jednou z možností. Porovnejte přesnou variantu, kompatibilní sondu a celkovou cenu; historické odkazy zde nedokládají nejlepší dnešní nabídku.',
            'bonus baterie a nabíječka'
                => 'příslušenství v balení ověřte u prodejce',
            // Exact live public 2026-11-10 buyer-page winner claims without
            // a current SKU/price comparison; correct copy, NEVER hrefs.
            'Nejlepší rozumná volba'
                => 'Příklad sestavy s mapováním',
            'Lowrance Hook Reveal 5, pokud chceš vyvážený sonar s mapováním a poctivým čtením dna.'
                => 'Lowrance Hook Reveal 5 je starší příklad sestavy s mapováním. Bez ověřených dnešních cen a variant nejde o univerzálního vítěze.',
            'Nejlepší budget'
                => 'Příklad jednoduchého základu',
            'Nejlepší volba pro toho, kdo nechce za rok litovat, že šel do malého displeje.'
                => 'Větší displej může zlepšit čitelnost. Zvažte však i sondu, celkovou cenu, montáž a napájení.',
            'Kdybych měl vybrat jen jeden model pro většinu lidí, beru Hook Reveal 5. Není to nejlevnější ani největší, ale jako celek mi dává nejrozumnější smysl. Pokud ale víš, že jsi na displej citlivý a mžourání tě u vody štve, pak bych se nebál připlatit za Eagle 7.'
                => 'Jeden univerzálně nejlepší model bez ověření aktuálních sestav a cen určit nelze. Pro mapování porovnejte GPS, kompatibilní sondu a funkce; pro čitelnost velikost displeje, montáž, napájení a konečnou cenu.'
        );
    }

    /**
     * Two reviewed legacy paragraphs can have harmless inline formatting or
     * HTML entities that defeat byte-exact text matching. Require ONE whole
     * paragraph with text equal to the reviewed old claim after whitespace
     * normalization. Do not touch hrefs, nested blocks or editor-rewritten text.
     * Return null if there is no unique unambiguous candidate.
     */
    // Source-proven 2026-11-10 public + REST: FAQPage in the_content (not
    // Yoast), mainEntity[].acceptedAnswer.text carries the full OLD sentence.
    // This is the same approved answer visible above on the article.
    const FAQ_JSONLD_OLD = 'Ano jako obecně známý referenční model ano. V tomto článku ale hlavní doporučení stavím jen na aktuálně dohledatelných nabídkách z OKfish a Chyť a pusť.';
    const FAQ_JSONLD_SAFE = 'Garmin Striker Vivid může být jednou z možností. Porovnejte přesnou variantu, kompatibilní sondu a celkovou cenu; historické odkazy zde nedokládají nejlepší dnešní nabídku.';

    /**
     * This JSON-LD is injected AFTER the earlier 32-priority editorial pass.
     * Locate one exact FAQPage script and one Question/Answer path only.
     * Patch strictly the old answer string in the ORIGINAL script bytes;
     * refuse invalid/ambiguous JSON or any other schema shape.
     */
    public static function render_faq_schema($content) {
        if (!is_string($content)
            || strpos($content, self::MARKER) === false
            || !self::eligible()
            || substr_count($content, self::FAQ_JSONLD_OLD) !== 1
            || !preg_match_all('~<script\b[^>]*>.*?</script\s*>~is', $content, $scripts,
                PREG_OFFSET_CAPTURE)) return $content;

        $edits = array();
        foreach ($scripts[0] as $hit) {
            $full = $hit[0];
            if (strpos($full, self::FAQ_JSONLD_OLD) === false) continue;
            if (!preg_match('~^<script\b([^>]*)>(.*?)</script\s*>$~is', $full, $parts)
                || !preg_match('~\btype\s*=\s*(["\x27])application/ld\+json\1~i', $parts[1])) {
                return $content;
            }
            $json = $parts[2];
            if (substr_count($json, self::FAQ_JSONLD_OLD) !== 1) return $content;
            $data = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)
                || ($data['@type'] ?? null) !== 'FAQPage'
                || !isset($data['mainEntity']) || !is_array($data['mainEntity'])) return $content;
            $expected = $data;
            $matched = 0;
            foreach ($data['mainEntity'] as $i => $question) {
                if (!is_array($question)
                    || ($question['@type'] ?? null) !== 'Question'
                    || !is_array($question['acceptedAnswer'] ?? null)
                    || ($question['acceptedAnswer']['@type'] ?? null) !== 'Answer'
                    || !is_string($question['acceptedAnswer']['text'] ?? null)
                    || strpos($question['acceptedAnswer']['text'], self::FAQ_JSONLD_OLD) === false) continue;
                if (substr_count($question['acceptedAnswer']['text'], self::FAQ_JSONLD_OLD) !== 1)
                    return $content;
                $expected['mainEntity'][$i]['acceptedAnswer']['text'] =
                    str_replace(self::FAQ_JSONLD_OLD, self::FAQ_JSONLD_SAFE,
                        $question['acceptedAnswer']['text']);
                ++$matched;
            }
            if ($matched !== 1) return $content;
            $updated_json = str_replace(self::FAQ_JSONLD_OLD, self::FAQ_JSONLD_SAFE, $json);
            $updated_data = json_decode($updated_json, true);
            if (json_last_error() !== JSON_ERROR_NONE || $updated_data !== $expected) return $content;
            $updated_script = str_replace($json, $updated_json, $full);
            if ($updated_script === $full) return $content;
            $edits[] = array('offset'=>$hit[1], 'old'=>$full, 'new'=>$updated_script);
        }
        if (count($edits) !== 1) return $content;
        $edit = $edits[0];
        return substr_replace($content, $edit['new'], $edit['offset'], strlen($edit['old']));
    }

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
            // Only source-reviewed formatted paragraphs (#3, #5, FAQ #9,
            // and the long final verdict #15) allow exact paragraph fallback.
            $position = array_search($old, array_keys(self::corrections()), true);
            if (!in_array($position, array(2, 4, 8, 14), true)) continue;
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
