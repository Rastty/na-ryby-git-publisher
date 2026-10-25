<?php
/**
 * One-URL controlled Yoast SERP metadata experiment.
 * On-server source read, admin-only Apply, backup, fail-closed and readback.
 * Never edits post_content, post_title, post_name, canonicals or existing hrefs.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Seo_Snippet_Pilot {
    const ID = 42401;
    const SLUG = 'jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne';
    const MODIFIED_GMT = '2026-10-23 06:34:54';
    const ORIGINAL_TITLE = 'Jak používat pípáky na ryby správně: nastavení, umístění a chyby, které zbytečně berou záběry';
    const ORIGINAL_META = 'Získejte úspěch při rybolovu s naším jednoduchým návodem na využívání pípáků! Zjistěte, jak účinně a bez problémů chytit ryby. Přečtěte si naše tipy a zkušenosti.✔️';
    const NEW_TITLE = 'Pípáky na ryby: jak vybrat, nastavit a používat';
    const NEW_META = 'Pípáky na ryby jednoduše: jak nastavit citlivost, kam hlásič umístit a kdy se vyplatí příposlech. Praktické rady i výběr správné sady.';
    const TITLE_KEY = '_yoast_wpseo_title';
    const META_KEY = '_yoast_wpseo_metadesc';
    const BACKUP = 'nry_pipaky_ctr_42401_backup_v1';
    const RECEIPT = 'nry_pipaky_ctr_42401_receipt_v1';

    private static function same_post($p) {
        return $p && (int)$p->ID === self::ID && $p->post_type === 'post'
            && $p->post_status === 'publish' && $p->post_name === self::SLUG
            && is_string($p->post_content) && strlen($p->post_content) >= 1000;
    }
    public static function inspect() {
        $p = get_post(self::ID);
        if (!self::same_post($p)) { return array('status'=>'blocked','reason'=>'identity'); }
        $oldTitle = get_post_meta(self::ID, self::TITLE_KEY, true);
        $oldMeta = get_post_meta(self::ID, self::META_KEY, true);
        if (!is_string($oldTitle) || !is_string($oldMeta)) {
            return array('status'=>'blocked','reason'=>'metadata_type');
        }
        $already = $oldTitle === self::NEW_TITLE && $oldMeta === self::NEW_META;
        $row = array('post_id'=>self::ID, 'source_sha'=>hash('sha256', $p->post_content),
            'original_modified_gmt'=>$p->post_modified_gmt,
            'old_title'=>$oldTitle, 'old_meta'=>$oldMeta,
            'had_title'=>metadata_exists('post', self::ID, self::TITLE_KEY),
            'had_meta'=>metadata_exists('post', self::ID, self::META_KEY));
        if ($already) { return array_merge(array('status'=>'already_applied'),$row); }
        if ($p->post_modified_gmt !== self::MODIFIED_GMT) {
            return array('status'=>'blocked','reason'=>'post_modified_gmt');
        }
        if (!in_array($oldTitle, array('', '%title%', self::ORIGINAL_TITLE), true)
            || !in_array($oldMeta, array('', self::ORIGINAL_META), true)) {
            return array('status'=>'blocked','reason'=>'original_meta_drift');
        }
        return array_merge(array('status'=>'ready'),$row);
    }
    private static function restore_meta($snap) {
        foreach (array(
            array(self::TITLE_KEY,$snap['old_title'],$snap['had_title']),
            array(self::META_KEY,$snap['old_meta'],$snap['had_meta'])
        ) as $row) {
            if ($row[2]) { update_post_meta(self::ID,$row[0],$row[1]); }
            else { delete_post_meta(self::ID,$row[0]); }
        }
        return get_post_meta(self::ID,self::TITLE_KEY,true)===$snap['old_title']
            && get_post_meta(self::ID,self::META_KEY,true)===$snap['old_meta'];
    }
    public static function execute() {
        $pre=self::inspect();
        if ($pre['status']==='already_applied') {
            return array('status'=>'applied_and_verified','writes'=>0,'already_applied'=>1);
        }
        if ($pre['status']!=='ready') {
            return array('status'=>'blocked_no_writes','writes'=>0,'reason'=>$pre['reason']);
        }
        // Backup originals before either metadata write; never replace the first
        // immutable snapshot under the fixed release ID.
        $snapshot=array(
            'post_id'=>self::ID, 'source_sha'=>$pre['source_sha'],
            'modified_gmt'=>$pre['original_modified_gmt'],
            'old_title'=>$pre['old_title'],'old_meta'=>$pre['old_meta'],
            'had_title'=>$pre['had_title'],'had_meta'=>$pre['had_meta']
        );
        if (!add_option(self::BACKUP,$snapshot,'','no')
            && get_option(self::BACKUP,null)!==$snapshot) {
            return array('status'=>'backup_conflict_no_writes','writes'=>0);
        }
        if (get_option(self::BACKUP,null)!==$snapshot) {
            return array('status'=>'backup_failed_no_writes','writes'=>0);
        }
        $fresh=self::inspect();
        if ($fresh['status']!=='ready' || $fresh['source_sha']!==$pre['source_sha']
            || $fresh['old_title']!==$pre['old_title']
            || $fresh['old_meta']!==$pre['old_meta']) {
            return array('status'=>'concurrent_change_no_writes','writes'=>0);
        }
        $ok1=update_post_meta(self::ID,self::TITLE_KEY,self::NEW_TITLE);
        $ok2=update_post_meta(self::ID,self::META_KEY,self::NEW_META);
        $p=get_post(self::ID);
        $verified=$ok1!==false && $ok2!==false
            && self::same_post($p)
            && hash('sha256',$p->post_content)===$pre['source_sha']
            && $p->post_modified_gmt===$pre['original_modified_gmt']
            && get_post_meta(self::ID,self::TITLE_KEY,true)===self::NEW_TITLE
            && get_post_meta(self::ID,self::META_KEY,true)===self::NEW_META;
        if (!$verified) {
            $restored=self::restore_meta($snapshot);
            return array('status'=>$restored?'rolled_back_verified':'uncertain_restore',
                'writes'=>0);
        }
        return array('status'=>'applied_and_verified','writes'=>1,'post_ids'=>array(self::ID),
            'changed_fields'=>array(self::TITLE_KEY,self::META_KEY),
            'content_sha_preserved'=>$pre['source_sha']);
    }
    public static function register_menu() {
        add_management_page('Na-Ryby SEO CTR pilot','Na-Ryby SEO CTR pilot','manage_options',
            'nry-seo-ctr-pilot',array(__CLASS__,'page'));
    }
    public static function page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $p=self::inspect();
        $last=get_option(self::RECEIPT,array());
        echo '<div class="wrap"><h1>Na-Ryby SEO CTR pilot – článek 42401</h1>';
        echo '<p>Mění pouze SEO titulek a meta description Yoast. URL, H1, článek, canonical ani affiliate odkazy se nemění. Před zápisem se uloží záloha. Bez WPVibe.</p>';
        echo '<p>Kontrola: <strong>'.esc_html($p['status']).'</strong>';
        if(isset($p['reason'])) {echo ' – '.esc_html($p['reason']);}
        echo '</p>';
        echo '<p>Nový SEO titulek: <strong>'.esc_html(self::NEW_TITLE).'</strong></p>';
        echo '<p>Nový meta popis: '.esc_html(self::NEW_META).'</p>';
        if(is_array($last)&&isset($last['status']))echo '<p>Poslední běh: <strong>'.esc_html($last['status']).'</strong></p>';
        if($p['status']==='ready') {
            echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
            echo '<input type="hidden" name="action" value="nry_seo_ctr_pilot_apply">';
            wp_nonce_field('nry_seo_ctr_pilot_apply');
            submit_button('Použít ověřená SEO metadata pouze pro #42401');
            echo '</form>';
        } else {
            echo '<p>Při blocked nepokračujte a neobcházejte kontrolu zdroje.</p>';
        }
        echo '</div>';
    }
    public static function handle_apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_seo_ctr_pilot_apply');
        update_option(self::RECEIPT,self::execute(),false);
        wp_safe_redirect(admin_url('tools.php?page=nry-seo-ctr-pilot'));
        exit;
    }
}
add_action('admin_menu',array('NRY_Seo_Snippet_Pilot','register_menu'));
add_action('admin_post_nry_seo_ctr_pilot_apply',array('NRY_Seo_Snippet_Pilot','handle_apply'));
