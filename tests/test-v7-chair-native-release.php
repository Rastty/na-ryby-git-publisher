<?php
/**
 * Isolated fail-closed contract for four server-native buyer guides (v7).
 * No website, external REST, WpVibe or actual production writes.
 */
define('ABSPATH', __DIR__);
class WP_Error { public function __construct($code, $message = '') {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
function add_action($name, $fn) { return true; }
function wp_kses($html, $allowed, $protocols) { return $html; }
$options=array();
$posts=array();
$writes=0;
function get_post($id) { global $posts; return $posts[$id] ?? null; }
function add_option($key,$value,$deprecated='',$autoload='yes') {
    global $options;
    if(array_key_exists($key,$options))return false;
    $options[$key]=$value;
    return true;
}
function get_option($key,$default=false) {
    global $options;
    return $options[$key] ?? $default;
}
function wp_update_post($data,$returnError=false) {
    global $posts,$writes;
    $id=(int)$data['ID'];
    if(!isset($posts[$id]))return new WP_Error('bad_post');
    $writes++;
    $posts[$id]->post_content=$data['post_content'];
    $posts[$id]->post_modified_gmt='2026-10-25 14:00:00';
    return $id;
}
function must($x,$why) {if(!$x)throw new RuntimeException($why);}
require dirname(__DIR__) . '/na-ryby-git-publisher.php';
$manifest=NRY_Git_Publisher::manifest();
must(!is_wp_error($manifest),'schema7 manifest rejected');
must($manifest['schema_version']===7,'wrong manifest version');
must(array_column($manifest['edits'],'post_id')===[34913,34977,35003,35011],
     'wrong post scope');
$originals=array();
foreach($manifest['edits'] as $edit) {
    $id=$edit['post_id'];
    // Exact source length is deliberately unknown to the public website:
    // test a valid point within the bounded range and immutable GMT.
    $source='<h2>'.str_repeat('x',7500).'</h2>';
    $posts[$id]=(object)array('ID'=>$id,'post_type'=>'post','post_status'=>'publish',
        'post_name'=>$edit['slug'],'post_modified_gmt'=>$edit['source_modified_gmt'],
        'post_content'=>$source);
    $originals[$id]=$source;
}
$rows=NRY_Git_Publisher::plan($manifest);
must(count($rows)===4 && count(array_filter($rows,fn($r)=>$r['status']==='ready'))===4,
     'four-post preflight did not return ready');
foreach($rows as $i=>$row){
    $prefix=$manifest['edits'][$i]['insert_html']."\n";
    must(strpos($row['candidate'],$prefix)===0,'not exact prepend');
    must(substr($row['candidate'],strlen($prefix))===$row['source'],'original drift');
}
$receipt=NRY_Git_Publisher::execute($manifest);
must($receipt['status']==='applied_and_verified' && $receipt['writes']===4,
     'four writes not verified');
must($writes===4,'unexpected mutation count');
must(count($options)===1,'no persistent prewrite backup');
$stored=reset($options);
must(count($stored)===4,'backup missing originals');
foreach($stored as $snap)must($snap['source']===$originals[$snap['id']], 'original changed in backup');
$again=NRY_Git_Publisher::execute($manifest);
must($again['writes']===0 && $again['already_applied']===4 && $writes===4,
     'idempotent replay wrote again');
foreach($manifest['edits'] as $edit){
    $id=$edit['post_id'];
    $posts[$id]->post_content=$originals[$id];
    $posts[$id]->post_modified_gmt=$edit['source_modified_gmt'];
}
$posts[34977]->post_modified_gmt='2026-10-25 14:00:00';
$no=NRY_Git_Publisher::execute($manifest);
must($no['status']==='blocked_no_writes' && $no['writes']===0 && $writes===4,
     'failed to stop entire batch on concurrent modification');
$posts[34977]->post_modified_gmt=$manifest['edits'][1]['source_modified_gmt'];
$posts[34913]->post_content='short';
$no=NRY_Git_Publisher::execute($manifest);
must($no['status']==='blocked_no_writes' && $writes===4,'source size drift did not stop');
$posts[34913]->post_content=$originals[34913];
$bad=$manifest;
$bad['edits'][0]['post_id']=44102; // historic KEEP
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)), 'KEEP ID allowed');
$bad=$manifest;
$bad['edits'][0]['insert_html'].='<script>alert(1)</script>';
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)), 'script allowed');
$bad=$manifest;
$bad['edits'][0]['insert_html']=str_replace(
    'https://na-ryby.eu/jak-vybrat-rybarske-kreslo/',
    'https://external-affiliate.example/push',
    $bad['edits'][0]['insert_html']);
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)), 'external link allowed');
$bad=$manifest;
$bad['edits'][0]['source_min_bytes']=3000;
must(is_wp_error(NRY_Git_Publisher::validate_manifest($bad)), 'unsafe source min allowed');
echo "ok: v7 scope/HTML/backup/replay/four writes/gmt drift/source bound/fail closed\n";
