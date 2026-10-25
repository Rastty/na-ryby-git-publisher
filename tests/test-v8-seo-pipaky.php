<?php
// Fully offline WordPress contract for the 42401 SEO-only pilot.
define('ABSPATH',__DIR__);
class WP_Error {public function __construct($a='error',$b=''){}}
function add_action($a,$b){return true;}
function is_wp_error($v){return $v instanceof WP_Error;}
$options=array();$meta=array();$post=null;$writeCalls=0;$failMetaOnce=false;
function get_post($id){global $post;return $id===42401?$post:null;}
function get_post_meta($id,$key,$single=true){global $meta;return $meta[$key]??'';}
function metadata_exists($type,$id,$key){global $meta;return array_key_exists($key,$meta);}
function update_post_meta($id,$key,$value){global $meta,$writeCalls,$failMetaOnce;
    $writeCalls++;if($failMetaOnce&&$key==='_yoast_wpseo_metadesc'){$failMetaOnce=false;return false;}
    $old=$meta[$key]??null;
    if($old===$value)return false;
    $meta[$key]=$value;return true;
}
function delete_post_meta($id,$key){global $meta;unset($meta[$key]);return true;}
function add_option($key,$value,$deprecated='',$autoload='yes'){global $options;
    if(array_key_exists($key,$options))return false;$options[$key]=$value;return true;
}
function get_option($key,$default=false){global $options;return $options[$key]??$default;}
function need($cond,$why){if(!$cond)throw new RuntimeException($why);}
require dirname(__DIR__).'/seo-snippet-pilot.php';
$post=(object)array('ID'=>42401,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>NRY_Seo_Snippet_Pilot::SLUG,
 'post_modified_gmt'=>NRY_Seo_Snippet_Pilot::MODIFIED_GMT,
 'post_content'=>'<h1>Current full article</h1>'.str_repeat('O',3300)
 .'<a href="https://tkqlhce.com/demo">legacy affiliate link</a>');
$body=$post->post_content;
$meta=array('_yoast_wpseo_metadesc'=>NRY_Seo_Snippet_Pilot::ORIGINAL_META);
need(NRY_Seo_Snippet_Pilot::inspect()['status']==='ready','initial readiness');
$done=NRY_Seo_Snippet_Pilot::execute();
need($done['status']==='applied_and_verified'&&$done['writes']===1,'apply failure');
need($post->post_content===$body,'body was changed');
need($post->post_modified_gmt===NRY_Seo_Snippet_Pilot::MODIFIED_GMT,'post date was changed');
need($meta[NRY_Seo_Snippet_Pilot::TITLE_KEY]===NRY_Seo_Snippet_Pilot::NEW_TITLE,'Yoast title mismatch');
need($meta[NRY_Seo_Snippet_Pilot::META_KEY]===NRY_Seo_Snippet_Pilot::NEW_META,'Yoast desc mismatch');
need(count($options)===1,'missing backup');
$backup=reset($options);
need($backup['source_sha']===hash('sha256',$body),'backup does not preserve source SHA');
need($backup['had_title']===false&&$backup['had_meta']===true,'backup presence incorrect');
$prev=$writeCalls;
$repeat=NRY_Seo_Snippet_Pilot::execute();
need($repeat['writes']===0&&$writeCalls===$prev,'idempotence mutated metadata');
$meta=array('_yoast_wpseo_metadesc'=>NRY_Seo_Snippet_Pilot::ORIGINAL_META);
$post->post_modified_gmt='2026-10-24 01:00:00';
need(NRY_Seo_Snippet_Pilot::execute()['status']==='blocked_no_writes','date drift not blocked');
$post->post_modified_gmt=NRY_Seo_Snippet_Pilot::MODIFIED_GMT;
$meta[NRY_Seo_Snippet_Pilot::META_KEY]='Unexpected non-matching previous text';
need(NRY_Seo_Snippet_Pilot::execute()['status']==='blocked_no_writes','old meta drift not blocked');
$meta=array('_yoast_wpseo_metadesc'=>NRY_Seo_Snippet_Pilot::ORIGINAL_META);
$post->post_content.='X';
need(NRY_Seo_Snippet_Pilot::execute()['status']==='backup_conflict_no_writes','source drift backup collision not blocked');
$post->post_content=$body;
$failMetaOnce=true;
$rolled=NRY_Seo_Snippet_Pilot::execute();
need($rolled['status']==='rolled_back_verified','partial metadata failure not rolled back');
need($post->post_content===$body,'rollback touched body');
need(!isset($meta[NRY_Seo_Snippet_Pilot::TITLE_KEY]),'title key absent originally must restore absent');
need($meta[NRY_Seo_Snippet_Pilot::META_KEY]===NRY_Seo_Snippet_Pilot::ORIGINAL_META,'description not restored');
$post->post_name='bad-slug';
need(NRY_Seo_Snippet_Pilot::inspect()['status']==='blocked','wrong slug not rejected');
echo "PASS v0.8 SEO title/meta one-post backup, body preservation, drift, rollback, idempotency and strict identity\n";
