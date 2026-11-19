<?php
/**
 * Source-guarded, render-only stay choices in the romantic fishing guide.
 * No stored WordPress writes, redirect, merchant requests, synthetic events or
 * claim that a stay includes fishing permits or a guaranteed price.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Romantic_Stay_Offers_57013_V1 {
    const ID = 57013;
    const SLUG = 'romanticky-pobyt-pro-dva-s-rybarenim-nezapomenutelne-chvile-plne-lasky-a-rybolovu';
    const URL = 'https://na-ryby.eu/romanticky-pobyt-pro-dva-s-rybarenim-nezapomenutelne-chvile-plne-lasky-a-rybolovu/';
    const MODIFIED_GMT = '2026-04-19 12:01:55';
    // First 512 *bytes* of the exact public REST rendered source, verified
    // 2026-11-19; this is intentionally NOT described as a raw WP SHA.
    const RENDERED_PREFIX_SHA256 = '13781a6c347e3bbb2b3382e958b4349c2648ba0e601603ea34aa853b11452d69';
    const RENDERED_PREFIX_BYTES = 512;
    const MARKER = 'data-nry-romantic-stay-offers="v1"';
    const OLD_BUTTON = '<p style="text-align:center;margin:26px 0;"><a href="https://na-ryby.eu/booking-cesko" style="display:inline-block;background:#fa0127;color:#ffffff;text-decoration:none;padding:14px 22px;border-radius:10px;font-weight:bold;">Mrknout na doporučené ubytování</a></p>';
    const CATEGORY = 'https://na-ryby.eu/booking-cesko';
    // AffiliatePort owner-approved advertiser 1990, publisher 1736. Both
    // sub_id5 targets are fixed primary Hauzi destinations, not invented URLs.
    const HAUZI_SRUB = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub';
    const HAUZI_MORE = 'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcz%2Fpro-dva%2Fpro-rybare%2Fwellness';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', __CLASS__.'::render', PHP_INT_MAX);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', __CLASS__.'::enqueue', 28);
        }
    }

    private static function eligible() {
        foreach (array('is_single','is_singular','get_queried_object_id','is_admin',
            'is_preview','home_url','get_permalink','get_post') as $fn) {
            if (!function_exists($fn)) return false;
        }
        if (!is_single(self::ID) || !is_singular('post')
            || (int)get_queried_object_id() !== self::ID
            || is_admin() || is_preview()
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || get_permalink(self::ID) !== self::URL) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && strpos($p->post_content, 'Romantický pobyt pro dva s rybařením') !== false;
    }

    public static function enqueue() {
        if (!self::eligible() || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-romantic-stay-offers-57013-v1',
            plugins_url('assets/nry-romantic-stay-offers-57013-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches) === false) return null;
        return array_map(function($url) { return html_entity_decode($url, ENT_QUOTES, 'UTF-8'); }, $matches[2]);
    }

    public static function block() {
        $srub = htmlspecialchars(self::HAUZI_SRUB, ENT_QUOTES, 'UTF-8');
        $more = htmlspecialchars(self::HAUZI_MORE, ENT_QUOTES, 'UTF-8');
        return '<section class="nry-romantic-offers" '.self::MARKER.' aria-label="Konkrétní pobyty pro dva s možností rybaření v okolí">'
            .'<p class="nry-romantic-offers__eyebrow">Konkrétní tip na pobyt · listopad 2026</p>'
            .'<h2>Romantický víkend a rybaření? Tady můžete začít s výběrem</h2>'
            .'<p>Nejdřív si vyberte zázemí pro dva, pak ověřte povolenku a skutečný přístup k vodě. '
            .'Ubytování ani wellness samo o sobě nezaručuje možnost lovu.</p>'
            .'<div class="nry-romantic-offers__grid">'
            .'<article data-nry-romantic-offer="hauzi-rybarsky-srub" class="nry-romantic-offers__featured">'
            .'<p class="nry-romantic-offers__tag">Vranovská přehrada · konkrétní srub</p>'
            .'<h3>Wellness srub – sauna a koupací sud</h3>'
            .'<p>Hauzi uvádí ubytování v okolí Vranovské přehrady, s finskou saunou a koupacím sudem. '
            .'Romantický odpočinek a rybářský program lze plánovat odděleně; před rezervací '
            .'ověřte termín, přístup k lovnému místu a místní pravidla.</p>'
            .'<p class="nry-romantic-offers__action"><a class="nry-romantic-offers__cta" href="'.$srub.'" '
            .'target="_blank" rel="sponsored nofollow noopener">Zobrazit konkrétní srub na Hauzi</a></p></article>'
            .'<article data-nry-romantic-offer="hauzi-pro-dva-wellness">'
            .'<p class="nry-romantic-offers__tag">Více lokalit · pro dva</p>'
            .'<h3>Další rybářské pobyty s wellness</h3>'
            .'<p>Porovnejte další chaty a ubytování pro páry. Filtry na Hauzi pomohou s výběrem, '
            .'ale konkrétní podmínky rybaření a vzdálenost od vody ověřte u objektu.</p>'
            .'<p class="nry-romantic-offers__action"><a class="nry-romantic-offers__cta nry-romantic-offers__cta--secondary" href="'.$more.'" '
            .'target="_blank" rel="sponsored nofollow noopener">Prohlédnout další pobyty na Hauzi</a></p></article>'
            .'</div><p class="nry-romantic-offers__more">Chcete nejdřív jiné české lokality? '
            .'<a href="'.self::CATEGORY.'">Přehled dalších pobytů</a>.</p>'
            .'<p class="nry-romantic-offers__note"><small>Partnerské odkazy. Termíny, ceny a dostupnost se mění; '
            .'rybářská povolenka ani konkrétní lovné místo nejsou potvrzenou součástí pobytu. '
            .'Podklady k nabídkám ověřeny 19. 11. 2026.</small></p>'
            .'</section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 1800
            || hash('sha256', substr($html, 0, self::RENDERED_PREFIX_BYTES)) !== self::RENDERED_PREFIX_SHA256
            || substr_count($html, self::OLD_BUTTON) !== 1
            || substr_count($html, '<h2>Co si pod tím lidé obvykle představují</h2>') !== 1
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::HAUZI_SRUB) !== false
            || strpos($html, self::HAUZI_MORE) !== false) return $html;
        $before = self::hrefs($html);
        if ($before === null || count(array_filter($before, function($href) {
            return $href === self::CATEGORY;
        })) !== 1) return $html;
        $updated = str_replace(self::OLD_BUTTON, self::block(), $html);
        $after = self::hrefs($updated);
        $added = array(self::HAUZI_SRUB, self::HAUZI_MORE);
        $preserved = array_values(array_filter($after ?? array(), function($href) use($added) {
            return !in_array($href, $added, true);
        }));
        return ($after !== null && $preserved === $before
            && count($after) === count($before) + 2
            && substr_count($updated, self::MARKER) === 1
            && substr_count($updated, 'data-nry-romantic-offer=') === 2
            && substr_count($updated, htmlspecialchars(self::HAUZI_SRUB, ENT_QUOTES, 'UTF-8')) === 1
            && substr_count($updated, htmlspecialchars(self::HAUZI_MORE, ENT_QUOTES, 'UTF-8')) === 1)
            ? $updated : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Romantic_Stay_Offers_57013_V1::boot();
