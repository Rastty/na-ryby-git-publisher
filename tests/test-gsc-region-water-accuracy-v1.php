<?php
/** Offline source identity + bytes-preserved 3-url editorial contract. */
define('ABSPATH',__DIR__);
$_SERVER['REQUEST_METHOD']='GET';
$GLOBALS['fix']=array(
 'id'=>56255,'slug'=>'rybareni-v-usteckem-kraji-oblibene-reviry-a-rybi-bohatstvi',
 'year_month'=>'2023-06','status'=>'publish','type'=>'post','admin'=>false,
 'preview'=>false,'feed'=>false,'custom'=>false,'main'=>true,'loop'=>true,
 'host'=>'https://na-ryby.eu','raw'=>''
);
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
function home_url($path='/'){return $GLOBALS['fix']['host'];}
function get_queried_object_id(){return $GLOBALS['fix']['id'];}
function get_permalink($id){return $GLOBALS['fix']['host'].'/'.$GLOBALS['fix']['slug'].'/';}
function get_post($id){$f=$GLOBALS['fix'];
 return (object)array('ID'=>$f['id'],'post_name'=>$f['slug'],
  'post_type'=>$f['type'],'post_status'=>$f['status'],
  'post_date_gmt'=>$f['year_month'].'-15 10:00:00','post_content'=>$f['raw']);
}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_style($name,$url,$deps,$ver){$GLOBALS['styles'][]=array($name,$url,$ver);}
function check($truth,$message){if(!$truth){fwrite(STDERR,"FAIL ".$message."\n");exit(1);}}
require dirname(__DIR__).'/gsc-region-water-accuracy-v1.php';
$c='NRY_GSC_Regional_Accuracy_V1';
check(count($GLOBALS['filters'])===1 && $GLOBALS['filters'][0]===array('the_content',95),'one scoped filter');
check(count($GLOBALS['actions'])===1,'scoped css action');
check(count($c::ROUTES)===3,'source-reviewed three roles');
$ids=array('usti'=>56255,'west'=>170111,'prague'=>170112);
foreach($c::ROUTES as $slug=>$route){
 $role=$route['role'];
 $f=&$GLOBALS['fix'];
 $f['id']=$ids[$role];$f['slug']=$slug;$f['year_month']=$route['year_month'];
 $original='<h2>'.$route['phrases'][0].'</h2>'.
    '<p>'.implode(' | ',$route['phrases']).'</p>'.
    str_repeat('<p>V původním článku jsou různá místa, rady a související odkazy.</p>',35).
    '<a href="https://www.chytapust.cz/original?utm_source=CJ&amp;id=8287231">Původní link</a>';
 $f['raw']=$original;
 check(strlen($original)>600,'long article');
 $block=$c::block($role);
 check(substr_count($block,$c::MARKER)===1,'one source proof marker '.$role);
 check(substr_count($block,'href="'.$c::RIS.'"')===1,'RIS official '.$role);
 check(substr_count($block,'href="'.$c::STAYS.'"')===1,'fishing stays first party '.$role);
 check(strpos($block,'rybí obsádky')!==false||strpos($block,'povolený lov')!==false||strpos($block,'povolenku')!==false,'no hard commercial or permit claims '.$role);
 check(strpos($block,'?utm_source=CJ')===false,'no affiliate sources in new block');
 $next=$c::render($original);
 check($next!==$original,'source route rendered '.$role);
 check(strpos($next,$block)===0,'source note ahead of original '.$role);
 check(substr($next,strlen($block))===$original,'original bytes preserved '.$role);
 check($c::render($next)===$next,'idempotence '.$role);
 check(substr_count($next,$c::MARKER)===1,'single section '.$role);
 check(strpos($next,'https://www.chytapust.cz/original?utm_source=CJ&amp;id=8287231')!==false,
   'merchant href unchanged '.$role);
 $c::enqueue();
 check(count($GLOBALS['styles'])===array_search($role,array('usti','west','prague'),true)+1,'style only on source-scoped pages');
 // Preserve rendering safety across request, role and editor protection drift.
 foreach(array('id','slug','year_month','status','type','admin','preview',
   'feed','custom','main','loop','host','raw') as $key){
   $was=$f[$key];
   switch($key){
     case 'id':$f[$key]=999999;break;
     case 'slug':$f[$key]='different-slug';break;
     case 'year_month':$f[$key]='2025-01';break;
     case 'status':$f[$key]='draft';break;
     case 'type':$f[$key]='page';break;
     case 'host':$f[$key]='https://evil.example';break;
     case 'raw':$f[$key]='Another editor source '.str_repeat('x',2000);break;
     case 'admin':case 'preview':case 'feed':case 'custom':$f[$key]=true;break;
     default:$f[$key]=false;
   }
   // Unknown numeric IDs are accepted only for the source-exact west/prague
   // slugs, but Usti is pinned to WP 56255. Other test drift must no-op.
   if($key!=='id'||$role==='usti')check($c::render($original)===$original,'fail closed '.$role.' '.$key);
   $f[$key]=$was;
 }
 $_SERVER['REQUEST_METHOD']='POST';
 check($c::render($original)===$original,'no POST writes '.$role);
 $_SERVER['REQUEST_METHOD']='GET';
 foreach($route['phrases'] as $phrase){
   $changed=str_replace($phrase,'Changed stored/editor text',$original);
   check($c::render($changed)===$changed,'source rendered content drift rejected '.$role);
 }
}
check(count($GLOBALS['styles'])===3,'three context-specific styles');
echo "NRY_GSC_REGION_THREE_SOURCE_GUARDS_OFFLINE_VERIFIED\n";
