<?php
/**
 * Source-bound editorial replacement for one existing published buyer guide.
 * Owner authorization: Na-Ryby 2026-11-09. Raw old source captured privately
 * by Prometheus Core issue #1588 / Actions #42636851885 (never committed).
 *
 * No generic content API. This is an idempotent one-time WordPress-local edit,
 * not another publisher service, plugin installation or REST writer.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Receiver_Buyer_46573_V1 {
    const ID=46573;
    const SLUG='pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare';
    const URL='https://na-ryby.eu/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/';
    const SOURCE_SHA='4556c2db67cdae9dacc7b60300abada57e29a7d7fb1bf93b88e253b6222f62f4';
    const SOURCE_BYTES=10229;
    const SOURCE_GMT='2023-05-12 15:13:13';
    const CANDIDATE_SHA='f5399d19338b8467e34981a90a245b2d36f1f9ab3ec4892c90d218663d542228';
    const CANDIDATE_BYTES=8633;
    const MARKER='data-nry-buyer-guide="receiver-46573-problem-first-20261109-v1"';
    const CANDIDATE_FILE='editorial/receiver-46573-decision-guide-20261109.html';
    const BACKUP='nry_receiver_46573_source_backup_20261109_v1';
    const LOCK='nry_receiver_46573_one_shot_lock_20261109_v1';
    const RECEIPT='nry_receiver_46573_release_receipt_20261109_v1';
    public static function boot() {
        add_action('wp_loaded',array(__CLASS__,'release'),47);
    }
    public static function candidate() {
        $path=__DIR__.'/'.self::CANDIDATE_FILE;
        $candidate=@file_get_contents($path);
        if (!is_string($candidate)
            || strlen($candidate)!==self::CANDIDATE_BYTES
            || !hash_equals(self::CANDIDATE_SHA,hash('sha256',$candidate))
            || substr_count($candidate,self::MARKER)!==1
            || strpos($candidate,'<script')!==false
            || strpos($candidate,'DOPLNIT-URL-')!==false
            || strpos($candidate,'Pípáky na ryby s příposlechem fungují na principu zesílení zvuku')!==false
            || substr_count($candidate,'[su_button url="https://na-ryby.eu/signalizatory"')!==1)
            return null;
        $paths=array(
            'https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/',
            'https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/',
            'https://na-ryby.eu/pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu/'
        );
        if (preg_match_all('/<a\\s+[^>]*\\bhref="([^"]+)"/i',$candidate,$links)===false
            || count($links[1])!==3) return null;
        sort($paths); $observed=$links[1]; sort($observed);
        if ($paths!==$observed) return null;
        if (substr_count($candidate,'<h2>')<5
            || substr_count($candidate,'<h3>')<4
            || substr_count($candidate,'<table>')!==1
            || substr_count($candidate,'<ol>')!==2) return null;
        return $candidate;
    }
    private static function receipt($state,$candidateSha='') {
        update_option(self::RECEIPT,array(
            'state'=>$state,'postId'=>self::ID,
            'at'=>gmdate('c'),'sourceSha'=>self::SOURCE_SHA,
            'candidateSha'=>$candidateSha),false);
    }
    private static function write($candidate) {
        if (!function_exists('wp_slash') || !function_exists('has_filter')
            || !function_exists('remove_filter') || !function_exists('add_filter'))
            return new WP_Error('writer_unavailable');
        $removed=array();
        foreach(array(
            array('content_save_pre','wp_filter_post_kses'),
            array('content_save_pre','wp_filter_global_styles_post'),
            array('content_filtered_save_pre','wp_filter_post_kses'),
            array('content_filtered_save_pre','wp_filter_global_styles_post')
        ) as $entry) {
            $priority=has_filter($entry[0],$entry[1]);
            if ($priority!==false) {
                remove_filter($entry[0],$entry[1],$priority);
                $removed[]=array($entry[0],$entry[1],$priority);
            }
        }
        try {
            return wp_update_post(array('ID'=>self::ID,
                'post_content'=>wp_slash($candidate)),true);
        } finally {
            foreach($removed as $entry) add_filter($entry[0],$entry[1],$entry[2]);
        }
    }
    private static function approved_current($p) {
        return is_object($p)
            && (int)($p->ID??0)===self::ID
            && ($p->post_type??'')==='post'
            && ($p->post_status??'')==='publish'
            && ($p->post_name??'')===self::SLUG
            && get_permalink(self::ID)===self::URL
            && ($p->post_modified_gmt??'')===self::SOURCE_GMT
            && is_string($p->post_content??null)
            && strlen($p->post_content)===self::SOURCE_BYTES
            && hash_equals(self::SOURCE_SHA,hash('sha256',$p->post_content));
    }
    public static function release() {
        if (rtrim(home_url('/'),'/')!=='https://na-ryby.eu') return;
        if (!self::approved_current(get_post(self::ID))) return;
        $token=wp_generate_uuid4();
        if (!add_option(self::LOCK,$token,'','no')) return;
        try {
            $p=get_post(self::ID);
            if (!self::approved_current($p)) return;
            $candidate=self::candidate();
            if ($candidate===null) {
                self::receipt('blocked_candidate_sha_or_integrity');return;
            }
            $original=array('postId'=>self::ID,
                'sha256'=>self::SOURCE_SHA,'source'=>$p->post_content);
            if (!add_option(self::BACKUP,$original,'','no')
                && get_option(self::BACKUP,null)!==$original) {
                self::receipt('backup_conflict_no_write');return;
            }
            if (get_option(self::BACKUP,null)!==$original) {
                self::receipt('backup_readback_blocked');return;
            }
            if (!self::approved_current(get_post(self::ID))) {
                self::receipt('concurrent_edit_no_write');return;
            }
            $updated=self::write($candidate);
            $after=get_post(self::ID);
            if (!is_wp_error($updated) && (int)$updated===self::ID
                && is_object($after)
                && is_string($after->post_content??null)
                && hash_equals(self::CANDIDATE_SHA,hash('sha256',$after->post_content))) {
                self::receipt('applied_and_verified_local',self::CANDIDATE_SHA);
            } else {
                // Unknown post-write state. Keep immutable backup; no blind
                // overwrite that could destroy a concurrent editor's change.
                self::receipt('post_write_uncertain_requires_review',self::CANDIDATE_SHA);
            }
        } catch (\Throwable $e) {
            self::receipt('exception_requires_review');
        } finally {
            if (get_option(self::LOCK,null)===$token) delete_option(self::LOCK);
        }
    }
}
NRY_Receiver_Buyer_46573_V1::boot();
