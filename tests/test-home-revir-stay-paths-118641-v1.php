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
function add_filter($name,$cb,$priority) { $GLOBALS['home_hooks'][$name]=$priority; }
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
check_home(($GLOBALS['home_hooks']['the_content'] ?? null)===90,'after existing homepage modules');
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
echo "PASS: homepage revír and stay discovery, exact anchors, no raw-source bypass\n";
