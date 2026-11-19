<?php
/** Offline-only read-only source identity contract for Štěrkáč post 12040. */
define('ABSPATH', __DIR__);
$GLOBALS['sterkac_post'] = (object)array(
    'ID' => 12040, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'soukromy-revir-sterkac-zajeci',
    'post_modified_gmt' => '2026-04-14 10:33:15',
    'post_content' => str_repeat('Soukromý revír Štěrkáč Zaječí, platné podmínky ověřte. ', 120)
        .'300 Kč listopad https://na-ryby.eu/booking-cesko vinarstviukaplicky.cz/rybarina',
);
function add_action($name, $callback, $priority) { $GLOBALS['sterkac_action'] = array($name, $callback, $priority); }
function get_post($id) { return $id === 12040 ? $GLOBALS['sterkac_post'] : null; }
function get_permalink($id) { return $id === 12040 ? 'https://na-ryby.eu/soukromy-revir-sterkac-zajeci/' : ''; }
function proof_assert($pass, $message) { if (!$pass) throw new RuntimeException($message); }
require dirname(__DIR__).'/sterkac-source-proof-12040-v1.php';
$cls = 'NRY_Sterkac_Source_12040_V1';
proof_assert($GLOBALS['sterkac_action'] === array('template_redirect', array($cls, 'handle'), 23), 'GET handler');
$proof = $cls::snapshot();
proof_assert($proof['state'] === 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'source available');
proof_assert($proof['sourceSha256'] === hash('sha256', $GLOBALS['sterkac_post']->post_content), 'raw WordPress SHA');
proof_assert($proof['sourceBytes'] === strlen($GLOBALS['sterkac_post']->post_content), 'raw byte count');
proof_assert(!array_key_exists('post_content', $proof) && !array_key_exists('content', $proof)
    && strpos(json_encode($proof), 'Štěrkáč') === false, 'must not reveal article text');
proof_assert($proof['literalCounts']['oldPermit300Mentions'] === 1
    && $proof['literalCounts']['novemberMentions'] === 1
    && $proof['literalCounts']['oldBookingCategoryLinks'] === 1, 'historical literal counts');
proof_assert($proof['publicationAuthorized'] === false && $proof['wordpressWrites'] === 0
    && $proof['merchantRequests'] === 0 && $proof['syntheticClicks'] === 0, 'read-only');
$original = $GLOBALS['sterkac_post']->post_content;
$GLOBALS['sterkac_post']->post_modified_gmt = '2026-04-15 00:00:00';
proof_assert($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'modified drift');
$GLOBALS['sterkac_post']->post_modified_gmt = $cls::MODIFIED_GMT;
$GLOBALS['sterkac_post']->post_name = 'another-revir';
proof_assert($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'slug drift');
$GLOBALS['sterkac_post']->post_name = $cls::SLUG;
$GLOBALS['sterkac_post']->post_status = 'draft';
proof_assert($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'status drift');
proof_assert($GLOBALS['sterkac_post']->post_content === $original, 'no stored changes');
echo "PASS: Sterkac 12040 source proof read-only and fail-closed\n";
