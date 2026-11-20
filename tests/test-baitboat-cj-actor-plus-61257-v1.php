<?php
/** Offline contract: no WordPress writes, CJ clicks or merchant requests. */
define('ABSPATH', __DIR__);
$GLOBALS['hooks'] = array();
$GLOBALS['options'] = array();
function add_action($name, $callback, $priority = 10) { $GLOBALS['hooks'][] = array($name, $callback, $priority); }
function add_filter($name, $callback, $priority = 10) { $GLOBALS['hooks'][] = array($name, $callback, $priority); }
function get_option($key, $fallback = false) { return $GLOBALS['options'][$key] ?? $fallback; }
function get_post($id) { return $id === 61257 ? $GLOBALS['post'] : false; }
function is_single($id) { return $id === 61257 && !empty($GLOBALS['single']); }
function is_singular($type) { return $type === 'post' && !empty($GLOBALS['single']); }
function must($yes, $message) { if (!$yes) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); } }

/** Minimal test double for the PHP WordPress HTML Tag Processor's anchor API. */
class WP_HTML_Tag_Processor {
    private $html;
    private $offset = 0;
    private $start = 0;
    private $tag = '';
    public function __construct($html) { $this->html = $html; }
    public function next_tag($args) {
        if (!preg_match('~<a\b[^>]*>~i', $this->html, $m, PREG_OFFSET_CAPTURE, $this->offset)) {
            return false;
        }
        $this->tag = $m[0][0];
        $this->start = $m[0][1];
        $this->offset = $this->start + strlen($this->tag);
        return true;
    }
    public function get_attribute($name) {
        return preg_match('~\b' . preg_quote($name, '~') . '="([^"]*)"~i', $this->tag, $m) ? $m[1] : null;
    }
    public function set_attribute($name, $value) {
        $escaped = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $pattern = '~\b' . preg_quote($name, '~') . '="[^"]*"~i';
        $replacement = $name . '="' . $escaped . '"';
        $new = preg_match($pattern, $this->tag)
            ? preg_replace($pattern, $replacement, $this->tag, 1)
            : substr_replace($this->tag, ' ' . $replacement, -1, 0);
        $this->html = substr_replace($this->html, $new, $this->start, strlen($this->tag));
        $this->tag = $new;
        $this->offset = $this->start + strlen($new);
    }
    public function get_updated_html() { return $this->html; }
}

require dirname(__DIR__) . '/baitboat-cj-actor-plus-61257-v1.php';
$c = 'NRY_Baitboat_CJ_Actor_Plus_61257_V1';
$merchant = $c::MERCHANT;
$old = 'http://www.dpbolvw.net/click-100927011-12347575?url=' . rawurlencode($merchant);
$new = $c::OWN_LINK;
must($c::old_href($old), 'owner-supplied exact product legacy href matches');
must($c::old_href(str_replace('http://', 'https://', $old)), 'legacy https also accepted');
must(!$c::old_href($new), 'own-property URL never matched as legacy');
foreach (array(
    str_replace('100927011', '8287231', $old),
    str_replace('12347575', '11111111', $old),
    str_replace('www.dpbolvw.net', 'www.dpbolvw.net.evil.test', $old),
    $old . '&ref=other',
    $old . '#fragment',
    str_replace('Boatman-Zavazeci-lodicka-Actor-Plus-Basic-cerna', 'Changed', $old),
    str_replace('https%3A', 'http%3A', $old),
) as $unsafe) { must(!$c::old_href($unsafe), 'reject non-identical URL'); }

$source = '<p>Výběr loděk</p>';
for ($i = 0; $i < 4; $i++) {
    $source .= '<p><a href="' . $old . '" rel="noopener">Boatman ' . $i . '</a></p>';
}
for ($i = 0; $i < 16; $i++) {
    $source .= '<p><a href="http://www.kqzyfj.com/click-100927011-12347575?url='
        . rawurlencode('https://www.chytapust.cz/Other/Item-' . $i) . '">Other ' . $i . '</a></p>';
}
$GLOBALS['post'] = (object) array('ID' => 61257, 'post_type' => 'post',
    'post_status' => 'publish', 'post_name' => $c::SLUG,
    'post_modified_gmt' => $c::MODIFIED_GMT, 'post_content' => $source);
$GLOBALS['single'] = true;
$inspection = $c::inspection();
must($inspection['ready'] && !$inspection['approved'], 'fresh private source match, disabled by default');
must($c::render($source) === $source, 'disabled is byte-identical to source');

$GLOBALS['options'][$c::APPROVAL] = array('source_sha' => $inspection['source_sha'],
    'candidate_sha' => $inspection['candidate_sha'],
    'link_sha' => hash('sha256', $new));
must($c::inspection()['approved'], 'source-bound gate explicitly enabled');
$rendered = $c::render($source);
must($rendered !== $source && substr_count($rendered, $new) === 4, 'only four owner links render');
must(substr_count($rendered, 'click-100927011-12347575') === 16, 'other sixteen links preserved');
must(substr_count($rendered, 'sponsored nofollow') === 4, 'sponsored and nofollow rel set');
must(substr_count($rendered, '<p>') === 21, 'surrounding editorial content preserved');

$GLOBALS['single'] = false;
must($c::render($source) === $source, 'unrelated template never changed');
$GLOBALS['single'] = true;
$GLOBALS['post']->post_modified_gmt = '2026-05-14 10:22:17';
must(!$c::inspection()['approved'], 'modified timestamp drift fails closed');
must($c::render($source) === $source, 'drifted source does not change visitor links');
$GLOBALS['post']->post_modified_gmt = $c::MODIFIED_GMT;
$GLOBALS['post']->post_content .= '<p>Changed by editor</p>';
must($c::inspection()['conflict'], 'private source hash drift blocks approval');
$GLOBALS['post']->post_content = $source;
must($c::inspection()['approved'], 'exact original source restores prior approval');
$GLOBALS['options'][$c::APPROVAL]['candidate_sha'] = str_repeat('0', 64);
must(!$c::inspection()['approved'], 'immutable candidate fingerprint required');
unset($GLOBALS['options'][$c::APPROVAL]);
must($c::render($source) === $source, 'one-option rollback restores all hrefs');

must($c::rewrite_html(str_replace('Boatman 3', 'Other', str_replace($old, 'https://invalid.example', $source))) === false,
    'wrong anchor count rejects entire transformation');
must($c::rewrite_html($source . '<a href="' . $new . '">already own</a>') === false,
    'existing identical new link blocks ambiguous rollout');
must(in_array(array('admin_menu', array($c, 'admin_menu'), 10), $GLOBALS['hooks'], true),
    'admin approval tooling registered');
must(in_array(array('admin_post_nry_cj_actor_plus_61257_apply', array($c, 'apply'), 10),
    $GLOBALS['hooks'], true), 'only guarded admin Apply registered');
$c::boot();
must(in_array(array('the_content', array($c, 'render'), 98), $GLOBALS['hooks'], true),
    'late visitor render, only after private approval');
echo "PASS: one-product, four-of-twenty, source-pinned, admin-gated, reversible render-only replacement\n";
