<?php
/** Offline non-network contract for the exact reviewed buyer-guide replacement. */
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=array();$GLOBALS['writes']=0;$GLOBALS['saved']=array();
function add_action($name,$cb,$priority=10) {$GLOBALS['hooks'][]=$name;}
function get_post($id) {return (object)array(
 'ID'=>46573,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>NRY_Receiver_Buyer_46573_V1::SLUG,
 'post_modified_gmt'=>NRY_Receiver_Buyer_46573_V1::SOURCE_GMT,
 'post_content'=>'unexpected-later-editor-source');}
function get_option($k,$default=null) {return $GLOBALS['saved'][$k]??$default;}
function get_permalink($id) {return NRY_Receiver_Buyer_46573_V1::URL;}
function home_url($path='/') {return 'https://na-ryby.eu/';}
function wp_update_post($data,$err=true) {$GLOBALS['writes']++;return 46573;}
function ok($x,$label) {if(!$x)throw new RuntimeException($label); echo "PASS: $label\n";}
require dirname(__DIR__).'/receiver-buyer-guide-release-46573-v1.php';
ok(in_array('wp_loaded',$GLOBALS['hooks'],true),'single one-time site-native release');
ok(in_array('template_redirect',$GLOBALS['hooks'],true),'independent source+backup readback available');
ok(NRY_Receiver_Buyer_46573_V1::ID===46573
 && NRY_Receiver_Buyer_46573_V1::SOURCE_BYTES===10229
 && NRY_Receiver_Buyer_46573_V1::SOURCE_GMT==='2023-05-12 15:13:13',
 'exact historical source identity pinned');
$c=NRY_Receiver_Buyer_46573_V1::candidate();
ok(is_string($c),'reviewed candidate exists with exact SHA and byte-size');
ok(strlen($c)===NRY_Receiver_Buyer_46573_V1::CANDIDATE_BYTES &&
 hash('sha256',$c)===NRY_Receiver_Buyer_46573_V1::CANDIDATE_SHA,
 'immutable candidate full-byte SHA256 verifies');
ok(substr_count($c,NRY_Receiver_Buyer_46573_V1::MARKER)===1,
 'single release marker');
ok(strpos($c,'Zesílení zvuku při záběru')===false
 &&strpos($c,'zaručen')===false
 &&strpos($c,'Než zaplatíte:')!==false,
 'no unsupported catch guarantees or historic false mechanism');
ok(substr_count($c,'<table>')===1 &&
 substr_count($c,'<ol>')===2,'buyer table and two practical checklists present');
ok(substr_count($c,'[su_button url="https://na-ryby.eu/signalizatory"')===1,
 'retained exact merchant destination once without fabricated product claims');
NRY_Receiver_Buyer_46573_V1::release();
ok($GLOBALS['writes']===0,'source SHA drift cannot cause a write');
$r=NRY_Receiver_Buyer_46573_V1::readback();
ok($r['state']==='NOT_VERIFIED' && $r['wordpressWrites']===0 &&
 !$r['rawSourceExposed'],'readback cannot claim publication without real backup+SHA');
ok(!isset($r['source'])&&!isset($r['html'])&&!isset($r['merchantUrl']),
 'public readback never discloses raw WordPress content or merchant URLs');
echo "PASS receiver buyer guide contract\n";
