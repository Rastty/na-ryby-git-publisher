<?php
/**
 * Three exact-source existing fishing-chair buyer-guide canaries.
 * No WordPress credentials, no WP writes; source byte hashes from production
 * v0.15 allowlisted read-only proof + prior authenticated editorial capture.
 */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($code,$message=''){}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($name,$callback,$priority=10){return true;}
function wp_kses($html,$allowed,$protocols){return $html;}
function test_ok($value,$name){if(!$value){fwrite(STDERR,"FAIL: ".$name."\n");exit(1);}}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-chair-three-20261102-frozen.json'),true);
$proof=array(
34858=>array('sha'=>'5fb4a53a467c81377182a31355ad74c899f10b60b441e78ffda3561cc95bbad7',
 'bytes'=>9529,'gmt'=>'2023-04-01 17:09:05'),
34953=>array('sha'=>'e1e8186db099c52dc56fdb91759364d96ecbd226497ee2a012a6ffedb087a551',
 'bytes'=>9191,'gmt'=>'2023-04-02 12:47:40'),
34969=>array('sha'=>'fd5c0429a376272ac01f6f226bfc9775620b8411817bc5564ae863ce22bde977',
 'bytes'=>9717,'gmt'=>'2023-04-02 16:21:41'),
);
test_ok($m['schema_version']===8,'only SHA-bound v8 is allowed');
test_ok($m['batch_id']==='chair-problem-decision-three-20261102-v1','immutable batch identity');
test_ok(array_column($m['edits'],'post_id')===array(34858,34953,34969),'exact reviewed page set and order');
test_ok(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'native plugin accepts guarded v8');
foreach($m['edits'] as $e){
 $id=$e['post_id'];$p=$proof[$id];
 test_ok($e['source_sha256']===$p['sha'],'hash pinned to actual production source');
 test_ok($e['source_modified_gmt']===$p['gmt'],'GMT pinned to production source');
 test_ok($e['source_min_bytes']===$p['bytes'] && $e['source_max_bytes']===$p['bytes']+1,
    'exact narrow source-byte window');
 $html=$e['insert_html'];$marker='data-nry-editorial-edit="'.$e['edit_id'].'"';
 test_ok(substr_count($html,$marker)===1 && strpos($html,'<section '.$marker.'>')===0,
    'single exact top-of-page insertion');
 test_ok(strlen($html)>=1000 && strlen($html)<=3000,'compact useful decision guide, no content dump');
 test_ok(substr_count($html,'<a href=')===2,'exact two internal action/navigation links');
 test_ok(!preg_match('~<(?:script|style|img|iframe|form)\b|(?:onload|onclick)\s*=|http://~iu',$html),
    'no active content, no insecure destinations');
 preg_match_all('~href="([^"]+)"~',$html,$links);
 test_ok(count($links[1])===2,'link count stable');
 foreach($links[1] as $url){
     test_ok((bool)preg_match('~^https://na-ryby\.eu/[a-z0-9-]+/$~D',$url),
       'all links are same-site public guides');
 }
 test_ok(strpos($html,'Cena:')===false && strpos($html,'skladem')===false,
    'no invented fresh price or stock claims');
}
$bad=$m;$bad['edits'][0]['source_sha256']=str_repeat('0',64);
test_ok(!is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
    'native only checks digest shape at preflight; server must match exact source in inspect');
$bad=$m;$bad['edits'][1]['insert_html'].='<script>alert(1)</script>';
test_ok(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'active JS fails closed');
$bad=$m;$bad['edits'][0]['post_id']=38064;
test_ok(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'protected post cannot enter batch');
echo "PASS: exact 3-post v8 SHA/GMT/size pin, unique problem content and internal navigation, zero WordPress writes\n";
