<?php
// Offline fail-closed contract. Full raw homepage fixture intentionally private.
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=array(); $GLOBALS['writes']=0;
function add_action($name,$cb,$priority=10){ $GLOBALS['hooks'][]=$name; }
function home_url($path='/'){ return 'https://na-ryby.eu/'; }
function get_post($id){return (object)array('ID'=>118641,'post_type'=>'page',
 'post_status'=>'publish','post_name'=>'na-ryby-eu-vse-pro-rybare',
 'post_modified_gmt'=>'2026-03-24 19:12:55','post_content'=>'unexpected editor change');}
function get_permalink($id){ return 'https://na-ryby.eu/'; }
function wp_update_post($data,$wp_error=false){$GLOBALS['writes']++;return 118641;}
function ok($assert,$message){if(!$assert)throw new RuntimeException($message); echo "PASS: $message\n";}
require __DIR__.'/../home-trust-cleanup-118641-v1.php';
ok(in_array('wp_loaded',$GLOBALS['hooks'],true),'bounded homepage hook registered');
ok(NRY_Home_Trust_118641::build_candidate('untrusted')===null,'unknown source cannot build a candidate');
ok(NRY_Home_Trust_118641::SOURCE_SHA!==NRY_Home_Trust_118641::CANDIDATE_SHA,
 'source and candidate are different exact hashes');
$method=new ReflectionMethod(NRY_Home_Trust_118641::class,'replacements');
$method->setAccessible(true);$rules=$method->invoke(null);
ok(count($rules)===24,'exact 24 bounded copy and link replacements');
$allOld=array();$allNew=array();
foreach($rules as $pair){
 ok(count($pair)===2 && is_string($pair[0]) && $pair[0]!=='',
 'each rule has a non-empty exact source');
 $allOld[]=$pair[0];$allNew[]=$pair[1];
}
ok(count(array_unique($allOld))===24,'no ambiguous duplicated search spans');
ok(count(array_filter($allNew,function($v){return strpos($v,'DOPLNIT-URL')!==false;}))===0,
 'no placeholders introduced');
NRY_Home_Trust_118641::run();
ok($GLOBALS['writes']===0,'current source drift fails closed without a write');
echo "PASS: homepage source-bound isolated release contract\n";
