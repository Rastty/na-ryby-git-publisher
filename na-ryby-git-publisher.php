<?php
/**
 * Plugin Name: Na-Ryby.eu – Git Publisher
 * Description: Apply guarded Git-managed content manifests from wp-admin without WPVibe or outbound WordPress REST writes.
 * Version: 0.7.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 */
if (!defined('ABSPATH')) { exit; }

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
    public static function validate_manifest($data) {
        if (!is_array($data) || !in_array($data['schema_version'] ?? null, array(1, 2, 3, 4, 5, 6, 7), true)
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
        if (!$v2 && !$v3 && !$v4 && !$v5 && !$v6 && !$v7 && (count($data['edits']) !== 2
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
                || (!$v4 && !$v5 && !$v6 && !$v7 && (!isset($e['anchor']) || !is_string($e['anchor'])))
                || (!$v4 && !$v5 && !$v6 && !$v7 && !preg_match($v3 ? '~^<h[23](?:\s[^>]*)?>[^<]{8,280}</h[23]>$~uD' : '~^<h[12](?:\s[^>]*)?>[^<]{8,280}</h[12]>$~uD', $e['anchor']))) {
                return new WP_Error('invalid_item', 'Neschválená identita, kotva nebo chráněný článek.');
            }
            $seen[$e['post_id']] = true;
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
        $candidate = $html . "\n" . $source;
        if (substr_count($candidate, $marker) !== 1
            || substr($candidate, strlen($html) + 1) !== $source) {
            return array('error' => 'append_only_reversibility');
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
            $built = self::build_v5_candidate($source, $edit);
            if (isset($built['already'])) {
                return array('id' => $post->ID, 'status' => 'already_applied');
            }
            $sizeMatches = isset($edit['source_bytes'])
                ? (strlen($source) === $edit['source_bytes'])
                : (isset($edit['source_min_bytes'], $edit['source_max_bytes'])
                    && strlen($source) >= $edit['source_min_bytes']
                    && strlen($source) <= $edit['source_max_bytes']);
            if ($post->post_modified_gmt !== $edit['source_modified_gmt']
                || !$sizeMatches) {
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
        if (in_array($manifest['schema_version'] ?? null, array(5, 6, 7), true)
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
            $result = wp_update_post(array('ID' => $row['id'], 'post_content' => $row['candidate']), true);
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
                $r = wp_update_post(array('ID' => $row['id'], 'post_content' => $row['source']), true);
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
        echo '<div class="wrap"><h1>Na-Ryby Git Publisher</h1>';
        echo '<p>Schválené změny jsou v manifest.json dodaném z Gitu. Nic se nepublikuje automaticky. WordPress zapisuje přímo na serveru, nikoliv přes WPVibe. Verze 5/6 porovnává identitu a datum úpravy; v5 přesnou délku, v6 bezpečný rozsah délky. Obě varianty zálohují původní text a kontrolují SHA během zápisu.</p>';
        if (is_wp_error($rows)) { echo '<p>' . esc_html($rows->get_error_message()) . '</p></div>'; return; }
        $blocked = false;
        echo '<table class="widefat striped"><thead><tr><th>Post ID</th><th>Kontrola</th></tr></thead><tbody>';
        foreach ($rows as $r) { if ($r['status'] === 'blocked') { $blocked = true; }
            echo '<tr><td>' . esc_html((string) $r['id']) . '</td><td>' . esc_html($r['status']) . '</td></tr>'; }
        echo '</tbody></table>';
        if (is_array($last) && isset($last['status'])) {
            echo '<p>Poslední běh: <strong>' . esc_html($last['status']) . '</strong></p>';
        }
        if (!$blocked) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            echo '<input type="hidden" name="action" value="nry_git_publisher_apply">';
            wp_nonce_field('nry_git_publisher_apply');
            submit_button('Zapsat ověřené změny do WordPressu'); echo '</form>';
        } else { echo '<p>Nelze pokračovat: některý článek se mezitím změnil.</p>'; }
        echo '</div>';
    }
    public static function handle_apply() {
        if (!current_user_can('manage_options')) { wp_die('Access denied'); }
        check_admin_referer('nry_git_publisher_apply');
        $receipt = self::execute(self::manifest());
        update_option(self::RECEIPT, is_wp_error($receipt) ? array('status' => 'manifest_invalid') : $receipt, false);
        wp_safe_redirect(admin_url('tools.php?page=nry-git-publisher')); exit;
    }
}
add_action('admin_menu', array('NRY_Git_Publisher', 'register_menu'));
add_action('admin_post_nry_git_publisher_apply', array('NRY_Git_Publisher', 'handle_apply'));
