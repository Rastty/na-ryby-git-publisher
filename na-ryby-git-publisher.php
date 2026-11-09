<?php
/**
 * Plugin Name: Na-Ryby.eu – Git Publisher
 * Description: Apply guarded Git-managed content manifests from wp-admin without WPVibe or outbound WordPress REST writes.
 * Version: 0.16.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH')) { exit; }
require_once __DIR__ . '/seo-snippet-pilot.php';
require_once __DIR__ . '/seo-pipaky-beginner-35755-v1.php';
require_once __DIR__ . '/seo-pipaky-advanced-description-36702-v1.php';
require_once __DIR__ . '/checklist-release-v1.php';
require_once __DIR__ . '/guarded-auto-publisher.php';
require_once __DIR__ . '/advisor-existing-page-release-v1.php';
require_once __DIR__ . '/buyer-click-telemetry-v1.php';
require_once __DIR__ . '/home-trust-cleanup-118641-v1.php';
require_once __DIR__ . '/carp-pillar-source-proof-118541-v1.php';
require_once __DIR__ . '/carp-pillar-trust-cleanup-118541-v1.php';
require_once __DIR__ . '/receiver-buyer-guide-release-46573-v1.php';

final class NRY_Git_Publisher {
    const MARKER = 'data-nry-advisor-internal-link="cohort-01"';
    const DESTINATION = 'https://na-ryby.eu/poradce-vybavy/';
    const RECEIPT = 'nry_git_publisher_last_receipt';

    public static function manifest() {
        $raw = @file_get_contents(__DIR__ . '/manifest.json');
        $data = $raw === false ? null : json_decode($raw, true);
        return self::validate_manifest($data);
    }

    // V1 is immutable legacy compatibility. New cohorts must use the
    // source-hash-bound, insert-only v2 contract.
    private static function carp_3m_trust_corrections() {
        return array(
            array('old_text'=>'Jeho cena se pohybuje okolo 4 000 Kč.', 'new_text'=>'Cena uvedená v původním přehledu je historická a nyní neověřená; aktuální cenu a variantu je nutné ověřit u prodejce.'),
            array('old_text'=>'Má naprosto dokonalé zpracování a stojí okolo 3 000 Kč.', 'new_text'=>'Kvalitu zpracování nelze bez doloženého testu hodnotit jako dokonalou. Historická cena není aktuálně ověřená.'),
            array('old_text'=>'Jeho cena se pohybuje okolo 5 000 Kč.', 'new_text'=>'Jde o historickou a nyní neověřenou cenu; před nákupem ověřte přesnou variantu, její účel a dostupnost.'),
            array('old_text'=>'Fox Horizon X3 12ft 3lbs je vynikající kaprový prut v délce 3 metry.', 'new_text'=>'Označení 12 ft znamená přibližně 3,66 m, nikoli 3 m; tento model tedy nelze uvádět jako přesně třímetrovou variantu.'),
            array('old_text'=>'Fox Horizon X5 Spod and Marker rod', 'new_text'=>'Fox Horizon X5 Spod and Marker rod (speciální spodový a značkovací prut, ne běžný chytací model 3 m)'),
            array('old_text'=>'Cenex Toure Spin 3m je další vynikající kaprový prut.', 'new_text'=>'Uvedený přívlačový model není ověřený třímetrový kaprový prut; nepovažujte tento historický příklad za aktuální nákupní doporučení.'),
            array('old_text'=>'Shimano Tribal TX-3 3lb je další skvělou volbou pro zkušené rybáře.', 'new_text'=>'Označení 3 lb vyjadřuje testovací křivku, nikoli délku prutu. Přesnou délku a variantu je nutné ověřit.'),
            array('old_text'=>'DAM Shadow Tele Mini Spin', 'new_text'=>'DAM Shadow Tele Mini Spin (přívlačový model, nikoli vhodné srovnání třímetrových kaprových prutů)'),
            array('old_text'=>'nedisponují takovou pevností jako karbonové pruty.', 'new_text'=>'nelze obecně považovat za slabší než karbonové pruty: teleskopický i dělený prut může být z karbonu a záleží na konstrukci konkrétního modelu.'),
        );
    }

    public static function validate_manifest($data) {
        if (!is_array($data) || !in_array($data['schema_version'] ?? null, array(1, 2, 3, 4, 5, 6, 7, 8), true)
            || ($data['site'] ?? '') !== 'https://na-ryby.eu'
            || !isset($data['batch_id'], $data['edits'])
            || !is_string($data['batch_id'])
            || !preg_match('/^[a-z0-9][a-z0-9-]{7,99}$/D', $data['batch_id'])
            || !is_array($data['edits']) || count($data['edits']) < 1
            || count($data['edits']) > 25) {
            return new WP_Error('manifest_invalid', 'Neplatný manifest změn.');
        }
        $v2 = $data['schema_version'] === 2;
        $v3 = $data['schema_version'] === 3;
        $v4 = $data['schema_version'] === 4;
        $v5 = $data['schema_version'] === 5;
        $v6 = $data['schema_version'] === 6;
        $v7 = $data['schema_version'] === 7;
        $v8 = $data['schema_version'] === 8;
        if (!$v2 && !$v3 && !$v4 && !$v5 && !$v6 && !$v7 && !$v8 && (count($data['edits']) !== 2
            || array_column($data['edits'], 'post_id') !== array(52975, 118517))) {
            return new WP_Error('legacy_scope', 'V1 povoluje jen historickou dvojici.');
        }
        $seen = array(); $markers = array();
        foreach ($data['edits'] as $e) {
            if (!is_array($e) || !isset($e['post_id'], $e['slug'])
                || !is_int($e['post_id']) || $e['post_id'] < 1
                || in_array($e['post_id'], array(38064, 42671), true)
                || isset($seen[$e['post_id']])
                || !is_string($e['slug']) || !preg_match('/^[a-z0-9-]{10,140}$/D', $e['slug'])
                || (!$v4 && !$v5 && !$v6 && !$v7 && !$v8 && (!isset($e['anchor']) || !is_string($e['anchor'])))
                || (!$v4 && !$v5 && !$v6 && !$v7 && !$v8 && !preg_match($v3 ? '~^<h[23](?:\s[^>]*)?>[^<]{8,280}</h[23]>$~uD' : '~^<h[12](?:\s[^>]*)?>[^<]{8,280}</h[12]>$~uD', $e['anchor']))) {
                return new WP_Error('invalid_item', 'Neschválená identita, kotva nebo chráněný článek.');
            }
            $seen[$e['post_id']] = true;
            if ($v8) {
                if (count($data['edits']) > 4 || in_array($e['post_id'], array(44102), true)) {
                    return new WP_Error('v8_scope', 'Neplatný rozsah automatické dávky.');
                }
                if (!isset($e['source_sha256']) || !is_string($e['source_sha256'])
                    || !preg_match('/^[a-f0-9]{64}$/D', $e['source_sha256'])) {
                    return new WP_Error('v8_source_hash', 'Chybí přesný otisk původního obsahu.');
                }
                // A single correction-only release removes six invented 1–10
                // numerical ratings without adding another redundant top panel.
                // All other schema v8 operations remain the original prepend flow.
                if (($e['placement'] ?? '') === 'sonar_spec_bonus_only') {
                    $check=self::validate_sonar_spec_bonus_correction($e,$data);
                    if (is_wp_error($check)) return $check;
                    continue;
                }
                if (($e['placement'] ?? '') === 'historic_sonar_offers_only') {
                    $check=self::validate_sonar_historic_offer_correction($e,$data);
                    if (is_wp_error($check)) return $check;
                    continue;
                }
                if (($e['placement'] ?? '') === 'ranking_claims_only') {
                    $check=self::validate_alarm_ranking_correction($e,$data);
                    if (is_wp_error($check)) return $check;
                    continue;
                }
                if (($e['placement'] ?? '') === 'price_corrections_only') {
                    $check=self::validate_pipaky_price_correction($e,$data);
                    if (is_wp_error($check)) return $check;
                    continue;
                }
                if (($e['placement'] ?? '') === 'corrections_only') {
                    $check=self::validate_alarm_scores_correction($e,$data);
                    if (is_wp_error($check)) { return $check; }
                    continue;
                }
                // Trust repair is confined to one exact public hub/batch.
                if (isset($e['text_corrections'])) {
                    // One exact reviewed 3m carp rod correction + problem-first guidance.
                    // No production authority for other KEEP/PROTECT topics.
                    if ($e['post_id']===54272) {
                        if ($data['batch_id']!=='carp-rods-3m-54272-source-trust-20261109-v1'
                            || count($data['edits'])!==1
                            || ($e['slug']??'')!=='kaprove-pruty-3m-recenze-nejlepsich-vyrobku-na-trhu'
                            || ($e['edit_id']??'')!=='kaprove-pruty-3m-54272-20261109-v1'
                            || ($e['placement']??'')!=='prepend'
                            || ($e['source_sha256']??'')!=='73cd04df7abb7199f68397323a9139ae791f3f0a20bb79333da9d7d65df60751'
                            || ($e['source_modified_gmt']??'')!=='2023-06-12 22:16:22'
                            || ($e['source_min_bytes']??0)!==10207
                            || ($e['source_max_bytes']??0)!==10208
                            || ($e['text_corrections']??null)!==self::carp_3m_trust_corrections()
                            || !is_string($e['insert_html']??null)
                            || hash('sha256',$e['insert_html'])!=='d45ba94885e5e5573ad3c7a72e4b138ba9377ace94f2b00c12235230b2947c7b') {
                            return new WP_Error('v8_carp_3m_scope','Only the exact current 3m buyer-trust repair may change.');
                        }
                    } elseif ($e['post_id']!==60341
                        || $data['batch_id']!=='chair-buyer-trust-60341-20261104-v1'
                        || count($data['edits'])!==1
                        || !is_array($e['text_corrections'])
                        || count($e['text_corrections'])!==3) {
                        return new WP_Error('v8_repair_scope','Neschválený textový zásah.');
                    }
                    $correction_keys=array();
                    foreach ($e['text_corrections'] as $correction) {
                        if (!is_array($correction)
                            || !isset($correction['old_text'],$correction['new_text'])
                            || !self::editorial_text($correction['old_text'],25,750)
                            || !self::editorial_text($correction['new_text'],25,1200)
                            || $correction['old_text']===$correction['new_text']
                            || isset($correction_keys[$correction['old_text']])) {
                            return new WP_Error('v8_repair_text','Neschválená věcná oprava.');
                        }
                        $correction_keys[$correction['old_text']]=true;
                    }
                }
                // Preserve v6 source-size and safe HTML rules. Only v8 allows
                // a source SHA, which must also match actual WordPress data.
                $proxy = $e;
                unset($proxy['source_sha256']);
                $check = self::validate_v6_edit($proxy);
                if (is_wp_error($check)) { return $check; }
                if (isset($markers[$e['edit_id']])) {
                    return new WP_Error('v8_duplicate_marker', 'Opakovaná značka.');
                }
                $markers[$e['edit_id']] = true;
                continue;
            }
            if ($v7) {
                $check = self::validate_v6_edit($e);
                if (is_wp_error($check)) { return $check; }
                if (isset($markers[$e['edit_id']])) {
                    return new WP_Error('v7_duplicate_marker', 'Opakované označení změny.');
                }
                $markers[$e['edit_id']] = true;
                continue;
            }
            if ($v6) {
                $check = self::validate_v6_edit($e);
                if (is_wp_error($check)) { return $check; }
                if (isset($markers[$e['edit_id']])) {
                    return new WP_Error('v6_duplicate_marker', 'Opakované označení.');
                }
                $markers[$e['edit_id']] = true;
                continue;
            }
            if ($v5) {
                $check = self::validate_v5_edit($e);
                if (is_wp_error($check)) { return $check; }
                if (isset($markers[$e['edit_id']])) {
                    return new WP_Error('v5_duplicate_marker', 'Opakované označení změny.');
                }
                $markers[$e['edit_id']] = true;
                continue;
            }
            if ($v4) {
                $check = self::validate_v4_edit($e);
                if (is_wp_error($check)) { return $check; }
                $markers[$e['edit_id']] = true;
                continue;
            }
            if (!$v2 && !$v3) {
                if (!isset($e['insert_html']) || !is_string($e['insert_html'])
                    || strpos($e['insert_html'], self::MARKER) === false
                    || substr_count($e['insert_html'], self::DESTINATION) !== 1
                    || substr_count($e['insert_html'], '<a ') !== 1
                    || stripos($e['insert_html'], '<script') !== false
                    || strlen($e['insert_html']) > 1400) {
                    return new WP_Error('legacy_item', 'Neplatná historická úprava.');
                }
                continue;
            }
            if ($v3) {
                if (!isset($e['source_sha256'], $e['edit_id'], $e['lead'], $e['choices'], $e['caution'])
                    || !is_string($e['source_sha256'])
                    || !preg_match('/^[a-f0-9]{64}$/D', $e['source_sha256'])
                    || !is_string($e['edit_id'])
                    || !preg_match('/^[a-z0-9][a-z0-9-]{7,63}$/D', $e['edit_id'])
                    || isset($markers[$e['edit_id']])
                    || !self::editorial_text($e['lead'], 30, 1000)
                    || !self::editorial_text($e['caution'], 25, 600)
                    || !is_array($e['choices']) || count($e['choices']) < 2
                    || count($e['choices']) > 4
                    || !is_array($e['corrections'] ?? array())
                    || count($e['corrections'] ?? array()) > 3
                    || isset($e['insert_html'], $e['link_url'])) {
                    return new WP_Error('v3_item', 'Neplatný redakční obsah nebo zdroj.');
                }
                foreach ($e['choices'] as $choice) {
                    if (!is_array($choice) || !isset($choice['situation'], $choice['recommendation'], $choice['tradeoff'])
                        || !self::editorial_text($choice['situation'], 8, 130)
                        || !self::editorial_text($choice['recommendation'], 15, 330)
                        || !self::editorial_text($choice['tradeoff'], 15, 330)) {
                        return new WP_Error('v3_choices', 'Neplatné rozhodovací scénáře.');
                    }
                }
                $correctionKeys = array();
                foreach ($e['corrections'] ?? array() as $c) {
                    if (!is_array($c) || !isset($c['old_text'], $c['new_text'])
                        || !self::editorial_text($c['old_text'], 25, 750)
                        || !self::editorial_text($c['new_text'], 25, 1200)
                        || $c['old_text'] === $c['new_text']
                        || isset($correctionKeys[$c['old_text']])) {
                        return new WP_Error('v3_correction', 'Nepovolená textová oprava.');
                    }
                    $correctionKeys[$c['old_text']] = true;
                }
                $markers[$e['edit_id']] = true;
                continue;
            }
            if (!isset($e['source_sha256'], $e['edit_id'], $e['intro'], $e['link_label'], $e['link_url'])
                || !is_string($e['source_sha256'])
                || !preg_match('/^[a-f0-9]{64}$/D', $e['source_sha256'])
                || !is_string($e['edit_id'])
                || !preg_match('/^[a-z0-9][a-z0-9-]{7,63}$/D', $e['edit_id'])
                || isset($markers[$e['edit_id']])
                || !is_string($e['intro']) || trim($e['intro']) === ''
                || strlen($e['intro']) > 1400
                || !is_string($e['link_label']) || trim($e['link_label']) === ''
                || strlen($e['link_label']) > 440
                || !is_string($e['link_url'])
                || !preg_match('~^https://na-ryby\.eu/(?:[a-z0-9_-]+/)+$~D', $e['link_url'])
                || isset($e['insert_html'])) {
                return new WP_Error('v2_item', 'Neplatný v2 záznam, SHA nebo interní odkaz.');
            }
            $markers[$e['edit_id']] = true;
        }
        // This opt-in source-on-server schema is confined to the two reviewed
        // buyer-guide rescue articles. Do not widen with generic post IDs.
        if ($v5 && (count($data['edits']) !== 2
            || array_keys($seen) !== array(50011, 53630))) {
            return new WP_Error('v5_scope', 'V5 povoluje pouze schválenou dvojici.');
        }
        if ($v7 && (count($data['edits']) !== 4
            || array_keys($seen) !== array(34913, 34977, 35003, 35011))) {
            return new WP_Error('v7_scope', 'V7 povoluje pouze vybranou čtveřici.');
        }
        if ($v6 && (count($data['edits']) !== 4
            || array_keys($seen) !== array(34882, 34905, 34921, 35026))) {
            return new WP_Error('v6_scope', 'V6 povoluje pouze vybranou čtveřici.');
        }
        return $data;
    }


    /**
     * A fixed five-price cleanup of already-published post 36702, scoped
     * to the SHA and modified GMT independently verified on WordPress.
     * No generic product/price replacement API or affiliate URL changes.
     */
    /** Eight fixed historic claim classes, no generic source editing. */
    private static function alarm_ranking_corrections() {
        return array(
            array('old_text'=>'⭐⭐⭐⭐⭐','new_text'=>'bez zveřejněného testovacího skóre'),
            array('old_text'=>'⭐⭐⭐⭐','new_text'=>'bez zveřejněného testovacího skóre'),
            array('old_text'=>'Pokud jste vášnivý rybář, určitě oceníte naše produkty.',
                'new_text'=>'Jde o historický redakční přehled, nikoli nabídku našeho obchodu; před nákupem ověřte přesný model a aktuální parametry u prodejce.'),
            array('old_text'=>'Nejlepší poměr cena/výkon',
                'new_text'=>'historické redakční označení bez ověřeného srovnání cen'),
            array('old_text'=>'Top produkt na trhu',
                'new_text'=>'historicky uváděná prémiová sada'),
            array('old_text'=>'100% vodotěsné',
                'new_text'=>'Stupeň ochrany před vodou ověřte v návodu konkrétního modelu'),
            array('old_text'=>'Voděodolnost zaručena',
                'new_text'=>'Odolnost vůči vodě ověřte podle specifikace výrobce'),
            array('old_text'=>'Pojďme se společně podívat na ty nejlepší signalizátory, kterou jsou momentálně na našem trhu.',
                'new_text'=>'Následuje historický přehled vybraných modelů, nikoli současný žebříček trhu.')
        );
    }

    private static function validate_alarm_ranking_correction($e,$manifest) {
        $keys=array_keys($e);
        $expected=array('post_id','slug','source_modified_gmt','source_min_bytes',
            'source_max_bytes','source_sha256','edit_id','placement','text_corrections');
        sort($keys);sort($expected);
        if (($manifest['batch_id']??'')!=='alarm-ranking-60354-legacy-claims-fix-20261107-v1'
            || count($manifest['edits']??array())!==1
            || ($e['post_id']??null)!==60354
            || ($e['slug']??'')!=='nejlepsi-rybarske-hlasice-a-signalizatory'
            || ($e['edit_id']??'')!=='alarm-ranking-60354-legacy-trust-20261107-v1'
            || ($e['placement']??'')!=='ranking_claims_only'
            || $keys!==$expected
            || ($e['source_sha256']??'')!=='a7d76b5908e09c4bd128548806c5bb3589b9aed88546770f4f4a9604ec0fea83'
            || ($e['source_modified_gmt']??'')!=='2026-10-23 06:35:08'
            || ($e['source_min_bytes']??0)!==20174
            || ($e['source_max_bytes']??0)!==20175
            || ($e['text_corrections']??null)!==self::alarm_ranking_corrections()) {
            return new WP_Error('v8_alarm_ranking_scope','Unapproved historic ranking correction.');
        }
        return true;
    }

    private static function build_alarm_ranking_candidate($source,$edit) {
        $rules=self::alarm_ranking_corrections();
        $counts=array(4,2,1,1,1,1,1,1);
        $label='bez zveřejněného testovacího skóre';
        if (substr_count($source,$label)===6) {
            $already=true;
            foreach ($rules as $i=>$rule) {
                if (substr_count($source,$rule['old_text'])!==0) $already=false;
                if ($i>=2 && substr_count($source,$rule['new_text'])!==1) $already=false;
            }
            if ($already) return array('already'=>true);
        }
        if (substr_count($source,$label)!==0) return array('error'=>'unreviewed_ranking_label_present');
        $candidate=$source;
        foreach ($rules as $i=>$rule) {
            $old=$rule['old_text'];$new=$rule['new_text'];
            // Five-star strings overlap four-star strings in original source.
            // After removing all four five-star instances only two four-star remain.
            if (substr_count($candidate,$old)!==$counts[$i]
                || substr_count($candidate,$new)!==($i===1 ? 4 : 0)) {
                return array('error'=>'ranking_claim_count_or_sequence_drift');
            }
            $offset=0;$escaped=htmlspecialchars($new,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
            for ($n=0;$n<$counts[$i];$n++) {
                $at=strpos($candidate,$old,$offset);
                if ($at===false || !self::plain_text_span($candidate,$at,strlen($old))) {
                    return array('error'=>'ranking_claim_not_safe_visible_text');
                }
                $candidate=substr_replace($candidate,$escaped,$at,strlen($old));
                $offset=$at+strlen($escaped);
            }
        }
        if (substr_count($candidate,$label)!==6) return array('error'=>'ranking_scores_not_fully_corrected');
        foreach ($rules as $i=>$rule) {
            if (strpos($candidate,$rule['old_text'])!==false
                || ($i>=2 && substr_count($candidate,$rule['new_text'])!==1)) {
                return array('error'=>'ranking_claim_remnant');
            }
        }
        $pattern='~(?:<a\b[^>]*>|<img\b[^>]*>|<iframe\b[^>]*>|\[[a-zA-Z][^\]]*\])~iu';
        preg_match_all($pattern,$source,$before);
        preg_match_all($pattern,$candidate,$after);
        if ($before[0]!==$after[0]) return array('error'=>'protected_affiliate_or_media_drift');
        return array('candidate'=>$candidate);
    }

    /**
     * Existing budget sonar guide #118455, three live source-counted unsafe
     * historical offers only. Not an unbounded price editor.
     */
    private static function sonar_historic_offer_corrections() {
        return array(
          array(
            'old_text'=>'v době přípravy článku skladem za 4 509 Kč',
            'new_text'=>'historická nabídka Eagle 4X – aktuální cenu a sklad ověřte u prodejce'),
          array(
            'old_text'=>'aktuálně 10 542 Kč a skladem 1 ks',
            'new_text'=>'historická nabídka Hook Reveal 5 – aktuální cenu a sklad ověřte u prodejce'),
          array(
            'old_text'=>'v době přípravy článku skladem za 13 889 Kč',
            'new_text'=>'historická nabídka Eagle 7 – aktuální cenu a sklad ověřte u prodejce'),
        );
    }

    private static function validate_sonar_historic_offer_correction($edit,$manifest) {
        $keys=array_keys($edit);
        $expected=array('post_id','slug','source_modified_gmt','source_min_bytes',
          'source_max_bytes','source_sha256','edit_id','placement','text_corrections');
        sort($keys);sort($expected);
        if (($manifest['batch_id']??'')!=='echolot-budget-118455-three-offers-fix-20261108-v1'
          || count($manifest['edits']??array())!==1
          || ($edit['post_id']??null)!==118455
          || ($edit['slug']??'')!=='nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz'
          || ($edit['edit_id']??'')!=='echolot-budget-118455-three-offers-20261108-v1'
          || ($edit['placement']??'')!=='historic_sonar_offers_only'
          || $keys!==$expected
          || ($edit['source_sha256']??'')!=='f0ba0b4dcd29646d470d084af077623b12fa687935347e626203a13107e7be79'
          || ($edit['source_modified_gmt']??'')!=='2026-11-04 11:04:24'
          || ($edit['source_min_bytes']??0)!==39795
          || ($edit['source_max_bytes']??0)!==39796
          || ($edit['text_corrections']??null)!==self::sonar_historic_offer_corrections()) {
            return new WP_Error('v8_sonar_historic_offer_scope',
                'Only the three reviewed plain historical 118455 offers may change.');
        }
        return true;
    }

    private static function build_sonar_historic_offer_candidate($source,$edit) {
        $rules=self::sonar_historic_offer_corrections();
        $already=true;
        foreach ($rules as $rule) {
            if (substr_count($source,$rule['old_text'])!==0
                || substr_count($source,$rule['new_text'])!==1) {
                $already=false;
                break;
            }
        }
        if ($already) return array('already'=>true);
        $candidate=$source;
        foreach ($rules as $rule) {
            $old=$rule['old_text'];$new=$rule['new_text'];
            if (substr_count($candidate,$old)!==1
                || substr_count($candidate,$new)!==0) {
                return array('error'=>'sonar_offer_missing_or_not_unique');
            }
            $at=strpos($candidate,$old);
            if (!self::plain_text_span($candidate,$at,strlen($old))) {
                return array('error'=>'sonar_offer_in_protected_html');
            }
            $replacement=htmlspecialchars($new,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
            $candidate=substr_replace($candidate,$replacement,$at,strlen($old));
        }
        foreach ($rules as $rule) {
            if (substr_count($candidate,$rule['new_text'])!==1
                || strpos($candidate,$rule['old_text'])!==false) {
                return array('error'=>'sonar_offer_not_fully_corrected');
            }
        }
        // Original affiliate anchors, images, embeds, shortcodes must survive
        // exactly, in order, with zero target/revenue attribution rewrites.
        $pattern='~(?:<a\b[^>]*>|<img\b[^>]*>|<iframe\b[^>]*>|\[[a-zA-Z][^\]]*\])~iu';
        preg_match_all($pattern,$source,$before);
        preg_match_all($pattern,$candidate,$after);
        if ($before[0]!==$after[0]) {
            return array('error'=>'protected_affiliate_or_media_drift');
        }
        return array('candidate'=>$candidate);
    }


    /** Fixed manufacturer-spec correction and unverified historic bonus terms.
     * Manufacturer: Lowrance Eagle 4x Bullet is a fishfinder without GPS.
     * Preflight counts and private raw SHA independently verified 2026-11-08.
     */
    private static function sonar_spec_bonus_corrections() {
        return array(
            array('old_text'=>'Nízká cena, GPS, jednoduchost, bonus baterie.',
                  'new_text'=>'Základní 2D sonar a jednoduché ovládání; model Eagle 4X nemá GPS. Bonusy ověřte u prodejce.','count'=>1),
            array('old_text'=>'baterie zdarma v balení',
                  'new_text'=>'případnou baterii v balení ověřte u prodejce','count'=>1),
            array('old_text'=>'Chyť a pusť tu má rozumnou aktuální nabídku včetně baterie a nabíječky.',
                  'new_text'=>'Před koupí ověřte aktuální cenu a zda jsou baterie a nabíječka součástí přesné varianty balení.','count'=>1),
            array('old_text'=>'V Chyť a pusť aktuálně dává větší smysl i díky přibalené baterii a nabíječce.',
                  'new_text'=>'Porovnejte skutečný obsah balení a konečnou cenu, včetně případné baterie a nabíječky.','count'=>1),
            array('old_text'=>'Lowrance Eagle 4X se sondou Bullet + baterie ZDARMA',
                  'new_text'=>'Lowrance Eagle 4X se sondou Bullet (bonusy ověřte)','count'=>2),
            array('old_text'=>'Lowrance Eagle 7 se sondou SplitShot HD + baterie + nabíječka ZDARMA',
                  'new_text'=>'Lowrance Eagle 7 se sondou SplitShot HD (balení ověřte)','count'=>2),
        );
    }

    private static function validate_sonar_spec_bonus_correction($edit,$manifest) {
        $keys=array_keys($edit);
        $expected=array('post_id','slug','source_modified_gmt','source_min_bytes',
            'source_max_bytes','source_sha256','edit_id','placement','text_corrections');
        sort($keys);sort($expected);
        if (($manifest['batch_id']??'')!=='echolot-budget-118455-spec-bonus-fix-20261108-v1'
            || count($manifest['edits']??array())!==1
            || ($edit['post_id']??null)!==118455
            || ($edit['slug']??'')!=='nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz'
            || ($edit['edit_id']??'')!=='echolot-budget-118455-spec-bonus-20261108-v1'
            || ($edit['placement']??'')!=='sonar_spec_bonus_only'
            || $keys!==$expected
            || ($edit['source_sha256']??'')!=='6d8736d5d2f62a5f6170871a7630d9e097f6dc6839a461e97da53d95cb166f7e'
            || ($edit['source_modified_gmt']??'')!=='2026-11-08 07:56:27'
            || ($edit['source_min_bytes']??0)!==39897
            || ($edit['source_max_bytes']??0)!==39898
            || ($edit['text_corrections']??null)!==self::sonar_spec_bonus_corrections()) {
            return new WP_Error('v8_sonar_spec_bonus_scope',
                'Only the six exact current buyer-trust assertions on post 118455 may change.');
        }
        return true;
    }

    private static function build_sonar_spec_bonus_candidate($source,$edit) {
        $rules=self::sonar_spec_bonus_corrections();
        $already=true;
        foreach ($rules as $rule) {
            if (substr_count($source,$rule['old_text'])!==0
                || substr_count($source,$rule['new_text'])!==$rule['count']) {
                $already=false;break;
            }
        }
        if ($already) return array('already'=>true);
        $candidate=$source;
        foreach ($rules as $rule) {
            $old=$rule['old_text'];$new=$rule['new_text'];$count=$rule['count'];
            if (substr_count($candidate,$old)!==$count
                || substr_count($candidate,$new)!==0) {
                return array('error'=>'sonar_spec_bonus_ambiguous_source');
            }
            $offset=0;$safe=0;
            while (($at=strpos($candidate,$old,$offset))!==false) {
                if (!self::plain_text_span($candidate,$at,strlen($old))) {
                    return array('error'=>'sonar_spec_bonus_protected_html');
                }
                $safe++;$offset=$at+strlen($old);
            }
            if ($safe!==$count) return array('error'=>'sonar_spec_bonus_plain_count_mismatch');
            $replacement=htmlspecialchars($new,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
            $candidate=str_replace($old,$replacement,$candidate,$changed);
            if ($changed!==$count) return array('error'=>'sonar_spec_bonus_replace_count_mismatch');
        }
        foreach ($rules as $rule) {
            if (substr_count($candidate,$rule['old_text'])!==0
                || substr_count($candidate,$rule['new_text'])!==$rule['count']) {
                return array('error'=>'sonar_spec_bonus_postcondition');
            }
        }
        // Merchant hrefs, images, embeds and original shortcode tokens
        // must be byte-for-byte unchanged and retain their relative order.
        $pattern='~(?:<a\\b[^>]*>|<img\\b[^>]*>|<iframe\\b[^>]*>|\\[[a-zA-Z][^\\]]*\\])~iu';
        preg_match_all($pattern,$source,$before);
        preg_match_all($pattern,$candidate,$after);
        if ($before[0]!==$after[0]) {
            return array('error'=>'sonar_spec_bonus_affiliate_or_media_drift');
        }
        return array('candidate'=>$candidate);
    }

    private static function pipaky_price_corrections() {
        $prices=array('1000','800','3000','1200','2000');
        $edits=array();
        foreach ($prices as $p) {
            $edits[]=array('old_text'=>'Cena: od '.$p.' Kč',
                'new_text'=>'Historická cena – aktuálně neověřeno');
        }
        return $edits;
    }
    private static function validate_pipaky_price_correction($e,$manifest) {
        $keys=array_keys($e);
        $expected=array('post_id','slug','source_modified_gmt','source_min_bytes',
            'source_max_bytes','source_sha256','edit_id','placement','text_corrections');
        sort($keys);sort($expected);
        if (($manifest['batch_id']??'')!=='pipaky-pokrocile-five-price-fix-36702-20261107-v1'
            || count($manifest['edits']??array())!==1
            || ($e['post_id']??null)!==36702
            || ($e['slug']??'')!=='pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu'
            || ($e['edit_id']??'')!=='pipaky-pokrocile-36702-20261107-v1'
            || ($e['placement']??'')!=='price_corrections_only'
            || $keys!==$expected
            || ($e['source_sha256']??'')!=='b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735'
            || ($e['source_modified_gmt']??'')!=='2026-11-07 11:23:04'
            || ($e['source_min_bytes']??0)!==12938
            || ($e['source_max_bytes']??0)!==12939
            || ($e['text_corrections']??null)!==self::pipaky_price_corrections()) {
            return new WP_Error('v8_pipaky_price_scope',
                'Only the five published #36702 plain-text price claims may be changed.');
        }
        return true;
    }
    private static function build_pipaky_price_candidate($source,$edit) {
        $rules=self::pipaky_price_corrections();
        $oldSum=0;$new=0;
        $expected='Historická cena – aktuálně neověřeno';
        foreach ($rules as $c) $oldSum+=substr_count($source,$c['old_text']);
        $new=substr_count($source,$expected);
        if ($oldSum===0 && $new===5
            && substr_count($source,'data-nry-editorial-edit="pipaky-pokrocile-36702-20261107-v1"')===1) {
            return array('already'=>true);
        }
        if ($oldSum!==5 || $new!==0) {
            return array('error'=>'five_prices_missing_or_ambiguous');
        }
        $candidate=$source;
        foreach ($rules as $rule) {
            $old=$rule['old_text'];
            if (substr_count($candidate,$old)!==1) {
                return array('error'=>'stale_price_not_unique');
            }
            $at=strpos($candidate,$old);
            if (!self::plain_text_span($candidate,$at,strlen($old))) {
                return array('error'=>'stale_price_inside_link_or_html_attribute');
            }
            $candidate=substr_replace($candidate,
                htmlspecialchars($rule['new_text'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),
                $at,strlen($old));
        }
        if (substr_count($candidate,$expected)!==5
            || substr_count($candidate,'data-nry-editorial-edit="pipaky-pokrocile-36702-20261107-v1"')!==1) {
            return array('error'=>'stale_price_candidate_incomplete');
        }
        foreach ($rules as $rule) {
            if (strpos($candidate,$rule['old_text'])!==false) {
                return array('error'=>'stale_price_remnant');
            }
        }
        // Every previous link, image, embed and shortcode token must survive
        // byte-for-byte and in original order; no merchant clickout mutation.
        $pattern='~(?:<a\\b[^>]*>|<img\\b[^>]*>|<iframe\\b[^>]*>|\\[[a-zA-Z][^\\]]*\\])~iu';
        preg_match_all($pattern,$source,$before);
        preg_match_all($pattern,$candidate,$after);
        if ($before[0]!==$after[0]) {
            return array('error'=>'protected_affiliate_or_media_drift');
        }
        return array('candidate'=>$candidate);
    }

    private static function alarm_score_warning_old() {
        return 'Uvedené částky v Kč, označení akcí 1+1 a číselná hodnocení typu 8,9/10 nejsou v tomto okamžiku nezávisle ověřenými cenami, skladovými nabídkami ani výsledkem zveřejněné srovnávací testovací metodiky.';
    }

    private static function alarm_score_warning_new() {
        return 'Uvedené ceny v Kč a akce 1+1 jsou orientační, nikoli právě ověřené skladové nabídky. Bodová hodnocení jsme z tabulky odstranili, protože pro ně není zveřejněná srovnávací testovací metodika.';
    }

    private static function alarm_score_corrections() {
        $scores=array('8,1 / 10','8,6 / 10','8,9 / 10',
            '9,2 / 10','9,1 / 10','9,4 / 10');
        $list=array();
        foreach ($scores as $score) {
            $list[]=array('old_text'=>$score,'new_text'=>'Bez doloženého skóre');
        }
        $list[]=array('old_text'=>self::alarm_score_warning_old(),
            'new_text'=>self::alarm_score_warning_new());
        return $list;
    }

    private static function validate_alarm_scores_correction($e,$manifest) {
        $expected_keys=array('post_id','slug','source_modified_gmt','source_min_bytes',
            'source_max_bytes','source_sha256','edit_id','placement','text_corrections');
        $keys=array_keys($e);
        sort($keys);sort($expected_keys);
        if (($manifest['batch_id']??'')!=='alarm-buyer-score-method-118218-20261104-v2'
            || count($manifest['edits']??array())!==1
            || $e['post_id']!==118218
            || ($e['slug']??'')!=='nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu'
            || ($e['edit_id']??'')!=='alarm-buyer-prices-trust-118218-v1'
            || $e['placement']!=='corrections_only'
            || $keys!==$expected_keys
            || !is_int($e['source_min_bytes']) || !is_int($e['source_max_bytes'])
            || $e['source_min_bytes']<5000
            || $e['source_max_bytes']!==$e['source_min_bytes']+1
            || $e['source_max_bytes']>100000
            || !is_string($e['source_modified_gmt'])
            || !preg_match('/^20[0-9]{2}-[01][0-9]-[0-3][0-9] [0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/D',$e['source_modified_gmt'])
            || $e['text_corrections']!==self::alarm_score_corrections()) {
            return new WP_Error('v8_alarm_score_scope','Only exact reviewed 118218 score cleanup is supported.');
        }
        return true;
    }

    private static function build_alarm_score_candidate($source,$edit) {
        $corrections=self::alarm_score_corrections();
        $ready=true;$already=true;
        foreach ($corrections as $item) {
            if (substr_count($source,$item['old_text'])!==1) $ready=false;
            if (substr_count($source,$item['old_text'])!==0
                || substr_count($source,$item['new_text'])!==
                    ($item['new_text']==='Bez doloženého skóre'?6:1)) $already=false;
        }
        if ($already && substr_count($source,
            'data-nry-editorial-edit="alarm-buyer-prices-trust-118218-v1"')===1) {
            return array('already'=>true);
        }
        if (!$ready) return array('error'=>'alarm_score_source_missing_or_ambiguous');
        $candidate=$source;
        foreach ($corrections as $item) {
            $old=$item['old_text'];
            if (substr_count($candidate,$old)!==1) {
                return array('error'=>'alarm_score_not_unique');
            }
            $at=strpos($candidate,$old);
            if (!self::plain_text_span($candidate,$at,strlen($old))) {
                return array('error'=>'alarm_score_inside_markup');
            }
            $candidate=substr_replace($candidate,
                htmlspecialchars($item['new_text'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),
                $at,strlen($old));
        }
        if (substr_count($candidate,'Bez doloženého skóre')!==6
            || substr_count($candidate,self::alarm_score_warning_new())!==1
            || substr_count($candidate,
                'data-nry-editorial-edit="alarm-buyer-prices-trust-118218-v1"')!==1) {
            return array('error'=>'alarm_score_incomplete');
        }
        // The entire legacy affiliate/image/embed/shortcode token sequence must
        // remain identical, with only seven visible text replacements.
        $pattern='~(?:<a\\b[^>]*>|<img\\b[^>]*>|<iframe\\b[^>]*>|\\[[a-zA-Z][^\\]]*\\])~iu';
        preg_match_all($pattern,$source,$oldTokens);
        preg_match_all($pattern,$candidate,$newTokens);
        if ($oldTokens[0]!==$newTokens[0]) {
            return array('error'=>'protected_affiliate_or_media_drift');
        }
        return array('candidate'=>$candidate);
    }

    private static function validate_v6_edit($e) {
        if (!isset($e['source_min_bytes'], $e['source_max_bytes'])
            || !is_int($e['source_min_bytes']) || !is_int($e['source_max_bytes'])
            || $e['source_min_bytes'] < 5000 || $e['source_min_bytes'] >= $e['source_max_bytes']
            || $e['source_max_bytes'] > 100000
            || isset($e['source_bytes'])) {
            return new WP_Error('v6_bounds', 'Neplatný bezpečnostní interval zdroje.');
        }
        // Reuse exact v5 HTML sanitizer, internal-only links, marker and
        // unmodified GMT guards. V6 records a reviewed source-size interval
        // because unauthenticated public REST cannot return raw post_content bytes.
        $proxy = $e;
        $proxy['source_bytes'] = $e['source_min_bytes'];
        return self::validate_v5_edit($proxy);
    }

    private static function validate_v5_edit($e) {
        if (!isset($e['source_modified_gmt'], $e['source_bytes'], $e['edit_id'],
                $e['insert_html'], $e['placement'])
            || $e['placement'] !== 'prepend'
            || !is_int($e['source_bytes']) || $e['source_bytes'] < 5000
            || $e['source_bytes'] > 100000
            || !is_string($e['source_modified_gmt'])
            || !preg_match('/^20[0-9]{2}-[01][0-9]-[0-3][0-9] [0-2][0-9]:[0-5][0-9]:[0-5][0-9]$/D',
                $e['source_modified_gmt'])
            || !is_string($e['edit_id'])
            || !preg_match('/^[a-z0-9][a-z0-9-]{7,63}$/D', $e['edit_id'])
            || !is_string($e['insert_html'])
            || strlen($e['insert_html']) < 1000 || strlen($e['insert_html']) > 16000
            || isset($e['source_sha256']) || isset($e['cleanup_ops'])
            || isset($e['corrections'])) {
            return new WP_Error('v5_invalid', 'Neplatné parametry redakční změny.');
        }
        $html = $e['insert_html'];
        $marker = 'data-nry-editorial-edit="' . $e['edit_id'] . '"';
        if (substr_count($html, $marker) !== 1
            || !preg_match('/^<section ' . preg_quote($marker, '/') . '>/u', $html)
            || strpos($html, '</section>') === false
            || preg_match('~(?:<\\?|<!--|--> |\\[[a-zA-Z][^\\]]*\\]|\\bon[a-z]+\\s*=|<(?:script|style|iframe|form|img|svg|object|embed)\\b)~iu', $html)) {
            return new WP_Error('v5_unsafe_html', 'Neplatné HTML nebo nesprávná značka.');
        }
        $allowed = array(
            'section' => array('data-nry-editorial-edit' => true),
            'div' => array(), 'h2' => array(), 'h3' => array(),
            'p' => array(), 'strong' => array(), 'em' => array(),
            'small' => array(), 'ol' => array(), 'ul' => array(),
            'li' => array(), 'table' => array(), 'thead' => array(),
            'tbody' => array(), 'tr' => array(), 'th' => array(),
            'td' => array(), 'a' => array('href' => true)
        );
        if (wp_kses($html, $allowed, array('https')) !== $html) {
            return new WP_Error('v5_sanitize_drift', 'Vklad musí obsahovat jen schválené čisté HTML.');
        }
        preg_match_all('~<a\\b[^>]*href="([^"]+)"[^>]*>~iu', $html, $links);
        preg_match_all('~<a\\b~iu', $html, $all_anchors);
        if (count($links[1]) < 1 || count($links[1]) > 6
            || count($links[1]) !== count($all_anchors[0])) {
            return new WP_Error('v5_links', 'Chybné nebo přebytečné odkazy.');
        }
        foreach ($links[1] as $url) {
            if (!preg_match('~^https://na-ryby\\.eu/[a-z0-9-]+/$~D', $url)) {
                return new WP_Error('v5_external', 'Povoleny jsou pouze ověřené interní odkazy.');
            }
        }
        return true;
    }

    private static function build_v5_candidate($source, $edit) {
        $html = $edit['insert_html'];
        $marker = 'data-nry-editorial-edit="' . $edit['edit_id'] . '"';
        if (substr_count($source, $marker) === 1
            && strpos($source, $html . "\n") === 0) {
            return array('already' => true);
        }
        if (strpos($source, $marker) !== false) {
            return array('error' => 'marker_conflict');
        }
        $cleaned=$source;
        if (isset($edit['text_corrections'])) {
            foreach ($edit['text_corrections'] as $correction) {
                $old=$correction['old_text']; $new=$correction['new_text'];
                if (substr_count($cleaned,$old)!==1) {
                    return array('error'=>'correction_missing_or_ambiguous');
                }
                $pos=strpos($cleaned,$old);
                if (!self::plain_text_span($cleaned,$pos,strlen($old))) {
                    return array('error'=>'correction_inside_markup_or_link');
                }
                $cleaned=substr_replace($cleaned,
                    htmlspecialchars($new,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'),
                    $pos,strlen($old));
            }
            // Preserve pre-existing links, images, iframes and shortcode tokens,
            // including all original affiliate creatives and target URLs.
            $pattern='~(?:<a\\b[^>]*>|<img\\b[^>]*>|<iframe\\b[^>]*>|\\[[a-zA-Z][^\\]]*\\])~iu';
            preg_match_all($pattern,$source,$prior);
            preg_match_all($pattern,$cleaned,$after);
            if ($prior[0]!==$after[0]) {
                return array('error'=>'protected_affiliate_or_media_drift');
            }
        }
        $candidate = $html . "\n" . $cleaned;
        if (substr_count($candidate, $marker) !== 1
            || substr($candidate, strlen($html) + 1) !== $cleaned) {
            return array('error' => 'prepend_reversibility');
        }
        return array('candidate' => $candidate);
    }


    private static function validate_v4_edit($e) {
        if (!isset($e['source_sha256'], $e['candidate_sha256'], $e['edit_id'], $e['cleanup_ops'])
            || !is_string($e['source_sha256']) || !preg_match('/^[a-f0-9]{64}$/D', $e['source_sha256'])
            || !is_string($e['candidate_sha256']) || !preg_match('/^[a-f0-9]{64}$/D', $e['candidate_sha256'])
            || $e['source_sha256'] === $e['candidate_sha256']
            || !is_string($e['edit_id']) || !preg_match('/^[a-z0-9][a-z0-9-]{7,63}$/D', $e['edit_id'])
            || !is_array($e['cleanup_ops']) || count($e['cleanup_ops']) < 1
            || count($e['cleanup_ops']) > 4
            || isset($e['insert_html']) || isset($e['corrections'])) {
            return new WP_Error('v4_invalid', 'Neplatný strukturální úklid nebo SHA.');
        }
        $kinds = array();
        foreach ($e['cleanup_ops'] as $op) {
            if (!is_array($op) || !isset($op['kind']) || !is_string($op['kind'])
                || isset($kinds[$op['kind']])) {
                return new WP_Error('v4_operation', 'Neplatná nebo opakovaná operace.');
            }
            $kinds[$op['kind']] = true;
            if ($op['kind'] === 'demote_body_h1') {
                if (!isset($op['before']) || !is_string($op['before']) || strlen($op['before']) > 950
                    || !preg_match('~^<h1(?:\s[^>]*)?>[^<]{10,300}</h1>$~iuD', $op['before'])) {
                    return new WP_Error('v4_heading', 'Neplatný obsah hlavního nadpisu.');
                }
            } elseif ($op['kind'] === 'dedupe_heading') {
                if (!isset($op['html']) || !is_string($op['html'])
                    || !preg_match('~^<h[23]>[^<]{10,200}</h[23]>$~iuD', $op['html'])) {
                    return new WP_Error('v4_duplicate', 'Neplatný opakovaný nadpis.');
                }
            } else {
                return new WP_Error('v4_unknown_operation', 'Neznámá hromadná operace.');
            }
        }
        return true;
    }

    private static function build_v4_candidate($source, $edit) {
        $srcHash = hash('sha256', $source);
        if (hash_equals($edit['candidate_sha256'], $srcHash)) {
            return array('already' => true);
        }
        if (!hash_equals($edit['source_sha256'], $srcHash)) {
            return array('error' => 'source_sha_drift');
        }
        $candidate = $source;
        foreach ($edit['cleanup_ops'] as $op) {
            if ($op['kind'] === 'demote_body_h1') {
                $before = $op['before'];
                if (substr_count($candidate, $before) !== 1) {
                    return array('error' => 'h1_missing_or_nonunique');
                }
                // Only change heading level; keep the original title and all attributes.
                $after = preg_replace('~^<h1~i', '<h2', $before, 1);
                $after = preg_replace('~</h1>$~i', '</h2>', $after, 1);
                if ($after === $before || strpos($candidate, $after) !== false) {
                    return array('error' => 'h2_collision');
                }
                $candidate = str_replace($before, $after, $candidate);
            } elseif ($op['kind'] === 'dedupe_heading') {
                $heading = $op['html'];
                $n = substr_count($candidate, $heading);
                if ($n < 2 || $n > 5) {
                    return array('error' => 'heading_repetition_mismatch');
                }
                while (substr_count($candidate, $heading) > 1) {
                    $pos = strrpos($candidate, $heading);
                    $candidate = substr_replace($candidate, '', $pos, strlen($heading));
                }
            }
        }
        $pattern = '~(?:<a\b[^>]*>|<img\b[^>]*>|<iframe\b[^>]*>|\[[a-zA-Z][^\]]*\])~iu';
        preg_match_all($pattern, $source, $prior);
        preg_match_all($pattern, $candidate, $after);
        if ($prior[0] !== $after[0]) {
            return array('error' => 'affiliate_media_shortcode_drift');
        }
        if (!hash_equals($edit['candidate_sha256'], hash('sha256', $candidate))) {
            return array('error' => 'candidate_sha_mismatch');
        }
        return array('candidate' => $candidate);
    }

    private static function editorial_text($value, $min, $max) {
        return is_string($value) && strlen($value) >= $min && strlen($value) <= $max
            && trim($value) === $value
            && !preg_match('/[<>\[\]{}]/u', $value)
            && !preg_match('~(?:https?://|www\.|data:|javascript:|&#|&[a-z]+;)~iu', $value)
            && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value);
    }

    private static function render_v3($edit) {
        $esc = function($value) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        $html = '<section class="nry-editorial-decision" data-nry-editorial-edit="'
            . $edit['edit_id'] . '"><h3>Rychlé rozhodnutí před nákupem</h3><p>'
            . $esc($edit['lead']) . '</p><ul>';
        foreach ($edit['choices'] as $choice) {
            $html .= '<li><strong>' . $esc($choice['situation'])
                . ':</strong> ' . $esc($choice['recommendation'])
                . ' <em>' . $esc($choice['tradeoff']) . '</em></li>';
        }
        return $html . '</ul><p><strong>Na co si dát pozor:</strong> '
            . $esc($edit['caution']) . '</p></section>';
    }

    private static function plain_text_span($html, $pos, $length) {
        $prefix = substr($html, 0, $pos);
        $lastLt = strrpos($prefix, '<');
        $lastGt = strrpos($prefix, '>');
        // Corrections must be literal visible text, never an HTML attribute.
        if ($lastLt !== false && ($lastGt === false || $lastLt > $lastGt)) { return false; }
        $windowStart = max(0, $pos - 2000);
        $window = substr($html, $windowStart, $pos - $windowStart);
        // Never alter shortcode parameters or anchor text.
        $lastOpenShortcode = strrpos($window, '[');
        $lastCloseShortcode = strrpos($window, ']');
        if ($lastOpenShortcode !== false
            && ($lastCloseShortcode === false || $lastOpenShortcode > $lastCloseShortcode)) {
            return false;
        }
        $lastOpenAnchor = strripos($prefix, '<a ');
        $lastCloseAnchor = strripos($prefix, '</a>');
        if ($lastOpenAnchor !== false
            && ($lastCloseAnchor === false || $lastOpenAnchor > $lastCloseAnchor)) {
            return false;
        }
        return strpos(substr($html, $pos, $length), '<') === false;
    }

    private static function build_v3_candidate($source, $edit) {
        $marker = 'data-nry-editorial-edit="' . $edit['edit_id'] . '"';
        $panel = self::render_v3($edit);
        if (substr_count($source, $marker) === 1
            && substr_count($source, $panel) === 1) { return array('already' => true); }
        if (strpos($source, $marker) !== false) { return array('error' => 'marker_conflict'); }
        if (!hash_equals($edit['source_sha256'], hash('sha256', $source))) {
            return array('error' => 'source_sha_drift');
        }
        if (substr_count($source, $edit['anchor']) !== 1) {
            return array('error' => 'anchor_conflict');
        }
        $candidate = $source;
        foreach ($edit['corrections'] ?? array() as $correction) {
            $old = $correction['old_text'];
            if (substr_count($candidate, $old) !== 1) {
                return array('error' => 'correction_nonunique');
            }
            $pos = strpos($candidate, $old);
            if (!self::plain_text_span($candidate, $pos, strlen($old))) {
                return array('error' => 'correction_markup');
            }
            $candidate = substr_replace($candidate, htmlspecialchars($correction['new_text'],
                ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'), $pos, strlen($old));
        }
        if (substr_count($candidate, $edit['anchor']) !== 1
            || strpos($candidate, $marker) !== false) {
            return array('error' => 'anchor_changed');
        }
        $candidate = str_replace($edit['anchor'],
            $edit['anchor'] . "\n" . $panel, $candidate);
        if (substr_count($candidate, $marker) !== 1
            || substr_count($candidate, $panel) !== 1) {
            return array('error' => 'candidate_invalid');
        }
        // No external or internal href/image destination, iframe or shortcode may change.
        $protected = '~(?:<a\b[^>]*>|<img\b[^>]*>|<iframe\b[^>]*>|'
            . '\[[a-zA-Z][^\]]*\])~iu';
        preg_match_all($protected, $source, $beforeTokens);
        preg_match_all($protected, $candidate, $afterTokens);
        if ($beforeTokens[0] !== $afterTokens[0]) {
            return array('error' => 'protected_token_drift');
        }
        return array('candidate' => $candidate);
    }

    private static function render_v2($edit) {
        $escape = function($value) {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
        return '<aside class="nry-advisor-internal-link" data-nry-git-edit="' . $edit['edit_id']
            . '"><p>' . $escape($edit['intro']) . ' <a href="' . $edit['link_url'] . '">'
            . $escape($edit['link_label']) . '</a></p></aside>';
    }

    public static function inspect($edit) {
        $post = get_post($edit['post_id']);
        if (!$post || (int) $post->ID !== $edit['post_id'] || $post->post_type !== 'post'
            || $post->post_status !== 'publish' || $post->post_name !== $edit['slug']) {
            return array('id' => $edit['post_id'], 'status' => 'blocked', 'reason' => 'identity');
        }
        $source = $post->post_content;
        if (!is_string($source) || strlen($source) < 1000 || strlen($source) > 350000) {
            return array('id' => $post->ID, 'status' => 'blocked', 'reason' => 'size');
        }
        if (isset($edit['placement'])) {
            // The source is captured on the actual WordPress server. No
            // outbound REST authentication or WPVibe is needed.
            $built = ($edit['placement']==='sonar_spec_bonus_only')
                ? self::build_sonar_spec_bonus_candidate($source,$edit)
                : (($edit['placement']==='historic_sonar_offers_only')
                ? self::build_sonar_historic_offer_candidate($source,$edit)
                : (($edit['placement']==='ranking_claims_only')
                    ? self::build_alarm_ranking_candidate($source,$edit)
                : (($edit['placement']==='corrections_only')
                    ? self::build_alarm_score_candidate($source,$edit)
                    : (($edit['placement']==='price_corrections_only')
                        ? self::build_pipaky_price_candidate($source,$edit)
                        : self::build_v5_candidate($source,$edit)))));
            if (isset($built['already'])) {
                return array('id' => $post->ID, 'status' => 'already_applied');
            }
            $sizeMatches = isset($edit['source_bytes'])
                ? (strlen($source) === $edit['source_bytes'])
                : (isset($edit['source_min_bytes'], $edit['source_max_bytes'])
                    && strlen($source) >= $edit['source_min_bytes']
                    && strlen($source) <= $edit['source_max_bytes']);
            if ($post->post_modified_gmt !== $edit['source_modified_gmt']
                || !$sizeMatches
                || (isset($edit['source_sha256']) && !hash_equals(
                    $edit['source_sha256'], hash('sha256', $source)))) {
                return array('id' => $post->ID, 'status' => 'blocked',
                    'reason' => 'source_modified_or_bytes_drift');
            }
            if (isset($built['error'])) {
                return array('id' => $post->ID, 'status' => 'blocked',
                    'reason' => $built['error']);
            }
            return array('id' => $post->ID, 'status' => 'ready', 'source' => $source,
                'candidate' => $built['candidate'],
                'source_sha' => hash('sha256', $source),
                'candidate_sha' => hash('sha256', $built['candidate']));
        }
        if (isset($edit['cleanup_ops'])) {
            $built = self::build_v4_candidate($source, $edit);
            if (isset($built['already'])) {
                return array('id' => $post->ID, 'status' => 'already_applied');
            }
            if (isset($built['error'])) {
                return array('id' => $post->ID, 'status' => 'blocked', 'reason' => $built['error']);
            }
            return array('id' => $post->ID, 'status' => 'ready',
                'source' => $source, 'candidate' => $built['candidate'],
                'source_sha' => hash('sha256', $source),
                'candidate_sha' => hash('sha256', $built['candidate']));
        }
        if (isset($edit['choices'])) {
            $built = self::build_v3_candidate($source, $edit);
            if (isset($built['already'])) {
                return array('id' => $post->ID, 'status' => 'already_applied');
            }
            if (isset($built['error'])) {
                return array('id' => $post->ID, 'status' => 'blocked',
                    'reason' => $built['error']);
            }
            return array('id' => $post->ID, 'status' => 'ready', 'source' => $source,
                'candidate' => $built['candidate'], 'source_sha' => hash('sha256', $source),
                'candidate_sha' => hash('sha256', $built['candidate']));
        }
        $v2 = isset($edit['source_sha256']);
        $marker = $v2 ? 'data-nry-git-edit="' . $edit['edit_id'] . '"' : self::MARKER;
        $target = $v2 ? $edit['link_url'] : self::DESTINATION;
        $insert = $v2 ? self::render_v2($edit) : $edit['insert_html'];
        if ((!$v2 && substr_count($source, $marker) === 1
                && strpos($source, $target) !== false)
            || ($v2 && substr_count($source, $marker) === 1
                && substr_count($source, $insert) === 1)) {
            return array('id' => $post->ID, 'status' => 'already_applied');
        }
        if (strpos($source, $marker) !== false || strpos($source, 'href="' . $target . '"') !== false
            || substr_count($source, $edit['anchor']) !== 1) {
            return array('id' => $post->ID, 'status' => 'blocked', 'reason' => 'anchor_conflict');
        }
        if ($v2 && !hash_equals($edit['source_sha256'], hash('sha256', $source))) {
            return array('id' => $post->ID, 'status' => 'blocked', 'reason' => 'source_sha_drift');
        }
        $candidate = str_replace($edit['anchor'], $edit['anchor'] . "\n" . $insert, $source);
        if (substr_count($candidate, $marker) !== 1 || substr_count($candidate, $insert) !== 1
            || str_replace($edit['anchor'] . "\n" . $insert, $edit['anchor'], $candidate) !== $source) {
            return array('id' => $post->ID, 'status' => 'blocked', 'reason' => 'candidate');
        }
        return array('id' => $post->ID, 'status' => 'ready', 'source' => $source,
            'candidate' => $candidate, 'source_sha' => hash('sha256', $source),
            'candidate_sha' => hash('sha256', $candidate));
    }

    public static function plan($manifest) {
        if (is_wp_error($manifest)) { return $manifest; }
        return array_map(array(__CLASS__, 'inspect'), $manifest['edits']);
    }

    public static function execute($manifest) {
        $rows = self::plan($manifest);
        if (is_wp_error($rows)) { return $rows; }
        foreach ($rows as $row) {
            if ($row['status'] === 'blocked') {
                return array('status' => 'blocked_no_writes', 'writes' => 0, 'rows' => self::safe_rows($rows));
            }
        }
        // Persist originals on WordPress itself before the first v5 write.
        // Never overwrite an older backup for the same immutable batch ID.
        if (in_array($manifest['schema_version'] ?? null, array(5, 6, 7, 8), true)
            && count(array_filter($rows, function($r) { return $r['status'] === 'ready'; })) > 0) {
            $key = 'nry_gp_backup_' . md5($manifest['batch_id']);
            $snapshots = array();
            foreach ($rows as $row) {
                if ($row['status'] === 'ready') {
                    $snapshots[] = array('id' => $row['id'],
                        'source_sha' => $row['source_sha'],
                        'source' => $row['source']);
                }
            }
            if (!add_option($key, $snapshots, '', 'no')) {
                // Existing backup is a hard stop if any original differs.
                if (get_option($key, null) !== $snapshots) {
                    return array('status' => 'backup_conflict_no_writes', 'writes' => 0);
                }
            }
            if (get_option($key, null) !== $snapshots) {
                return array('status' => 'backup_verification_failed_no_writes', 'writes' => 0);
            }
        }
        $done = array(); $failed = null;
        foreach ($rows as $index => $row) {
            if ($row['status'] !== 'ready') { continue; }
            $fresh = self::inspect($manifest['edits'][$index]);
            if ($fresh['status'] !== 'ready' || $fresh['source_sha'] !== $row['source_sha']
                || $fresh['candidate_sha'] !== $row['candidate_sha']) {
                $failed = array('id' => $row['id'], 'reason' => 'concurrent_edit'); break;
            }
            $result = self::verified_local_write($row['id'],$row['candidate'],
                in_array($manifest['schema_version'] ?? null,array(5,6,7,8),true));
            if (is_wp_error($result) || (int) $result !== (int) $row['id']) {
                $failed = array('id' => $row['id'], 'reason' => 'write_failed'); break;
            }
            $after = get_post($row['id']);
            if (!$after || hash('sha256', $after->post_content) !== $row['candidate_sha']) {
                $failed = array('id' => $row['id'], 'reason' => 'readback_failed'); break;
            }
            $done[] = $row;
        }
        if ($failed !== null) {
            $uncertain = array(); $restored = array();
            foreach ($rows as $row) {
                if ($row['id'] !== $failed['id'] || $row['status'] !== 'ready') { continue; }
                $p = get_post($row['id']);
                if ($p && hash('sha256', $p->post_content) === $row['candidate_sha']) { $done[] = $row; }
                elseif (!$p || hash('sha256', $p->post_content) !== $row['source_sha']) { $uncertain[] = $row['id']; }
                break;
            }
            foreach (array_reverse($done) as $row) {
                $p = get_post($row['id']);
                if (!$p || hash('sha256', $p->post_content) !== $row['candidate_sha']) {
                    $uncertain[] = $row['id']; continue;
                }
                $r = self::verified_local_write($row['id'],$row['source'],
                    in_array($manifest['schema_version'] ?? null,array(5,6,7,8),true));
                $p = get_post($row['id']);
                if (is_wp_error($r) || !$p || hash('sha256', $p->post_content) !== $row['source_sha']) {
                    $uncertain[] = $row['id'];
                } else { $restored[] = $row['id']; }
            }
            return array('status' => $uncertain ? 'uncertain' : 'rolled_back_verified',
                'failed' => $failed, 'restored' => $restored,
                'uncertain' => array_values(array_unique($uncertain)));
        }
        return array('status' => 'applied_and_verified', 'changed' => array_column($done, 'id'),
            'writes' => count($done), 'already_applied' => count($rows) - count($done));
    }


    /**
     * Only trusted schema v5-v8 candidate writes can bypass WordPress's
     * anonymous-post KSES filters. All candidate and backup/source SHA checks
     * happen in plan/inspect/execute before reaching this helper.
     * WP arrays require wp_slash; KSES would silently strip legacy scripts.
     */
    private static function verified_local_write($postId,$content,$trusted) {
        if (!$trusted || !function_exists('wp_slash')
            || !function_exists('has_filter') || !function_exists('remove_filter')
            || !function_exists('add_filter')) {
            // CLI mocks and legacy manual-only contracts remain unchanged.
            return wp_update_post(array('ID'=>$postId,'post_content'=>$content),true);
        }
        $removed=array();
        foreach (array(
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
            return wp_update_post(array('ID'=>$postId,'post_content'=>wp_slash($content)),true);
        } finally {
            foreach ($removed as $entry) add_filter($entry[0],$entry[1],$entry[2]);
        }
    }


    /**
     * Public read-only SHA/GMT proof for fixed, separate buyer-intent scopes.
     * Each scope is a hardcoded identity allowlist, not an arbitrary post query.
     * Never expose HTML, CJ routes, draft data or release authorization.
     */
    public static function buyer_source_proof_snapshot($scope='chairs') {
        if ($scope==='alarm-ranking') {
            // Only existing published 2023 alarm ranking, never arbitrary post IDs.
            $allow = array(60354 => 'nejlepsi-rybarske-hlasice-a-signalizatory');
        } elseif ($scope==='pipaky-main') {
            // GSC query-intent lead #42401; fixed published slug only.
            // Neither arbitrary IDs nor raw source are ever exposed.
            $allow = array(42401 =>
                'jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne');
        } elseif ($scope==='pipaky-professional') {
            // One source-bound brand-comparison guide, hash-only, no user IDs.
            $allow = array(36702 => 'pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu');
        } elseif ($scope==='pipaky-beginner') {
            // Exactly one published beginner how-to; never any user-supplied ID.
            $allow = array(35755 => 'pipaky-na-ryby-prakticke-rady-pro-zacatecniky');
        } elseif ($scope==='alarm-guide') {
            // Exactly one existing published alarm buyer guide; not open ID lookup.
            $allow = array(118218 => 'nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu');
        } elseif ($scope==='echolot-pillar') {
            // Exact known published sonar decision guide, no open-ended IDs.
            $allow = array(118553 => 'jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce');
        } elseif ($scope==='echolot-boat') {
            // Boat-only buyer guide: source identity needed before a fresh
            // problem-first internal navigation improvement, not raw HTML.
            $allow = array(60637 =>
                'jak-vybrat-nejlepsi-rybarsky-echolot-na-lod-vybirame-sonar-pro-rybare');
        } elseif ($scope==='echolot-historic') {
            // Historic buying comparison: keep URL intact, never relaunch
            // the old current-prices claims or change existing merchant hrefs.
            $allow = array(36518 =>
                'porovnani-nejlepsich-echolotu-na-trhu-v-roce-2023');
        } elseif ($scope==='echolot-budget') {
            // One exact verified published buyer guide, no arbitrary ID.
            $allow = array(118455 => 'nejlepsi-echoloty-do-15-000-kc-co-realne-uvidite-za-rozumny-peniz');
        } elseif ($scope==='buyer-hub') {
            // Exact public post only: never make this an arbitrary ID lookup.
            $allow = array(60341 => 'nejlepsi-rybarske-kreslo-vybirame-zidlicku-na-ryby');
        } elseif ($scope==='evergreen') {
            $allow = array(11901 => 'jak-vybrat-rybarske-kreslo');
        } elseif ($scope==='chairs') {
            $allow = array(
                34858 => 'jak-vybrat-detske-rybarske-kreslo-pro-pohodlne-rybareni',
                34953 => 'jak-vychytat-nejlepsi-rybarske-kreslo-na-nadchazejici-akci',
                34969 => 'jak-si-vybrat-levne-a-pohodlne-rybarske-kreslo',
            );
        } else {
            return array('schema'=>1,'publication_authorized'=>false,
                'wordpress_writes'=>0,'rows'=>array(),
                'state'=>'BLOCKED_UNSUPPORTED_PROOF_SCOPE');
        }
        $rows=array();
        foreach ($allow as $id=>$slug) {
            $post=get_post($id);
            if (!is_object($post) || (int)($post->ID??0)!==$id
                || ($post->post_type??'')!=='post'
                || ($post->post_status??'')!=='publish'
                || ($post->post_name??'')!==$slug
                || !is_string($post->post_content??null)
                || strlen($post->post_content)<1000
                || !is_string($post->post_modified_gmt??null)
                || !preg_match('/^20\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/D',$post->post_modified_gmt)
                || get_permalink($id)!=='https://na-ryby.eu/'.$slug.'/') {
                $rows[]=array('id'=>$id,'state'=>'BLOCKED_IDENTITY_OR_SOURCE');
                continue;
            }
            $row=array(
                'id'=>$id,'state'=>'SOURCE_SHA_AVAILABLE_NOT_PUBLICATION_AUTHORITY',
                'slug'=>$slug,'source_modified_gmt'=>$post->post_modified_gmt,
                'source_bytes'=>strlen($post->post_content),
                'source_sha256'=>hash('sha256',$post->post_content)
            );
            if ($scope==='pipaky-beginner' || $scope==='pipaky-professional'
                || $scope==='pipaky-main') {
                // Metadata hashes only, never disclose source body or meta value.
                // This enables a second source-pinned, rollback-capable Yoast
                // snippet correction, NOT permission to write or redirect.
                $title=get_post_meta($id,'_yoast_wpseo_title',true);
                $desc=get_post_meta($id,'_yoast_wpseo_metadesc',true);
                if (!is_string($title) || !is_string($desc)) {
                    $row['state']='BLOCKED_NON_STRING_SEO_METADATA';
                    unset($row['source_sha256']);
                } else {
                    $row['raw_yoast_title_sha256']=hash('sha256',$title);
                    $row['raw_yoast_description_sha256']=hash('sha256',$desc);
                    $row['raw_yoast_title_bytes']=strlen($title);
                    $row['raw_yoast_description_bytes']=strlen($desc);
                    $row['raw_yoast_title_exists']=metadata_exists('post',$id,'_yoast_wpseo_title');
                    $row['raw_yoast_description_exists']=metadata_exists('post',$id,'_yoast_wpseo_metadesc');
                }
            }
            if ($scope==='pipaky-professional') {
                // Exactly five historical public price literals. Return only
                // bounded counts, never raw body, href, source fragments, CJ
                // tracking parameters or proposed publication permission.
                // A safe plain-text span must not straddle any HTML attribute
                // nor touch an anchor, image or shortcode.
                $price_labels=array(
                    '1000'=>'Cena: od 1000 Kč',
                    '800'=>'Cena: od 800 Kč',
                    '3000'=>'Cena: od 3000 Kč',
                    '1200'=>'Cena: od 1200 Kč',
                    '2000'=>'Cena: od 2000 Kč'
                );
                $raw_counts=array(); $safe_counts=array();
                foreach ($price_labels as $key=>$needle) {
                    $raw_counts[$key]=substr_count($post->post_content,$needle);
                    $safe_counts[$key]=0;
                    $offset=0;
                    while (($at=strpos($post->post_content,$needle,$offset))!==false) {
                        if (self::plain_text_span($post->post_content,$at,strlen($needle))) {
                            $safe_counts[$key]++;
                        }
                        $offset=$at+strlen($needle);
                    }
                }
                $row['legacy_price_raw_literal_counts']=$raw_counts;
                $row['legacy_price_safe_plain_counts']=$safe_counts;
                // Unverified destination provenance. Counts only, and do
                // not use them as authority to delete/edit a link.
                $row['legacy_external_host_literal_counts']=array(
                    'zaclony'=>substr_count($post->post_content,'rybarske-potreby-na-zaclony.cz'),
                    'lukavecu'=>substr_count($post->post_content,'rybarske-potreby-na-lukavecu.cz')
                );
            }
            if ($scope==='echolot-budget') {
                // Three historically public product-price and stock sentences.
                // Source-only counts: no body, merchant href, stock price
                // entitlement or future edit authority is returned.
                $targets=array(
                    'eagle4x_old_offer'=>'v době přípravy článku skladem za 4 509 Kč',
                    'hook5_old_offer'=>'aktuálně 10 542 Kč a skladem 1 ks',
                    'eagle7_old_offer'=>'v době přípravy článku skladem za 13 889 Kč',
                );
                $raw=array();$plain=array();
                foreach ($targets as $key=>$phrase) {
                    $raw[$key]=substr_count($post->post_content,$phrase);
                    $plain[$key]=0;$offset=0;
                    while (($pos=strpos($post->post_content,$phrase,$offset))!==false) {
                        if (self::plain_text_span($post->post_content,$pos,strlen($phrase))) {
                            $plain[$key]++;
                        }
                        $offset=$pos+strlen($phrase);
                    }
                }
                $row['historical_offer_raw_counts']=$raw;
                $row['historical_offer_safe_plain_counts']=$plain;
                // 2026-11-08 fixed public buyer-trust verification only.
                // Count exact claims in server-side raw source and plain text,
                // never disclose HTML or enable writing/affiliate changes.
                $trustTargets=array(
                    'eagle4x_false_gps'=>'Nízká cena, GPS, jednoduchost, bonus baterie.',
                    'eagle4x_battery_assertion'=>'baterie zdarma v balení',
                    'eagle7_current_bundle'=>'Chyť a pusť tu má rozumnou aktuální nabídku včetně baterie a nabíječky.',
                    'eagle7_current_cta'=>'V Chyť a pusť aktuálně dává větší smysl i díky přibalené baterii a nabíječce.',
                    'eagle4x_bonus_title'=>'Lowrance Eagle 4X se sondou Bullet + baterie ZDARMA',
                    'eagle7_bonus_title'=>'Lowrance Eagle 7 se sondou SplitShot HD + baterie + nabíječka ZDARMA',
                );
                $trustRaw=array();$trustSafe=array();
                foreach ($trustTargets as $key=>$phrase) {
                    $trustRaw[$key]=substr_count($post->post_content,$phrase);
                    $trustSafe[$key]=0;$offset=0;
                    while (($pos=strpos($post->post_content,$phrase,$offset))!==false) {
                        if (self::plain_text_span($post->post_content,$pos,strlen($phrase))) {
                            $trustSafe[$key]++;
                        }
                        $offset=$pos+strlen($phrase);
                    }
                }
                $row['fixed_offer_claim_raw_counts']=$trustRaw;
                $row['fixed_offer_claim_safe_plain_counts']=$trustSafe;
            }
            if ($scope==='alarm-ranking') {
                // Fixed phrases from the public 2023 article; disclose ONLY
                // raw/visible-safe occurrence counts, never source fragments.
                $targets=array(
                    'false_shop_claim'=>'Pokud jste vášnivý rybář, určitě oceníte naše produkty.',
                    'unproven_price_winner'=>'Nejlepší poměr cena/výkon',
                    'unproven_top_winner'=>'Top produkt na trhu',
                    'five_stars'=>'⭐⭐⭐⭐⭐',
                    'four_stars'=>'⭐⭐⭐⭐',
                    'unproven_waterproof'=>'100% vodotěsné',
                    'unproven_waterproof_guarantee'=>'Voděodolnost zaručena',
                    'current_market_promise'=>'Pojďme se společně podívat na ty nejlepší signalizátory, kterou jsou momentálně na našem trhu.'
                );
                $raw=array();$safe=array();
                foreach ($targets as $key=>$phrase) {
                    $raw[$key]=substr_count($post->post_content,$phrase);
                    $safe[$key]=0;$offset=0;
                    while (($at=strpos($post->post_content,$phrase,$offset))!==false) {
                        if (self::plain_text_span($post->post_content,$at,strlen($phrase))) $safe[$key]++;
                        $offset=$at+strlen($phrase);
                    }
                }
                $row['legacy_rank_raw_literal_counts']=$raw;
                $row['legacy_rank_safe_plain_counts']=$safe;
            }
            if ($scope==='alarm-guide') {
                // Six visible published scores only. Report COUNTS, never raw HTML,
                // links, merchant parameters, cookies or new write permissions.
                $score_labels=array('8,1 / 10','8,6 / 10','8,9 / 10',
                    '9,2 / 10','9,1 / 10','9,4 / 10');
                $score_counts=array();
                foreach ($score_labels as $score) {
                    $score_counts[$score]=substr_count($post->post_content,$score);
                }
                $row['legacy_table_score_literal_counts']=$score_counts;
                $row['buyer_warning_marker_count']=substr_count(
                    $post->post_content,'data-nry-editorial-edit="alarm-buyer-prices-trust-118218-v1"');
                $row['historical_score_warning_count']=substr_count(
                    $post->post_content,'Uvedené částky v Kč, označení akcí 1+1 a číselná hodnocení typu 8,9/10 nejsou v tomto okamžiku nezávisle ověřenými cenami, skladovými nabídkami ani výsledkem zveřejněné srovnávací testovací metodiky.');
            }
            if ($scope==='buyer-hub') {
                // Only fixed, already public text checks; no raw source or merchant IDs.
                $row['trust_correction_targets']=array(
                    'false_manufacturer_claim'=>substr_count($post->post_content,
                        'Naše společnost se specializuje na výrobu a prodej nejlepších rybářských křesel na trhu.'),
                    'sales_claim'=>substr_count($post->post_content,
                        'Jsme si jisti, že si u nás vyberete židličku na ryby, která splní vaše očekávání a zvýší vaši radost z rybaření.'),
                    'visible_keyword_line'=>substr_count($post->post_content,
                        'klíčová slova: rybářské křeslo, židlička na ryby, pohodlí, ergonomický design, opěrky pro záda, odolnost, úložný prostor, rybářský náčiní'),
                    'keyword_line_prefix'=>substr_count($post->post_content,
                        'klíčová slova: rybářské křeslo, židlička na ryby'),
                    'keyword_marker_only'=>substr_count($post->post_content,
                        'klíčová slova:'),
                    'false_chair_ownership_prefix'=>substr_count($post->post_content,
                        'Naše rybářská křesla jsou také vybavena')
                );
            }
            $rows[]=$row;
        }
        return array('schema'=>1,'site'=>'https://na-ryby.eu',
            'data_class'=>'HASH_AND_MODIFIED_GMT_ONLY_NO_POST_CONTENT',
            'publisher_version'=>'0.16.0','publication_authorized'=>false,
            'gsc_protection_checked'=>false,'wordpress_writes'=>0,'rows'=>$rows);
    }
    public static function handle_buyer_public_proof() {
        $chairs=isset($_GET['nry_buyer_source_proof'])
            && $_GET['nry_buyer_source_proof']==='1';
        $evergreen=isset($_GET['nry_evergreen_source_proof'])
            && $_GET['nry_evergreen_source_proof']==='1';
        $hub=isset($_GET['nry_buyer_hub_source_proof'])
            && $_GET['nry_buyer_hub_source_proof']==='1';
        $echolot=isset($_GET['nry_echolot_budget_source_proof'])
            && $_GET['nry_echolot_budget_source_proof']==='1';
        $pillar=isset($_GET['nry_echolot_pillar_source_proof'])
            && $_GET['nry_echolot_pillar_source_proof']==='1';
        $boat=isset($_GET['nry_echolot_boat_source_proof'])
            && $_GET['nry_echolot_boat_source_proof']==='1';
        $historic=isset($_GET['nry_echolot_historic_source_proof'])
            && $_GET['nry_echolot_historic_source_proof']==='1';
        $alarm=isset($_GET['nry_alarm_guide_source_proof'])
            && $_GET['nry_alarm_guide_source_proof']==='1';
        $beginner=isset($_GET['nry_pipaky_beginner_source_proof'])
            && $_GET['nry_pipaky_beginner_source_proof']==='1';
        $professional=isset($_GET['nry_pipaky_professional_source_proof'])
            && $_GET['nry_pipaky_professional_source_proof']==='1';
        $ranking=isset($_GET['nry_alarm_ranking_source_proof'])
            && $_GET['nry_alarm_ranking_source_proof']==='1';
        $pipakyMain=isset($_GET['nry_pipaky_main_source_proof'])
            && $_GET['nry_pipaky_main_source_proof']==='1';
        // Exactly one explicitly allowlisted GET selector; never arbitrary IDs.
        if (((int)$chairs+(int)$evergreen+(int)$hub+(int)$echolot+(int)$pillar+(int)$boat+(int)$historic+(int)$alarm+(int)$beginner+(int)$professional+(int)$pipakyMain+(int)$ranking)!==1
            || !function_exists('is_page') || !is_page('poradce-vybavy')) return;
        if (rtrim(home_url('/'),'/')!=='https://na-ryby.eu') return;
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        echo wp_json_encode(self::buyer_source_proof_snapshot(
            $boat ? 'echolot-boat' : ($historic ? 'echolot-historic' : ($ranking ? 'alarm-ranking' : ($pipakyMain ? 'pipaky-main' : ($professional ? 'pipaky-professional' : ($beginner ? 'pipaky-beginner' : ($alarm ? 'alarm-guide' : ($pillar ? 'echolot-pillar' : ($echolot ? 'echolot-budget' : ($hub ? 'buyer-hub' : ($evergreen ? 'evergreen' : 'chairs'))))))))))));
        exit;
    }

    private static function safe_rows($rows) {
        return array_map(function ($r) { return array('id' => $r['id'], 'status' => $r['status']); }, $rows);
    }
    public static function register_menu() {
        add_management_page('Na-Ryby Git Publisher', 'Na-Ryby Git Publisher', 'manage_options',
            'nry-git-publisher', array(__CLASS__, 'page'));
    }
    public static function page() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        $rows = self::plan(self::manifest()); $last = get_option(self::RECEIPT, array());
        $autoEnabled = get_option('nry_gp_auto_enabled_v1', 'no') === 'yes';
        echo '<div class="wrap"><h1>Na-Ryby Git Publisher</h1>';
        echo '<p>Schválené změny jsou v manifest.json dodaném z Gitu. WordPress zapisuje přímo na serveru, nikoliv přes WPVibe. Verze 5/6 porovnává identitu a datum úpravy; v5 přesnou délku, v6 bezpečný rozsah délky. Obě varianty zálohují původní text a kontrolují SHA během zápisu.</p>';
        echo $autoEnabled
            ? '<p><strong>Automatika je zapnutá</strong> pro omezené dávky v5–v8. Ruční Apply je při zapnuté automatice vypnuto. Stav a vypnutí: Nástroje → Na-Ryby Auto Publisher.</p>'
            : '<p>Nic se nepublikuje automaticky. Omezenou automatiku lze aktivovat v Nástroje → Na-Ryby Auto Publisher.</p>';
        if (is_wp_error($rows)) { echo '<p>' . esc_html($rows->get_error_message()) . '</p></div>'; return; }
        $blocked = false;
        echo '<table class="widefat striped"><thead><tr><th>Post ID</th><th>Kontrola</th></tr></thead><tbody>';
        foreach ($rows as $r) { if ($r['status'] === 'blocked') { $blocked = true; }
            echo '<tr><td>' . esc_html((string) $r['id']) . '</td><td>' . esc_html($r['status']) . '</td></tr>'; }
        echo '</tbody></table>';
        if (is_array($last) && isset($last['status'])) {
            echo '<p>Poslední běh: <strong>' . esc_html($last['status']) . '</strong></p>';
        }
        if (!$blocked && !$autoEnabled) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_git_publisher_apply">';
            wp_nonce_field('nry_git_publisher_apply');
            submit_button('Zapsat ověřené změny do WordPressu'); echo '</form>';
        } elseif ($blocked) { echo '<p>Nelze pokračovat: některý článek se mezitím změnil.</p>'; }
        echo '</div>';
    }
    public static function handle_apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_git_publisher_apply');
        if (get_option('nry_gp_auto_enabled_v1', 'no') === 'yes') {
            wp_die('Nejprve vypněte automatiku v Nástroje → Na-Ryby Auto Publisher.');
        }
        $receipt = self::execute(self::manifest());
        update_option(self::RECEIPT, is_wp_error($receipt) ? array('status' => 'manifest_invalid') : $receipt, false);
        wp_safe_redirect(admin_url('tools.php?page=nry-git-publisher')); exit;
    }
}
add_action('template_redirect',array('NRY_Git_Publisher','handle_buyer_public_proof'),22);
add_action('admin_menu', array('NRY_Git_Publisher', 'register_menu'));
add_action('admin_post_nry_git_publisher_apply', array('NRY_Git_Publisher', 'handle_apply'));
