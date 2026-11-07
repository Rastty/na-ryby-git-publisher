<?php
/** Offline one-field SEO edit / rollback regression; no real WordPress writes. */
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=array();$GLOBALS['meta']=array();$GLOBALS['options']=array();
$GLOBALS['writes']=0;$GLOBALS['fail_first_update']=false;
$GLOBALS['original']=str_repeat('O',226);
$GLOBALS['post']=(object)array(
 'ID'=>36702,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>'pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu',
 'post_modified_gmt'=>'2026-11-07 11:23:04',
 'post_content'=>str_repeat('U',12938)
);
function add_action($name,$cb,$priority=10){$GLOBALS['hooks'][]=array($name,$cb,$priority);}
function home_url($s='/'){return 'https://na-ryby.eu/';}
function get_post($id){return $id===36702?$GLOBALS['post']:null;}
function get_permalink($id){return 'https://na-ryby.eu/pipaky-na-ryby-pro-profesionaly-prehled-nejlepsich-znacek-a-modelu/';}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function metadata_exists($type,$id,$key){return $type==='post' && array_key_exists($key,$GLOBALS['meta'][$id]??array());}
function get_option($k,$default=null){return $GLOBALS['options'][$k]??$default;}
function add_option($k,$v,$deprecated='',$autoload='no'){
 if(array_key_exists($k,$GLOBALS['options']))return false;
 $GLOBALS['options'][$k]=$v;return true;
}
function update_post_meta($id,$key,$value){
 if($id!==36702||$key!=='_yoast_wpseo_metadesc')throw new RuntimeException('unexpected WP meta field');
 if($GLOBALS['fail_first_update'] && $value===NRY_Advanced_Pipaky_Description_TestDouble::NEW_META){
   $GLOBALS['fail_first_update']=false;return false;
 }
 $GLOBALS['writes']++;$GLOBALS['meta'][$id][$key]=$value;return 1;
}
function must($x,$why){if(!$x)throw new RuntimeException($why);}
$path=dirname(__DIR__).'/seo-pipaky-advanced-description-36702-v1.php';
$src=file_get_contents($path);
must(strpos($src,"SOURCE_SHA = 'b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735'")!==false,
 'production exact source SHA unchanged');
must(strpos($src,"ORIGINAL_META_HASH = 'd76fb27cc3756e747d995bd3ac990a1eb9d3ce39214a1cd8bfc2d81124c79f0c'")!==false,
 'original actual WordPress meta fingerprint unchanged');
must(strpos($src,"SOURCE_BYTES = 12938")!==false
 && strpos($src,"MODIFIED_GMT = '2026-11-07 11:23:04'")!==false,
 'exact content length and modified date unchanged');
must(strpos($src,'wp_update_post(')===false&&strpos($src,'wp_insert_post(')===false
 &&strpos($src,'delete_post_meta(')===false,'one field only, never body or title');
must(strpos($src,'update_post_meta(self::ID, self::TITLE_KEY')===false,
 'title is never modified');
must(strpos($src,'update_post_meta(self::ID, self::META_KEY')!==false,
 'only existing meta description can be changed');

// Do not weaken shipped production constants. Isolate a test-only
// in-memory copy with fixture SHA and original-meta digest.
$test=str_replace('<?php','',$src);
$test=str_replace('NRY_Advanced_Pipaky_Description_V1',
                   'NRY_Advanced_Pipaky_Description_TestDouble',$test);
$test=str_replace('b862ecbd7abc3a87864df1eb6e2c6ff1c4a2621216d941acbfab646bff71d735',
                  hash('sha256',$GLOBALS['post']->post_content),$test);
$test=str_replace('d76fb27cc3756e747d995bd3ac990a1eb9d3ce39214a1cd8bfc2d81124c79f0c',
                  hash('sha256',$GLOBALS['original']),$test);
eval($test);
$c='NRY_Advanced_Pipaky_Description_TestDouble';
must(count($GLOBALS['hooks'])===1 &&
 $GLOBALS['hooks'][0]===array('wp_loaded',array($c,'maybe_execute'),38),
 'single bounded hook');
must(strlen($c::NEW_META)>115&&strlen($c::NEW_META)<185
 &&strpos($c::NEW_META,'párování')!==false
 &&strpos($c::NEW_META,'kompatibilitu')!==false,
 'real helpful SEO description scope');
$before=$GLOBALS['post']->post_content;
$GLOBALS['meta'][36702]=array($c::META_KEY=>$GLOBALS['original']);
must($c::inspect()['status']==='ready','source + original metadata ready');
$GLOBALS['post']->post_status='draft';
must($c::execute()['status']==='blocked_no_writes' && $GLOBALS['writes']===0,
 'draft never written');
$GLOBALS['post']->post_status='publish';
$GLOBALS['post']->post_modified_gmt='2026-11-07 12:00:00';
must($c::execute()['status']==='blocked_no_writes','source date drift hard stop');
$GLOBALS['post']->post_modified_gmt=$c::MODIFIED_GMT;
$GLOBALS['meta'][36702][$c::TITLE_KEY]='custom author title';
must($c::execute()['status']==='blocked_no_writes','title drift hard stop');
unset($GLOBALS['meta'][36702][$c::TITLE_KEY]);
$GLOBALS['meta'][36702][$c::META_KEY]='unexpected editor change';
must($c::execute()['status']==='blocked_no_writes','meta change hard stop');
$GLOBALS['meta'][36702][$c::META_KEY]=$GLOBALS['original'];
$GLOBALS['fail_first_update']=true;
$failed=$c::execute();
must($failed['status']==='rolled_back_verified'
 &&get_post_meta(36702,$c::META_KEY,true)===$GLOBALS['original'],
 'single meta failure restored from backup');
must($GLOBALS['post']->post_content===$before
 &&!metadata_exists('post',36702,$c::TITLE_KEY),'no HTML/title changes in rollback');
// Backup exists immutably; simulate distinct successful invocation after
// restoring the exact previous state. A previous failed attempt cannot
// be approved with mismatched backup.
$success=$c::execute();
must($success['status']==='applied_and_verified' && $success['writes']===1,
 'one Yoast meta field updated and verified');
must(get_post_meta(36702,$c::META_KEY,true)===$c::NEW_META
 &&!metadata_exists('post',36702,$c::TITLE_KEY)
 &&$GLOBALS['post']->post_content===$before,'only description changed');
$snapshot=get_option($c::BACKUP);
must(is_array($snapshot)
 &&$snapshot['original_meta']===$GLOBALS['original']
 &&$snapshot['source_sha256']===hash('sha256',$before),'immutable original backup');
$writes=$GLOBALS['writes'];
must($c::execute()['status']==='already_applied_no_write'
 &&$GLOBALS['writes']===$writes,'idempotent repeat');
$c::maybe_execute();
must(get_option($c::RECEIPT)['status']==='already_applied_no_write'
 &&$GLOBALS['writes']===$writes,'hook receipt no duplicate write');
echo "PASS: 36702 source-locked description-only edit, drift guards, rollback, 1 write and idempotency\n";
