<?php
/**
 * Render-only trust repair for the 2018 Hracholusky bungalow article.
 *
 * Removes the exact obsolete, unapproved Agoda/CJ box and its unsupported
 * lowest-price guarantee. Gives visitors a precise internal route to the
 * already-live source-reviewed fishing stays block on post 59891. No new
 * external affiliate URL, post-content write, metadata edit, or redirect.
 * Reversible by removing this module's require from the Git publisher.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Hracholusky_Stay_Trust_17606_V1 {
    const ID = 17606;
    const SLUG = 'bungalov-u-prehrady-hracholusky';
    const URL = 'https://na-ryby.eu/bungalov-u-prehrady-hracholusky/';
    const MODIFIED_GMT = '2018-02-01 19:51:47';
    const DESTINATION = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const LEGACY_URL = 'https://www.anrdoezrs.net/click-8287231-15735403?url=https%3A%2F%2Fwww.agoda.com%2Fsearch%3Fcity%3D1420%26textToSearch%3DHracholusky%26ds%3DNVXXV6AsTxxuHYjr';
    const MARKER = 'data-nry-hracholusky-stay-trust="v1"';

    public static function boot() {
        // Offline publisher writer-contract tests intentionally provide a
        // minimal add_filter stub, without the frontend query functions.
        // Never register a frontend correction in that backend/test context.
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 75);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 28);
        }
    }

    private static function eligible($require_loop = true) {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || ($require_loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url')
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        if (!is_object($p) || (int)($p->ID ?? 0) !== self::ID
            || ($p->post_type ?? '') !== 'post' || ($p->post_status ?? '') !== 'publish'
            || ($p->post_name ?? '') !== self::SLUG
            || ($p->post_modified_gmt ?? '') !== self::MODIFIED_GMT) return false;
        $raw = $p->post_content ?? null;
        return is_string($raw)
            && substr_count($raw, '<div class="agoda-booking-box"') === 1
            && substr_count($raw, self::LEGACY_URL) === 1
            && substr_count($raw, '* Garantovaná nejnižší cena') === 1;
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-hracholusky-stay-trust-17606-v1',
            plugins_url('assets/nry-hracholusky-stay-trust-v1.css', __FILE__),
            array(), '1.0.0');
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $m) === false) return null;
        return $m[2];
    }

    private static function legacy_box_span($html) {
        $needle = '<div class="agoda-booking-box"';
        if (substr_count($html, $needle) !== 1) return null;
        $start = strpos($html, $needle);
        if ($start === false || preg_match_all('~</?div\b[^>]*>~i', $html, $tags,
            PREG_OFFSET_CAPTURE, $start) === false) return null;
        $depth = 0;
        foreach ($tags[0] as $tag) {
            $depth += preg_match('~^<div\b~i', $tag[0]) ? 1 : -1;
            if ($depth < 0) return null;
            if ($depth === 0) {
                $end = $tag[1] + strlen($tag[0]);
                return ($end - $start) <= 3500 ? array($start, $end) : null;
            }
        }
        return null;
    }

    public static function transform($html) {
        if (!is_string($html) || strpos($html, self::MARKER) !== false
            || substr_count($html, self::LEGACY_URL) !== 1
            || substr_count($html, '* Garantovaná nejnižší cena') !== 1
            || strpos($html, self::DESTINATION) !== false) return $html;
        $span = self::legacy_box_span($html);
        if ($span === null) return $html;
        $box = substr($html, $span[0], $span[1] - $span[0]);
        if (substr_count($box, self::LEGACY_URL) !== 1
            || substr_count($box, '* Garantovaná nejnižší cena') !== 1
            || substr_count($box, 'Ubytování u revíru Hracholusky') !== 1
            || substr_count($box, 'Našli jsme pro vás nejlepší penziony') !== 1) return $html;
        $old_hrefs = self::hrefs($html);
        if ($old_hrefs === null || substr_count(implode('\n', $old_hrefs), self::LEGACY_URL) !== 1) return $html;

        $replacement = '<section class="nry-hracholusky-stay-trust" '.self::MARKER
            .' aria-label="Ubytování a rybaření u Hracholusk">'
            .'<p class="nry-hracholusky-stay-trust__label">Kde se ubytovat u Hracholusk</p>'
            .'<h2>Chystáte rybářskou dovolenou u přehrady?</h2>'
            .'<p>Popis tohoto bungalovu pochází z roku 2018. Jeho dnešní dostupnost, cenu, '
            .'podmínky půjčení loďky ani možnost rybaření jsme nově nepotvrdili. '
            .'Před rezervací si vše ověřte u provozovatele.</p>'
            .'<p><strong>Další ubytování v lokalitě:</strong> Pension U Vlčků '
            .'v Hracholuskách je uvedený na Hportalu, který mezi aktivitami zmiňuje rybaření. '
            .'Tato informace sama o sobě nezaručuje povolenku ani právo lovu.</p>'
            .'<p><a class="nry-hracholusky-stay-trust__cta" href="'.self::DESTINATION.'">'
            .'Prohlédnout konkrétní pobyty a ubytování pro rybáře</a></p>'
            .'<p><small>Aktualizace podkladů 18. 11. 2026. '
            .'Na cílové stránce jsou také označené partnerské nabídky; '
            .'volné termíny a podmínky lovu vždy ověřte samostatně.</small></p>'
            .'</section>';
        $changed = substr_replace($html, $replacement, $span[0], $span[1] - $span[0]);
        $new_hrefs = self::hrefs($changed);
        $expected = array_values(array_filter($old_hrefs, function ($href) {
            return $href !== self::LEGACY_URL;
        }));
        $actual = array_values(array_filter($new_hrefs ?? array(), function ($href) {
            return $href !== self::DESTINATION;
        }));
        return ($new_hrefs !== null
            && substr_count($changed, self::DESTINATION) === 1
            && strpos($changed, self::LEGACY_URL) === false
            && strpos($changed, '* Garantovaná nejnižší cena') === false
            && substr_count($changed, self::MARKER) === 1
            && count($new_hrefs) === count($old_hrefs)
            && $expected === $actual) ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Hracholusky_Stay_Trust_17606_V1::boot();
