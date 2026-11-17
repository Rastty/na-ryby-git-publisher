<?php
// Offline, source-bound renderer unit test. No WordPress writes or external calls.
define('ABSPATH', __DIR__);
$GLOBALS['nry_boat'] = array(
    'id'=>119598,'singular'=>true,'admin'=>false,'preview'=>false,
    'site'=>'https://na-ryby.eu/','type'=>'post','status'=>'publish',
    'slug'=>'nejlepsi-nafukovaci-cluny-pro-rybare',
    'modified'=>'2026-05-15 12:17:30',
    'permalink'=>'https://na-ryby.eu/nejlepsi-nafukovaci-cluny-pro-rybare/',
);
function add_filter($name,$callback,$priority=10){$GLOBALS['boat_filter']=compact('name','callback','priority');}
function add_action($name,$callback){$GLOBALS['boat_action']=compact('name','callback');}
function get_queried_object_id(){return $GLOBALS['nry_boat']['id'];}
function is_singular($type){return $type==='post' && $GLOBALS['nry_boat']['singular'];}
function is_admin(){return $GLOBALS['nry_boat']['admin'];}
function is_preview(){return $GLOBALS['nry_boat']['preview'];}
function home_url($path='/'){return $GLOBALS['nry_boat']['site'];}
function get_permalink($id){return $GLOBALS['nry_boat']['permalink'];}
function get_post($id){$p=$GLOBALS['nry_boat'];return (object)array(
    'ID'=>$id,'post_type'=>$p['type'],'post_status'=>$p['status'],
    'post_name'=>$p['slug'],'post_modified_gmt'=>$p['modified']);}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_script($handle,$src,$deps,$version,$footer){$GLOBALS['boat_script']=compact('handle','src','deps','version','footer');}
function verify($truth,$message){if(!$truth)throw new RuntimeException($message);echo "PASS $message\n";}

require __DIR__.'/../boat-load-check-119598-v1.php';
$c='NRY_Boat_Load_Check_119598_V1';
verify(($GLOBALS['boat_filter']['name']??'')==='the_content','frontend content filter registered');
verify(($GLOBALS['boat_action']['name']??'')==='wp_enqueue_scripts','frontend asset action registered');
$html='<div><h1>Nafukovací čluny pro rybáře</h1>'.$c::ANCHOR.'<p>Rychlý orientační výpočet zátěže.</p>';
for($i=0;$i<13;$i++)$html.='<a href="https://www.tkqlhce.com/click-100927011-12347575?url=example-'.$i.'">Nabídka</a>';
$html.='<a href="https://na-ryby.eu/poradce-vybavy/">Poradce</a>';
$html.=str_repeat('<p>Praktické podklady pro výběr nafukovacího člunu a vybavení.</p>',400).'</div>';
$next=$c::render($html);
verify($next!==$html && substr_count($next,$c::MARKER)===1,'one load check inserted next to original heading');
verify(strpos($next,$c::MARKER)>strpos($next,$c::ANCHOR),'section positioned after load heading');
verify(substr_count($next,$c::CJ)===13 && substr_count($next,'<a ')===14,'all 14 original links preserved without extra merchant targets');
preg_match_all('/<a\s+href="([^"]+)"/', $html, $beforeLinks);
preg_match_all('/<a\s+href="([^"]+)"/', $next, $afterLinks);
verify($beforeLinks[1]===$afterLinks[1],'exact ordered legacy href bytes remain unchanged');
verify(strpos($next,'form data-nry-boat-load-form="v1"')!==false,'read-only browser form present');
verify(strpos($next,'maximální počet osob')!==false,'separate persons safety condition is explicit');
verify($c::render($next)===$next,'idempotent rendering');
$c::enqueue();
verify(($GLOBALS['boat_script']['src']??'')===plugins_url('assets/nry-boat-load-v1.js',__FILE__) && ($GLOBALS['boat_script']['version']??'')==='1.0.0','versioned first-party asset');
foreach(array('id'=>21,'singular'=>false,'admin'=>true,'preview'=>true,'site'=>'https://other.invalid/',
    'type'=>'page','status'=>'draft','slug'=>'changed','modified'=>'2026-11-17 00:00:00',
    'permalink'=>'https://na-ryby.eu/changed/') as $key=>$value){
    $original=$GLOBALS['nry_boat'][$key];$GLOBALS['nry_boat'][$key]=$value;
    verify($c::render($html)===$html,'fail-closed identity '.$key);
    $GLOBALS['nry_boat'][$key]=$original;
}
foreach(array($c::ANCHOR,$c::CJ,'Rychlý orientační výpočet zátěže') as $guard){
    $other=str_replace($guard,'changed by editor',$html);
    verify($c::render($other)===$other,'fail-closed source drift '.substr(md5($guard),0,6));
}
echo "PASS zero stored-post writes and zero affiliate rewrites\n";
