<?php
/** Exact three false-current sonar offers; mock WordPress, zero live writes. */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($code,$message=''){}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($hook,$handler,$priority=10){return true;}
function wp_kses($html,$allowed,$protocols){return $html;}
$posts=array();$options=array();$writes=0;
function get_post($id){global $posts;return $posts[$id]??null;}
function add_option($key,$value,$deprecated='',$autoload='no'){
 global $options;if(array_key_exists($key,$options))return false;
 $options[$key]=$value;return true;
}
function get_option($key,$default=false){global $options;return $options[$key]??$default;}
function wp_update_post($data,$error=false){
 global $posts,$writes;$id=$data['ID'];
 if(!isset($posts[$id]))return new WP_Error('missing_post');
 $writes++;$posts[$id]->post_content=$data['post_content'];
 $posts[$id]->post_modified_gmt='2026-11-08 08:15:00';return $id;
}
function must($ok,$why){if(!$ok)throw new RuntimeException($why);}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-echolot-budget-118455-three-offers-frozen.json'),true);
must($m['schema_version']===8 && $m['site']==='https://na-ryby.eu'
 && $m['batch_id']==='echolot-budget-118455-three-offers-fix-20261108-v1'
 && count($m['edits'])===1,'immutable single reviewed sonar batch');
$e=$m['edits'][0];
must($e['post_id']===118455 && $e['source_min_bytes']===39795
 && $e['source_sha256']==='f0ba0b4dcd29646d470d084af077623b12fa687935347e626203a13107e7be79'
 && count($e['text_corrections'])===3,'exact source and three plain price clauses');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),
 'fixed three corrections pass strict manifest only at genuine original SHA');
$parts=array();
foreach ($e['text_corrections'] as $r) $parts[]='<p>'.$r['old_text'].'</p>';
$legacy='<a href="https://go.dognet.com/?cid=reviewed">Původní nabídka</a>'
 .'<img src="https://na-ryby.eu/wp-content/uploads/sonar.jpg" alt="Sonar">'
 .'[legacy_sonar id="118455"]';
$head='<h2>Historické ceny nejsou živé nabídky</h2>'.implode("\n",$parts).$legacy;
must(strlen($head)<39795,'fake source fits pinned bytes');
$source=$head.str_repeat('x',39795-strlen($head));
$posts[118455]=(object)array('ID'=>118455,'post_type'=>'post',
 'post_status'=>'publish','post_name'=>$e['slug'],
 'post_modified_gmt'=>$e['source_modified_gmt'],'post_content'=>$source);
must(NRY_Git_Publisher::plan($m)[0]['status']==='blocked','fake content hash never publishes');
$mock=$m;$mock['edits'][0]['source_sha256']=hash('sha256',$source);
must(NRY_Git_Publisher::plan($mock)[0]['status']==='ready','visible safe clauses are ready');
$unsafe=$mock;$unsafe['edits'][0]['source_sha256']=str_repeat('a',64);
must(NRY_Git_Publisher::execute($unsafe)['status']==='blocked_no_writes' && $writes===0,
 'wrong current SHA blocks all writes');
$unsafe=$mock;$unsafe['edits'][0]['source_modified_gmt']='2026-11-09 01:00:00';
must(NRY_Git_Publisher::execute($unsafe)['status']==='blocked_no_writes' && $writes===0,
 'source modified date drift must block');
$changed=NRY_Git_Publisher::execute($mock);
must($changed['status']==='applied_and_verified'&&$changed['writes']===1&&$writes===1,
 'one safe original WordPress write only');
$body=$posts[118455]->post_content;
foreach($e['text_corrections'] as $r){
 must(strpos($body,$r['old_text'])===false,'old price/stock clause no longer present');
 must(substr_count($body,$r['new_text'])===1,'exact reader-safe correction present once');
}
must(strpos($body,$legacy)!==false,'original href, media and shortcode unchanged');
must(count($options)===1 && reset($options)[0]['source']===$source,
 'source snapshot immutable in private WordPress backup');
$replay=NRY_Git_Publisher::execute($mock);
must($replay['status']==='applied_and_verified' && $replay['writes']===0 && $writes===1,
 'replay is idempotent with zero new WordPress writes');
$bad=$m;$bad['edits'][0]['post_id']=118553;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
 'no other sonar article is allowed');
$bad=$m;$bad['edits'][0]['text_corrections'][0]['new_text']='Buy it today';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),
 'cannot authorize arbitrary merchandising');
echo "PASS 118455: exact three source-safe price/stock corrections, immutable backup, links parity, drift/replay fail closed\n";
