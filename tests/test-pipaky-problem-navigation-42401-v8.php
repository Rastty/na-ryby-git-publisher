<?php
/** Offline fail-closed one-article journey QA. No real WordPress writes. */
define('ABSPATH',__DIR__);
class WP_Error { public function __construct($code,$msg='') {} }
function is_wp_error($x) { return $x instanceof WP_Error; }
function add_action($name,$callback,$priority=10) { return true; }
function wp_kses($html,$allowed,$protocols) { return $html; }
$posts=array();$options=array();$writes=0;
function get_post($id) { global $posts;return $posts[$id]??null; }
function add_option($key,$value,$unused='',$autoload='no') {
 global $options;if(array_key_exists($key,$options))return false;
 $options[$key]=$value;return true;
}
function get_option($key,$default=false) {
 global $options;return $options[$key]??$default;
}
function wp_update_post($data,$throw=false) {
 global $posts,$writes;$id=(int)$data['ID'];
 if(!isset($posts[$id]))return new WP_Error('unknown_post');
 $writes++;$posts[$id]->post_content=$data['post_content'];
 $posts[$id]->post_modified_gmt='2026-11-07 13:58:00';
 return $id;
}
function must($ok,$message) {
 if(!$ok) {fwrite(STDERR,"FAIL: ".$message."\n");exit(1);}
}
require dirname(__DIR__).'/na-ryby-git-publisher.php';

$m=json_decode(file_get_contents(__DIR__.'/v8-pipaky-problem-navigation-42401-20261107-frozen.json'),true);
must($m['schema_version']===8
 &&$m['site']==='https://na-ryby.eu'
 &&$m['batch_id']==='pipaky-problem-navigation-42401-20261107-v1'
 &&count($m['edits'])===1,'exact single post batch');
$e=$m['edits'][0];
must($e['post_id']===42401
 &&$e['slug']==='jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne'
 &&$e['source_sha256']==='7f3b1fa8073d4167cdd770c6d869044be07d3db4d54884306a50cd0bbe6681f8'
 &&$e['source_modified_gmt']==='2026-10-23 06:34:54'
 &&$e['source_min_bytes']===14221&&$e['source_max_bytes']===14222
 &&$e['placement']==='prepend','exact authenticated live WordPress source');
$h=$e['insert_html'];
$marker='data-nry-editorial-edit="pipaky-rychle-rozhodnuti-42401-20261107-v1"';
must(strlen($h)>1300 && strlen($h)<2400
 &&strpos($h,'<section '.$marker.'>')===0
 &&substr_count($h,$marker)===1,'one useful section, not generic search text');
must(substr_count($h,'<li>')===4 && substr_count($h,'<a href=')===4,
 'four distinct user problems and same-site paths');
foreach(array('Pípá bez záběru','první hlásič','Vybíráte samostatný hlásič',
 'Rozšiřujete starší sadu','bez dalšího nákupu') as $phrase)
 must(strpos($h,$phrase)!==false,'problem-solving guidance: '.$phrase);
$paths=array(
 'https://na-ryby.eu/proc-mi-signalizator-pipa-bez-zaberu-5-nejcastejsich-pricin-a-jak-je-vyresit/',
 'https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/',
 'https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/',
 'https://na-ryby.eu/pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu/'
);
preg_match_all('~href="([^"]+)"~',$h,$hrefs);
must($hrefs[1]===$paths,'exact internal destinations and no new affiliate links');
must(!preg_match('~<(?:script|img|iframe|form|svg|style)\b|https?://(?:www\.)?(?:tkqlhce|jdoqocy)~i',$h),
 'no scripts, active embeds or new merchant tracking');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),
 'production schema v8 manifest valid with real SHA');

// Mock only the source hash to verify the real PHP inspect/execute path;
// source-identity validation MUST reject the mock as a production manifest.
$legacy='<p>Originální návod s podrobným nastavením.</p>'
 .'<a href="https://www.tkqlhce.com/click-legacy?sid=retain">Původní partner</a>'
 .'<img src="https://na-ryby.eu/wp-content/uploads/legacy.jpg" alt="Pípák">'
 .'<iframe src="https://na-ryby.eu/legacy-embed"></iframe>'
 .'[legacy_shortcode id="42401"]';
$source=$legacy.str_repeat('O',14221-strlen($legacy));
must(strlen($source)===14221,'synthetic original size correct');
$posts[42401]=(object)array('ID'=>42401,'post_type'=>'post',
 'post_status'=>'publish','post_name'=>$e['slug'],
 'post_modified_gmt'=>$e['source_modified_gmt'],'post_content'=>$source);
$fixture=$m;$fixture['edits'][0]['source_sha256']=hash('sha256',$source);
must(is_wp_error(NRY_Git_Publisher::validate_manifest($fixture))===false,
 'safe schema with any well-formed SHA, but server still matches actual SHA');
$ready=NRY_Git_Publisher::plan($fixture);
must(count($ready)===1 &&$ready[0]['status']==='ready'
 &&$ready[0]['candidate']===$h."\n".$source,
 'exact approved prepend candidate, original body byte-for-byte');
$wrong=$fixture;$wrong['edits'][0]['source_sha256']=str_repeat('0',64);
must(NRY_Git_Publisher::plan($wrong)[0]['status']==='blocked',
 'incorrect source hash blocks');
$wrong=$fixture;$wrong['edits'][0]['post_id']=38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($wrong)),
 'protected post cannot be edited');
$wrong=$fixture;$wrong['edits'][0]['insert_html'].='<script>alert(1)</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($wrong)),
 'active markup rejected');
$posts[42401]->post_modified_gmt='2026-11-07 13:57:20';
must(NRY_Git_Publisher::execute($fixture)['status']==='blocked_no_writes'
 &&$writes===0,'concurrent edit before applying blocks');
$posts[42401]->post_modified_gmt=$e['source_modified_gmt'];
$done=NRY_Git_Publisher::execute($fixture);
must($done['status']==='applied_and_verified' &&$done['writes']===1
 &&$writes===1,'one guarded WP write and exact SHA verified');
must($posts[42401]->post_content===$h."\n".$source
 &&substr_count($posts[42401]->post_content,$marker)===1
 &&strpos($posts[42401]->post_content,$legacy)!==false,
 'no alteration of old content, affiliate, media, shortcode, embed');
must(count($options)===1
 &&reset($options)[0]['source']===$source
 &&reset($options)[0]['source_sha']===hash('sha256',$source),
 'private original preserved as immutable backup');
$again=NRY_Git_Publisher::execute($fixture);
must($again['status']==='applied_and_verified' &&$again['writes']===0
 &&$writes===1,'replayed batch causes zero extra edits');
echo "PASS: 42401 high-intent four-path navigation, no affiliate change, source lock, backup, replay\n";
