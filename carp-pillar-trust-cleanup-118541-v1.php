<?php
/**
 * Exact-source single-post cleanup for public carp-rod pillar #118541.
 * Owner-approved on 2026-11-09 after GET-only source proof #42634218341.
 * NO generic WordPress REST writes or arbitrary post/URL selection.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Carp_Pillar_Trust_118541 {
    const ID=118541;
    const SLUG='jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika';
    const URL='https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/';
    const SOURCE_SHA='9f6500edcc12b11c391aa05b89bd70f16b2ac7d3172ce19974c651a4e3af5b9c';
    const SOURCE_BYTES=48250;
    const SOURCE_GMT='2026-03-21 11:02:25';
    const MARKER='data-nry-carp-pillar-trust="118541-20261109-v1"';
    const BACKUP='nry_carp_pillar_118541_20261109_source';
    const LOCK='nry_carp_pillar_118541_20261109_lock';
    const RECEIPT='nry_carp_pillar_118541_20261109_receipt';
    const OLD_HEADING='Interní rozcestník: kam pokračovat podle toho, co řešíte';
    const NEW_HEADING='Kam pokračovat podle toho, co řešíte';
    const OLD_PARAGRAPH='Tady je přesně místo, kde má pillar článek fungovat jako rozcestník celé tématické clustery.';
    const NEW_PARAGRAPH='Vyberte si z navazujících průvodců jen téma, které potřebujete vyřešit. Jiný prut dává smysl pro první nákup, jiný pro dlouhé náhozy, mobilní lov nebo krmení raketou.';
    public static function boot() {
        add_action('wp_loaded',array(__CLASS__,'release'),46);
    }
    /** Candidate is derived deterministically from a single exact approved source. */
    public static function candidate($raw,$enforceSource=true) {
        if (!is_string($raw)
            || ($enforceSource && (strlen($raw)!==self::SOURCE_BYTES
                || !hash_equals(self::SOURCE_SHA,hash('sha256',$raw)))))
            return null;
        $fence=str_repeat(chr(96),3);
        if (substr_count($raw,self::OLD_HEADING)!==1
            || substr_count($raw,self::OLD_PARAGRAPH)!==1
            || substr_count($raw,$fence)!==1
            || strpos($raw,self::MARKER)!==false) return null;
        $candidate=str_replace(self::OLD_HEADING,
            '<span hidden '.self::MARKER.'></span>'.self::NEW_HEADING,$raw);
        $candidate=str_replace(self::OLD_PARAGRAPH,self::NEW_PARAGRAPH,$candidate);
        $candidate=str_replace($fence,'',$candidate);
        if (substr_count($candidate,self::MARKER)!==1
            || strpos($candidate,self::OLD_HEADING)!==false
            || strpos($candidate,self::OLD_PARAGRAPH)!==false
            || strpos($candidate,$fence)!==false
            || abs(strlen($candidate)-strlen($raw))>500) return null;
        // Every original merchant/internal destination, image and shortcode
        // must be retained byte-for-byte and in source order.
        foreach(array(
            '~\\bhref\\s*=\\s*(["\\x27])(.*?)\\1~is',
            '~\\bsrc\\s*=\\s*(["\\x27])(.*?)\\1~is',
            '~\\[[a-z][^\\]\\r\\n]*\\]~i'
        ) as $rx) {
            preg_match_all($rx,$raw,$before);
            preg_match_all($rx,$candidate,$after);
            if (($before[0]??array())!==($after[0]??array())) return null;
        }
        return $candidate;
    }
    private static function record($state,$candidateSha='') {
        update_option(self::RECEIPT,array(
            'state'=>$state,'at'=>gmdate('c'),
            'sourceSha'=>self::SOURCE_SHA,'candidateSha'=>$candidateSha),false);
    }
    private static function write($content) {
        if (!function_exists('wp_slash') || !function_exists('has_filter')
            || !function_exists('remove_filter') || !function_exists('add_filter')) {
            return new WP_Error('writer_not_available');
        }
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
                'post_content'=>wp_slash($content)),true);
        } finally {
            foreach($removed as $entry) add_filter($entry[0],$entry[1],$entry[2]);
        }
    }
    public static function release() {
        if (rtrim(home_url('/'),'/')!=='https://na-ryby.eu') return;
        $p=get_post(self::ID);
        if (!is_object($p) || (int)($p->ID??0)!==self::ID
            || ($p->post_type??'')!=='post'
            || ($p->post_status??'')!=='publish'
            || ($p->post_name??'')!==self::SLUG
            || get_permalink(self::ID)!==self::URL
            || !is_string($p->post_content??null)
            || strlen($p->post_content)!==self::SOURCE_BYTES
            || !hash_equals(self::SOURCE_SHA,hash('sha256',$p->post_content))
            || ($p->post_modified_gmt??'')!==self::SOURCE_GMT) return;
        $lock=wp_generate_uuid4();
        if (!add_option(self::LOCK,$lock,'','no')) return;
        try {
            $p=get_post(self::ID);
            if (!is_object($p)
                || !is_string($p->post_content??null)
                || !hash_equals(self::SOURCE_SHA,hash('sha256',$p->post_content))
                || $p->post_modified_gmt!==self::SOURCE_GMT) return;
            $source=$p->post_content;
            $candidate=self::candidate($source);
            if ($candidate===null) {self::record('candidate_not_proven_no_write');return;}
            $candidateSha=hash('sha256',$candidate);
            $backup=array('postId'=>self::ID,'sourceSha'=>self::SOURCE_SHA,'source'=>$source);
            if (!add_option(self::BACKUP,$backup,'','no')
                && get_option(self::BACKUP,null)!==$backup) {
                self::record('immutable_backup_conflict_no_write');return;
            }
            if (get_option(self::BACKUP,null)!==$backup) {
                self::record('backup_unreadable_no_write');return;
            }
            $fresh=get_post(self::ID);
            if (!is_object($fresh) || !is_string($fresh->post_content??null)
                || !hash_equals(self::SOURCE_SHA,hash('sha256',$fresh->post_content))
                || $fresh->post_modified_gmt!==self::SOURCE_GMT) {
                self::record('concurrent_editor_no_write');return;
            }
            $res=self::write($candidate);
            $after=get_post(self::ID);
            if (!is_wp_error($res) && (int)$res===self::ID
                && is_object($after)
                && is_string($after->post_content??null)
                && hash_equals($candidateSha,hash('sha256',$after->post_content))) {
                self::record('applied_and_verified_local',$candidateSha);
            } else {
                // Unknown post-write state: never blindly overwrite an
                // editor's concurrent changes or fake a successful release.
                self::record('post_write_uncertain_manual_review',$candidateSha);
            }
        } catch (\Throwable $ex) {
            self::record('unexpected_exception_manual_review');
        } finally {
            if (get_option(self::LOCK,null)===$lock) delete_option(self::LOCK);
        }
    }
}
NRY_Carp_Pillar_Trust_118541::boot();
