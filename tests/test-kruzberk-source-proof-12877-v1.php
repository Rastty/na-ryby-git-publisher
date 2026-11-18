<?php
/** Offline-only raw WordPress source proof contract for post 12877. */
define('ABSPATH', __DIR__);
$GLOBALS['kruzberk_post'] = (object)array(
    'ID' => 12877, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'rybarska-chata-kruzberk-rybarina-moravskoslezsky-kraj',
    'post_modified_gmt' => '2026-04-14 23:33:21',
    'post_content' => str_repeat('Rybářská chata Kružberk u řeky Moravice. ', 145)
        .'https://na-ryby.eu/booking-cesko tracking.affiliateclub.cz',
);
function add_action($name, $callback, $priority) {
    $GLOBALS['kruzberk_action'] = array($name, $callback, $priority);
}
function get_post($id) { return $id === 12877 ? $GLOBALS['kruzberk_post'] : null; }
function get_permalink($id) { return $id === 12877
    ? 'https://na-ryby.eu/rybarska-chata-kruzberk-rybarina-moravskoslezsky-kraj/' : ''; }
function proof_assert($pass, $message) { if (!$pass) throw new RuntimeException($message); }
require dirname(__DIR__).'/kruzberk-source-proof-12877-v1.php';
$class = 'NRY_Kruzberk_Source_12877_V1';
proof_assert($GLOBALS['kruzberk_action'] === array('template_redirect', array($class, 'handle'), 23), 'GET handler only');
$snapshot = $class::snapshot();
proof_assert($snapshot['state'] === 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'valid source');
proof_assert($snapshot['sourceSha256'] === hash('sha256', $GLOBALS['kruzberk_post']->post_content), 'raw source SHA');
proof_assert($snapshot['literalCounts']['legacyAffiliateClubLinks'] === 1, 'legacy link count');
proof_assert($snapshot['literalCounts']['bookingCategoryLinks'] === 1, 'category link count');
proof_assert($snapshot['publicationAuthorized'] === false && $snapshot['wordpressWrites'] === 0
    && $snapshot['syntheticClicks'] === 0, 'no writes or fabricated traffic');
$original = $GLOBALS['kruzberk_post']->post_content;
$GLOBALS['kruzberk_post']->post_modified_gmt = '2026-04-15 00:00:00';
proof_assert($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'modified date must match');
$GLOBALS['kruzberk_post']->post_modified_gmt = $class::MODIFIED_GMT;
$GLOBALS['kruzberk_post']->post_name = 'other-article';
proof_assert($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'slug must match');
$GLOBALS['kruzberk_post']->post_name = $class::SLUG;
$GLOBALS['kruzberk_post']->post_status = 'draft';
proof_assert($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'must be public');
proof_assert($original === $GLOBALS['kruzberk_post']->post_content, 'no stored changes');
echo "PASS: Kružberk 12877 source proof read-only and fail-closed\n";
