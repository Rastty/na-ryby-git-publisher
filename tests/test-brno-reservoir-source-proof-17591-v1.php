<?php
/** Offline-only read-only source proof contract for post 17591. */
define('ABSPATH', __DIR__);
$GLOBALS['brno_post'] = (object)array(
    'ID' => 17591, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'chata-brnenska-prehrada',
    'post_modified_gmt' => '2018-01-22 19:53:18',
    'post_content' => str_repeat('Chata Brněnská přehrada, dostupnost ověřte. ', 140)
        .'Vranov 300 Kč 2420 https://na-ryby.eu/booking-cesko',
);
function add_action($name, $callback, $priority) { $GLOBALS['brno_action'] = array($name, $callback, $priority); }
function get_post($id) { return $id === 17591 ? $GLOBALS['brno_post'] : null; }
function get_permalink($id) { return $id === 17591 ? 'https://na-ryby.eu/chata-brnenska-prehrada/' : ''; }
function proof_assert($pass, $message) { if (!$pass) throw new RuntimeException($message); }
require dirname(__DIR__).'/brno-reservoir-source-proof-17591-v1.php';
$class = 'NRY_Brno_Reservoir_Source_17591_V1';
proof_assert($GLOBALS['brno_action'] === array('template_redirect', array($class, 'handle'), 23), 'GET handler');
$proof = $class::snapshot();
proof_assert($proof['state'] === 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'source available');
proof_assert($proof['sourceSha256'] === hash('sha256', $GLOBALS['brno_post']->post_content), 'exact raw SHA');
proof_assert($proof['sourceBytes'] === strlen($GLOBALS['brno_post']->post_content), 'exact byte count');
proof_assert(!array_key_exists('post_content', $proof) && !array_key_exists('content', $proof)
    && strpos(json_encode($proof), 'Brněnská přehrada') === false, 'no article body disclosure');
proof_assert($proof['literalCounts']['vranovMentions'] === 1
    && $proof['literalCounts']['permitPriceMentions'] === 1, 'historical literal counts');
proof_assert($proof['publicationAuthorized'] === false && $proof['wordpressWrites'] === 0
    && $proof['merchantRequests'] === 0 && $proof['syntheticClicks'] === 0, 'read-only');
$original = $GLOBALS['brno_post']->post_content;
$GLOBALS['brno_post']->post_modified_gmt = '2018-01-23 00:00:00';
proof_assert($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'modified drift');
$GLOBALS['brno_post']->post_modified_gmt = $class::MODIFIED_GMT;
$GLOBALS['brno_post']->post_name = 'another-chalet';
proof_assert($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'slug drift');
$GLOBALS['brno_post']->post_name = $class::SLUG;
$GLOBALS['brno_post']->post_status = 'draft';
proof_assert($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'status drift');
proof_assert($GLOBALS['brno_post']->post_content === $original, 'no WordPress changes');
echo "PASS: Brno reservoir 17591 source proof read-only and fail-closed\n";
