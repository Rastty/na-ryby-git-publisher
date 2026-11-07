<?php
/** Offline, fail-closed 1-post source-bound release test. No real WP writes. */
define('ABSPATH',__DIR__);
class WP_Error { public function __construct($code, $message='') {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
function add_action($hook,$cb,$priority=10) { return true; }
function wp_kses($html,$allowed,$protocols) { return $html; }
$options=array();$posts=array();$writes=0;
function get_post($id) { global $posts;return $posts[$id]??null; }
function add_option($key,$value,$unused='',$autoload='no') {
 global $options;if(array_key_exists($key,$options))return false;
 $options[$key]=$value;return true;
}
function get_option($key,$default=false) {
 global $options;return $options[$key]??$default;
}
function wp_update_post($data,$returnError=false) {
 global $posts,$writes;$id=(int)$data['ID'];
 if(!isset($posts[$id]))return new WP_Error('bad_post');
 $writes++;$posts[$id]->post_content=$data['post_content'];
 $posts[$id]->post_modified_gmt='2026-11-07 10:50:00';
 return $id;
}
function must($ok,$message){if(!$ok)throw new RuntimeException($message);}
require dirname(__DIR__).'/na-ryby-git-publisher.php';

$m=json_decode(file_get_contents(dirname(__DIR__).'/manifest.json'),true);
must($m['schema_version']===8 && $m['batch_id']==='pipaky-diagnostika-35755-20261107-v1','immutable batch');
must(count($m['edits'])===1,'one post only');
$e=$m['edits'][0];
must($e['post_id']===35755 && $e['slug']==='pipaky-na-ryby-prakticke-rady-pro-zacatecniky','one target');
must($e['source_sha256']==='2ae4c0adb81a0fc7b667e95bb23d9b1e3f4c590b173c98b8e125c48c41c2763f','live source proof');
must($e['source_modified_gmt']==='2026-10-23 06:35:07','exact original modified date');
must($e['source_min_bytes']===14984 && $e['source_max_bytes']===14985,'narrow source window');
$h=$e['insert_html'];$marker='data-nry-editorial-edit="pipaky-diagnostika-35755-20261107-v1"';
must(strlen($h)>1600 && strlen($h)<4500,'compact troubleshooting diagnosis');
must(substr_count($h,$marker)===1 && strpos($h,'<section '.$marker.'>')===0,'single marker at start');
must(substr_count($h,'<li>')===4,'four distinct visitor problems');
must(strpos($h,'Nepípne ani při posunutí vlasce')!==false
 && strpos($h,'Pípá opakovaně, ale ryba nezabírá')!==false
 && strpos($h,'Na jemné záběry reaguje pozdě')!==false
 && strpos($h,'příposlech však mlčí')!==false,'all four distinct diagnoses');
must(substr_count($h,'<a href=')===3,'three internal links only');
must(strpos($h,'<script')===false && strpos($h,'<img')===false && strpos($h,'<form')===false,'no active content');
must(strpos($h,' Kč')===false && strpos($h,'skladem')===false,'no invented live price/stock');
preg_match_all('~href="([^"]+)"~',$h,$urls);
must(count($urls[1])===3,'three href targets');
foreach($urls[1] as $u)must((bool)preg_match('~^https://na-ryby\.eu/[a-z0-9-]+/$~D',$u),'no external merchant redirect');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'valid native schema8');

$legacy='<p>Původní obsah beze změny</p>'
 .'<a href="https://www.tkqlhce.com/click-123?sid=legacy">Původní affiliate odkaz</a>'
 .'<img src="https://example.com/legacy.jpg" alt="Legacy">'
 .'[legacy_shortcode xyz="one"]';
$source=$legacy.str_repeat('x',14984-strlen($legacy));
must(strlen($source)===14984,'synthetic source exact length');
$posts[35755]=(object)array('ID'=>35755,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>$e['slug'],'post_content'=>$source,
 'post_modified_gmt'=>$e['source_modified_gmt']);
$fixture=$m;$fixture['edits'][0]['source_sha256']=hash('sha256',$source);
$ready=NRY_Git_Publisher::plan($fixture);
must(count($ready)===1 && $ready[0]['status']==='ready','mock current source readied');
$stale=$fixture;$stale['edits'][0]['source_sha256']=str_repeat('0',64);
must(NRY_Git_Publisher::plan($stale)[0]['status']==='blocked','wrong SHA blocked');
$posts[35755]->post_modified_gmt='2026-11-07 08:10:00';
must(NRY_Git_Publisher::execute($fixture)['status']==='blocked_no_writes','concurrent editor blocked');
must($writes===0,'stale preflight wrote no content');
$posts[35755]->post_modified_gmt=$e['source_modified_gmt'];
$done=NRY_Git_Publisher::execute($fixture);
must($done['status']==='applied_and_verified' && $done['writes']===1,'one guarded write');
must($writes===1,'only one WP operation');
must(strpos($posts[35755]->post_content,$h."\n")===0,'exact prefix');
must(substr($posts[35755]->post_content,strlen($h)+1)===$source,'original body preserved byte-for-byte');
must(substr_count($posts[35755]->post_content,$marker)===1,'single inserted marker');
must(strpos($posts[35755]->post_content,$legacy)!==false,'original affiliate/image/shortcode preserved');
must(count($options)===1,'immutable backup recorded');
$snapshot=reset($options);
must(count($snapshot)===1 && $snapshot[0]['source']===$source
 && $snapshot[0]['source_sha']===hash('sha256',$source),'backup exact source');
$replay=NRY_Git_Publisher::execute($fixture);
must($replay['status']==='applied_and_verified' && $replay['writes']===0
 && $writes===1,'repeat release zero additional writes');
$bad=$fixture;$bad['edits'][0]['insert_html'].='<script>alert(1)</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'active content rejected');
$bad=$fixture;$bad['edits'][0]['post_id']=38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'protected post rejected');
echo "PASS: one useful pípáky diagnosis source-bound, unchanged legacy affiliate, backup, replay, no unsafe publication\n";
