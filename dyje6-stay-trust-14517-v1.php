<?php
/**
 * Exact-source, frontend-only 2026 Dyje 6 safety and lodging recommendations.
 *
 * No WordPress stored-content writes, new redirect endpoints, credentials,
 * cookies, merchant probes or synthetic clicks. Roll back one plugin require.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Dyje6_Stay_Trust_14517_V1 {
    const ID = 14517;
    const SLUG = 'vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj';
    const URL = 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/';
    const MODIFIED_GMT = '2026-04-18 14:01:51';
    const SOURCE_SHA256 = '51e48960eb8256455aed10f266f9b3942d71d2e9a10aff00741df9cc210640c5';
    const OFFICIAL = 'https://www.rybnikarstvipohorelice.cz/dyje-6/';
    const OLD_BIVAK_GUIDE = 'https://na-ryby.eu/nejlepsi-rybarske-bivaky-jak-vybrat-ten-nejlepsi/';
    const EXISTING_CATEGORY = 'https://na-ryby.eu/booking-cesko';
    // Single reviewed, specific partner destinations. Never resolve or visit
    // these from code, QA, monitors or CI: only real human CTAs navigate.
    const SLEVOMAT = 'https://www.slevomat.cz/akce/2464086-dovolena-na-palave-polopenze-i-vstup-do-wellness?utm_source=affiliate&utm_medium=cpc&utm_campaign=dis_akv_gen_cze_all_buy_j3d7tsgc_narybyCZ&utm_content=dyje6_14517_termal_musov&utm_term=j3d7tsgc&a_box=j3d7tsgc';
    const HPORTAL = 'https://go.dognet.com/?chid=5QoXiSqx&url=https%3A%2F%2Fhportal.cz%2Fcs%2Fpasohlavky%2Fpenzion-pasohlavky%2F';
    const FACTS_MARKER = 'data-nry-dyje6-facts="v1"';
    const OFFERS_MARKER = 'data-nry-dyje6-offers="v1"';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), 75);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 28);
        }
    }

    private static function eligible($need_loop = true) {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || ($need_loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_permalink') || get_permalink(self::ID) !== self::URL
            || !function_exists('get_post')) return false;
        $p = get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post' && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && hash('sha256', $p->post_content) === self::SOURCE_SHA256;
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-dyje6-stay-trust-14517-v1',
            plugins_url('assets/nry-dyje6-stay-trust-v1.css', __FILE__), array(), '1.0.0');
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches) === false) return null;
        return array_map(function ($href) {
            return html_entity_decode($href, ENT_QUOTES, 'UTF-8');
        }, $matches[2]);
    }

    private static function facts() {
        return '<section class="nry-dyje6-facts" '.self::FACTS_MARKER.' aria-label="Ověřené podmínky revíru Dyje 6">'
            .'<h3>Dyje 6: povolenky a omezení podle správce (2026)</h3>'
            .'<p>Věstonická nádrž je <strong>přírodní rezervace</strong>. K lovu potřebujete '
            .'<strong>platný státní rybářský lístek a místní povolenku vydanou Rybníkářstvím Pohořelice</strong>; '
            .'samotná běžná svazová povolenka tuto místní povolenku nenahrazuje.</p>'
            .'<p><strong>Ceník pro rok 2026:</strong> denní 350 Kč, týdenní 1 380 Kč, '
            .'měsíční 4 450 Kč a roční 13 800 Kč. Dětské varianty a termíny prodeje '
            .'ověřte u správce; ceny staršího článku 200/800 Kč už nejsou aktuální.</p>'
            .'<p><strong>Lov jen na určeném úseku jižní hráze</strong> mezi koncem lesíka u '
            .'rybářské základny a závorou směrem k Dolním Věstonicím. Přístup pouze '
            .'po asfaltové cestě na koruně hráze. Na ostrůvky a pobřežní mělčiny se nevstupuje; '
            .'mimo povolenou dobu lovu se na hrázi nepřespává.</p>'
            .'<p><a href="'.self::OFFICIAL.'" target="_blank" rel="noopener">'
            .'Přečíst přesná pravidla a aktuální ceník přímo u správce revíru</a>.</p>'
            .'<p><small>Ověřeno 18. 11. 2026 podle pravidel pro 1. 1.–31. 12. 2026. '
            .'Před výpravou ověřte případné nové změny, doby hájení a omezení.</small></p>'
            .'</section>';
    }

    private static function offers() {
        $slevomat = htmlspecialchars(self::SLEVOMAT, ENT_QUOTES, 'UTF-8');
        $hportal = htmlspecialchars(self::HPORTAL, ENT_QUOTES, 'UTF-8');
        return '<section class="nry-dyje6-offers" '.self::OFFERS_MARKER.' aria-label="Ubytování v okolí Nových Mlýnů">'
            .'<h2>Kde přespat při výpravě na Věstonickou nádrž</h2>'
            .'<p>Ubytování vybírejte v Pasohlávkách a okolí. <strong>Jde o samostatné '
            .'pobytové nabídky</strong>, nikoliv o místa lovu; délku cesty k povolené '
            .'části hráze, parkování, volné pokoje a rybářská pravidla ověřte zvlášť.</p>'
            .'<article data-nry-offer-id="2464086" data-nry-merchant="slevomat.cz">'
            .'<h3>Hotel Termal Mušov – Pasohlávky</h3>'
            .'<p>Pobyt s polopenzí a wellness. Slevomat uvádí Nové Mlýny asi 1,7 km od '
            .'hotelu, ne od konkrétního povoleného místa lovu na Dyji 6. '
            .'Nabídka na Slevomatu má uvedenou platnost do 31. 3. 2027 podle kapacit.</p>'
            .'<p><a class="nry-dyje6-offers__cta" href="'.$slevomat.'" '
            .'rel="sponsored nofollow noopener" target="_blank">'
            .'Zkontrolovat pobyt a volné termíny na Slevomatu</a></p></article>'
            .'<article data-nry-offer-id="hportal-penzion-pasohlavky" data-nry-merchant="hportal.cz">'
            .'<h3>Penzion Pasohlávky – Pasohlávky 206</h3>'
            .'<p>Další možnost přespání v obci podle katalogu Hportal. '
            .'Záznam neprokazuje, že ubytování zahrnuje rybaření, '
            .'povolenku nebo konkrétní termín.</p>'
            .'<p><a class="nry-dyje6-offers__cta nry-dyje6-offers__cta--secondary" href="'.$hportal.'" '
            .'rel="sponsored nofollow noopener" target="_blank">'
            .'Ověřit Penzion Pasohlávky na Hportalu</a></p></article>'
            .'<p><a href="'.self::EXISTING_CATEGORY.'">Prohlédnout i další pobyty v Česku</a>.</p>'
            .'<p><small>Partnerské odkazy. Provize se odvíjí od podmínek affiliate programu; '
            .'povolenka k rybolovu ani dostupnost ubytování není automaticky v ceně. '
            .'Podklady k nabídkám ověřeny 18. 11. 2026. Nabídky a termíny se mění.</small></p>'
            .'</section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strpos($html, self::FACTS_MARKER) !== false
            || strpos($html, self::OFFERS_MARKER) !== false
            || strpos($html, self::SLEVOMAT) !== false || strpos($html, self::HPORTAL) !== false) return $html;
        $old_prices = "<p>Podle dostupných informací se na Věstonické nádrži prodávají denní i týdenní povolenky, a to i ve zvýhodněné variantě pro mládež. Uvedené ceny byly:</p>\n"
            ."<ul>\n<li>denní povolenka: 200 korun</li>\n<li>denní povolenka pro mládež do 15 let: 100 korun</li>\n"
            ."<li>týdenní povolenka: 800 korun</li>\n<li>týdenní povolenka pro mládež: 400 korun</li>\n</ul>\n"
            ."<p>Beru to jako orientační údaj, ne jako konečné pravidlo na věčné časy. Ceny a podmínky se mohou změnit, takže před výpravou je rozumné ověřit aktuální informace u správce revíru.</p>\n";
        $old_stays = "<div style=\"background:#f8f9fa;border:1px solid #d9e2ec;border-radius:10px;padding:18px 20px;margin:24px 0;\"><strong>Mrknout na doporučené ubytování:</strong></p>\n"
            ."<p style=\"margin:10px 0 14px 0;\">Pokud chcete spojit rybaření s přespáním v okolí, podívejte se na přehled možností pro pobyty u vody.</p>\n"
            ."<p><a href=\"".self::EXISTING_CATEGORY."\" style=\"display:inline-block;background:#0056b3;color:#fff;text-decoration:none;padding:11px 16px;border-radius:6px;font-weight:700;\">Najít ubytování pro rybáře</a></div>\n";
        $old_bivak = '<li>prostor pro <a href="'.self::OLD_BIVAK_GUIDE.'" data-internallinksmanager029f6b8e52c="14" title="bivak">bivak</a>, lehátko nebo plachtu</li>';
        $changes = array(
            $old_prices => self::facts()."\n",
            $old_stays => self::offers()."\n",
            $old_bivak => '<li>bezpečné místo pro výbavu jen v povoleném úseku hráze; pobyt v rezervaci má svá omezení</li>',
            '<li>ochrana proti větru a dešti, pokud zůstáváte přes noc</li>'
                => '<li>ochrana proti větru a dešti během povoleného lovu; nocleh plánujte mimo hráz</li>',
        );
        $old_links = self::hrefs($html);
        if ($old_links === null || substr_count(implode(' ', $old_links), self::OLD_BIVAK_GUIDE) !== 1
            || substr_count(implode(' ', $old_links), self::EXISTING_CATEGORY) !== 1) return $html;
        $changed = $html;
        foreach ($changes as $old => $new) {
            if (substr_count($changed, $old) !== 1) return $html;
            $changed = str_replace($old, $new, $changed);
        }
        $new_links = self::hrefs($changed);
        $new_only = array(self::OFFICIAL, self::SLEVOMAT, self::HPORTAL);
        $preserved = array_values(array_filter($old_links, function($link) {
            return $link !== self::OLD_BIVAK_GUIDE;
        }));
        $remaining = array_values(array_filter($new_links ?? array(), function($link) use ($new_only) {
            return !in_array($link, $new_only, true);
        }));
        return ($new_links !== null && $remaining === $preserved
            && count($new_links) === count($old_links) + 2
            && substr_count($changed, self::FACTS_MARKER) === 1
            && substr_count($changed, self::OFFERS_MARKER) === 1
            && substr_count($changed, htmlspecialchars(self::SLEVOMAT, ENT_QUOTES, 'UTF-8')) === 1
            && substr_count($changed, htmlspecialchars(self::HPORTAL, ENT_QUOTES, 'UTF-8')) === 1
            && strpos($changed, 'denní povolenka: 200 korun') === false
            && strpos($changed, 'týdenní povolenka: 800 korun') === false)
            ? $changed : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Dyje6_Stay_Trust_14517_V1::boot();
