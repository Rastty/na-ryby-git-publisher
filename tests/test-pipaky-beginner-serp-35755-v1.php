<?php
// Isolated fixed-scope Yoast update: source-mismatch MUST never write.
// Positive release is proven on server via live source hash and public readback.
define('ABSPATH', __DIR__);
$GLOBALS['hooks']=array();$GLOBALS['meta']=array();$GLOBALS['options']=array();
$GLOBALS['writes']=0;$GLOBALS['post']=(object)array(
 'ID'=>35755,'post_type'=>'post','post_status'=>'publish',
 'post_name'=>'pipaky-na-ryby-prakticke-rady-pro-zacatecniky',
 'post_modified_gmt'=>'2026-10-23 06:35:07',
 'post_content'=>str_repeat('x',14984));
function add_action($hook,$cb,$priority=10) {$GLOBALS['hooks'][]=array($hook,$cb,$priority);}
function get_post($id){return $id===35755?$GLOBALS['post']:null;}
function get_permalink($id){return 'https://na-ryby.eu/pipaky-na-ryby-prakticke-rady-pro-zacatecniky/';}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_post_meta($id,$key,$single=true){return $GLOBALS['meta'][$id][$key]??'';}
function metadata_exists($type,$id,$key){return $type==='post'&&array_key_exists($key,$GLOBALS['meta'][$id]??array());}
function update_post_meta($id,$key,$value){$GLOBALS['writes']++;$GLOBALS['meta'][$id][$key]=$value;return 1;}
function delete_post_meta($id,$key){$GLOBALS['writes']++;unset($GLOBALS['meta'][$id][$key]);return true;}
function get_option($k,$default=null){return $GLOBALS['options'][$k]??$default;}
function add_option($k,$v,$unused='',$autoload='no'){
 if(array_key_exists($k,$GLOBALS['options']))return false;
 $GLOBALS['options'][$k]=$v;return true;
}
function must($flag,$message){if(!$flag){fwrite(STDERR,"FAIL: ".$message."\n");exit(1);}}
require dirname(__DIR__).'/seo-pipaky-beginner-35755-v1.php';
must(count($GLOBALS['hooks'])===1&&
  $GLOBALS['hooks'][0]===array('wp_loaded',
   array('NRY_Beginner_Pipaky_Serp_V1','maybe_execute'),36),
 'only one WordPress load hook');
$c='NRY_Beginner_Pipaky_Serp_V1';
must($c::ID===35755&&$c::SLUG==='pipaky-na-ryby-prakticke-rady-pro-zacatecniky',
 'single approved source');
must(hash('sha256','')===$c::ORIGINAL_TITLE_HASH,
 'raw Yoast title was absent, not blindly replaced');
must(hash('sha256',$c::ORIGINAL_META)===$c::ORIGINAL_META_HASH
  && strlen($c::ORIGINAL_META)===175,
 'old meta original from on-server SHA proof');
must(strpos($c::NEW_TITLE,'začátečníky')!==false
  && strpos($c::NEW_META,'falešný poplach')!==false,
 'clear search intent and real article content');
$src=file_get_contents(dirname(__DIR__).'/seo-pipaky-beginner-35755-v1.php');
must(strpos($src,'wp_update_post(')===false
  && strpos($src,'wp_insert_post(')===false
  && strpos($src,'post_content=')===false,
 'SEO release never edits WordPress body, slug or internal links');
$GLOBALS['meta'][35755]=array($c::META_KEY=>$c::ORIGINAL_META);
must($c::inspect()['status']==='blocked','source SHA mismatch hard stop');
$r=$c::execute();
must($r['status']==='blocked_no_writes'&&$r['writes']===0
  && $GLOBALS['writes']===0,'no fallback writes with arbitrary source');
$c::maybe_execute();
must($GLOBALS['writes']===0,'load hook cannot bypass source identity');
$receipt=get_option($c::RECEIPT);
must($receipt['status']==='blocked_no_writes','blocked receipt visible');
$originalLock=get_option($c::LOCK);
$c::maybe_execute();
must(get_option($c::LOCK)===$originalLock&&$GLOBALS['writes']===0,
 'second load never retries unsafe release');
// Private rollback is testable independently of content SHA. It must restore
// the precisely absent title and original existing meta.
$GLOBALS['meta'][35755][$c::TITLE_KEY]=$c::NEW_TITLE;
$GLOBALS['meta'][35755][$c::META_KEY]=$c::NEW_META;
$reflection=new ReflectionMethod($c,'rollback');
$reflection->setAccessible(true);
$ok=$reflection->invoke(null,array('had_title'=>false,'had_meta'=>true,
  'original_title'=>'','original_meta'=>$c::ORIGINAL_META));
must($ok&&$GLOBALS['writes']===2
  && !metadata_exists('post',35755,$c::TITLE_KEY)
  && get_post_meta(35755,$c::META_KEY,true)===$c::ORIGINAL_META,
  'rollback restores nonexistent title and full historical meta');
echo "PASS: exact source/meta hashes, no body changes, blocked writes and rollback\n";
