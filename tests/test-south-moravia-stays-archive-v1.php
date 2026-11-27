<?php
define('ABSPATH', '/');
$GLOBALS['filters'] = array();
function is_single($s = null) { return false; }
function get_post($id = null) { return null; }
function add_filter($hook, $callback, $priority = 10) { $GLOBALS['filters'][] = $hook; }
function add_action($hook, $callback, $priority = 10) { $GLOBALS['filters'][] = $hook; }
require __DIR__.'/../south-moravia-stays-archive-v1.php';
function check($ok, $name) {
 if (!$ok) { fwrite(STDERR,"FAIL ".$name."\n"); exit(1); }
 echo "PASS ".$name."\n";
}
$h='<div><p>Revíry | Ubytování</p>'
    .'<h3>Zukalův rybník</h3><h3>Rybník Popůvky</h3>'
    .str_repeat('<p>historický zápis <a href="https://example.test/unchanged">Původní odkaz</a></p>', 75)
    .'</div>';
$p=NRY_South_Moravia_Stays_Archive_V1::transform($h);
check($p!==$h && substr_count($p,'data-nry-jm-archive-stays="v1"')===1,'new one-panel route');
check(substr($p,-strlen($h))===$h,'original HTML and href bytes preserved');
check(substr_count($p,'href="'.NRY_South_Moravia_Stays_Archive_V1::CYCLO.'"')===1
 && substr_count($p,'href="'.NRY_South_Moravia_Stays_Archive_V1::SRUB.'"')===1
 && substr_count($p,'href="'.NRY_South_Moravia_Stays_Archive_V1::HUB.'"')===1,'three first-party targets only');
check(NRY_South_Moravia_Stays_Archive_V1::transform($p)===$p,'idempotent');
check(NRY_South_Moravia_Stays_Archive_V1::transform(str_replace('Popůvky','xx',$h))===str_replace('Popůvky','xx',$h),'old archive drift fails closed');
check(NRY_South_Moravia_Stays_Archive_V1::transform('Short body')==='Short body','short body excluded');
$doc='<html><body><h1>Rybaření s ubytováním – Jihomoravský kraj</h1>'.$h.'</body></html>';
$out=NRY_South_Moravia_Stays_Archive_V1::transform_document($doc);
check($out!==$doc && substr_count($out,'data-nry-jm-archive-stays="v1"')===1,'theme document fallback');
check(str_replace(NRY_South_Moravia_Stays_Archive_V1::block(),'',$out)===$doc,'fallback keeps entire source');
check(NRY_South_Moravia_Stays_Archive_V1::transform_document(str_replace('Jihomoravský kraj','Moravskoslezský kraj',$doc))===str_replace('Jihomoravský kraj','Moravskoslezský kraj',$doc),'incorrect title blocked');
check(NRY_South_Moravia_Stays_Archive_V1::title_allowed('Rybaření s ubytováním - Jihomoravský kraj'),'exact 2017 plain-hyphen title');
check(NRY_South_Moravia_Stays_Archive_V1::title_allowed('Rybaření s ubytováním – Jihomoravský kraj'),'exact public en-dash title');
check(!NRY_South_Moravia_Stays_Archive_V1::title_allowed('Rybaření s ubytováním – Jihočeský kraj'),'other region rejected');
check(count($GLOBALS['filters'])===3,'WordPress hooks registered only when runtime is present');
echo "SOUTH_MORAVIA_STAYS_ARCHIVE_OFFLINE_PASS\n";
