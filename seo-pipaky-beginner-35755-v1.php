<?php
/**
 * One-shot source-pinned Yoast snippet correction for existing beginner post 35755.
 * No content, title, post status, slug, permalink, CTA, image or affiliate edit.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Beginner_Pipaky_Serp_V1 {
    const ID=35755;
    const SLUG='pipaky-na-ryby-prakticke-rady-pro-zacatecniky';
    const SOURCE_SHA='2ae4c0adb81a0fc7b667e95bb23d9b1e3f4c590b173c98b8e125c48c41c2763f';
    const SOURCE_BYTES=14984;
    const MODIFIED_GMT='2026-10-23 06:35:07';
    const ORIGINAL_TITLE_HASH='e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';
    const ORIGINAL_META_HASH='58633a1d19718b45bd386c83e19393f3ee1a63485efd49da8148bde66c768416';
    const ORIGINAL_META='Pípáky na ryby jsou ideálním pomocníkem pro začátečníky. Získáte praktické rady a tipy, jak nejlépe lovit ryby. Užijte si svou rybářskou výpravu naplno!✔️';
    const NEW_TITLE='Pípák na ryby pro začátečníky: nastavení bez chyb';
    const NEW_META='První pípák na ryby? Jak nastavit citlivost a hlasitost, uložit prut, poznat falešný poplach a otestovat signalizátor před nahozením. Praktický postup.';
    const TITLE_KEY='_yoast_wpseo_title';
    const META_KEY='_yoast_wpseo_metadesc';
    const BACKUP='nry_pipaky_beginner_serp_35755_backup_20261104_v1';
    const RECEIPT='nry_pipaky_beginner_serp_35755_receipt_20261104_v1';
    const LOCK='nry_pipaky_beginner_serp_35755_lock_20261104_v1';

    private static function source_ok($p) {
        return is_object($p) && (int)($p->ID??0)===self::ID
            && ($p->post_type??'')==='post'
            && ($p->post_status??'')==='publish'
            && ($p->post_name??'')===self::SLUG
            && ($p->post_modified_gmt??'')===self::MODIFIED_GMT
            && is_string($p->post_content??null)
            && strlen($p->post_content)===self::SOURCE_BYTES
            && hash('sha256',$p->post_content)===self::SOURCE_SHA
            && get_permalink(self::ID)==='https://na-ryby.eu/'.self::SLUG.'/';
    }
    public static function inspect() {
        if (rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
            || !self::source_ok(get_post(self::ID))) {
            return array('status'=>'blocked','reason'=>'identity_or_exact_source_changed');
        }
        $title=get_post_meta(self::ID,self::TITLE_KEY,true);
        $meta=get_post_meta(self::ID,self::META_KEY,true);
        if (!is_string($title)||!is_string($meta)) {
            return array('status'=>'blocked','reason'=>'metadata_type');
        }
        if ($title===self::NEW_TITLE&&$meta===self::NEW_META) {
            return array('status'=>'already_applied','source_sha'=>self::SOURCE_SHA);
        }
        if (metadata_exists('post',self::ID,self::TITLE_KEY)
            || !metadata_exists('post',self::ID,self::META_KEY)
            || strlen($title)!==0
            || strlen($meta)!==175
            || hash('sha256',$title)!==self::ORIGINAL_TITLE_HASH
            || hash('sha256',$meta)!==self::ORIGINAL_META_HASH
            || $meta!==self::ORIGINAL_META) {
            return array('status'=>'blocked','reason'=>'original_yoast_fields_changed');
        }
        return array('status'=>'ready','source_sha'=>self::SOURCE_SHA,
            'original_title'=>$title,'original_meta'=>$meta,
            'had_title'=>false,'had_meta'=>true);
    }
    private static function rollback($previous) {
        if ($previous['had_title']) update_post_meta(self::ID,self::TITLE_KEY,$previous['original_title']);
        else delete_post_meta(self::ID,self::TITLE_KEY);
        if ($previous['had_meta']) update_post_meta(self::ID,self::META_KEY,$previous['original_meta']);
        else delete_post_meta(self::ID,self::META_KEY);
        return !metadata_exists('post',self::ID,self::TITLE_KEY)
            && get_post_meta(self::ID,self::TITLE_KEY,true)===''
            && metadata_exists('post',self::ID,self::META_KEY)
            && get_post_meta(self::ID,self::META_KEY,true)===$previous['original_meta'];
    }
    public static function execute() {
        $before=self::inspect();
        if ($before['status']==='already_applied') {
            return array('status'=>'already_applied_no_write','writes'=>0);
        }
        if ($before['status']!=='ready') {
            return array('status'=>'blocked_no_writes','reason'=>$before['reason'],'writes'=>0);
        }
        $snapshot=array('id'=>self::ID,'source_sha'=>self::SOURCE_SHA,
            'modified_gmt'=>self::MODIFIED_GMT,
            'original_title'=>$before['original_title'],
            'original_meta'=>$before['original_meta'],
            'had_title'=>false,'had_meta'=>true);
        if (!add_option(self::BACKUP,$snapshot,'','no')
            && get_option(self::BACKUP,null)!==$snapshot) {
            return array('status'=>'backup_conflict_no_writes','writes'=>0);
        }
        if (get_option(self::BACKUP,null)!==$snapshot
            || self::inspect()['status']!=='ready') {
            return array('status'=>'backup_or_concurrent_source_changed','writes'=>0);
        }
        $ok1=update_post_meta(self::ID,self::TITLE_KEY,self::NEW_TITLE);
        $ok2=update_post_meta(self::ID,self::META_KEY,self::NEW_META);
        $check=self::inspect();
        $verified=($ok1!==false && $ok2!==false
            && $check['status']==='already_applied'
            && metadata_exists('post',self::ID,self::TITLE_KEY)
            && metadata_exists('post',self::ID,self::META_KEY));
        if (!$verified) {
            $restored=self::rollback($snapshot);
            return array('status'=>$restored?'rolled_back_verified':'uncertain_rollback',
                'writes'=>0);
        }
        return array('status'=>'applied_and_verified','writes'=>2,
            'post_id'=>self::ID,'changed_fields'=>array(self::TITLE_KEY,self::META_KEY),
            'backup_present'=>true,'source_sha_preserved'=>self::SOURCE_SHA);
    }
    public static function maybe_execute() {
        // No manual run, no new remote writer or public action endpoint.
        // Only this immutable reviewed source and exactly two metadata fields.
        $done=get_option(self::RECEIPT,null);
        if (is_array($done)&&isset($done['status'])) return;
        $lock=array('created_gmt'=>gmdate('c'),'batch'=>'pipaky-beginner-35755-serp-20261104-v1');
        if (!add_option(self::LOCK,$lock,'','no')) return;
        $result=self::execute();
        add_option(self::RECEIPT,$result,'','no');
        // A stale lock fails closed; no concurrent writers can silently retry.
    }
}
add_action('wp_loaded',array('NRY_Beginner_Pipaky_Serp_V1','maybe_execute'),36);
