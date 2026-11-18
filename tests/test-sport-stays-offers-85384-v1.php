<?php
/** Offline regression of the literal, source-pinned, frontend-only offer block. */
define('ABSPATH', __DIR__);
function is_single($id = null) { return $id === 85384; }
function add_filter($hook, $callback, $priority) { $GLOBALS['sport_filter'] = array($hook, $callback, $priority); }
function add_action($hook, $callback, $priority) { $GLOBALS['sport_action'] = array($hook, $callback, $priority); }
function check_85384($ok, $reason) { if (!$ok) throw new RuntimeException($reason); }
require dirname(__DIR__).'/sport-stays-offers-85384-v1.php';
$class = 'NRY_Sport_Stays_Offers_85384_V1';
check_85384($GLOBALS['sport_filter'] === array('the_content', $class.'::render', PHP_INT_MAX), 'only content hook');
check_85384($GLOBALS['sport_action'] === array('wp_enqueue_scripts', $class.'::enqueue', 28), 'only style hook');
// First 512 UTF-8 bytes of the independently observed original WP REST
// content.rendered; this freezes the exact prerequisite to transformations.
$prefix = base64_decode('PGgxPk5lamxlcMWhw60gbcOtc3RhIHBybyBzcG9ydG92bsOtIHJ5Ym9sb3YgcyB1Ynl0b3bDoW7DrW0gdiDEjGVza8OpIHJlcHVibGljZTwvaDE+CjxwPkplc3RsaSBobGVkw6F0ZSBtw61zdG8sIGtkZSBzaSBvcHJhdmR1IHphY2h5dMOhdGUgYSB6w6Fyb3ZlxYggbmVtdXPDrXRlIHBvIGthxb5kw6kgdnljaMOhemNlIGJhbGl0IHbDvWJhdnUgYSDFmWXFoWl0IG7DoXZyYXQgZG9txa8sIHRlbmhsZSDEjWzDoW5layBqZSBwcm8gdsOhcy4gU3BvcnRvdm7DrSByeWJvbG92IHMgdWJ5dG92w6Fuw61tIGTDoXbDoSBzbXlzbCBobGF2bsSbIHRlaGR5LCBrZHnFviBjaGNldGUgYsO9dCB1IHZvZHkgZMOpbGUsIGNoeXRhdCB2IGtsaWR1IG9kIHLDoW5hIGRvIHZlxI1lcmEgYSBtw610IHrDoXplbcOtIHBvIHJ1Y2UuIFYgxIxlc2t1IGplIHRha292w71jaCBtw61zdCBob2RuxJssIGFsZSBuZSBrYcW+ZMOpIHNwbG7DrSB0bywgY28gcnliw6HFmSBwb3TFmWVidWplIHYgcHJheGkuPC9wPgo8cD5OZWpkZSBqZW4=', true);
check_85384(is_string($prefix) && strlen($prefix) === $class::PREFIX_BYTES
    && hash('sha256', $prefix) === $class::PREFIX_SHA256, 'reviewed HTML prefix bytes');
$old = $class::LEGACY_BOOKING;
$other = 'https://na-ryby.eu/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/';
$html = $prefix.str_repeat('<p>Praktický průvodce rybařením s ubytováním.</p>', 330)
    .'<a href="'.$old.'">Doporučené ubytování</a>'
    .'<a href="'.$other.'">Poradce</a>'
    .'<a href="'.$old.'">Pobyt pro rybáře</a>'
    .'<a href="'.$old.'">Další pobyty</a>';
check_85384(strlen($html) > 10000, 'source-shaped fixture size');
$changed = $class::transform($html);
check_85384($changed !== $html && $changed === $class::transform($html), 'source-pinned deterministic change');
check_85384(substr_count($changed, $class::MARKER) === 1, 'one reviewed section');
check_85384(substr_count($changed, 'data-nry-sport-offer=') === 3, 'three source-bound offers');
check_85384(substr_count($changed, 'rel="sponsored nofollow noopener"') === 3, 'exact sponsorship');
check_85384(substr_count($changed, 'href="'.$class::STAYS.'"') === 4, 'three old links now valid guide + more');
check_85384(substr_count($changed, 'href="'.$other.'"') === 1, 'unrelated link preserved');
check_85384(strpos($changed, 'href="'.$old.'"') === false, 'no old booking dead end');
foreach (array($class::SOUTH_BOHEMIA, $class::SLAPY, $class::PALAVA) as $url) {
    check_85384(substr_count($changed, htmlspecialchars($url, ENT_QUOTES, 'UTF-8')) === 1,
        'one exact approved affiliate destination');
}
check_85384($class::transform($changed) === $changed, 'idempotent render');
check_85384($class::transform('drift'.$html) === 'drift'.$html, 'render prefix drift blocked');
check_85384($class::transform(str_replace('href="'.$old.'"', 'href="'.$class::STAYS.'"', $html)) ===
    str_replace('href="'.$old.'"', 'href="'.$class::STAYS.'"', $html), 'old links changed blocked');
check_85384($class::transform($html.$class::MARKER) === $html.$class::MARKER, 'duplicate placement blocked');
check_85384($class::transform($html.$class::SLAPY) === $html.$class::SLAPY, 'preexisting affiliate blocked');
check_85384($class::transform($html.$class::ANCHOR) === $html.$class::ANCHOR, 'duplicate anchor blocked');
echo "PASS: #85384 exactly three sponsored offers and three old booking links repaired, no WP writes\n";
