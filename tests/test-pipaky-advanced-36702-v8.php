<?php
/** Offline one-post source-SHA reviewed advanced alarm decision. No live WP writes. */
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($code,$message=''){}}
function is_wp_error($x){return $x instanceof WP_Error;}
function add_action($name,$cb,$priority=10){return true;}
function wp_kses($html,$allowed,$protocols){return $html;}
$posts=array();$options=array();$writes=0;
function get_post($id){global $posts;return $posts[$id]??null;}
function add_option($k,$v,$deprecated='',$autoload='no'){
 global $options;if(array_key_exists($k,$options))return false;
 $options[$k]=$v;return true;
}
function get_option($k,$default=false){global $options;return $options[$k]??$default;}
function wp_update_post($payload,$error=false){
 global $posts,$writes;$id=(int)$payload['ID'];
 if(!isset($posts[$id]))return new WP_Error('missing_post');
 $writes++;$posts[$id]->post_content=$payload['post_content'];
 $posts[$id]->post_modified_gmt='2026-11-07 12:00:00';return $id;
}
function must($ok,$why){if(!$ok)throw new RuntimeException($why);}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-pipaky-advanced-36702-20261107-frozen.json'),true);
must($m['schema_version']===8&&$m['site']==='https://na-ryby.eu'
 &&$m['batch_id']==='pipaky-pokrocile-rozhodnuti-36702-20261107-v1'
 &&count($m['edits'])===1,'exact advanced source-bound batch');
$e=$m['edits'][0];
must($e['post_id']===36702
 &&$e['slug']==='pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu'
 &&$e['source_sha256']==='2904911169f1d49d30f46518c91c8f6220bfce71d978e73f11b3b0a26b2aeb6d'
 &&$e['source_modified_gmt']==='2026-10-23 06:35:08'
 &&$e['source_min_bytes']===10314&&$e['source_max_bytes']===10315,
 'live SHA/GMT/source length pinned');
$h=$e['insert_html'];$marker='data-nry-editorial-edit="pipaky-pokrocile-36702-20261107-v1"';
must(strlen($h)>2200&&strlen($h)<5000,'useful compact decision content');
must(strpos($h,'<section '.$marker.'>')===0&&substr_count($h,$marker)===1,'one section marker');
must(substr_count($h,'<li>')===4&&substr_count($h,'<a href=')===3,'four decisions and 3 navigation paths');
foreach(array('příposlech není nutný','náhradních baterií','kompatibilitu','není nezávislý srovnávací test') as $phrase)
 must(stripos($h,$phrase)!==false,'missing buyer-relevant caveat: '.$phrase);
must(!preg_match('~<(?:script|style|iframe|form|img|svg)\b|(?:\bonclick\b|http://)~iu',$h),'no unsafe active HTML');
preg_match_all('~href="([^"]+)"~',$h,$links);
must(count($links[1])===3,'three exact internal links');
foreach($links[1] as $url)must((bool)preg_match('~^https://na-ryby\.eu/[a-z0-9-]+/$~D',$url),
 'internal-only routes, no affiliate rewrites');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'native v8 contract accepts reviewed manifest');
// Synthetic body contains an already-existing older editorial marker, shortcode,
// affiliate destination and original image: our new insert must preserve all.
$legacy='<p>Původní text včetně původní návštěvnické rady.</p>'
 .'<section data-nry-editorial-edit="historical-link-first-v1"></section>'
 .'<a href="https://www.tkqlhce.com/click-123?sid=legacy">Původní link</a>'
 .'<img src="https://na-ryby.eu/wp-content/test.jpg" alt="Pípák">'
 .'[legacy_shortcode id="36702"]';
$source=$legacy.str_repeat('x',10314-strlen($legacy));
must(strlen($source)===10314,'mock source length');
$posts[36702]=(object)array('ID'=>36702,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>$e['slug'],'post_modified_gmt'=>$e['source_modified_gmt'],
 'post_content'=>$source);
$fixture=$m;$fixture['edits'][0]['source_sha256']=hash('sha256',$source);
must(NRY_Git_Publisher::plan($fixture)[0]['status']==='ready','source and candidate inspected');
$bad=$fixture;$bad['edits'][0]['source_sha256']=str_repeat('0',64);
must(NRY_Git_Publisher::plan($bad)[0]['status']==='blocked','changed source blocks');
$posts[36702]->post_modified_gmt='2026-11-07 09:00:00';
must(NRY_Git_Publisher::execute($fixture)['status']==='blocked_no_writes'
 &&$writes===0,'concurrent editing blocks entire batch');
$posts[36702]->post_modified_gmt=$e['source_modified_gmt'];
$done=NRY_Git_Publisher::execute($fixture);
must($done['status']==='applied_and_verified'&&$done['writes']===1&&$writes===1,
 'exact WP content updated once');
must(strpos($posts[36702]->post_content,$h."\n")===0
 &&substr($posts[36702]->post_content,strlen($h)+1)===$source,
 'original content and older affiliate/image preserved');
must(count($options)===1,'pre-write immutable backup created');
$backup=reset($options);
must($backup[0]['source']===$source&&$backup[0]['source_sha']===hash('sha256',$source),
 'private snapshot captured full original');
$again=NRY_Git_Publisher::execute($fixture);
must($again['status']==='applied_and_verified'&&$again['writes']===0&&$writes===1,
 'idempotent replay');
$bad=$fixture;$bad['edits'][0]['insert_html'].='<script>bad</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'unsafe HTML rejects');
$bad=$fixture;$bad['edits'][0]['post_id']=38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'protected content blocked');
echo "PASS #36702 four useful advanced signalizer decisions, exact source, old links preserved, backup, public-verifiable one write\n";
