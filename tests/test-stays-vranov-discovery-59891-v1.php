<?php
define('ABSPATH', true);
$GLOBALS['nry_post'] = (object)array(
    'ID'=>59891, 'post_type'=>'post', 'post_status'=>'publish',
    'post_name'=>'rybarske-pobyty-v-prirode-nejlepe-hodnocene-ubytovani-pro-rybare'
);
$GLOBALS['nry_good'] = true;
function add_filter($a,$b,$c) {}
function add_action($a,$b,$c) {}
function is_single($id) { return $GLOBALS['nry_good'] && $id===59891; }
function is_singular($type) { return $type==='post'; }
function get_queried_object_id() { return 59891; }
function is_admin() { return false; }
function is_preview() { return false; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return NRY_Stays_Vranov_Discovery_59891_V1::URL; }
function get_post($id) { return $GLOBALS['nry_post']; }
require __DIR__.'/../lipno-pair-offers-119692-v1.php';
require __DIR__.'/../six-real-stay-offers-v1.php';
require __DIR__.'/../stays-vranov-discovery-59891-v1.php';
function check_nry($condition,$label) {
    if (!$condition) { fwrite(STDERR, "FAIL: ".$label."\n"); exit(1); }
}
$c = 'NRY_Stays_Vranov_Discovery_59891_V1';
$source = str_repeat('x', 3000)
    .'<section data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1">'
    .'<h3>Pobyt, který má rybaření v nabídce</h3>'
    .'<article><h4>Statek Jedraž – Jižní Čechy</h4><p>Vlastní výbava.</p></article>'
    .'<h3>Další možnosti ubytování v rybářských lokalitách</h3>'
    .'<article>Hracholusky</article></section>';
$out=$c::render($source);
check_nry($out!==$source,'positive transform');
check_nry(substr_count($out,$c::MARKER)===1,'one navigation');
check_nry(strpos($out,$c::CYCLO)<strpos($out,$c::WELLNESS),'order');
check_nry(str_replace($c::block().$c::lipno(),'',$out)===$source,'source byte preservation');
check_nry(substr_count($out,'data-nry-lipno-hub-offer=')===2,'two sponsored Lipno offers');
check_nry(substr_count($out,'data-nry-vranov-hub-offer=')===2,'two sponsored Vranov offers');
check_nry($c::render($out)===$out,'idempotency');
$without=str_replace($c::EDIT_MARKER,'changed',$source);
check_nry($c::render($without)===$without,'source marker mismatch');
$double=str_replace($c::NEXT,$c::NEXT.$c::NEXT,$source);
check_nry($c::render($double)===$double,'duplicated anchor');
$linked=str_replace($c::NEXT,'<a href="'.$c::CYCLO.'">Old link</a>'.$c::NEXT,$source);
check_nry($c::render($linked)===$linked,'existing link guard');
$GLOBALS['nry_good']=false;
check_nry($c::render($source)===$source,'other page guard');
$GLOBALS['nry_good']=true;
$GLOBALS['nry_post']->post_status='draft';
check_nry($c::render($source)===$source,'unpublished guard');
echo "STAYS_VRANOV_59891_OFFLINE_PASS\n";
