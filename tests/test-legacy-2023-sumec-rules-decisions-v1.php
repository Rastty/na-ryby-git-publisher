<?php
/** Two historically indexed source-bound redirects to current RIS, no DB writes. */
define('ABSPATH',__DIR__);
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['fix']=array('id'=>220001,'slug'=>'','month'=>'2023-06',
 'status'=>'publish','type'=>'post','admin'=>false,'preview'=>false,
 'feed'=>false,'custom'=>false,'main'=>true,'loop'=>true,
 'host'=>'https://na-ryby.eu','raw'=>'');
$GLOBALS['filters']=array();$GLOBALS['actions']=array();$GLOBALS['styles']=array();
function add_filter($n,$cb,$p=10){$GLOBALS['filters'][]=array($n,$p);}
function add_action($n,$cb,$p=10){$GLOBALS['actions'][]=array($n,$p);}
function is_single($slug){return $slug===$GLOBALS['fix']['slug'];}
function is_admin(){return $GLOBALS['fix']['admin'];}
function is_preview(){return $GLOBALS['fix']['preview'];}
function is_feed(){return $GLOBALS['fix']['feed'];}
function is_customize_preview(){return $GLOBALS['fix']['custom'];}
function is_main_query(){return $GLOBALS['fix']['main'];}
function in_the_loop(){return $GLOBALS['fix']['loop'];}
function home_url($p='/'){return $GLOBALS['fix']['host'];}
function get_queried_object_id(){return $GLOBALS['fix']['id'];}
function get_permalink($id){return $GLOBALS['fix']['host'].'/'.$GLOBALS['fix']['slug'].'/';}
function get_post($id){$f=$GLOBALS['fix'];
 return (object)array('ID'=>$f['id'],'post_name'=>$f['slug'],
  'post_type'=>$f['type'],'post_status'=>$f['status'],
  'post_date_gmt'=>$f['month'].'-15 08:00:00','post_content'=>$f['raw']);
}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_style($name,$url,$deps,$ver){$GLOBALS['styles'][]=array($name,$url,$ver);}
function check($ok,$msg){if(!$ok){fwrite(STDERR,"FAIL ".$msg."\n");exit(1);}}
require dirname(__DIR__).'/legacy-2023-sumec-rules-decisions-v1.php';
$c='NRY_2023_Rules_Sumec_Decisions_V1';
check($GLOBALS['filters']===array(array('the_content',96)),'one render hook');
check(count($GLOBALS['actions'])===1,'one style hook');
check(count($c::ROUTES)===2,'exact two source-guarded posts');
foreach($c::ROUTES as $slug=>$row) {
 $f=&$GLOBALS['fix'];$f['slug']=$slug;$f['id']++;
 $original='<h2>'.implode(' a ',$row['source']).'</h2>'.
   str_repeat('<p>Historický a dnes neověřený text o podmínkách lovu a výpravách.</p>',38).
   '<a href="https://www.chytapust.cz/legacy?publisher=8287231">Historický merchant odkaz</a>';
 $f['raw']=$original;
 check(strlen($original)>1300,'fixture source long enough');
 $section=$c::block($row['role']);
 check(substr_count($section,$c::MARKER)===1,'one marker');
 check(substr_count($section,'href="'.$c::RULES.'"')===1,'official rules link');
 check(strpos($section,'rel="sponsored"')===false,'no invented sponsored links');
 $html=$c::render($original);
 check($html!==$original,'positive source-bound render '.$slug);
 check(strpos($html,$section)===0,'decision-first article content');
 check(substr($html,strlen($section))===$original,'every original byte preserved');
 check($c::render($html)===$html,'idempotence');
 check(strpos($html,'publisher=8287231')!==false,'original merchant untouched');
 $c::enqueue();
 check(count($GLOBALS['styles'])===($row['role']==='sumec'?1:2),'one shared style each');
 foreach(array('id','slug','month','status','type','admin','preview','feed',
               'custom','main','loop','host','raw') as $key) {
   $prev=$f[$key];
   if ($key==='id') $f[$key]=0;
   elseif($key==='slug')$f[$key]='another-post';
   elseif($key==='month')$f[$key]='2025-05';
   elseif($key==='status')$f[$key]='draft';
   elseif($key==='type')$f[$key]='page';
   elseif($key==='host')$f[$key]='https://other.example';
   elseif($key==='raw')$f[$key]='Different text '.str_repeat('x',2500);
   elseif(in_array($key,array('admin','preview','feed','custom'),true))$f[$key]=true;
   else $f[$key]=false;
   // Unknown numeric ID is disallowed when zero; old external WordPress IDs
   // are not guessed or used as publication authority.
   check($c::render($original)===$original,'fail closed '.$slug.' '.$key);
   $f[$key]=$prev;
 }
 foreach($row['source'] as $word) {
   $changed=str_replace($word,'EDITOR_CHANGED_SOURCE',$original);
   check($c::render($changed)===$changed,'rendered content change rejected');
   $f['raw']=$changed;
   check($c::render($original)===$original,'stored WP source drift rejected');
   $f['raw']=$original;
 }
 $_SERVER['REQUEST_METHOD']='POST';
 check($c::render($original)===$original,'POST never rewrites');
 $_SERVER['REQUEST_METHOD']='GET';
}
check(count($GLOBALS['styles'])===2,'one style on each exact post');
echo "PASS NRY_LEGACY_2023_SUMEC_RULES_DECISIONS_SOURCE_LOCKED_2\n";
