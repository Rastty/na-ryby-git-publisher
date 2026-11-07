<?php
/** Offline proof for 42401 source-bound, append-only buyer routing. NO live WP write. */
define('ABSPATH', __DIR__);
class WP_Error { public function __construct($code,$message=''){} }
function is_wp_error($x){ return $x instanceof WP_Error; }
function add_action($name,$callback,$priority=10){ return true; }
function wp_kses($html,$allowed,$protocols){ return $html; }
$posts=array(); $options=array(); $writes=0;
function get_post($id){ global $posts; return $posts[$id]??null; }
function add_option($key,$value,$deprecated='',$autoload='no'){
    global $options; if(array_key_exists($key,$options))return false;
    $options[$key]=$value; return true;
}
function get_option($key,$default=false){global $options;return $options[$key]??$default;}
function wp_update_post($data,$wp_error=false){
    global $posts,$writes;
    if(!isset($posts[$data['ID']]))return new WP_Error('not_found');
    $writes++;$posts[$data['ID']]->post_content=$data['post_content'];
    $posts[$data['ID']]->post_modified_gmt='2026-11-07 14:00:00';
    return $data['ID'];
}
function must($ok,$message){if(!$ok)throw new RuntimeException($message);}
require dirname(__DIR__).'/na-ryby-git-publisher.php';
$m=json_decode(file_get_contents(__DIR__.'/v8-pipaky-42401-quick-choice-20261107-frozen.json'),true);
must($m['schema_version']===8 && $m['batch_id']==='pipaky-42401-quick-choice-router-20261107-v1'
    && count($m['edits'])===1,'only approved 42401');
$e=$m['edits'][0];$h=$e['insert_html'];
must($e['post_id']===42401
    && $e['slug']==='jednoduchy-navod-jak-vyuzivat-pipaky-na-ryby-vetsinou-ucinne'
    && $e['source_sha256']==='7f3b1fa8073d4167cdd770c6d869044be07d3db4d54884306a50cd0bbe6681f8'
    && $e['source_modified_gmt']==='2026-10-23 06:34:54','pinned authenticated WordPress capture #1442');
$marker='data-nry-editorial-edit="pipaky-navod-42401-quick-choice-20261107-v1"';
must(strpos($h,'<section '.$marker.'>')===0 && substr_count($h,$marker)===1,'one marker');
must(substr_count($h,'<li>')===4 && substr_count($h,'<a href=')===4,'four useful paths and only four links');
must(strpos($h,'není důvod automaticky kupovat nový')!==false,'avoid unnecessary purchase');
must(strpos($h,'Ceny, sklad a kompatibilitu konkrétní varianty')!==false,'avoid unsupported stock and price');
must(!preg_match('~<(?:script|style|iframe|form|img|svg)\\b|\\bonclick\\b|http://~iu',$h),'no active content');
preg_match_all('~href="([^"]+)"~',$h,$urls);
must(count($urls[1])===4 && count(array_unique($urls[1]))===4,'four unique URLs');
foreach($urls[1] as $url)must((bool)preg_match('~^https://na-ryby\\.eu/[a-z0-9-]+/$~D',$url),'internal-only exact links');
must(!is_wp_error(NRY_Git_Publisher::validate_manifest($m)),'reviewed v8 source contract accepted');
$legacy='<h1>Původní článek</h1><p>Žádné nové prodejní odkazy.</p>'
    .'<a href="https://www.tkqlhce.com/click-123?sid=old">Původní odkaz</a>'
    .'<img src="https://na-ryby.eu/wp-content/old.jpg" alt="Pípák">'
    .'[legacy_shortcode id="42401"]';
$source=$legacy.str_repeat('x',10000-strlen($legacy));
must(strlen($source)===10000,'stable mock source');
$posts[42401]=(object)array('ID'=>42401,'post_type'=>'post','post_status'=>'publish',
    'post_name'=>$e['slug'],'post_modified_gmt'=>$e['source_modified_gmt'],
    'post_content'=>$source);
must(NRY_Git_Publisher::plan($m)[0]['status']==='blocked','real source SHA cannot authorize synthetic fixture');
$test=$m;$test['edits'][0]['source_sha256']=hash('sha256',$source);
must(NRY_Git_Publisher::plan($test)[0]['status']==='ready','mock exact source ready');
$bad=$test;$bad['edits'][0]['source_modified_gmt']='2026-11-07 13:00:00';
must(NRY_Git_Publisher::execute($bad)['status']==='blocked_no_writes' && $writes===0,'GMT drift blocks all writes');
$bad=$test;$bad['edits'][0]['source_sha256']=str_repeat('0',64);
must(NRY_Git_Publisher::execute($bad)['status']==='blocked_no_writes' && $writes===0,'SHA drift blocks all writes');
$done=NRY_Git_Publisher::execute($test);
must($done['status']==='applied_and_verified' && $done['writes']===1 && $writes===1,'one guarded write');
must($posts[42401]->post_content===$h."\n".$source,'prepend only, original source preserved byte for byte');
must(count($options)===1 && reset($options)[0]['source']===$source,'immutable original source backup');
$replayed=NRY_Git_Publisher::execute($test);
must($replayed['status']==='applied_and_verified' && $replayed['writes']===0 && $writes===1,'idempotent replay');
$bad=$m;$bad['edits'][0]['insert_html'].='<script>alert(1)</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'unsafe HTML rejected');
$bad=$m;$bad['edits'][0]['post_id']=38064;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)),'protected post cannot be targeted');
echo "PASS #42401 four useful decision paths, source SHA, backup, one write, replay 0, old affiliate links preserved\n";
