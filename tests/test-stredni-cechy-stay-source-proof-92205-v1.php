<?php
/** Offline-only, no WordPress write or merchant request. */
define('ABSPATH', __DIR__);
$GLOBALS['nry_92205_post'] = (object)array(
    'ID' => 92205, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach',
    'post_modified_gmt' => '2024-10-07 12:15:48',
    'post_content' => str_repeat('Rybářské ubytování ', 100)
        .' Nabízíme nezapomenutelné Jindřichovice pod Smrkem rybareni-strednicesko.cz',
);
function add_action($name, $callback, $priority) { $GLOBALS['nry_92205_action'] = array($name, $callback, $priority); }
function get_post($id) { return $id === 92205 ? $GLOBALS['nry_92205_post'] : null; }
function get_permalink($id) { return $id === 92205
    ? 'https://na-ryby.eu/zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach/' : ''; }
function check($v, $message) { if (!$v) throw new RuntimeException($message); }
require dirname(__DIR__).'/stredni-cechy-stay-source-proof-92205-v1.php';
$class = 'NRY_Stredni_Cechy_Stay_Source_92205_V1';
check($GLOBALS['nry_92205_action'][0] === 'template_redirect', 'only template redirect handler');
$ready = $class::snapshot();
check($ready['state'] === 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'published exact source');
check($ready['sourceSha256'] === hash('sha256', $GLOBALS['nry_92205_post']->post_content), 'raw source SHA');
check($ready['literalCounts']['weOfferExperience'] === 1, 'misleading offer language counted');
check($ready['literalCounts']['jindrichovice'] === 1, 'mislocated page content counted');
check($ready['publicationAuthorized'] === false && $ready['wordpressWrites'] === 0, 'never a writer');
$GLOBALS['nry_92205_post']->post_modified_gmt = '2024-10-08 00:00:00';
check($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'modified source blocked');
$GLOBALS['nry_92205_post']->post_modified_gmt = '2024-10-07 12:15:48';
$GLOBALS['nry_92205_post']->post_name = 'some-other-post';
check($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'slug mismatch blocked');
$GLOBALS['nry_92205_post']->post_name = 'zazitkove-rybareni-s-ubytovanim-ve-strednich-cechach';
$GLOBALS['nry_92205_post']->post_status = 'draft';
check($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'draft blocked');
echo "PASS: exact-source hash-only proof; no edits, events or external requests\n";
