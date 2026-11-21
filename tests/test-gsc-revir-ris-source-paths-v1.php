<?php
define('ABSPATH',__DIR__);
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['nry_fixture']=array('id'=>46986,'slug'=>'rybarske-reviry-kde-muzete-ulovit-candata-obecneho-v-cr',
  'modified'=>'2026-04-15 17:01:43','source'=>'<h1>Kde v ČR chytit candáta obecného: revíry, místa a praktické tipy</h1>',
  'status'=>'publish','post_type'=>'post','host'=>'https://na-ryby.eu',
  'main'=>true,'loop'=>true,'admin'=>false,'preview'=>false,'feed'=>false,'custom'=>false);
$GLOBALS['nry_filters']=array();$GLOBALS['nry_actions']=array();$GLOBALS['nry_styles']=array();
function add_filter($hook,$fn,$priority=10){$GLOBALS['nry_filters'][]=array($hook,$priority);}
function add_action($hook,$fn,$priority=10){$GLOBALS['nry_actions'][]=array($hook,$priority);}
function is_single($slug){return $slug===$GLOBALS['nry_fixture']['slug'];}
function is_admin(){return $GLOBALS['nry_fixture']['admin'];}
function is_preview(){return $GLOBALS['nry_fixture']['preview'];}
function is_feed(){return $GLOBALS['nry_fixture']['feed'];}
function is_customize_preview(){return $GLOBALS['nry_fixture']['custom'];}
function is_main_query(){return $GLOBALS['nry_fixture']['main'];}
function in_the_loop(){return $GLOBALS['nry_fixture']['loop'];}
function home_url($path='/'){return $GLOBALS['nry_fixture']['host'];}
function get_queried_object_id(){return $GLOBALS['nry_fixture']['id'];}
function get_permalink($id){$f=$GLOBALS['nry_fixture'];return $f['host'].'/'.$f['slug'].'/';}
function get_post($id){$f=$GLOBALS['nry_fixture'];
 return (object)array('ID'=>$f['id'],'post_name'=>$f['slug'],
   'post_modified_gmt'=>$f['modified'],'post_content'=>$f['source'].'<p>source</p>',
   'post_type'=>$f['post_type'],'post_status'=>$f['status']);
}
function wp_enqueue_style($id,$url,$deps,$version){$GLOBALS['nry_styles'][]=$url;}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function ok($yes,$message){if(!$yes){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require dirname(__DIR__).'/gsc-revir-ris-source-paths-v1.php';
$cls='NRY_GSC_Revir_Source_Paths_V1';
ok(count($GLOBALS['nry_filters'])===1 && $GLOBALS['nry_filters'][0]===array('the_content',94),'filter');
ok(count($GLOBALS['nry_actions'])===1 && $GLOBALS['nry_actions'][0]===array('wp_enqueue_scripts',25),'style hook');
ok(count($cls::ROUTES)===3,'exact three source-reviewed indexed URLs');
foreach(array(46986,17820,17825) as $id){
  $entry=$cls::ROUTES[$id];
  $GLOBALS['nry_fixture']['id']=$id;
  $GLOBALS['nry_fixture']['slug']=$entry['slug'];
  $GLOBALS['nry_fixture']['modified']=$entry['modified'];
  $GLOBALS['nry_fixture']['source']=$entry['source'];
  $block=$cls::block($id);
  ok(substr_count($block,$cls::MARKER)===1,'one marker in output');
  ok(substr_count($block,'href="'.$cls::RIS.'"')===1,'official RIS source once');
  ok(substr_count($block,'href="'.$cls::RULES.'"')===1,'official 2026-2027 rule source once');
  ok(strpos($block,'2026')!==false || $id===46986,'historical rules signposted');
  if($id===46986){
    ok(substr_count($block,'href="'.$cls::DYJE6.'"')===1,'dyje 6 owned path');
    ok(substr_count($block,'href="'.$cls::STAYS.'"')===1,'stays owned path');
    ok(substr_count($block,'href="'.$cls::SONAR.'"')===1,'sonar owned path');
    ok(substr_count($block,'<li>')===3,'three actionable decisions');
    $html=$cls::CANDAT_SOURCE.'<p>Úvod.</p>'.str_repeat('<p>Ověřený původní text a odkazy.</p>',90)
       .$cls::CANDAT_ANCHOR.'<p>Původní výklad.</p>'
       .'<a href="https://www.chytapust.cz/original?keep=1&amp;x=2">Původní proklik</a>';
  }elseif($id===17820){
    $html='&nbsp;<table><tr id="revir_37"><td>403</td><td>001</td><td>BOJOVSKÝ POTOK 1</td></tr>'
      .'<tr id="revir_38"><td>KOCÁBA 1</td></tr></table>'.str_repeat('<p>Archivní revíry.</p>',90);
  }else{
    $html=$cls::CENTRAL_SOURCE.'<p>Středočeské revíry.</p>'
      .'<h3>413 998 BOŽKOV</h3>'.str_repeat('<p>Archivní revíry.</p>',90);
  }
  ok(strlen($html)>=500,'fixture size');
  $new=$cls::render($html);
  ok($new!==$html,'renders correct live source for '.$id);
  ok(substr_count($new,$cls::MARKER)===1,'one output proof marker');
  ok(str_replace($block,'',$new)===$html,'byte preserving original body for '.$id);
  ok($cls::render($new)===$new,'idempotent');
  if($id===46986){
    ok(strpos($new,$block)<strpos($new,$cls::CANDAT_ANCHOR),'decision above original h2');
    ok(strpos($new,'https://www.chytapust.cz/original?keep=1&amp;x=2')!==false,'affiliate untouched');
  }else{
    ok(strpos($new,$block)===0,'historical archive warning before old 2018 text');
  }
  $cls::enqueue();
  ok(count($GLOBALS['nry_styles'])===array_search($id,array(46986,17820,17825),true)+1,'one stylesheet per eligible post');
  foreach(array('admin','preview','feed','custom','main','loop','modified','slug','status','post_type','source','host','id') as $key){
    $orig=$GLOBALS['nry_fixture'][$key];
    if(in_array($key,array('admin','preview','feed','custom'),true))$GLOBALS['nry_fixture'][$key]=true;
    elseif($key==='main'||$key==='loop')$GLOBALS['nry_fixture'][$key]=false;
    elseif($key==='modified')$GLOBALS['nry_fixture'][$key]='2026-11-21 10:10:10';
    elseif($key==='slug')$GLOBALS['nry_fixture'][$key]='different';
    elseif($key==='status')$GLOBALS['nry_fixture'][$key]='draft';
    elseif($key==='post_type')$GLOBALS['nry_fixture'][$key]='page';
    elseif($key==='source')$GLOBALS['nry_fixture'][$key]='<p>Different editorial source</p>';
    elseif($key==='host')$GLOBALS['nry_fixture'][$key]='https://evil.example';
    else $GLOBALS['nry_fixture'][$key]=111111;
    ok($cls::render($html)===$html,'fail-closed '.$id.':'.$key);
    $GLOBALS['nry_fixture'][$key]=$orig;
  }
  $_SERVER['REQUEST_METHOD']='POST';
  ok($cls::render($html)===$html,'POST denied');
  $_SERVER['REQUEST_METHOD']='GET';
  $broken=$id===46986?str_replace($cls::CANDAT_ANCHOR,'<h2>new editor heading</h2>',$html):
     ($id===17820?str_replace('BOJOVSKÝ POTOK 1','New water',$html):
       str_replace('413 998 BOŽKOV','Different water',$html));
  ok($cls::render($broken)===$broken,'body changed => no unsafe insert');
  ok($cls::render($html.$html)===$html.$html || $id!==46986,
    'duplicate candat anchor rejected');
}
echo "NRY_GSC_REVIR_RIS_THREE_SOURCE_GUARDS_OFFLINE_VERIFIED\n";
