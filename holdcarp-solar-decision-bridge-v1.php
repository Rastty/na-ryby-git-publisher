<?php
/**
 * Render-only 20W/40W Holdcarp review bridge to the existing public Wh guide.
 * No WP writes, merchant URL changes, synthetic clicks, stock or price claims.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Holdcarp_Solar_Decision_Bridge_V1 {
    const HOST = 'https://na-ryby.eu';
    const GUIDE = 'https://na-ryby.eu/powerbanka-nebo-solarni-panel-na-ryby/';
    const MARKER = 'data-nry-holdcarp-energy-bridge="v1"';

    private static function products() {
        return array(
            'holdcarp-solarni-panel-foldable-solar-system-20w-recenze' => '20W',
            'holdcarp-solarni-panel-foldable-solar-system-40w-recenze' => '40W',
        );
    }

    public static function panel($variant) {
        if ($variant !== '20W' && $variant !== '40W') return '';
        $context = $variant === '20W'
            ? 'Model 20 W má nižší jmenovitý špičkový výkon a může dávat smysl při menší denní spotřebě.'
            : 'Model 40 W má vyšší jmenovitý špičkový výkon, ale ani ten nezaručuje nabití v zamračeném dni.';
        return '<aside '.self::MARKER.' data-nry-holdcarp-model="'.$variant.'" '
            .'style="border:1px solid #b8d1c4;border-left:5px solid #286b50;border-radius:10px;'
            .'padding:16px 18px;margin:18px 0 24px;background:#f1f7f2" '
            .'aria-label="Aktuální rozhodovací informace k solárnímu panelu">'
            .'<p style="margin:0 0 8px"><strong>Nejdřív spotřeba, pak výkon panelu</strong></p>'
            .'<p style="margin:0 0 8px">'.$context.' Údaj 20/40 W je jmenovitý výkon za stanovených '
            .'testovacích podmínek, nikoli počet Wh, které panel každý den vyrobí. '
            .'Skutečný výnos závisí na oslunění, úhlu, stínu a ztrátách nabíjení.</p>'
            .'<p style="margin:0 0 8px">Příklad: zařízení s příkonem 10 W po dobu 5 hodin '
            .'spotřebuje 50 Wh. Pro výběr ověřte vstupní napětí a proud, konektor, polaritu, '
            .'potřebu regulátoru a povolené podmínky použití přesně podle manuálu '
            .'svého panelu i nabíjeného zařízení. Není bezpečné předpokládat univerzální '
            .'kompatibilitu pouze z označení 20/40 W.</p>'
            .'<p style="margin:0 0 8px">Starší hodnocení 5/5 a tvrzení o rychlosti nabíjení '
            .'níže nejsou na této stránce doložena nezávislým měřením. '
            .'Cenu ani skladovost konkrétní nabídky nyní neověřujeme.</p>'
            .'<p style="margin:0"><a href="'.self::GUIDE.'">'
            .'Spočítat potřebné Wh na 1–3 noci a porovnat powerbanku se solárem →</a>'
            .' <small>(praktický průvodce Na-Ryby.eu, bez registrace)</small></p>'
            .'</aside>';
    }

    public static function transform($html, $variant) {
        if (!is_string($html) || strlen($html) < 1500
            || strpos($html, self::MARKER) !== false
            || strpos($html, 'Foldable Solar System '.$variant) === false
            || strpos($html, 'HODNOCENÍ') === false) return $html;
        $panel = self::panel($variant);
        if ($panel === '' || substr_count($panel, self::MARKER) !== 1
            || substr_count($panel, 'href="'.self::GUIDE.'"') !== 1) return $html;
        return $panel.$html;
    }

    public static function render($html) {
        foreach (array('is_single','is_singular','get_queried_object_id','get_post',
            'get_permalink','home_url','is_admin','is_preview','is_main_query','in_the_loop') as $fn) {
            if (!function_exists($fn)) return $html;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET'
            || is_admin() || is_preview() || !is_singular('post')
            || !is_main_query() || !in_the_loop()
            || rtrim(home_url('/'), '/') !== self::HOST) return $html;
        $id = (int) get_queried_object_id();
        if ($id <= 0 || !is_single($id)) return $html;
        $post = get_post($id);
        if (!is_object($post) || (int)($post->ID ?? 0) !== $id
            || ($post->post_type ?? '') !== 'post' || ($post->post_status ?? '') !== 'publish') return $html;
        $slug = (string) ($post->post_name ?? '');
        $products = self::products();
        if (!isset($products[$slug]) || get_permalink($id) !== self::HOST.'/'.$slug.'/') return $html;
        $variant = $products[$slug];
        if (strpos((string)($post->post_title ?? ''), 'Holdcarp') === false
            || strpos((string)($post->post_title ?? ''), 'Foldable Solar System '.$variant) === false
            || !is_string($post->post_content ?? null)
            || strpos($post->post_content, 'Foldable Solar System '.$variant) === false
            || strpos($post->post_content, 'HODNOCENÍ') === false) return $html;
        return self::transform($html, $variant);
    }

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_single')
            && function_exists('is_singular') && function_exists('get_post'))
            add_filter('the_content', array(__CLASS__,'render'), 94);
    }
}
NRY_Holdcarp_Solar_Decision_Bridge_V1::boot();
