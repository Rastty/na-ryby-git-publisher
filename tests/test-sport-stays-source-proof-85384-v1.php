<?php
/** Raw WordPress source proof never mutates content or contacts merchants. */
define('ABSPATH', __DIR__);
$GLOBALS['sport_85384_post'] = (object)array(
    'ID' => 85384, 'post_type' => 'post', 'post_status' => 'publish',
    'post_name' => 'nejlepsi-mista-pro-sportovni-rybolov-s-ubytovanim-v-ceske-republice',
    'post_modified_gmt' => '2026-04-12 12:38:45',
    'post_content' => str_repeat('Praktické rady k rybaření s ubytováním. ', 130)
        .'https://na-ryby.eu/booking-cesko https://na-ryby.eu/booking-cesko',
);
function add_action($name, $callback, $priority) {
    $GLOBALS['sport_85384_action'] = array($name, $callback, $priority);
}
function get_post($id) { return $id === 85384 ? $GLOBALS['sport_85384_post'] : null; }
function get_permalink($id) { return $id === 85384
    ? 'https://na-ryby.eu/nejlepsi-mista-pro-sportovni-rybolov-s-ubytovanim-v-ceske-republice/' : ''; }
function require_proof($condition, $message) { if (!$condition) throw new RuntimeException($message); }
require dirname(__DIR__).'/sport-stays-source-proof-85384-v1.php';
$class = 'NRY_Sport_Stays_Source_85384_V1';
require_proof($GLOBALS['sport_85384_action'] === array('template_redirect', array($class, 'handle'), 23),
    'only read-only template handler');
$snapshot = $class::snapshot();
require_proof($snapshot['state'] === 'SOURCE_SHA_AVAILABLE_NO_PUBLICATION', 'exact source valid');
require_proof($snapshot['sourceSha256'] === hash('sha256', $GLOBALS['sport_85384_post']->post_content),
    'raw source SHA matches');
require_proof($snapshot['literalCounts']['legacyBookingLinks'] === 2, 'two old booking links counted');
require_proof($snapshot['publicationAuthorized'] === false && $snapshot['wordpressWrites'] === 0
    && $snapshot['syntheticClicks'] === 0, 'no write or manufactured traffic');
$original = $GLOBALS['sport_85384_post']->post_content;
$GLOBALS['sport_85384_post']->post_modified_gmt = '2026-04-13 00:00:00';
require_proof($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'updated source blocked');
$GLOBALS['sport_85384_post']->post_modified_gmt = '2026-04-12 12:38:45';
$GLOBALS['sport_85384_post']->post_name = 'wrong-page';
require_proof($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'wrong slug blocked');
$GLOBALS['sport_85384_post']->post_name = $class::SLUG;
$GLOBALS['sport_85384_post']->post_status = 'draft';
require_proof($class::snapshot()['state'] === 'BLOCKED_SOURCE_DRIFT', 'non-public post blocked');
require_proof($original === $GLOBALS['sport_85384_post']->post_content, 'raw content untouched');
echo "PASS: #85384 exact read-only WordPress source fingerprint\n";
