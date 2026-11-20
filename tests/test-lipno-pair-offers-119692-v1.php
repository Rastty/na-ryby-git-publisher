<?php
/** Offline context, exact source prefix and pair-of-offers assertions. */
define('ABSPATH', __DIR__);
$GLOBALS['id'] = 119692;
$GLOBALS['modified'] = '2026-05-24 22:01:42';
$GLOBALS['admin'] = false;
$GLOBALS['preview'] = false;
$GLOBALS['loop'] = true;
$GLOBALS['permalink'] = 'https://na-ryby.eu/rybarsky-vikend-na-lipne-pro-dva-jak-ho-naplanovat-aby-to-davalo-smysl/';
function is_single($id) { return $GLOBALS['id'] === $id; }
function is_singular($type) { return $type === 'post'; }
function get_queried_object_id() { return $GLOBALS['id']; }
function is_main_query() { return true; }
function in_the_loop() { return $GLOBALS['loop']; }
function is_admin() { return $GLOBALS['admin']; }
function is_preview() { return $GLOBALS['preview']; }
function home_url($path) { return 'https://na-ryby.eu/'; }
function get_permalink($id) { return $GLOBALS['permalink']; }
function get_post($id) {
    return (object)array('ID'=>$id,'post_type'=>'post','post_status'=>'publish',
        'post_name'=>'rybarsky-vikend-na-lipne-pro-dva-jak-ho-naplanovat-aby-to-davalo-smysl',
        'post_modified_gmt'=>$GLOBALS['modified'],
        'post_content'=>str_repeat('Rybářský víkend u Lipna pro dva. ',700));
}
function add_filter($hook,$cb,$priority) {}
function check($ok,$message) { if (!$ok) throw new RuntimeException($message); }
require dirname(__DIR__).'/lipno-pair-offers-119692-v1.php';
$cl='NRY_Lipno_Pair_Offers_119692_V1';
$prefix=base64_decode('PHNlY3Rpb24gc3R5bGU9ImZvbnQtZmFtaWx5OiBBcmlhbCwgSGVsdmV0aWNhLCBzYW5zLXNlcmlmOyBsaW5lLWhlaWdodDogMS42NTsgY29sb3I6ICMxZjI5Mzc7IG1heC13aWR0aDogOTgwcHg7IG1hcmdpbjogMCBhdXRvOyBwYWRkaW5nOiAwOyI+CjxoMSBzdHlsZT0iZm9udC1zaXplOiAyLjFyZW07IGxpbmUtaGVpZ2h0OiAxLjE1OyBtYXJnaW46IDAgMCAxNnB4OyBjb2xvcjogIzBmMTcyYTsiPlJ5YsOhxZlza8O9IHbDrWtlbmQgbmEgTGlwbsSbIHBybyBkdmE6IGphayBobyBuYXBsw6Fub3ZhdCwgYWJ5IHRvIGTDoXZhbG8gc215c2w8L2gxPgo8ZGl2IHN0eWxlPSJiYWNrZ3JvdW5kOiBsaW5lYXItZ3JhZGllbnQoMTgwZGVnLCAjZjhmYWZjIDAlLCAjZWVmNmYzIDEwMCUpOyBib3JkZXI6IDFweCBzb2xpZCAjZGJlN2UzOyBib3JkZXItcmFkaXVzOiAxNnB4OyBwYWRkaW5nOiAyMnB4OyBtYXJnaW46IDAgMCAyMnB4OyI+CjxwIHN0eWxlPSJtYXJnaW46IDAgMCAxMnB4OyBmb250LXNpemU6IDEuMDg=',true);
check(is_string($prefix) && strlen($prefix)===512 && hash('sha256',$prefix)===$cl::PREFIX_SHA256,'PINNED_PREFIX');
$original=$prefix.str_repeat(' ',24000).$cl::DECISION.'<h2>Decision</h2></section>'
    ."\n".$cl::TABLE.'<h2>Table preserved</h2></section>';
$new=$cl::render($original);
check($new!==$original,'NO_VISIBLE_OFFERS');
check(substr_count($new,$cl::MARKER)===1,'MARKER_COUNT');
check(substr_count($new,'data-nry-lipno-pair-offer=')===2,'OFFERS_COUNT');
check(substr_count($new,'rel="sponsored nofollow noopener"')===2,'AFFILIATE_DISCLOSURE');
check(substr_count($new,htmlspecialchars($cl::JBX,ENT_QUOTES,'UTF-8'))===1,'JBX_DEEP_LINK');
check(substr_count($new,htmlspecialchars($cl::VYHLIDKA,ENT_QUOTES,'UTF-8'))===1,'VYHLIDKA_DEEP_LINK');
check(substr_count($new,$cl::STAYS)===1,'HUB_HANDOFF');
check(strpos($new,$cl::DECISION)<strpos($new,$cl::MARKER)
    && strpos($new,$cl::MARKER)<strpos($new,$cl::TABLE),'POSITION');
check(str_replace($cl::block()."\n",'',$new)===$original,'ORIGINAL_SOURCE_NOT_PRESERVED');
check($cl::render($new)===$new,'DOUBLE_INSERTION');
check($cl::render('x'.$original)==='x'.$original,'PREFIX_DRIFT');
$without=str_replace($cl::TABLE,'<section id="different">',$original);
check($cl::render($without)===$without,'TABLE_DRIFT');
foreach(array(array('id',12),array('modified','2026-11-19 00:00:00'),
    array('admin',true),array('preview',true),array('loop',false),
    array('permalink','https://na-ryby.eu/other/')) as $case) {
    list($key,$value)=$case; $prior=$GLOBALS[$key]; $GLOBALS[$key]=$value;
    check($cl::render($original)===$original,'NON_TARGET_MUTATION_'.$key);
    $GLOBALS[$key]=$prior;
}
foreach(array($cl::JBX=>'https://www.hauzi.cz/jbx-resort-albatros',
    $cl::VYHLIDKA=>'https://www.hauzi.cz/komfortny-penzion-so-saunou-na-lipne') as $url=>$target) {
    $parts=parse_url($url); parse_str($parts['query']??'',$q);
    check(($parts['host']??'')==='tracking.affiliateport.eu' && ($q['o']??'')==='1990'
        && ($q['a']??'')==='1736' && ($q['sub_id5']??'')===$target && count($q)===3,'HAUZI_DEEP_LINK_DRIFT');
}
echo "LIPNO_PAIR_119692_CONTRACT_OK\n";
