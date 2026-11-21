<?php
/**
 * Staged, intentionally DISARMED front-end factual repair of Štěrkáč #12040.
 * The exact raw WordPress SHA must first be verified by the separate source
 * proof PR #283, then pinned here in a separately reviewed activation commit.
 * Never modifies stored content, makes merchant requests, or generates clicks.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sterkac_Rules_12040_V1 {
    const ID = 12040;
    const SLUG = 'soukromy-revir-sterkac-zajeci';
    const CANONICAL = 'https://na-ryby.eu/soukromy-revir-sterkac-zajeci/';
    const MODIFIED_GMT = '2026-04-14 10:33:15';
    // Intentionally EMPTY: no production activation without raw WP source SHA.
    const VERIFIED_RAW_SHA256 = '';
    const MARKER = 'data-nry-sterkac-rules="v1"';
    const HEADING = '<h1>Soukromý revír Štěrkáč Zaječí</h1>';
    const FOOTER = '<div style="border-top:1px solid #e5e5e5;margin-top:28px;padding-top:12px;color:#666;font-size:14px;">';
    const OPERATOR = 'https://vinarstviukaplicky.cz/rybarina/';

    public static function boot() {
        // No hooks or side effects at all until the exact source SHA is set.
        if (!self::armed()) return;
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__.'::enqueue', 28);
            add_action('template_redirect', __CLASS__.'::startBuffer', 24);
        }
    }

    public static function armed() {
        return preg_match('/^[a-f0-9]{64}$/D', self::VERIFIED_RAW_SHA256) === 1;
    }

    private static function eligible() {
        if (!self::armed() || !function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::CANONICAL
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_post')) return false;
        $post = get_post(self::ID);
        return is_object($post) && (int)($post->ID ?? 0) === self::ID
            && ($post->post_type ?? '') === 'post'
            && ($post->post_status ?? '') === 'publish'
            && ($post->post_name ?? '') === self::SLUG
            && ($post->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($post->post_content ?? null)
            && hash_equals(self::VERIFIED_RAW_SHA256, hash('sha256', $post->post_content));
    }

    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-sterkac-rules-12040-v1',
            plugins_url('assets/nry-sterkac-rules-12040-v1.css', __FILE__), array(), '1.0.0');
    }

    public static function notice() {
        return '<section class="nry-sterkac-rules" '.self::MARKER.' aria-label="Ověřené podmínky revíru Štěrkáč Zaječí">'
            .'<p class="nry-sterkac-rules__eyebrow">Štěrkáč Zaječí · ověřeno 19. 11. 2026</p>'
            .'<h2>Před odjezdem: správné povolenky a pravidla</h2>'
            .'<p><strong>Denní 500 Kč (6–18 h), noční 500 Kč (18–6 h).</strong> Noční povolenku nelze koupit samostatně. '
            .'Běžná sezóna podle rybářského řádu je 10. dubna až 10. října; mimo ni jen po dohodě s provozovatelem.</p>'
            .'<p>Lov je <strong>chyť a pusť</strong> na nejvýše tři pruty. Povolen je jeden háček i s protihrotem, '
            .'vyžaduje se úniková montáž. <strong>Lov dravců je celoročně zakázán.</strong> '
            .'Potřebujete i platný český rybářský lístek; ověřte úplný řád před cestou.</p>'
            .'<p class="nry-sterkac-rules__actions"><a href="'.self::OPERATOR.'" target="_blank" '
            .'rel="noopener noreferrer">Ověřit oficiální pravidla a ceny</a>'
            .' <a href="tel:+420725924195">Rezervace: 725 924 195</a></p>'
            .'<p class="nry-sterkac-rules__caveat">Ceny a podmínky se mohou změnit. '
            .'Rezervaci chaty nebo místa potvrzuje provozovatel, nikoli Na-ryby.eu.</p></section>';
    }

    private static function replaceSection($html, $start, $end, $replacement) {
        if (substr_count($html, $start) !== 1 || substr_count($html, $end) !== 1) return false;
        $from = strpos($html, $start);
        $to = strpos($html, $end);
        if ($from === false || $to === false || $from >= $to) return false;
        return substr($html, 0, $from).$replacement."\n".substr($html, $to);
    }

    public static function transform($html) {
        // Source-lock is checked by render/startBuffer; the body has additional
        // exact public-rendered anchors, so unexpected template drift is inert.
        if (!is_string($html) || strlen($html) < 3500
            || substr_count($html, self::HEADING) !== 1
            || substr_count($html, self::MARKER) !== 0
            || substr_count($html, '300 Kč') !== 2
            || substr_count($html, 'listopad') !== 2
            || substr_count($html, 'bez protihrotu') !== 4
            || substr_count($html, 'si rybu i ponechat') !== 1
            || substr_count($html, 'booking-cesko') !== 1) return $html;

        $changed = preg_replace('~<p><strong>Rychlá odpověď:</strong>.*?</p>~us',
            '<p><strong>Rychlá odpověď:</strong> Štěrkáč Zaječí je soukromý revír s lovem chyť a pusť '
            .'na nejvýše tři pruty. Provozovatel připouští jednoháček i s protihrotem a únikovou montáž; '
            .'lov dravců zakazuje po celý rok. Řád uvádí sezónu 10. dubna až 10. října, '
            .'mimo ni je nutná dohoda. Ceny a rezervace si ověřte přímo u provozovatele.</p>',
            $html, 1, $count_intro);
        if ($changed === null || $count_intro !== 1) return $html;
        $changed = str_replace('<strong>háčky bez protihrotu</strong>',
            '<strong>jednoháček i s protihrotem, úniková montáž</strong>', $changed, $count_hook);
        $changed = str_replace('<strong>duben až listopad</strong>',
            '<strong>10. dubna až 10. října, jinak po dohodě</strong>', $changed, $count_season);
        if ($count_hook !== 1 || $count_season !== 1) return $html;

        $sections = array(
            array('<h2>Ryby na Štěrkáči: na co se dá zaměřit</h2>',
                '<h2>Ceny povolenek a co z nich reálně plyne</h2>',
                '<h2>Ryby na Štěrkáči a povolený způsob lovu</h2>'
                .'<p>Provozovatel uvádí v obsádce kapry, amury, štiky, sumce, jesetery a vyzy. '
                .'<strong>Samotná přítomnost dravců však neznamená povolení jejich lovu:</strong> '
                .'aktuální řád jejich cílený lov výslovně zakazuje po celý rok. Pro plánování výpravy '
                .'vycházejte z povoleného systému položené bez splávku a pravidel chyť a pusť.</p>'),
            array('<h2>Ceny povolenek a co z nich reálně plyne</h2>',
                '<h2>Pravidla lovu: tady se chyby nevyplácejí</h2>',
                '<h2>Cena povolenky, sezóna a rezervace</h2>'
                .'<p>Podle ceníku provozovatele ověřeného 19. 11. 2026 stojí <strong>denní povolenka '
                .'500 Kč (6:00–18:00)</strong> a <strong>noční povolenka 500 Kč (18:00–6:00)</strong>. '
                .'Noční povolenku nelze koupit samostatně. Prodej vyžaduje doklad totožnosti, '
                .'rybář u sebe musí mít platný rybářský lístek pro ČR.</p>'
                .'<p>Podle provozního řádu je běžný provoz od 10. dubna do 10. října; '
                .'mimo sezónu lze domluvit výjimku při obsazení alespoň jedné chaty čtyřmi rybáři. '
                .'Vždy předem zavolejte <a href="tel:+420725924195">725 924 195</a> a ověřte aktuální stav.</p>'),
            array('<h2>Pravidla lovu: tady se chyby nevyplácejí</h2>',
                '<h2>Jaké vybavení se na Štěrkáč hodí nejvíc</h2>',
                '<h2>Co přesně vyžaduje místní řád</h2>'
                .'<ul><li>Jen systém chyť a pusť, položená bez splávku a nejvýše tři pruty; '
                .'jednoháček i s protihrotem, povinná úniková montáž.</li>'
                .'<li>Podběrák minimálně 80 × 80 cm, podložka typu vanička s pevnými boky '
                .'alespoň 20 cm, pean a dezinfekce.</li>'
                .'<li>Vlasec alespoň 0,35 mm. V noci osvětlené místo viditelné ze 50 m '
                .'a elektronické signalizátory záběru.</li>'
                .'<li>Celoroční zákaz lovu dravců, zákaz zavážecích lodiček, vyvážení krmiv '
                .'i lovu z člunu; další zákazy a podmínky uvádí '
                .'<a href="'.self::OPERATOR.'">oficiální rybářský řád</a>.</li></ul>'),
            array('<h2>Služby, zázemí a proč se vyplatí plánovat pobyt jako výpravu, ne jako zastávku</h2>',
                '<h2>Pro koho je Štěrkáč Zaječí vhodný a komu může sednout méně</h2>',
                '<h2>Chatky, bivakovací místa a přímá rezervace</h2>'
                .'<p>Provozovatel nabízí chatu pro čtyři osoby za 2 000 Kč/noc, '
                .'VIP chatu pro pět osob za 2 500 Kč/noc a bivakovací místo za 200 Kč/noc '
                .'(ceník kontrolován 19. 11. 2026). Ubytování vyžaduje příslušné povolenky k lovu. '
                .'Rezervaci chaty či místa řešte přímo s provozovatelem na '
                .'<a href="tel:+420725924195">725 924 195</a>; platby na místě jsou hotovostní. '
                .'Neslibujeme volné termíny ani rezervaci přes obecné ubytovací portály.</p>'),
            array('<h2>Rychlé shrnutí před odjezdem</h2>', self::FOOTER,
                '<h2>Rychlé shrnutí před odjezdem</h2><p>Ověřte termín a ceny u správce, '
                .'vezměte platný český rybářský lístek, doklad totožnosti, povolenku, '
                .'dostatečně velký podběrák a vaničkovou podložku. Při nočním lovu '
                .'nezapomeňte na osvětlení a elektronické signalizátory; '
                .'při výběru nástrah respektujte zákaz cíleného lovu dravců. '
                .'Než vyrazíte, přečtěte si <a href="'.self::OPERATOR.'">úplný aktuální řád</a>.</p>'),
        );
        foreach ($sections as $spec) {
            $changed = self::replaceSection($changed, $spec[0], $spec[1], $spec[2]);
            if ($changed === false) return $html;
        }
        $changed = str_replace(self::HEADING, self::HEADING."\n".self::notice(), $changed, $count_heading);
        if ($count_heading !== 1 || substr_count($changed, self::MARKER) !== 1
            || strpos($changed, '300 Kč') !== false
            || strpos($changed, 'listopad') !== false
            || strpos($changed, 'bez protihrotu') !== false
            || strpos($changed, 'si rybu i ponechat') !== false
            || strpos($changed, 'booking-cesko') !== false) return $html;
        return $changed;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }

    public static function startBuffer() {
        // Legacy tagDiv may not use the_content. Gated by the identical raw
        // SHA and queried-post check; GET-only, no admin/preview/feed.
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET' || !self::eligible()
            || headers_sent() || (function_exists('is_feed') && is_feed())) return;
        ob_start(array(__CLASS__, 'transformDocument'));
    }

    public static function transformDocument($document, $flags = 0) {
        if (!is_string($document) || strlen($document) < 20000
            || strpos($document, self::MARKER) !== false
            || substr_count($document, self::HEADING) !== 1
            || substr_count($document, self::FOOTER) !== 1) return $document;
        $start = strpos($document, self::HEADING);
        $footer = strpos($document, self::FOOTER, $start);
        if ($footer === false || $footer <= $start) return $document;
        $end = strpos($document, '</div>', $footer);
        if ($end === false) return $document;
        $end += strlen('</div>');
        $original = substr($document, $start, $end - $start);
        $changed = self::transform($original);
        if ($changed === $original) return $document;
        $result = substr($document, 0, $start).$changed.substr($document, $end);
        return substr_count($result, self::MARKER) === 1 ? $result : $document;
    }
}
NRY_Sterkac_Rules_12040_V1::boot();
