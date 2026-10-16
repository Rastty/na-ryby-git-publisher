<?php
/**
 * Plugin Name: Na-Ryby.eu – Git Publisher
 * Description: Apply guarded Git-managed content manifests from wp-admin without WPVibe or outbound WordPress REST writes.
 * Version: 0.2.0
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
        if (!is_array($data) || !in_array($data['schema_version'] ?? null, array(1, 2), true)
            || ($data['site'] ?? '') !== 'https://na-ryby.eu'
            || !isset($data['batch_id'], $data['edits'])
            || !is_string($data['batch_id'])
            || !preg_match('/^[a-z0-9][a-z0-9-]{7,99}$/D', $data['batch_id'])
            || !is_array($data['edits']) || count($data['edits']) < 1
            || count($data['edits']) > 25) {
            return new WP_Error('manifest_invalid', 'Neplatný manifest změn.');
        }
        $v2 = $data['schema_version'] === 2;
        if (!$v2 && (count($data['edits']) !== 2
            || array_column($data['edits'], 'post_id') !== array(52975, 118517))) {
            return new WP_Error('legacy_scope', 'V1 povoluje jen historickou dvojici.');
        }
        $seen = array(); $markers = array();
        foreach ($data['edits'] as $e) {
            if (!is_array($e) || !isset($e['post_id'], $e['slug'], $e['anchor'])
                || !is_int($e['post_id']) || $e['post_id'] < 1
                || in_array($e['post_id'], array(38064, 42671), true)
                || isset($seen[$e['post_id']])
                || !is_string($e['slug']) || !preg_match('/^[a-z0-9-]{10,140}$/D', $e['slug'])
                || !is_string($e['anchor'])
                || !preg_match('~^<h[12](?:\s[^>]*)?>[^<]{8,280}</h[12]>$~uD', $e['anchor'])) {
                return new WP_Error('invalid_item', 'Neschválená identita, kotva nebo chráněný článek.');
            }
            $seen[$e['post_id']] = true;
            if (!$v2) {
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
        return $data;
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
        echo '<p>Schválené změny jsou v manifest.json dodaném z Gitu. Nic se nepublikuje automaticky. WordPress zapisuje přímo na serveru, nikoliv přes WPVibe.</p>';
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
