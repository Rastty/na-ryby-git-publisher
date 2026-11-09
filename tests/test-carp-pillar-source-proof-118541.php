<?php
// Offline contract: public hash/count only; no direct WordPress writer.
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=array();
$GLOBALS['fixture']=null;
function add_action($x,$cb,$priority=10) {$GLOBALS['hooks'][]=array($x,$cb);}
function get_post($id) {return $GLOBALS['fixture'];}
function get_permalink($id) {return NRY_Carp_Pillar_Source_118541::URL;}
function ok($x,$s){if(!$x)throw new RuntimeException($s); echo "PASS: $s\n";}
require dirname(__DIR__).'/carp-pillar-source-proof-118541-v1.php';
ok(count($GLOBALS['hooks'])===1,'exact single read-only probe hook');
ok(NRY_Carp_Pillar_Source_118541::snapshot()['state']==='BLOCKED_EXACT_POST_IDENTITY','missing source blocked');
$GLOBALS['fixture']=(object)array('ID'=>118541,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>NRY_Carp_Pillar_Source_118541::SLUG,
 'post_modified_gmt'=>'2026-03-21 10:00:00',
 'post_content'=>str_repeat('Kapr prut a vyber; ',65).NRY_Carp_Pillar_Source_118541::EDITORIAL_PHRASE .str_repeat(chr(96),3));
$r=NRY_Carp_Pillar_Source_118541::snapshot();
ok($r['state']==='SOURCE_ONLY_NO_PUBLICATION' && $r['publicationAuthorized']===false && $r['wordpressWrites']===0,'snapshot never authorizes publication');
ok($r['sourceSha256']===hash('sha256',$GLOBALS['fixture']->post_content) && $r['literalCounts']['editorialPhraseExact']===1 && $r['literalCounts']['backtickFence']===1,'exact source/counts, no raw text');
ok(!isset($r['post_content']) && !isset($r['affiliateUrl']) && !isset($r['body']),'no private content or merchant URL in public receipt');
$GLOBALS['fixture']->post_name='other';ok(NRY_Carp_Pillar_Source_118541::snapshot()['state']==='BLOCKED_EXACT_POST_IDENTITY','slug drift blocked');
$GLOBALS['fixture']->post_name=NRY_Carp_Pillar_Source_118541::SLUG;
$GLOBALS['fixture']->post_status='draft';ok(NRY_Carp_Pillar_Source_118541::snapshot()['state']==='BLOCKED_EXACT_POST_IDENTITY','draft blocked');
