<?php
/** Independent read-only raw WP post fingerprint and source drift contract. */
define('ABSPATH', __DIR__);
$GLOBALS['cyklochata_post'] = (object)array(
    'ID' => 11983, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'rybarska-chata-cyklochata-vranovska-prehrada',
    'post_modified_gmt' => '2023-04-11 12:57:02',
    'post_content' => str_repeat('Rybářská chata Cyklochata, podmínky ověřte předem. ', 45)
        .'agoda-booking-box anrdoezrs.net/click-8287231-15735403 '
        .'Garantovaná nejnižší cena Zobrazit nabídky na nejlepší ubytování',
);
function add_action($name, $callback, $priority) { $GLOBALS['cyklochata_hook'] = array($name, $callback, $priority); }
function get_post($id) { return $id === 11983 ? $GLOBALS['cyklochata_post'] : null; }
function get_permalink($id) { return $id === 11983 ? 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/' : ''; }
function proof_assert($ok, $msg) { if (!$ok) throw new RuntimeException($msg); }
require dirname(__DIR__).'/cyklochata-source-proof-11983-v1.php';
$cls = 'NRY_Cyklochata_Source_11983_V1';
proof_assert($GLOBALS['cyklochata_hook'] === array('template_redirect', array($cls, 'handle'), 23), 'GET handler');
$x = $cls::snapshot();
proof_assert($x['state'] === 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'source available');
proof_assert($x['sourceSha256'] === hash('sha256', $GLOBALS['cyklochata_post']->post_content), 'raw SHA');
proof_assert($x['sourceBytes'] === strlen($GLOBALS['cyklochata_post']->post_content), 'raw bytes');
proof_assert($x['literalCounts']['oldAgodaCjLinks'] === 1
    && $x['literalCounts']['lowestPriceClaims'] === 1
    && $x['literalCounts']['oldGenericCtas'] === 1
    && $x['literalCounts']['verifiedHauziListingLinks'] === 0, 'literal counts');
proof_assert(!array_key_exists('content', $x) && !array_key_exists('post_content', $x), 'no stored article returned');
proof_assert($x['publicationAuthorized'] === false && $x['wordpressWrites'] === 0
    && $x['merchantRequests'] === 0 && $x['syntheticClicks'] === 0, 'no writes or outbound');
$GLOBALS['cyklochata_post']->post_modified_gmt = '2023-04-12 00:00:00';
proof_assert($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'modified drift');
$GLOBALS['cyklochata_post']->post_modified_gmt = $cls::MODIFIED_GMT;
$GLOBALS['cyklochata_post']->post_name = 'wrong-chata';
proof_assert($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'slug drift');
$GLOBALS['cyklochata_post']->post_name = $cls::SLUG;
$GLOBALS['cyklochata_post']->post_status = 'draft';
proof_assert($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'publish drift');
echo "PASS: Cyklochata 11983 GET-only source proof fail-closed\n";
