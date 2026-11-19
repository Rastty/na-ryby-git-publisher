<?php
/**
 * Bounded, frontend-only replacement of post 59113's false operator and
 * reservation claims, nested HTML documents and invented FAQ answers.
 * Never updates WordPress storage, redirects, cookies or affiliate tracking.
 * The whole replacement fails closed on a changed source or post identity.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Private_Fishing_Stay_Trust_59113_V1 {
    const ID = 59113;
    const SLUG = 'nezapomenutelna-rybarska-dovolena-na-privatnim-reviru-s-komfortnim-ubytovanim';
    const URL = 'https://na-ryby.eu/nezapomenutelna-rybarska-dovolena-na-privatnim-reviru-s-komfortnim-ubytovanim/';
    const MODIFIED_GMT = '2023-08-18 07:08:57';
    // First 512 rendered REST bytes, independently read on 19 Nov 2026.
    const RENDERED_PREFIX_SHA256 = 'ce56f7035ea3ac94ce9210629afc698440214cc16fb4bb6eac37804054caa3c9';
    const MARKER = 'data-nry-private-stay="v1"';
    const STAYS = 'https://na-ryby.eu/rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare/';
    const GEAR = 'https://na-ryby.eu/lov-kapra';

    public static function boot() {
        if (function_exists('is_single') && function_exists('add_filter')) {
            add_filter('the_content', array(__CLASS__, 'render'), PHP_INT_MAX);
        }
        if (function_exists('is_single') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'), 28);
        }
    }

    private static function eligible($loop = true) {
        if (!function_exists('is_single') || !is_single(self::ID)
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('get_queried_object_id')
            || (int)get_queried_object_id() !== self::ID
            || ($loop && (!function_exists('is_main_query') || !is_main_query()
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
            && substr_count($p->post_content, 'Naše privátní revíry') === 1
            && substr_count($p->post_content, 'Rezervace je velmi jednoduchá') === 1
            && substr_count($p->post_content, 'Tento článek pojednává o soukromých revírech') === 1;
    }

    public static function enqueue() {
        if (!self::eligible(false) || !function_exists('wp_enqueue_style')
            || !function_exists('plugins_url')) return;
        wp_enqueue_style('nry-private-stay-trust-59113-v1',
            plugins_url('assets/nry-private-fishing-stay-trust-59113-v1.css', __FILE__), array(), '1.0.0');
    }

    public static function block() {
        return '<section class="nry-private-stay" '.self::MARKER.' aria-label="Jak vybrat soukromý revír s ubytováním">'
            .'<p class="nry-private-stay__eyebrow">Průvodce rybářskou dovolenou</p>'
            .'<h2>Soukromý revír s ubytováním: co si ověřit před rezervací</h2>'
            .'<p class="nry-private-stay__lead">Na-Ryby.eu je informační web. Neprovozujeme vlastní soukromé revíry, '
            .'chatky ani rezervační formulář a nezajišťujeme rybářské instruktory. '
            .'Pomůžeme vám porozumět rozdílům mezi pobyty a vybrat konkrétní nabídku.</p>'
            .'<div class="nry-private-stay__steps">'
            .'<div><strong>1. Vyberte konkrétní vodu</strong><p>Ověřte správce, druhy ryb, sezonní omezení, '
            .'povolené způsoby lovu a pravidla chyť a pusť. Soukromý rybník nemusí být revírem ČRS.</p></div>'
            .'<div><strong>2. Rozlište rybolov od ubytování</strong><p>To, že chata stojí u vody, '
            .'ještě neznamená oprávnění chytat. Zkontrolujte, zda je rybolov opravdu součástí '
            .'zvolené varianty a kolik prutů či hodin zahrnuje.</p></div>'
            .'<div><strong>3. Ověřte termín a podmínky</strong><p>Porovnejte dostupnost, kapacitu, '
            .'minimální počet nocí, vybavení a storno podmínky přímo u poskytovatele.</p></div>'
            .'</div>'
            .'<article class="nry-private-stay__offer" data-nry-private-example="jedraz">'
            .'<p class="nry-private-stay__eyebrow">Příklad nabídky s rybařením · jižní Čechy</p>'
            .'<h3>Statek Jedraž – vybraná varianta s lovem na soukromém rybníku</h3>'
            .'<p>V popisu konkrétní varianty Slevomatu je uvedeno jedno 24hodinové rybaření '
            .'metodou chyť a pusť na dva pruty, s vlastním vybavením. V období od 1. května '
            .'do 1. listopadu není lov podle podmínek varianty možný v sobotu. '
            .'Dostupnost nabídky se může měnit; tyto podmínky si před nákupem znovu přečtěte.</p>'
            .'<a class="nry-private-stay__cta" href="'.self::STAYS.'">'
            .'Zobrazit rybářské pobyty a ověřit nabídky</a>'
            .'<p class="nry-private-stay__small">V přehledu jsou samostatné partnerské odkazy '
            .'na konkrétní poskytovatele. Kliknutí ani rezervace zde nejsou zaručené.</p>'
            .'</article>'
            .'<h2>Časté otázky před rybářským pobytem</h2>'
            .'<h3>Musím mít rybářský lístek a povolenku?</h3>'
            .'<p>Na svazových vodách ověřte povinné doklady včetně rybářského lístku '
            .'a příslušné povolenky. Soukromé vody mají vlastní pravidla, která vám musí '
            .'potvrdit provozovatel konkrétního místa.</p>'
            .'<h3>Je ubytování automaticky zároveň rezervací rybolovu?</h3>'
            .'<p>Ne. Někdy se rybolov kupuje zvlášť, jinde jej obsahuje pouze určitá varianta '
            .'pobytu. Ověřte místo lovu, rozsah služby a rezervaci před platbou.</p>'
            .'<h3>Jaký je minimální pobyt a jaké ryby mohu lovit?</h3>'
            .'<p>Obojí závisí na konkrétním areálu a termínu. Univerzální dvě noci ani úlovek '
            .'kapra, štiky nebo sumce nelze slíbit bez podmínek dané vody.</p>'
            .'<p class="nry-private-stay__footer">Připravujete si vlastní výbavu? '
            .'<a href="'.self::GEAR.'">Prohlédněte si průvodce lovem kapra</a>. '
            .'Informace o nabídce prověřeny 19. 11. 2026; aktuální dostupnost '
            .'a místní pravidla je třeba ověřit znovu.</p></section>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 8000
            || hash('sha256', substr($html, 0, 512)) !== self::RENDERED_PREFIX_SHA256
            || strpos($html, self::MARKER) !== false
            || strpos($html, self::STAYS) !== false
            || substr_count($html, '<!DOCTYPE html>') !== 2) return $html;
        foreach (array(
            '<h2>Nezapomenutelná rybářská dovolená na privátním revíru s komfortním ubytováním</h2>',
            'Naše privátní revíry se nachází v krásné přírodě',
            'Rezervace je velmi jednoduchá. Stačí vyplnit rezervační formulář na našem webu',
            '<a href="http://www.nezapomenutelnyrevir.cz">',
            'Tento článek pojednává o soukromých revírech s ubytováním',
            'Na privátním revíru můžete očekávat různé druhy ryb',
        ) as $anchor) {
            if (substr_count($html, $anchor) !== 1) return $html;
        }
        $replacement = self::block();
        // The historical document contained invented booking claims, a dead
        // offsite URL, invalid nested HTML/FAQ and no vetted merchant CTA.
        // Replacing its body as one unit avoids leaving any such assertion.
        return (substr_count($replacement, self::MARKER) === 1
            && substr_count($replacement, 'href="'.self::STAYS.'"') === 1
            && substr_count($replacement, 'href="'.self::GEAR.'"') === 1
            && strpos($replacement, 'http://') === false
            && strpos($replacement, 'rezervační formulář na našem webu') === false)
            ? $replacement : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }
}
NRY_Private_Fishing_Stay_Trust_59113_V1::boot();
