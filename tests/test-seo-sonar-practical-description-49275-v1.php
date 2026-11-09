<?php
/** Offline fixed-id SEO metadata contract; no credentials or live WP writes. */
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=array();
$GLOBALS['options']=array();
$GLOBALS['meta']=array('_yoast_wpseo_metadesc'=>null);
$GLOBALS['write_calls']=0;$GLOBALS['fail_next']=false;
$GLOBALS['fixture']=str_repeat('S',12027);
$GLOBALS['wp']=(object)array(
 'ID'=>49275,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>'jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun',
 'post_modified_gmt'=>'2026-10-16 11:38:12',
 'post_content'=>$GLOBALS['fixture']
);
function add_action($hook,$callback,$priority=10){$GLOBALS['hooks'][]=array($hook,$callback,$priority);}
function home_url($path){return 'https://na-ryby.eu'.$path;}
function get_permalink($id){return $id===49275?'https://na-ryby.eu/jak-efektivne-pouzivat-echolot-a-sonar-na-lod-nebo-clun/':'https://na-ryby.eu/other/';}
function get_post($id){return $id===49275?$GLOBALS['wp']:null;}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$key]??'';}
function metadata_exists($type,$id,$key){return array_key_exists($key,$GLOBALS['meta']);}
function add_option($key,$value,$deprecated='',$autoload='yes'){
 if(array_key_exists($key,$GLOBALS['options']))return false;
 $GLOBALS['options'][$key]=$value;return true;
}
function get_option($key,$default=false){return $GLOBALS['options'][$key]??$default;}
function update_post_meta($id,$key,$value){
 $GLOBALS['write_calls']++;
 if($GLOBALS['fail_next']){$GLOBALS['fail_next']=false;return false;}
 if(!isset($GLOBALS['meta'][$key]))return false;
 $old=$GLOBALS['meta'][$key];$GLOBALS['meta'][$key]=$value;return $old!==$value;
}
function must($v,$why){if(!$v){fwrite(STDERR,'FAIL: '.$why."\n");exit(1);}}
$source=file_get_contents(dirname(__DIR__).'/seo-sonar-practical-description-49275-v1.php');
must(strpos($source,"const SOURCE_SHA = '4c57249b88928376dc24a41885b5e9397069e5128d1c462e0fc4b50f1c560330'")!==false,
 'source file must pin real authenticated 12027-byte article digest');
must(!preg_match('/\b(?:wp_update_post|wp_insert_post|wp_delete_post|delete_post_meta)\s*\(/',$source),
 'must never touch WordPress content, title, URL or image');
$realSha='4c57249b88928376dc24a41885b5e9397069e5128d1c462e0fc4b50f1c560330';
$mockSha=hash('sha256',$GLOBALS['fixture']);
// A runtime-only code fixture changes ONLY the source digest to match the
// fake 12,027-byte post; the checked-in real digest remains untouched.
$contract=str_replace("const SOURCE_SHA = '".$realSha."'",
 "const SOURCE_SHA = '".$mockSha."'",$source);
must($contract!==$source,'replace the exact digest in isolated test only');
eval(substr($contract,5));
must(count($GLOBALS['hooks'])===1,'one WordPress load hook');
must($GLOBALS['hooks'][0][0]==='wp_loaded'&&$GLOBALS['hooks'][0][2]===39,'single exact hook');
$C='NRY_Sonar_Practical_Serp_49275_V1';
$GLOBALS['meta']['_yoast_wpseo_metadesc']=$C::ORIGINAL_META;
must($C::inspect()['status']==='ready','exact source and generic Yoast meta ready');
$oldBody=$GLOBALS['wp']->post_content;
$oldTitle=$GLOBALS['wp']->post_name;
$done=$C::execute();
must($done['status']==='applied_and_verified'&&$done['writes']===1,'one-field live-style apply succeeded');
must($GLOBALS['meta']['_yoast_wpseo_metadesc']===$C::NEW_META,'new focused meta verified');
must($GLOBALS['wp']->post_content===$oldBody&&$GLOBALS['wp']->post_name===$oldTitle,
 'body/links/media/source URL untouched');
must(!array_key_exists('_yoast_wpseo_title',$GLOBALS['meta']),'Yoast title never edited');
must($GLOBALS['options'][$C::BACKUP]['original_meta']===$C::ORIGINAL_META,'immutable backup');
must($GLOBALS['options'][$C::BACKUP]['source_sha256']===$mockSha,'backup source pinned');
$before=$GLOBALS['write_calls'];
must($C::execute()['status']==='already_applied_no_write','second apply is idempotent');
must($before===$GLOBALS['write_calls'],'no repeat metadata writes');
$GLOBALS['meta']['_yoast_wpseo_metadesc']=$C::ORIGINAL_META;
$GLOBALS['wp']->post_modified_gmt='2026-10-17 11:38:12';
must($C::execute()['status']==='blocked_no_writes','different modified GMT blocks write');
$GLOBALS['wp']->post_modified_gmt=$C::MODIFIED_GMT;
$GLOBALS['wp']->post_content.='X';
must($C::inspect()['status']==='blocked','source content drift blocks write');
$GLOBALS['wp']->post_content=$oldBody;
$GLOBALS['meta']['_yoast_wpseo_metadesc']='Unexpected editorial manual update';
must($C::execute()['status']==='blocked_no_writes','custom editorial Yoast meta protected');
$GLOBALS['meta']['_yoast_wpseo_metadesc']=$C::ORIGINAL_META;
$GLOBALS['meta']['_yoast_wpseo_title']='Custom editorial title';
must($C::execute()['status']==='blocked_no_writes','custom Yoast title protected');
unset($GLOBALS['meta']['_yoast_wpseo_title']);
$GLOBALS['fail_next']=true;
$rolled=$C::execute();
must($rolled['status']==='rolled_back_verified'&&$rolled['writes']===0,'failed WP update must revert');
must($GLOBALS['meta']['_yoast_wpseo_metadesc']===$C::ORIGINAL_META,'rollback exactly original');
must($GLOBALS['wp']->post_content===$oldBody,'rollback content intact');
$GLOBALS['meta']['_yoast_wpseo_metadesc']=$C::NEW_META;
$GLOBALS['options']=array();
$GLOBALS['write_calls']=0;
$C::maybe_execute();
must($GLOBALS['write_calls']===0,'already applied must not rewrite on boot');
must($GLOBALS['options'][$C::RECEIPT]['status']==='already_applied_no_write','idempotent receipt');
must(count($GLOBALS['options'])===2,'one lock and one receipt');
echo "PASS: #49275 one-field source SHA / GMT / Yoast backup + original drift / rollback / idempotence / URL preservation\n";
