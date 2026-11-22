<?php
/** Offline disarmed exact-object offer, obsolete claim cleanup and page fallback. */
define('ABSPATH', __DIR__);
$GLOBALS['wellness_hooks'] = array();
function add_action($name, $callback, $priority) { $GLOBALS['wellness_hooks'][] = array('action', $name); }
function add_filter($name, $callback, $priority) { $GLOBALS['wellness_hooks'][] = array('filter', $name); }
function is_single($id) { return $id === 17536; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return 17536; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return 'https://na-ryby.eu/chata-rybarsky-wellness-srub/'; }
function get_post($id) {
    return (object)array('ID' => $id, 'post_type' => 'post', 'post_status' => 'publish',
        'post_name' => 'chata-rybarsky-wellness-srub',
        'post_modified_gmt' => '2023-04-11 13:48:48',
        'post_content' => str_repeat('Synthetic source; no verified SHA.', 100));
}
function check_wellness($ok, $msg) { if (!$ok) throw new RuntimeException($msg); }
require dirname(__DIR__).'/wellness-srub-hauzi-offer-17536-v1.php';
$cls = 'NRY_Wellness_Srub_Hauzi_Offer_17536_V1';
check_wellness($cls::armed() && $cls::VERIFIED_RAW_SHA256 === '0f31f6da4e9a4f0a2330226b99ba9054136d1085005f39d303502d34655d5137', 'production raw SHA pinned');
check_wellness($GLOBALS['wellness_hooks'] === array(array('filter','the_content'),array('action','wp_enqueue_scripts'),array('action','template_redirect')), 'precise frontend hooks');
check_wellness($cls::HAUZI_AFFILIATE ===
    'https://tracking.affiliateport.eu/click?o=1990&a=1736&sub_id5=https%3A%2F%2Fwww.hauzi.cz%2Frybarsky-srub',
    'approved exact-property Hauzi target');
$box = '<div class="agoda-booking-box" style="background: #f8f9fa; border: 2px solid #0056b3; border-radius: 12px; padding: 25px; margin: 35px 0; font-family: sans-serif; box-shadow: 0 4px 10px rgba(0,0,0,0.1); clear: both;">'."\n"
    .'<h4 style="margin: 0 0 10px; color: #0056b3; font-size: 20px; font-weight: bold;">🏡 Ubytování u revíru Vranov</h4>'."\n"
    .'<p style="margin: 0 0 15px; font-size: 15px; color: #333;">Našli jsme pro vás nejlepší penziony, chaty a hotely v lokalitě <strong>Vranov nad Dyjí</strong> a okolí. Rezervujte včas přímo u vody!</p>'."\n"
    .'<p>    <a href="https://www.anrdoezrs.net/click-8287231-15735403?url=https%3A%2F%2Fwww.agoda.com%2Fsearch%3Fcity%3D67512%26textToSearch%3DVranov%2Bnad%2BDyj%25C3%25AD%26ds%3DNVXXV6AsTxxuHYjr" target="_blank" style="display: inline-block; background: #0056b3; color: white !important; text-decoration: none !important; padding: 12px 25px; border-radius: 6px; font-weight: bold; font-size: 16px; border: none; cursor: pointer;">Zobrazit nabídky na nejlepší ubytování</a></p>'."\n"
    .'<div style="margin-top: 10px; font-size: 11px; color: #999;">* Garantovaná nejnižší cena a možnost zrušení zdarma u většiny objektů.</div>'."\n"
    .'</div>'."\n";
check_wellness(strlen($box) > 900 && strpos($box, $cls::LEGACY_END) !== false,
    'legacy full box unique markers');
$html = "</div>\n</div>\n".$cls::INTRO
    .str_repeat('Historický popis rybářského wellness srubu ve Vranovské přehradě. ', 43)
    .$cls::OLD_BOAT
    .'</p><h2>Chata Rybářský wellness srub &#8211; Vranovská přehrada &#8211; Okolí</h2>'
    .$box;
check_wellness(strlen($html) > 3000, 'article enough bytes');
$new = $cls::transform($html);
check_wellness($new !== $html && substr_count($new, $cls::MARKER) === 1,
    'one hero at top');
check_wellness(substr_count($new, htmlspecialchars($cls::HAUZI_AFFILIATE, ENT_QUOTES, 'UTF-8')) === 2
    && substr_count($new, 'rel="sponsored nofollow noopener"') === 2,
    'two correct affiliate CTAs with disclosure');
check_wellness(substr_count($new, $cls::OWNER_LISTING) === 1, 'independent listing');
check_wellness(strpos($new, $cls::OLD_AGODA) === false
    && strpos($new, $cls::OLD_CLAIM) === false
    && strpos($new, $cls::OLD_BOAT) === false
    && strpos($new, 'jejich aktuální dostupnost ale není ověřena') !== false,
    'remove unapproved link, price guarantee, unverified boat certainty');
check_wellness(strpos($new, 'nižší hladina Vranovské přehrady') !== false
    && strpos($new, 'povolenku') !== false, 'seasonal fishing warning prominently visible');
check_wellness($cls::transform($new) === $new, 'idempotent');
check_wellness($cls::render($html) === $html, 'synthetic WP raw SHA not eligible');
foreach (array(
    str_replace($cls::OLD_BOAT, 'Changed boat availability', $html),
    str_replace($cls::INTRO, 'Different chalet', $html),
    str_replace($cls::OLD_AGODA, 'another-link', $html),
    $html.$box,
) as $drifted) {
    check_wellness($cls::transform($drifted) === $drifted, 'source markup drift fails closed');
}
$doc = '<!doctype html><html><body>'
    .str_repeat('<div class="unrelated-menu">Other site navigation</div>', 500)
    .$html.'<section id="theme-reviews">Original unrelated reviews.</section></body></html>';
$changedDoc = $cls::transformDocument($doc);
check_wellness($changedDoc !== $doc && substr_count($changedDoc, $cls::MARKER) === 1
    && strpos($changedDoc, 'Original unrelated reviews.') !== false, 'safe tagDiv fallback');
check_wellness($cls::transformDocument($changedDoc) === $changedDoc, 'document idempotent');
$short = "</div>\n</div>\n".$cls::INTRO
    .str_repeat('Historický popis rybářského wellness srubu ve Vranovské přehradě. ', 12)
    .$cls::OLD_BOAT
    .'</p><h2>Chata Rybářský wellness srub &#8211; Vranovská přehrada &#8211; Okolí</h2>'
    .$box;
check_wellness(strlen($short)>1800 && strlen($short)<3000, 'short-rendered article regression fixture');
check_wellness($cls::transform($short)!==$short,'short original content must not be silently rejected');
check_wellness($cls::transform(str_replace($cls::OLD_BOAT,'changed boat details',$short))===str_replace($cls::OLD_BOAT,'changed boat details',$short), 'short source drift fail-closed');
echo "PASS: wellness srub 17536 exact-source Hauzi offer and short-body guard\n";
