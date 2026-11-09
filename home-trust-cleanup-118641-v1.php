<?php
/**
 * Source-locked single-page P0 homepage cleanup, 2026-11-09.
 * The bounded source proof is private Core run #42615373398, issue #1580.
 * Only the already published WordPress FRONT PAGE #118641 is eligible.
 * Zero arbitrary URL writes, no REST write endpoint, no credentials.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Home_Trust_118641 {
    const ID = 118641;
    const SOURCE_SHA = 'c53c62c61285dcc23a0a4a657bdd4a80fc44c1cacdf8cbe1a8a9e375cf30559d';
    const CANDIDATE_SHA = '80e86d2c85d495a2502737243b4887b6ef48098906bc0cf958e08ec29dc6574f';
    const MARKER = '<span hidden data-nry-home-trust="source-bound-118641-20261109-v1"></span>';
    const BACKUP = 'nry_home_trust_118641_20261109_original';
    const LOCK = 'nry_home_trust_118641_20261109_lock';
    const RECEIPT = 'nry_home_trust_118641_20261109_receipt';
    public static function boot() {
        add_action('wp_loaded',array(__CLASS__,'run'),45);
    }
    private static function replacements() {
        return array(
            array('Vizuální placeholder pro budoucí expanzi webu směrem k výpravám, pobytům a tipům k vodě.','Tipy pro plánování výpravy a výběr vody. U soukromých revírů vždy ověřte aktuální pravidla a rezervace.'),
            array('Tohle jsou URL, které mají být na stránce vidět nejvíc.','Vyberte si průvodce podle problému, který řešíte, a snadněji se rozhodněte před nákupem.'),
            array('Prostor pro budoucí přehledy soukromých revírů, lokalit a inspirace podle typu vody.','Hledáte vodu pro další výpravu? Začněte přehledem soukromých revírů a ověřte si pravidla konkrétní lokality.'),
            array('href="https://na-ryby.eu/category/soukrome-rybarske-reviry/">Hlavní landing revírů','href="https://na-ryby.eu/category/soukrome-rybarske-reviry/">Přehled soukromých revírů'),
            array('href="DOPLNIT-URL-BIVAKY-HUB">Bivaky – hlavní rozcestník','href="https://na-ryby.eu/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/">Jak vybrat bivak'),
            array('href="DOPLNIT-URL-ECHOLOTY-HUB">Echoloty – hlavní rozcestník','href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/">Jak vybrat echolot'),
            array('href="DOPLNIT-URL-PRUTY-HUB">Kaprové pruty – hlavní rozcestník','href="https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/">Jak vybrat kaprový prut'),
            array('href="DOPLNIT-URL-LEHATKA-HUB">Lehátka – hlavní rozcestník','href="https://na-ryby.eu/rybarska-lehatka-kompletni-pruvodce-vyberem-pro-rybare/">Jak vybrat rybářské lehátko'),
            array('<li><a href="DOPLNIT-URL-REVIRY-2">Soukromé revíry</a></li>',''),
            array('<li><a href="DOPLNIT-URL-REVIRY-3">Inspirace k vodě</a></li>',''),
            array('Budoucí vizuální vstup do sekce s pobyty u vody, chatami a pohodlnějšími výpravami.','Chystáte se na více dní u vody? Pomůžeme vybrat přístřešek a předejít častým problémům při nocování.'),
            array('<h3>Chaty a rybářské pobyty</h3>','<h3>Výprava na více dní</h3>'),
            array('href="DOPLNIT-URL-UBYTOVANI-1">Landing ubytování','href="https://na-ryby.eu/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/">Výběr rybářského bivaku'),
            array('href="DOPLNIT-URL-UBYTOVANI-2">Chaty u vody','href="https://na-ryby.eu/brolly-vs-bivak-co-vybrat-na-vikend-a-co-na-tyden/">Brolly, nebo bivak?'),
            array('href="DOPLNIT-URL-UBYTOVANI-3">Rybářské pobyty','href="https://na-ryby.eu/kondenzace-vody-v-bivaku-proc-vznika-a-jak-ji-zabranit/">Jak předejít kondenzaci'),
            array('<h3>Další tematické huby</h3>','<h3>Echolot podle způsobu lovu</h3>'),
            array('Zde budeme později přidávat nové clustery, například boilies, recepty, tipy na víkend nebo sezonní témata.','Rozhodujete se mezi echolotem na loď, menší plavidlo nebo omezený rozpočet? Začněte podle toho, kde budete lovit.'),
            array('href="DOPLNIT-URL-BUDOUCI-1">Budoucí landing page','href="https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/">Jak vybrat echolot'),
            array('href="DOPLNIT-URL-BUDOUCI-2">Další tematická sekce','href="https://na-ryby.eu/nejlepsi-echoloty-pro-lov-z-lodi-lowrance-vs-garmin-vs-humminbird/">Echoloty pro lov z lodě'),
            array('href="DOPLNIT-URL-BUDOUCI-3">Nový obsahový hub','href="https://na-ryby.eu/nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz/">Echolot do 15 000 Kč'),
            array('<span class="nr-feature-tag">Připravujeme</span>','<span class="nr-feature-tag">Echoloty</span>'),
            array('<span class="nr-kicker" style="color: #9ad7e4;">Připravovaná expanze</span>','<span class="nr-kicker" style="color: #9ad7e4;">Na další výpravu</span>'),
            array('<h2>Rybářské revíry, pobyty a ubytování u vody</h2>','<h2>Vyberte revír a připravte se k vodě</h2>'),
            array('.....','Začněte výběrem vody a průvodcem pro nocování nebo hledání ryb. Používejte ověřené postupy, nikoli nedokončené odkazy.')
        );
    }
    public static function build_candidate($source) {
        if (!is_string($source) || hash('sha256',$source)!==self::SOURCE_SHA) return null;
        $candidate=$source;
        foreach(self::replacements() as $pair) {
            if (substr_count($candidate,$pair[0])!==1) return null;
            $candidate=str_replace($pair[0],$pair[1],$candidate);
        }
        $open='<section class="nr-homepage-premium">';
        if (substr_count($candidate,$open)!==1
            || strpos($candidate,'DOPLNIT-URL')!==false
            || strpos($candidate,'Vizuální placeholder')!==false) return null;
        $candidate=str_replace($open,$open.self::MARKER,$candidate);
        return hash('sha256',$candidate)===self::CANDIDATE_SHA?$candidate:null;
    }
    private static function safe_write($content) {
        if (!function_exists('wp_slash') || !function_exists('has_filter'))
            return new WP_Error('missing_guarded_writer');
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
            return wp_update_post(array('ID'=>self::ID,'post_content'=>wp_slash($content)),true);
        } finally {
            foreach($removed as $entry) add_filter($entry[0],$entry[1],$entry[2]);
        }
    }
    private static function receipt($status) {
        update_option(self::RECEIPT,array('status'=>$status,'at'=>gmdate('c')),false);
    }
    public static function run() {
        if (rtrim(home_url('/'),'/')!=='https://na-ryby.eu') return;
        $post=get_post(self::ID);
        if (!$post || (int)$post->ID!==self::ID || $post->post_type!=='page'
            || $post->post_status!=='publish'
            || $post->post_name!=='na-ryby-eu-vse-pro-rybare'
            || get_permalink(self::ID)!=='https://na-ryby.eu/'
            || !is_string($post->post_content)) return;
        $sha=hash('sha256',$post->post_content);
        if ($sha===self::CANDIDATE_SHA || $sha!==self::SOURCE_SHA
            || $post->post_modified_gmt!=='2026-03-24 19:12:55'
            || strlen($post->post_content)!==18369) return;
        $lock=wp_generate_uuid4();
        if (!add_option(self::LOCK,$lock,'','no')) return;
        try {
            $post=get_post(self::ID);
            if (!$post || hash('sha256',$post->post_content)!==self::SOURCE_SHA
                || $post->post_modified_gmt!=='2026-03-24 19:12:55') return;
            $source=$post->post_content;
            $candidate=self::build_candidate($source);
            if ($candidate===null) { self::receipt('candidate_mismatch_no_write'); return; }
            $backup=array('id'=>self::ID,'source_sha'=>self::SOURCE_SHA,'source'=>$source);
            if (!add_option(self::BACKUP,$backup,'','no')
                && get_option(self::BACKUP,null)!==$backup) {
                self::receipt('immutable_backup_conflict_no_write'); return;
            }
            if (get_option(self::BACKUP,null)!==$backup) {
                self::receipt('backup_readback_failed_no_write'); return;
            }
            $fresh=get_post(self::ID);
            if (!$fresh || hash('sha256',$fresh->post_content)!==self::SOURCE_SHA
                || $fresh->post_modified_gmt!=='2026-03-24 19:12:55') {
                self::receipt('concurrent_editor_no_write'); return;
            }
            $result=self::safe_write($candidate);
            $after=get_post(self::ID);
            if (!is_wp_error($result) && (int)$result===self::ID
                && $after && hash('sha256',$after->post_content)===self::CANDIDATE_SHA) {
                self::receipt('applied_and_verified_local');
            } else {
                // Never blindly overwrite uncertain content after another editor.
                self::receipt('write_or_readback_failed_manual_review');
            }
        } catch (\Throwable $e) {
            self::receipt('unexpected_exception_manual_review');
        } finally {
            if (get_option(self::LOCK,null)===$lock) delete_option(self::LOCK);
        }
    }
}
NRY_Home_Trust_118641::boot();
