<?php
/** Offline homepage discovery contract; never touches WordPress or merchants. */
define('ABSPATH', __DIR__);
$GLOBALS['home_hooks'] = array();
$GLOBALS['home_query_id'] = 118641;
$GLOBALS['home_admin'] = false;
$GLOBALS['home_preview'] = false;
$GLOBALS['home_host'] = 'https://na-ryby.eu/';
$GLOBALS['home_url'] = 'https://na-ryby.eu/';
$GLOBALS['home_post'] = (object)array(
    'ID'=>118641,'post_type'=>'page','post_status'=>'publish',
    'post_name'=>'na-ryby-eu-vse-pro-rybare','post_content'=>'source-drift');
function add_filter($name,$cb,$priority) { $GLOBALS['home_hooks'][$name][]=$priority; }
function is_front_page() { return true; }
function is_page($id) { return $id===$GLOBALS['home_query_id']; }
function is_main_query() { return true; }
function in_the_loop() { return true; }
function is_admin() { return $GLOBALS['home_admin']; }
function is_preview() { return $GLOBALS['home_preview']; }
function get_queried_object_id() { return $GLOBALS['home_query_id']; }
function home_url($path='/') { return $GLOBALS['home_host']; }
function get_permalink($id) { return $GLOBALS['home_url']; }
function get_post($id) { return $GLOBALS['home_post']; }
function check_home($ok,$reason) { if (!$ok) throw new RuntimeException($reason); }
require dirname(__DIR__).'/home-revir-stay-paths-118641-v1.php';
$C='NRY_Home_Revir_Stay_Paths_118641_V1';
check_home(($GLOBALS['home_hooks']['the_content'] ?? null)===array(90,96),'two scoped homepage filters');
$markup='<span hidden '.$C::SOURCE_MARKER.'></span>'
    .'<section><h3>Kam na ryby</h3><p>Hledáte vodu?</p><ul>'.$C::REVIR_ANCHOR.'</ul></section>'
    .'<section><h3>Výprava na více dní</h3><p>'.$C::STAY_INTRO.'</p><ul>'.$C::STAY_ANCHOR.'</ul></section>';
$changed=$C::transform($markup);
check_home($changed!==$markup,'two unique homepage cards enhanced');
check_home(substr_count($changed,$C::MARKER)===1,'idempotent marker');
check_home(substr_count($changed,$C::DYJE6)===1,'Dyje 6 gets one discoverable link');
check_home(substr_count($changed,$C::MORAVIA_REVIRS)===1,'South Moravia archive linked');
check_home(substr_count($changed,$C::STAY_GUIDE)===1,'useful stay guide linked');
check_home(strpos($changed,'Nejprve vyberte vodu a pobyt')!==false,'stay card now matches actual intent');
check_home(substr_count($changed,$C::REVIR_ANCHOR)===1
    && substr_count($changed,$C::STAY_ANCHOR)===1,'existing links preserved');
check_home($C::transform($changed)===$changed,'repeat render no change');
check_home($C::transform(str_replace($C::REVIR_ANCHOR,'editor link',$markup))
    ===str_replace($C::REVIR_ANCHOR,'editor link',$markup),'editor changed archive link wins');
check_home($C::transform($markup.$markup)===$markup.$markup,'duplicate sections fail closed');
$unmarked=str_replace($C::SOURCE_MARKER,'editor-drifts',$markup);
check_home($C::transform($unmarked)===$unmarked,'missing trusted homepage marker blocks');
check_home($C::render($markup)===$markup,'raw WP source drift blocks render');
check_home($C::RAW_SHA256==='80e86d2c85d495a2502737243b4887b6ef48098906bc0cf958e08ec29dc6574f',
    'pin exact reviewed homepage candidate');
$GLOBALS['home_admin']=true;
check_home($C::render($markup)===$markup,'admin blocked');
$GLOBALS['home_admin']=false;
$GLOBALS['home_preview']=true;
check_home($C::render($markup)===$markup,'preview blocked');
$GLOBALS['home_preview']=false;
$copySource='<span hidden '.$C::SOURCE_MARKER.'></span>';
$copySource.=str_repeat('<p>Původní homepage a odkazy zůstávají beze změny.</p>',60);
foreach($C::visitorCopyPairs() as $pair) {
    $copySource.='<p>'.$pair[0].'</p>';
}
$oldMerchant='<a href="https://www.chytapust.cz/old-product/?old=1&amp;src=2">Historický odkaz</a>';
$copySource.=$oldMerchant;
check_home(strlen($copySource)>2500,'realistic visitor copy fixture');
$better=$C::transformVisitorCopy($copySource);
check_home($better!==$copySource,'six actual visitor copy fixes');
check_home(substr_count($better,$C::COPY_MARKER)===1,'one public copy proof marker');
check_home(substr_count($better,$C::SOURCE_MARKER)===1,'source marker unchanged');
check_home(substr_count($better,$oldMerchant)===1,'old merchant URL byte-for-byte unchanged');
foreach($C::visitorCopyPairs() as $pair) {
    check_home(strpos($better,$pair[0])===false,'old internal text gone');
    check_home(substr_count($better,$pair[1])===1,'exact visitor copy present');
}
check_home($C::transformVisitorCopy($better)===$better,'idempotent home visitor copy');
$edited=str_replace($C::visitorCopyPairs()[0][0],'Editor owns this paragraph',$copySource);
check_home($C::transformVisitorCopy($edited)===$edited,'changed copy fails closed, no partial edits');
$duped=$copySource.'<p>'.$C::visitorCopyPairs()[0][0].'</p>';
check_home($C::transformVisitorCopy($duped)===$duped,'duplicate homepage note fails closed');
$noProof=str_replace($C::SOURCE_MARKER,'untrusted',''.$copySource);
check_home($C::transformVisitorCopy($noProof)===$noProof,'missing source proof fails closed');
check_home($C::renderVisitorCopy($copySource)===$copySource,'raw WP source hash drift blocks copy render');
$GLOBALS['home_admin']=true;
check_home($C::renderVisitorCopy($copySource)===$copySource,'admin never transformed');
$GLOBALS['home_admin']=false;
$GLOBALS['home_preview']=true;
check_home($C::renderVisitorCopy($copySource)===$copySource,'preview never transformed');
$GLOBALS['home_preview']=false;
echo "PASS: homepage revír and stay discovery, exact anchors, no raw-source bypass\n";
