<?php
/**
 * Na-Ryby #49275: one-field source-pinned sonar practical SERP improvement.
 *
 * Historically UPDATE_EXPAND_REVIEW in the full 15,966 WP lifecycle (#1641),
 * not KEEP/PROTECT; currently actual final 90d GSC 118 impressions / 0 clicks
 * (#1636), modified 2026-10-16 outside 14d editorial cooldown.
 * Exact public Yoast description/unchanged title verified on 2026-11-09 via
 * real GET-only echolot workflow PR #140.
 *
 * NO post body/title/slug/canonical/links/image changes; ONE Yoast description.
 * Failed preflight or concurrent drift => 0 metadata writes. Immutable backup,
 * lock, same-source readback, rollback on partial verification.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Sonar_Practical_Serp_49275_V1 {
    const ID = 49275;
    const SLUG = 'jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun';
    const SOURCE_SHA = '4c57249b88928376dc24a41885b5e9397069e5128d1c462e0fc4b50f1c560330';
    const SOURCE_BYTES = 12027;
    const MODIFIED_GMT = '2026-10-16 11:38:12';
    const ORIGINAL_META = 'Získejte lepší přehled o podvodním světě s tím, jak správně používat echolot a sonar na vaší lodi nebo člunu. Naučte se efektivněji lovit ryby a vyhnout se nebezpečným překážkám.✔️';
    const NEW_META = 'Jak používat echolot na lodi: nastavení citlivosti, umístění sondy, čtení dna a časté chyby při lovu z člunu. Praktické rady a navazující výběr sonaru.';
    const META_KEY = '_yoast_wpseo_metadesc';
    const TITLE_KEY = '_yoast_wpseo_title';
    const BACKUP = 'nry_sonar_49275_serp_backup_20261109_v1';
    const RECEIPT = 'nry_sonar_49275_serp_receipt_20261109_v1';
    const LOCK = 'nry_sonar_49275_serp_lock_20261109_v1';

    private static function source_ok($p) {
        return function_exists('home_url')
            && rtrim(home_url('/'), '/') === 'https://na-ryby.eu'
            && is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post'
            && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED_GMT
            && is_string($p->post_content ?? null)
            && strlen($p->post_content) === self::SOURCE_BYTES
            && hash('sha256', $p->post_content) === self::SOURCE_SHA
            && get_permalink(self::ID) === 'https://na-ryby.eu/' . self::SLUG . '/';
    }
    public static function inspect() {
        if (!self::source_ok(get_post(self::ID))) {
            return array('status'=>'blocked', 'reason'=>'exact_article_source_drift');
        }
        // Preserve all existing editorial SEO title choices.
        $title = get_post_meta(self::ID,self::TITLE_KEY,true);
        $meta = get_post_meta(self::ID,self::META_KEY,true);
        if (!is_string($title) || !is_string($meta)) {
            return array('status'=>'blocked','reason'=>'metadata_type');
        }
        if (metadata_exists('post',self::ID,self::TITLE_KEY) || $title !== '') {
            return array('status'=>'blocked','reason'=>'yoast_title_customized');
        }
        if (!metadata_exists('post',self::ID,self::META_KEY)) {
            return array('status'=>'blocked','reason'=>'original_description_unstored');
        }
        if ($meta === self::NEW_META) {
            return array('status'=>'already_applied','source_sha256'=>self::SOURCE_SHA);
        }
        if ($meta !== self::ORIGINAL_META) {
            return array('status'=>'blocked','reason'=>'original_description_changed');
        }
        return array('status'=>'ready','original_meta'=>$meta);
    }
    public static function execute() {
        $pre=self::inspect();
        if ($pre['status']==='already_applied')
            return array('status'=>'already_applied_no_write','writes'=>0);
        if ($pre['status']!=='ready')
            return array('status'=>'blocked_no_writes','writes'=>0,'reason'=>$pre['reason']);
        $snapshot=array(
            'post_id'=>self::ID,'source_sha256'=>self::SOURCE_SHA,
            'source_bytes'=>self::SOURCE_BYTES,'modified_gmt'=>self::MODIFIED_GMT,
            'original_meta'=>$pre['original_meta'],'original_title_present'=>false,
            'original_title'=>'','meta_key'=>self::META_KEY
        );
        if (!add_option(self::BACKUP,$snapshot,'','no')
            && get_option(self::BACKUP,null)!==$snapshot) {
            return array('status'=>'backup_conflict_no_writes','writes'=>0);
        }
        if (get_option(self::BACKUP,null)!==$snapshot
            || self::inspect()['status']!=='ready') {
            return array('status'=>'prewrite_source_changed','writes'=>0);
        }
        $result=update_post_meta(self::ID,self::META_KEY,self::NEW_META);
        $post=get_post(self::ID);
        if ($result!==false
            && self::source_ok($post)
            && self::inspect()['status']==='already_applied'
            && get_post_meta(self::ID,self::META_KEY,true)===self::NEW_META) {
            return array('status'=>'applied_and_verified','writes'=>1,
                'post_id'=>self::ID,'meta_key'=>self::META_KEY,
                'content_sha_preserved'=>self::SOURCE_SHA,
                'backup_present'=>true);
        }
        // Zero ambiguity: if native WP write/readback fails, restore one field.
        update_post_meta(self::ID,self::META_KEY,$snapshot['original_meta']);
        $restored=self::source_ok(get_post(self::ID))
            && get_post_meta(self::ID,self::META_KEY,true)===$snapshot['original_meta'];
        return array('status'=>$restored?'rolled_back_verified':'rollback_uncertain',
            'writes'=>0);
    }
    public static function maybe_execute() {
        if (!function_exists('get_option') || !function_exists('add_option')) return;
        $receipt=get_option(self::RECEIPT,null);
        if (is_array($receipt)&&isset($receipt['status'])) return;
        $lock=array('batch'=>'sonar-practical-serp-49275-20261109-v1',
            'created_gmt'=>gmdate('c'));
        if (!add_option(self::LOCK,$lock,'','no')) return;
        $result=self::execute();
        add_option(self::RECEIPT,$result,'','no');
    }
}
if (function_exists('add_action')) {
    add_action('wp_loaded',
        array('NRY_Sonar_Practical_Serp_49275_V1','maybe_execute'),39);
}
