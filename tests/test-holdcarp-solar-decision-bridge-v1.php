<?php
// Offline fail-closed review bridge; source article and old hrefs must remain identical.
define('ABSPATH','/');
$GLOBALS['handlers'] = array();
function is_single($id = null) { return false; }
function is_singular($type = null) { return false; }
function get_post($id = null) { return null; }
function add_filter($hook,$callback,$priority) { $GLOBALS['handlers'][] = array($hook,$callback,$priority); }
require_once __DIR__.'/../holdcarp-solar-decision-bridge-v1.php';
function check($pass,$name) { if (!$pass) { fwrite(STDERR,"FAIL $name\n"); exit(1); } echo "PASS $name\n"; }
$source = '<p>HODNOCENÍ: 5/5 ⭐⭐⭐⭐⭐ Holdcarp Foldable Solar System 40W</p>'
    .str_repeat('<p>historický text <a href="https://www.tkqlhce.com/legacy">původní obchod</a></p>',100);
$r = NRY_Holdcarp_Solar_Decision_Bridge_V1::transform($source,'40W');
check($r !== $source && substr_count($r,'data-nry-holdcarp-energy-bridge="v1"') === 1,'one panel');
check(substr($r,-strlen($source)) === $source,'historic body and outbound links unmodified');
check(substr_count($r,'href="https://na-ryby.eu/powerbanka-nebo-solarni-panel-na-ryby/"') === 1,'one first-party guide link');
check(NRY_Holdcarp_Solar_Decision_Bridge_V1::transform($r,'40W') === $r,'idempotence');
check(NRY_Holdcarp_Solar_Decision_Bridge_V1::transform($source,'20W') === $source,'wrong model fail closed');
check(NRY_Holdcarp_Solar_Decision_Bridge_V1::transform(str_replace('HODNOCENÍ','xx',$source),'40W') === str_replace('HODNOCENÍ','xx',$source),'missing source marker fails closed');
check(NRY_Holdcarp_Solar_Decision_Bridge_V1::transform($source,'88W') === $source,'unapproved model fails closed');
$source20 = str_replace('40W','20W',$source);
check(NRY_Holdcarp_Solar_Decision_Bridge_V1::transform($source20,'20W') !== $source20,'second review covered');
check(count($GLOBALS['handlers']) === 1 && $GLOBALS['handlers'][0][0] === 'the_content','render filter only');
