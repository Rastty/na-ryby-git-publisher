<?php
/** Offline exact-object affiliate, legacy-link removal and disarmed gate. */
define('ABSPATH', __DIR__);
$GLOBALS['registered_hooks'] = array();
function add_filter($name, $callback, $priority) { $GLOBALS['registered_hooks'][] = array('filter', $name); }
function add_action($name, $callback, $priority) { $GLOBALS['registered_hooks'][] = array('action', $name); }
function is_single($id) { return $id === 11983; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return 11983; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/rybarska-chata-cyklochata-vranovska-prehrada/'; }
function get_post($id) {
    return (object)array('ID' => $id, 'post_type' => 'post', 'post_status' => 'publish',
        'post_name' => 'rybarska-chata-cyklochata-vranovska-prehrada',
        'post_modified_gmt' => '2023-04-11 12:57:02',
        'post_content' => str_repeat('Synthetic, unverified WP source. ', 130));
}
function verify_offer($ok, $why) { if (!$ok) throw new RuntimeException($why); }
require dirname(__DIR__).'/cyklochata-hauzi-offer-11983-v1.php';
$cls = 'NRY_Cyklochata_Hauzi_Offer_11983_V1';
verify_offer(!$cls::armed() && $cls::VERIFIED_RAW_SHA256 === '', 'source SHA unset');
verify_offer($GLOBALS['registered_hooks'] === array(), 'disarmed module registers NO WP hooks');
verify_offer(strpos($cls::HAUZI_AFFILIATE,
    'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Fcyklochata') === 0, 'approved exact listing tracking URL');

$box = '<div class="agoda-booking-box" style="background: #f8f9fa; border: 2px solid #0056b3; border-radius: 12px; padding: 25px; margin: 35px 0; font-family: sans-serif; box-shadow: 0 4px 10px rgba(0,0,0,0.1); clear: both;">'."\n"
    .'<h4 style="margin: 0 0 10px; color: #0056b3; font-size: 20px; font-weight: bold;">🏡 Ubytování u revíru Vranov</h4>'."\n"
    .'<p style="margin: 0 0 15px; font-size: 15px; color: #333;">Našli jsme pro vás nejlepší penziony, chaty a hotely v lokalitě <strong>Vranov nad Dyjí</strong> a okolí. Rezervujte včas přímo u vody!</p>'."\n"
    .'<p>    <a href="https://www.anrdoezrs.net/click-8287231-15735403?url=https%3A%2F%2Fwww.agoda.com%2Fsearch%3Fcity%3D67512%26textToSearch%3DVranov%2Bnad%2BDyj%25C3%25AD%26ds%3DNVXXV6AsTxxuHYjr" target="_blank" style="display: inline-block; background: #0056b3; color: white !important; text-decoration: none !important; padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 16px; border: none; cursor: pointer;">Zobrazit nabídky na nejlepší ubytování</a></p>'."\n"
    .'<div style="margin-top: 10px; font-size: 11px; color: #999;">* Garantovaná nejnižší cena a možnost zrušení zdarma u většiny objektů.</div>'."\n"
    .'</div>'."\n";
verify_offer(strlen($box) > 900 && strpos($box, $cls::LEGACY_END) !== false,
    'exact legacy box marker, not generic substring replacement');
$old = "</div>\n</div>\n".$cls::INTRO
    .str_repeat('Historický detail chaty a okolí bez doložení aktuální dostupnosti. ', 40)
    .'</p><h2>Rybářská chata Cyklochata &#8211; Vranovská přehrada &#8211; Okolí</h2>'
    .$box;
verify_offer(strlen($old) > 2500, 'old body long enough');
$new = $cls::transform($old);
verify_offer($new !== $old, 'replaces exact legacy generic box');
verify_offer(substr_count($new, $cls::MARKER) === 1, 'prominent exact-object hero only once');
verify_offer(substr_count($new, htmlspecialchars($cls::HAUZI_AFFILIATE, ENT_QUOTES, 'UTF-8')) === 2,
    'two visible approved Hauzi CTAs, escaped query separators');
verify_offer(substr_count($new, 'rel="sponsored nofollow noopener"') === 2,
    'both CTAs disclose sponsorship');
verify_offer(substr_count($new, $cls::OFFICIAL) === 1,
    'independent official operator info');
verify_offer(strpos($new, $cls::LEGACY_LINK) === false
    && strpos($new, $cls::LEGACY_CTA) === false
    && strpos($new, $cls::LEGACY_CLAIM) === false,
    'old CJ/Agoda and guarantee completely removed');
verify_offer(strpos($new, 'rybářskou povolenku') !== false
    && strpos($new, 'Aktuální cenu') !== false, 'honest booking and fishing caveats');
verify_offer($cls::transform($new) === $new, 'idempotent output');
verify_offer($cls::render($old) === $old, 'NO frontend change while disarmed');
verify_offer($cls::transform(str_replace($cls::LEGACY_CTA, 'Different link', $old)) ===
    str_replace($cls::LEGACY_CTA, 'Different link', $old), 'legacy CTA drift fails closed');
verify_offer($cls::transform(str_replace($cls::INTRO, 'Wrong property', $old)) ===
    str_replace($cls::INTRO, 'Wrong property', $old), 'original intro drift fails closed');
verify_offer($cls::transform($old.$box) === $old.$box,
    'duplicate legacy booking box fails closed');
verify_offer($cls::transform(str_replace($cls::LEGACY_LINK, 'other-network', $old)) ===
    str_replace($cls::LEGACY_LINK, 'other-network', $old), 'different affiliate fails closed');
$document = '<!doctype html><html><head><title>Cyklochata</title></head><body>'
    .str_repeat('<nav class="unrelated-menu">Another page element.</nav>', 500)
    .$old.'<section id="unrelated-reviews">Keep theme review untouched</section></body></html>';
verify_offer(strlen($document) > 20000, 'tagDiv full-document fallback fixture');
$changedDocument = $cls::transformDocument($document);
verify_offer($changedDocument !== $document
    && substr_count($changedDocument, $cls::MARKER) === 1
    && strpos($changedDocument, 'Keep theme review untouched') !== false,
    'exact one article in document and preserve rest');
verify_offer($cls::transformDocument($changedDocument) === $changedDocument,
    'whole-document idempotent');
echo "PASS: Cyklochata 11983 exact Hauzi offer fail-closed, DISARMED\n";
