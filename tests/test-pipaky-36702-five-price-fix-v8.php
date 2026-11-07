<?php
/** Five historical noncurrent prices in ONE exact #36702 article, isolated PHP. */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($code,$message='') {}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($name,$cb,$priority=10){return true;}
function wp_kses($html,$allowed,$protocols){return $html;}
function must($x,$why){if(!$x){fwrite(STDERR,"FAIL: ".$why."\\n");exit(1);}}
$posts=array();$options=array();$writes=0;
function get_post($id){global $posts;return $posts[$id]??null;}
function add_option($key,$value,$deprecated='',$autoload='no'){
 global $options;if(array_key_exists($key,$options))return false;
 $options[$key]=$value;return true;
}
function get_option($key,$default=null){global $options;return $options[$key]??$default;}
function wp_update_post($payload,$err=false){
 global $posts,$writes;
 $id=(int)$payload['ID'];$writes++;
 $posts[$id]->post_content=$payload['post_content'];
 return $id;
}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-pipaky-36702-five-prices-20261107-frozen.json'),true);
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'exact reviewed manifest valid');
must($m['schema_version']===8 &&$m['site']==='https://na-ryby.eu'
 &&$m['batch_id']==='pipaky-pokrocile-five-price-fix-36702-20261107-v1'
 &&count($m['edits'])===1,'one approved site and batch');
$e=$m['edits'][0];
must($e['post_id']===36702&&$e['placement']==='price_corrections_only'
 &&$e['source_sha256']==='b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735'
 &&$e['source_modified_gmt']==='2026-11-07 11:23:04'
 &&$e['source_min_bytes']===12938&&$e['source_max_bytes']===12939,
 'live source proof immutable');
must(count($e['text_corrections'])===5,'exact five source corrections');
$expected=array('1000','800','3000','1200','2000');
foreach($expected as $i=>$p)must(
 $e['text_corrections'][$i]===array(
  'old_text'=>'Cena: od '.$p.' Kč',
  'new_text'=>'Historická cena – aktuálně neověřeno'),'no invented price or other change');
$marker='data-nry-editorial-edit="pipaky-pokrocile-36702-20261107-v1"';
$begin='<section '.$marker.'><h2>Pokročilé hlásiče</h2></section>';
$preserve='<a href="https://www.tkqlhce.com/click-444?sid=old">Původní affiliate</a>'
 .'<a href="https://rybarske-potreby-na-zaclony.cz/">Starý externí odkaz</a>'
 .'<a href="https://rybarske-potreby-na-lukavecu.cz/">Starý externí odkaz</a>'
 .'<img src="https://na-ryby.eu/wp-content/uploads/image.jpg" alt="Současný">'
 .'[legacy_short_code id="1983"]';
$raw=$begin.$preserve.'<div><p>'.implode('</p><p>',
 array_map(function($p){return 'Cena: od '.$p.' Kč';},$expected)).'</p></div>';
$source=$raw.str_repeat('A',12938-strlen($raw));
must(strlen($source)===12938,'mock exact-length original source');
$posts[36702]=(object)array('ID'=>36702,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>$e['slug'],'post_modified_gmt'=>$e['source_modified_gmt'],
 'post_content'=>$source);
$fixture=$m;$fixture['edits'][0]['source_sha256']=hash('sha256',$source);
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($fixture)),'fixture valid');
$ready=NRY_Git_Publisher::plan($fixture);
must($ready[0]['status']==='ready','five plain text source spans ready');
$candidate=$ready[0]['candidate'];
must(substr_count($candidate,'Historická cena – aktuálně neověřeno')===5,
 'five source amounts replaced with no new values');
foreach($expected as $p)must(strpos($candidate,'Cena: od '.$p.' Kč')===false,
 'price is no longer displayed');
must(strpos($candidate,$preserve)!==false
 &&strpos($candidate,$begin)===0,'all original markup and legacy affiliate preserved');
must($writes===0,'plan has no WordPress writes');
$bad=$fixture;$bad['edits'][0]['source_sha256']=str_repeat('0',64);
must(NRY_Git_Publisher::plan($bad)[0]['status']==='blocked','unknown source SHA blocks');
$bad=$fixture;$bad['batch_id']='other-batch';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'batch cannot widen');
$bad=$fixture;$bad['edits'][0]['post_id']=38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'protected post cannot be changed');
$bad=$fixture;$bad['edits'][0]['text_corrections'][0]['new_text']='Cena: od 500 Kč';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'no fabricated replacement amount');
$posts[36702]->post_content=str_replace('Cena: od 1000 Kč','Cena: od 999 Kč',$source);
must(NRY_Git_Publisher::plan($fixture)[0]['status']==='blocked','source drift stops');
$posts[36702]->post_content=$source;
$posts[36702]->post_modified_gmt='2026-11-07 13:00:00';
must(NRY_Git_Publisher::execute($fixture)['status']==='blocked_no_writes'
 &&$writes===0,'concurrent editor changes blocked');
$posts[36702]->post_modified_gmt=$e['source_modified_gmt'];
$done=NRY_Git_Publisher::execute($fixture);
must($done['status']==='applied_and_verified'&&$done['writes']===1
 &&$writes===1,'one guarded WordPress write actually applied');
must($posts[36702]->post_content===$candidate,'candidate SHA preserved');
must(count($options)===1 &&reset($options)[0]['source']===$source,
 'exact original source private backup');
$again=NRY_Git_Publisher::execute($fixture);
must($again['status']==='applied_and_verified'&&$again['writes']===0
 &&$writes===1,'idempotent no second write');
echo "PASS: five independent historical prices replaced, original links/media intact, backup, drift, replay\\n";
