<?php
/** Source-pinned 60354 legacy claims remediation; mock WP, never live writes. */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($code,$message=''){}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($x,$cb,$priority=10){return true;}
function wp_kses($html,$allowed,$protocols){return $html;}
$posts=array();$options=array();$writes=0;
function get_post($id){global $posts;return $posts[$id]??null;}
function add_option($key,$value,$deprecated='',$autoload='no'){
 global $options; if(array_key_exists($key,$options))return false;
 $options[$key]=$value;return true;
}
function get_option($key,$default=false){global $options;return $options[$key]??$default;}
function wp_update_post($data,$error=false){
 global $posts,$writes; $id=$data['ID'];
 if(!isset($posts[$id]))return new WP_Error('missing_post');
 $writes++;$posts[$id]->post_content=$data['post_content'];
 $posts[$id]->post_modified_gmt='2026-11-07 18:00:00';return $id;
}
function must($ok,$why){if(!$ok)throw new RuntimeException($why);}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-alarm-ranking-60354-claims-20261107-frozen.json'),true);
must($m['schema_version']===8 && count($m['edits'])===1
 && $m['batch_id']==='alarm-ranking-60354-legacy-claims-fix-20261107-v1','immutable single batch');
$e=$m['edits'][0];
must($e['post_id']===60354 && $e['source_min_bytes']===20174
 && $e['source_sha256']==='a7d76b5908e09c4bd128548806c5bb3589b9aed88546770f4f4a9604ec0fea83'
 && count($e['text_corrections'])===8,'exact source and eight claim classes');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'reviewed narrow operation valid');
$terms='';
foreach($e['text_corrections'] as $i=>$c) {
 $times=($i===0)?4:(($i===1)?2:1);
 for($j=0;$j<$times;$j++)$terms.='<p>'.$c['old_text'].'</p>';
}
$legacy='<a href="https://www.tkqlhce.com/click-123?sid=protected">Původní affiliate</a>'
 .'<img src="https://na-ryby.eu/wp-content/uploads/old.jpg" alt="Original">'
 .'[old_product_offer id="60354"]';
$base='<h2>Historické srovnání značek</h2>'.$terms.$legacy;
must(strlen($base)<20174,'synthetic source fits pinned length');
$source=$base.str_repeat('x',20174-strlen($base));
$posts[60354]=(object)array('ID'=>60354,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>$e['slug'],'post_modified_gmt'=>$e['source_modified_gmt'],
 'post_content'=>$source);
must(NRY_Git_Publisher::plan($m)[0]['status']==='blocked','cannot publish mock with real SHA');
$mock=$m;$mock['edits'][0]['source_sha256']=hash('sha256',$source);
must(NRY_Git_Publisher::plan($mock)[0]['status']==='ready','safe visible corrections ready');
$bad=$mock;$bad['edits'][0]['source_sha256']=str_repeat('0',64);
must(NRY_Git_Publisher::execute($bad)['status']==='blocked_no_writes' && $writes===0,
 'stale SHA fails before WordPress write');
$bad=$mock;$bad['edits'][0]['source_modified_gmt']='2026-11-07 19:00:00';
must(NRY_Git_Publisher::execute($bad)['status']==='blocked_no_writes' && $writes===0,
 'modified GMT drift fails');
$done=NRY_Git_Publisher::execute($mock);
must($done['status']==='applied_and_verified' && $done['writes']===1 && $writes===1,
 'exact one protected content write');
$after=$posts[60354]->post_content;
foreach($e['text_corrections'] as $c)must(strpos($after,$c['old_text'])===false,
 'all unsupported original claims removed');
must(substr_count($after,'bez zveřejněného testovacího skóre')===6,
 '6 unproven scores corrected with no false star rankings');
must(strpos($after,$legacy)!==false,'existing original affiliate href, image and shortcode preserved');
must(count($options)===1 && reset($options)[0]['source']===$source,
 'on-WordPress immutable full source backup created');
$again=NRY_Git_Publisher::execute($mock);
must($again['status']==='applied_and_verified' && $again['writes']===0 && $writes===1,
 'idempotent replay zero writes');
$bad=$m;$bad['edits'][0]['post_id']=118218;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'scope cannot move to other post');
$bad=$m;$bad['edits'][0]['text_corrections'][0]['new_text']='Better price!'; 
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'approved fixed replacement cannot drift');
echo "PASS 60354: six unverified scores and 6 claim spans corrected; backed up, old links/media retained, drift fails, replay zero\n";
