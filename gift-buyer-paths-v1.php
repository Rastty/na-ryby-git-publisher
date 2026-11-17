<?php
/**
 * Two archived gift posts: bounded first-party purchasing decisions, not a
 * product recommendation, current price claim or stored editorial rewrite.
 * No existing links are altered; rollback by removing the plugin require.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Gift_Buyer_Paths_V1 {
    const MARKER = 'data-nry-gift-buyer="v1"';

    public static function pages() {
        return array(
            46852 => array('slug'=>'nejlepsi-darky-pro-rybare-tipy-na-nadcasove-a-prakticke-prezentace',
                'modified'=>'2023-05-13 16:02:20', 'anchor'=>'Nejlepší dárky pro rybáře', 'minimum'=>5000),
            35630 => array('slug'=>'novinky-pro-rybare-nejzajimavejsi-vanocni-darky-na-trhu',
                'modified'=>'2023-04-06 00:40:35', 'anchor'=>'Nejzajímavější vánoční dárky', 'minimum'=>7000),
        );
    }

    public static function routes() {
        return array(
            'night' => array('label'=>'Nevím, co už má',
                'description'=>'Začněte čelovkou nebo osvětlením. Před nákupem ověřte nabíjení, ovládání a voděodolnost.',
                'action'=>'Jak vybrat čelovku',
                'path'=>'/celovky-pro-rybare-porovnani-cen-a-vykonu/'),
            'spinning' => array('label'=>'Chytá na přívlač',
                'description'=>'U prutu rozhoduje délka, vrhací zátěž a konkrétní způsob lovu; bez těchto údajů nekupujte naslepo.',
                'action'=>'Porovnat přívlačové pruty',
                'path'=>'/nejlepsi-privlacove-pruty-do-3-000-kc/'),
            'feeder' => array('label'=>'Chytá na feeder',
                'description'=>'U feederového prutu záleží na řece či stojaté vodě, síle proudu a zátěži krmítka.',
                'action'=>'Vybrat feederový prut',
                'path'=>'/nejlepsi-method-feeder-pruty-do-3-500-kc/'),
            'camp' => array('label'=>'Přespává u vody',
                'description'=>'U bivaku musí sedět velikost, konstrukce a transport. Je to větší dárek, který raději předem proberte.',
                'action'=>'Prohlédnout průvodce bivaky',
                'path'=>'/nejlepsi-rybarske-bivaky-do-10-000-kc/'),
        );
    }

    private static function selected($in_loop = true) {
        if (!function_exists('get_queried_object_id') || !function_exists('is_singular')
            || !is_singular('post') || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || ($in_loop && (!function_exists('is_main_query') || !is_main_query()
                || !function_exists('in_the_loop') || !in_the_loop()))
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_post') || !function_exists('get_permalink')) return false;
        $id=(int)get_queried_object_id(); $pages=self::pages();
        if (!isset($pages[$id])) return false;
        $p=get_post($id); $set=$pages[$id];
        if (!is_object($p) || (int)($p->ID ?? 0)!==$id
            || ($p->post_type ?? '')!=='post' || ($p->post_status ?? '')!=='publish'
            || ($p->post_name ?? '')!==$set['slug']
            || ($p->post_modified_gmt ?? '')!==$set['modified']
            || get_permalink($id)!=='https://na-ryby.eu/'.$set['slug'].'/') return false;
        return $set;
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is', $html, $matches)===false) return null;
        return $matches[2];
    }

    public static function panel() {
        $cards='';
        foreach (self::routes() as $key=>$route) {
            $cards.='<li class="nry-gift-buyer__card"><h3>'.htmlspecialchars($route['label'],ENT_QUOTES,'UTF-8').'</h3>'
                .'<p>'.htmlspecialchars($route['description'],ENT_QUOTES,'UTF-8').'</p>'
                .'<a data-nry-gift-path="'.$key.'" href="https://na-ryby.eu'.$route['path'].'">'
                .htmlspecialchars($route['action'],ENT_QUOTES,'UTF-8').'</a></li>';
        }
        return '<nav class="nry-gift-buyer" '.self::MARKER.' aria-label="Výběr dárku podle toho, jak rybář chytá">'
            .'<p class="nry-gift-buyer__eyebrow">Rychlý výběr bez hádání značky a ceny</p>'
            .'<h2>Jaký dárek rybáři opravdu využije?</h2>'
            .'<p>Nejdřív zjistěte, jak chytá. U dražší nebo rozměrově citlivé výbavy si raději potvrďte přesné parametry. '
            .'Níže vedou odkazy na naše průvodce, nikoli na ověřeně skladové konkrétní produkty.</p>'
            .'<ul class="nry-gift-buyer__grid">'.$cards.'</ul>'
            .'<p class="nry-gift-buyer__caveat">Články níže mohou obsahovat starší příklady. Aktuální cenu, dostupnost '
            .'a vhodnou variantu vždy ověřte před zaplacením; bez znalosti výbavy může být lepší poukaz nebo domluvený výběr.</p>'
            .'</nav>';
    }

    public static function transform($html,$set) {
        if (!is_string($html) || !is_array($set) || strlen($html)<$set['minimum']
            || strpos($html,self::MARKER)!==false
            || substr_count($html,$set['anchor'])<1) return $html;
        $old=self::hrefs($html);
        if ($old===null || count($old)<3) return $html;
        $panel=self::panel(); $new=$panel."\n".$html;
        $links=self::hrefs($new);
        $expected=array();
        foreach (self::routes() as $route) $expected[]='https://na-ryby.eu'.$route['path'];
        if ($links===null || count($links)!==count($old)+count($expected)
            || array_slice($links,0,count($expected))!==$expected
            || array_slice($links,count($expected))!==$old) return $html;
        return $new;
    }

    public static function render($html) {
        $set=self::selected();
        return $set ? self::transform($html,$set) : $html;
    }

    public static function enqueue() {
        if (!self::selected(false) || !function_exists('plugins_url')) return;
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style('nry-gift-buyer-v1',plugins_url('assets/nry-gift-buyer-v1.css',__FILE__),array(),'1.0.0');
        }
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script('nry-gift-buyer-v1',plugins_url('assets/nry-gift-buyer-v1.js',__FILE__),array(),'1.0.0',true);
        }
    }

    public static function boot() {
        if (function_exists('is_singular') && function_exists('add_filter'))
            add_filter('the_content',array(__CLASS__,'render'),80);
        if (function_exists('is_singular') && function_exists('add_action'))
            add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),32);
    }
}
NRY_Gift_Buyer_Paths_V1::boot();
