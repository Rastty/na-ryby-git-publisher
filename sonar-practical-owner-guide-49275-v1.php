<?php
/**
 * Read-only, exact-source owner-first sonar advice on post #49275.
 * Rendered HTML only; no post update, no merchant links changed, no telemetry.
 * Remove one plugin require_once to roll back immediately.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sonar_Practical_Owner_49275_V1 {
    const ID = 49275;
    const SLUG = 'jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun';
    const URL = 'https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/';
    const SOURCE_SHA = '4c57249b88928376dc24a41885b5e9397069e5128d1c462e0fc4b50f1c560330';
    const SOURCE_BYTES = 12027;
    const SOURCE_GMT = '2026-10-16 11:38:12';
    const MARKER = 'data-nry-sonar-owner-guide="49275-20261110-v1"';
    const PILLAR = 'https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';
    const BUDGET = 'https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/';

    private static function corrections() {
        return array(
            array(
                'pro plavce, kteří potřebují mít přehled o hladině vody a stavu dna',
                'pro rybáře, kteří potřebují sledovat hloubku a strukturu dna'
            ),
            array(
                'Tato funkce vám umožní získat více detailních informací o okolí a pomůže vám přesně nalézt potenciální rybí loviště.',
                'Užší svazek může zvýraznit detaily v menší oblasti pod sondou, ale sám o sobě nezaručí nalezení ryb. Vhodný režim závisí na hloubce, cíli a použité sondě.'
            ),
            array(
                'Pravidelná kalibrace vašeho echolotu nebo sonaru pomáhá udržovat vysokou účinnost a přesnost.',
                'Před vyplutím zkontrolujte uchycení a úhel sondy, kabeláž a napájení.'
            ),
            array(
                'Je důležité postupovat podle pokynů výrobce pro kalibraci, například před každým rybolovem či v průběhu tůry.',
                'Kalibraci provádějte pouze tehdy, když ji konkrétní senzor nebo funkce vyžaduje, a vždy podle návodu výrobce.'
            ),
            array(
                'Používání echolotu a sonaru může být užitečné nejen pro rybáře, ale i pro plavce, kteří chtějí mít kompletní přehled o své okolí a chtějí bezpečně plout po hladině.',
                'Echolot pomáhá číst hloubku a podvodní struktury, ale nenahrazuje bezpečnou navigaci ani sledování překážek před lodí.'
            )
        );
    }

    private static function panel() {
        return '<section '.self::MARKER.' aria-label="Použití echolotu před dalším nákupem">'
            .'<h3>Než vyměníte echolot: tři kontroly u vody</h3>'
            .'<ol><li><strong>Výpadky nebo šum:</strong> zkontrolujte polohu sondy, '
            .'proudění vody a kabely podle návodu k vašemu modelu.</li>'
            .'<li><strong>Nečitelný obraz:</strong> porovnejte automatickou a ruční '
            .'citlivost, rozsah hloubky a dostupné frekvence; nenastavujte vše na maximum.</li>'
            .'<li><strong>Málo detailů:</strong> ověřte, jaký režim podporuje '
            .'konkrétní kombinace jednotky a sondy. Nový displej sám o sobě '
            .'nemusí problém vyřešit.</li></ol>'
            .'<p>Pokud vám po těchto kontrolách funkce stále nestačí, projděte '
            .'<a href="'.self::PILLAR.'">průvodce výběrem echolotu</a>, '
            .'nebo při pevně stanoveném rozpočtu '
            .'<a href="'.self::BUDGET.'">srovnání sestav do 15 000 Kč</a>. '
            .'U nabídky vždy prověřte přesnou sondu, kompatibilitu a aktuální '
            .'celkovou cenu.</p></section>';
    }

    public static function boot() {
        // Standalone publisher tests lack a real front-end query runtime.
        if (function_exists('add_filter') && function_exists('is_single')
            && function_exists('get_queried_object_id') && function_exists('in_the_loop')) {
            add_filter('the_content', array(__CLASS__, 'render'), 53);
        }
    }

    private static function eligible() {
        if (!is_single(self::ID) || !function_exists('is_singular')
            || !is_singular('post')
            || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_main_query') || !is_main_query()
            || !in_the_loop()
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'),'/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::SOURCE_GMT
            && is_string($p->post_content ?? null)
            && strlen($p->post_content) === self::SOURCE_BYTES
            && hash_equals(self::SOURCE_SHA, hash('sha256',$p->post_content));
    }

    private static function links($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',
            $html,$matches) === false) return null;
        return $matches[2];
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 6000
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::PILLAR) !== false
            || strpos($html, self::BUDGET) !== false) return $html;
        $heading = '~<h2\b[^>]*>\s*Jak efektivně používat echolot a sonar na loď nebo člun\s*</h2>~u';
        if (preg_match_all($heading,$html,$headings,PREG_OFFSET_CAPTURE) !== 1) return $html;
        $originalLinks=self::links($html);
        if ($originalLinks === null) return $html;
        // Never edit text inside an existing link, even if an editor later
        // wraps one of these sentences in a commercial anchor.
        if (preg_match_all('~<a\b[^>]*>.*?</a>~isu', $html, $anchors) === false) return $html;
        $updated=$html;
        foreach (self::corrections() as $pair) {
            foreach ($anchors[0] as $anchor) {
                if (strpos($anchor,$pair[0]) !== false) return $html;
            }
            if (substr_count($updated,$pair[0]) !== 1
                || strpos($updated,$pair[1]) !== false) return $html;
            $updated=str_replace($pair[0],$pair[1],$updated);
        }
        if (preg_match_all($heading,$updated,$heads,PREG_OFFSET_CAPTURE) !== 1) return $html;
        $at=$heads[0][0][1]+strlen($heads[0][0][0]);
        $updated=substr_replace($updated,"\n".self::panel(),$at,0);
        $afterLinks=self::links($updated);
        if ($afterLinks === null || substr_count($updated,self::MARKER)!==1
            || substr_count($updated,self::PILLAR)!==1
            || substr_count($updated,self::BUDGET)!==1) return $html;
        // Original in-content outbound and editorial links survive byte-for-byte.
        $kept=array_values(array_filter($afterLinks,
            function($url){ return $url!==self::PILLAR && $url!==self::BUDGET; }));
        if ($kept !== $originalLinks) return $html;
        return $updated;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Sonar_Practical_Owner_49275_V1::boot();
