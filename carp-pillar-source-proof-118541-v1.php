<?php
/**
 * Read-only, one-post exact-source diagnostics for the publicly published carp
 * rod decision pillar. Returns hashes/counts only. No HTML, auth secrets,
 * merchant destinations, cookies, admin state or new WordPress write endpoints.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Carp_Pillar_Source_118541 {
    const ID=118541;
    const SLUG='jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika';
    const URL='https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/';
    const MARKDOWN_FENCE='&#96;&#96;&#96;';
    const EDITORIAL_PHRASE='Tady je přesně místo, kde má pillar článek fungovat jako rozcestník celé tématické clustery.';
    const EDITORIAL_HEADING='Interní rozcestník: kam pokračovat podle toho, co řešíte';
    const OLD_FENCE='\x60\x60\x60';
    public static function boot() {
        add_action('template_redirect',array(__CLASS__,'handle'),23);
    }
    public static function snapshot() {
        $p=get_post(self::ID);
        if (!is_object($p) || (int)($p->ID??0)!==self::ID
            || ($p->post_type??'')!=='post'
            || ($p->post_status??'')!=='publish'
            || ($p->post_name??'')!==self::SLUG
            || !is_string($p->post_content??null)
            || strlen($p->post_content)<1000
            || get_permalink(self::ID)!==self::URL) {
            return array('schema'=>1,'state'=>'BLOCKED_EXACT_POST_IDENTITY',
                'postId'=>self::ID,'publicationAuthorized'=>false,'wordpressWrites'=>0);
        }
        $raw=$p->post_content;
        $labels=array(
            'editorialPhraseExact'=>substr_count($raw,self::EDITORIAL_PHRASE),
            'editorialHeadingExact'=>substr_count($raw,self::EDITORIAL_HEADING),
            'backtickFence'=>substr_count($raw,'```'),
            'curlyQuoteBacktick'=>substr_count($raw,'„`'),
            'htmlCodeFenceEntity'=>substr_count($raw,self::MARKDOWN_FENCE),
            'contentGuideLinks'=>substr_count($raw,'https://na-ryby.eu/nejlepsi-kaprove-pruty-do-3-000-kc-srovnani-a-vyber/'),
        );
        return array('schema'=>1,'site'=>'https://na-ryby.eu',
            'state'=>'SOURCE_ONLY_NO_PUBLICATION','postId'=>self::ID,
            'canonical'=>self::URL,'modifiedGmt'=>$p->post_modified_gmt,
            'sourceSha256'=>hash('sha256',$raw),
            'sourceBytes'=>strlen($raw),'literalCounts'=>$labels,
            'publicationAuthorized'=>false,'wordpressWrites'=>0);
    }
    public static function handle() {
        if (!isset($_GET['nry_carp_pillar_source_proof'])
            || $_GET['nry_carp_pillar_source_proof']!=='1'
            || !function_exists('is_page') || !is_page('poradce-vybavy')
            || rtrim(home_url('/'),'/')!=='https://na-ryby.eu') return;
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        echo wp_json_encode(self::snapshot());
        exit;
    }
}
NRY_Carp_Pillar_Source_118541::boot();
