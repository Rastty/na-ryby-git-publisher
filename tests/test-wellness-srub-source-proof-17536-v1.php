<?php
/** Raw WordPress source SHA: one exact wellness-cabin post, GET only. */
define('ABSPATH', __DIR__);
$GLOBALS['wellness_post'] = (object)array(
    'ID' => 17536, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'chata-rybarsky-wellness-srub',
    'post_modified_gmt' => '2023-04-11 13:48:48',
    'post_content' => str_repeat('Srub a aktuální podmínky rybaření je třeba ověřit. ', 50)
        .'agoda-booking-box anrdoezrs.net/click-8287231-15735403 '
        .'Garantovaná nejnižší cena stále prokrmovaná místa',
);
function add_action($name, $callback, $priority) { $GLOBALS['wellness_hook'] = array($name, $callback, $priority); }
function get_post($id) { return $id === 17536 ? $GLOBALS['wellness_post'] : null; }
function get_permalink($id) { return $id === 17536 ? 'https://na-ryby.eu/chata-rybarsky-wellness-srub/' : ''; }
function expect_wellness($ok, $reason) { if (!$ok) throw new RuntimeException($reason); }
require dirname(__DIR__).'/wellness-srub-source-proof-17536-v1.php';
$cls = 'NRY_Wellness_Srub_Source_17536_V1';
expect_wellness($GLOBALS['wellness_hook'] === array('template_redirect', array($cls, 'handle'), 23), 'GET hook');
$s = $cls::snapshot();
expect_wellness($s['state'] === 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'source identity');
expect_wellness($s['sourceSha256'] === hash('sha256', $GLOBALS['wellness_post']->post_content),
    'raw not rendered SHA');
expect_wellness($s['sourceBytes'] === strlen($GLOBALS['wellness_post']->post_content), 'source bytes');
expect_wellness($s['literalCounts']['oldAgodaCjLinks'] === 1
    && $s['literalCounts']['oldBoatFeedingClaims'] === 1
    && $s['literalCounts']['verifiedHauziListingLinks'] === 0, 'legacy markers');
expect_wellness(!isset($s['content']) && !isset($s['post_content'])
    && $s['publicationAuthorized'] === false && $s['wordpressWrites'] === 0
    && $s['merchantRequests'] === 0 && $s['syntheticClicks'] === 0, 'GET-only no body');
$GLOBALS['wellness_post']->post_modified_gmt = '2026-11-19 00:00:00';
expect_wellness($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'modified guard');
$GLOBALS['wellness_post']->post_modified_gmt = $cls::MODIFIED_GMT;
$GLOBALS['wellness_post']->post_name = 'other-cabin';
expect_wellness($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'slug guard');
$GLOBALS['wellness_post']->post_name = $cls::SLUG;
$GLOBALS['wellness_post']->post_status = 'draft';
expect_wellness($cls::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'status guard');
echo "PASS: wellness srub 17536 GET-only source identity fail-closed\n";
