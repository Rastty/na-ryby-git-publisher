<?php
/**
 * Search Console CTR experiment for three already-indexed fishing/stay URLs.
 * Render-only Yoast snippet filters; no post, meta, title, URL or affiliate writes.
 * Baselines: anonymous public <head>, 20 Nov 2026; GSC 90d #2029.
 * Fails closed on source/editor drift. Roll back by removing this require.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_GSC_Revir_Stays_SEO_20261120_V1 {
    private static function records() {
        return array(
            14517 => array(
                'slug' => 'vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj',
                'url' => 'https://na-ryby.eu/vestonicka-nadrz-dyje-6-rybarina-jihomoravsky-kraj/',
                'modified' => '2026-04-18 14:01:51',
                'sha256' => '51e48960eb8256455aed10f266f9b3942d71d2e9a10aff00741df9cc210640c5',
                'old_title' => 'Soukromý revír Věstonická nádrž (Dyje 6) - informace, ceny, kontakty',
                'old_description' => 'Rybaření na revíru Dyje 6 a všechny informace na jednom místě. Ceny povolenek, ubytování Věstonická nádrž a informace k rybolovu.',
                'title' => 'Věstonická nádrž (Dyje 6): lov, povolenky, přístup',
                'description' => 'Věstonická nádrž (Dyje 6): kde se smí chytat, jak ověřit místní povolenku a podmínky na Nových Mlýnech. Praktické tipy před cestou.',
            ),
            17591 => array(
                'slug' => 'chata-brnenska-prehrada',
                'url' => 'https://na-ryby.eu/chata-brnenska-prehrada/',
                'modified' => '2018-01-22 19:53:18',
                'sha256' => '28dcd7c75cd173508727082c1b7f69a322211cde3acf0ef55fed7136b215a261',
                'old_title' => 'Chata Brněnská přehrada - Rybařina Brněnská přehrada',
                'old_description' => 'Chata Brněnská přehrada - ideální dovolená pro rybáře i s rodinou, blízko vody a výborná cena. Rybářská dovolená pro celou rodinu.',
                'title' => 'Chata Brněnská přehrada: informace a okolní pobyty',
                'description' => 'Chata Brněnská přehrada: starší popis objektu a další tipy na ubytování u vody. Před rezervací ověřte termín, dostupnost i pravidla rybolovu.',
            ),
            12166 => array(
                'slug' => 'rybarska-dovolena-jizni-morava',
                'url' => 'https://na-ryby.eu/rybarska-dovolena-jizni-morava/',
                'modified' => '2017-06-28 21:26:22',
                'sha256' => '2bdb8599825facdb6bd90393136074ea5af40efe3b265e58ab75da99e0ff9587',
                'old_title' => 'Rybářská dovolená jižní morava',
                // This page had no public meta description on 20 Nov.
                'old_description' => '',
                'title' => 'Rybářská dovolená jižní Morava: chaty a revíry',
                'description' => 'Rybářská dovolená na jižní Moravě: přehled rybářských chat, Nové Mlýny a Brněnská přehrada. Jak vybrat pobyt a ověřit pravidla lovu.',
            ),
        );
    }

    private static $probe = array(
        'title' => array('calls' => 0, 'first' => null, 'applied' => false),
        'description' => array('calls' => 0, 'first' => null, 'applied' => false),
    );

    private static function probe_requested() {
        return isset($_GET['nry_gsc_snippet_probe'])
            && $_GET['nry_gsc_snippet_probe'] === 'source_v1';
    }

    private static function probe_call($kind, $current, $result, $record, $baseline_key) {
        if (!self::probe_requested()) return;
        self::$probe[$kind]['calls']++;
        $row = array(
            'inputBytes' => is_string($current) ? strlen($current) : null,
            'inputSha256' => is_string($current) ? hash('sha256', $current) : null,
            'baselineMatched' => $record && is_string($current)
                && $current === $record[$baseline_key],
            'applied' => $result !== $current,
        );
        if (self::$probe[$kind]['first'] === null) self::$probe[$kind]['first'] = $row;
        if ($row['applied']) self::$probe[$kind]['applied'] = true;
    }

    public static function boot() {
        if (!function_exists('add_filter') || !function_exists('is_single')) return;
        add_filter('wpseo_title', array(__CLASS__, 'title'), 20);
        add_filter('wpseo_metadesc', array(__CLASS__, 'description'), 20);
        if (function_exists('add_action')) {
            add_action('wp_footer', array(__CLASS__, 'probe_footer'), PHP_INT_MAX);
        }
    }

    private static function eligible() {
        foreach (array('is_single', 'is_singular', 'get_queried_object_id',
            'home_url', 'get_permalink', 'get_post', 'is_admin', 'is_preview') as $fn) {
            if (!function_exists($fn)) return null;
        }
        if (!is_singular('post') || is_admin() || is_preview()
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return null;
        $id = (int) get_queried_object_id();
        $records = self::records();
        if (!isset($records[$id]) || !is_single($id)) return null;
        $record = $records[$id];
        if (get_permalink($id) !== $record['url']) return null;
        $post = get_post($id);
        if (!is_object($post) || (int)($post->ID ?? 0) !== $id
            || ($post->post_type ?? '') !== 'post'
            || ($post->post_status ?? '') !== 'publish'
            || ($post->post_name ?? '') !== $record['slug']
            || ($post->post_modified_gmt ?? '') !== $record['modified']
            || !is_string($post->post_content ?? null)
            || !hash_equals($record['sha256'], hash('sha256', $post->post_content))) return null;
        return $record;
    }

    public static function title($current) {
        $record = self::eligible();
        $result = $record && is_string($current) && $current === $record['old_title']
            ? $record['title'] : $current;
        self::probe_call('title', $current, $result, $record, 'old_title');
        return $result;
    }

    public static function description($current) {
        $record = self::eligible();
        $result = $record && is_string($current) && $current === $record['old_description']
            ? $record['description'] : $current;
        self::probe_call('description', $current, $result, $record, 'old_description');
        return $result;
    }

    public static function probe_footer() {
        if (!self::probe_requested()) return;
        foreach (array('is_single', 'is_singular', 'get_queried_object_id',
            'home_url', 'get_permalink', 'get_post', 'is_admin', 'is_preview') as $fn) {
            if (!function_exists($fn)) return;
        }
        $id = (int) get_queried_object_id();
        $records = self::records();
        if (!isset($records[$id]) || !is_single($id) || !is_singular('post')
            || is_admin() || is_preview()
            || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu') return;
        $record = $records[$id];
        if (get_permalink($id) !== $record['url']) return;
        $post = get_post($id);
        $raw = is_object($post) && is_string($post->post_content ?? null)
            ? $post->post_content : null;
        // Boolean source identity and bounded input digests only. No article
        // text, visitor data, cookies, merchant calls or WordPress writes.
        $proof = array(
            'status' => 'NRY_GSC_STAYS_SNIPPET_PROBE_V1',
            'postId' => $id,
            'eligible' => self::eligible() !== null,
            'modifiedMatches' => is_object($post)
                && ($post->post_modified_gmt ?? null) === $record['modified'],
            'sourceMatches' => is_string($raw)
                && hash_equals($record['sha256'], hash('sha256', $raw)),
            'title' => self::$probe['title'],
            'description' => self::$probe['description'],
            'wordpressWrites' => 0,
            'merchantRequests' => 0,
            'syntheticClicks' => 0,
        );
        echo "\n<!-- NRY_GSC_STAYS_SNIPPET_PROBE_V1 "
            .json_encode($proof, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            ." -->\n";
    }
}
NRY_GSC_Revir_Stays_SEO_20261120_V1::boot();
