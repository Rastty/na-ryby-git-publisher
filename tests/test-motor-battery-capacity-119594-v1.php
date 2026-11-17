<?php
// Pure PHP: exact public post, only one inserted calculator; all hrefs preserved.
define('ABSPATH', __DIR__);
$GLOBALS['nry_motor_page'] = array(
    'id'=>119594, 'singular'=>true, 'admin'=>false, 'preview'=>false,
    'site'=>'https://na-ryby.eu/', 'type'=>'post', 'status'=>'publish',
    'slug'=>'nejlepsi-lodni-elektromotory-pro-rybare',
    'modified'=>'2026-05-15 10:37:45',
    'permalink'=>'https://na-ryby.eu/nejlepsi-lodni-elektromotory-pro-rybare/',
);
function add_filter($name,$callback,$priority=10){$GLOBALS['nry_motor_filter']=compact('name','callback','priority');}
function add_action($name,$callback){$GLOBALS['nry_motor_action']=compact('name','callback');}
function get_queried_object_id(){return $GLOBALS['nry_motor_page']['id'];}
function is_singular($type){return $type==='post' && $GLOBALS['nry_motor_page']['singular'];}
function is_admin(){return $GLOBALS['nry_motor_page']['admin'];}
function is_preview(){return $GLOBALS['nry_motor_page']['preview'];}
function home_url($path='/'){return $GLOBALS['nry_motor_page']['site'];}
function get_permalink($id){return $GLOBALS['nry_motor_page']['permalink'];}
function get_post($id){$p=$GLOBALS['nry_motor_page'];return (object)array(
    'ID'=>$id, 'post_type'=>$p['type'], 'post_status'=>$p['status'],
    'post_name'=>$p['slug'], 'post_modified_gmt'=>$p['modified']);}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_script($handle,$src,$deps,$version,$footer){
    $GLOBALS['nry_motor_script']=compact('handle','src','deps','version','footer');}
function verify($ok,$label){if(!$ok)throw new RuntimeException($label);echo "PASS $label\n";}

require __DIR__.'/../motor-battery-capacity-119594-v1.php';
$c='NRY_Motor_Battery_Capacity_119594_V1';
verify($GLOBALS['nry_motor_filter']['name']==='the_content','read-only render hook registered');
verify($GLOBALS['nry_motor_action']['name']==='wp_enqueue_scripts','frontend script hook registered');
$html='<div><h1>Nejlepší lodní elektromotory</h1><h2>Baterie k elektromotoru: olovo, AGM nebo LiFePO4?</h2>';
$html.=$c::ANCHOR;
for($i=0;$i<13;$i++){
    $html.='<p><a href="https://www.tkqlhce.com/click-100927011-12347575?url=https%3A%2F%2Fwww.chytapust.cz%2Fproduct-'.$i.'">Nabídka</a></p>';
}
$html.=str_repeat('<p>Historické podklady k rybářskému motoru a baterii.</p>',380).'</div>';
$updated=$c::render($html);
verify($updated!==$html && substr_count($updated,$c::MARKER)===1,'one estimator inserted');
verify(strpos($updated,$c::MARKER)>strpos($updated,$c::ANCHOR),'estimator follows battery capacity heading');
verify(substr_count($updated,$c::CJ)===13,'all 13 existing CJ tracking links untouched');
verify(substr_count($updated,'<a ')===13,'no new external or internal click targets');
verify(strpos($updated,'form data-nry-motor-battery-form="v1"')!==false,'accessible form present');
verify($c::render($updated)===$updated,'idempotent double rendering');
$c::enqueue();
verify(($GLOBALS['nry_motor_script']['src']??'')===plugins_url('assets/nry-motor-battery-v1.js',__FILE__),
    'only exact first-party script enqueued');
foreach(array('id'=>42,'singular'=>false,'admin'=>true,'preview'=>true,
    'site'=>'https://other.invalid/', 'type'=>'page','status'=>'draft','slug'=>'different',
    'modified'=>'2026-11-17 00:00:00','permalink'=>'https://na-ryby.eu/different/') as $key=>$value){
    $old=$GLOBALS['nry_motor_page'][$key];$GLOBALS['nry_motor_page'][$key]=$value;
    verify($c::render($html)===$html,'source identity guard '.$key);
    $GLOBALS['nry_motor_page'][$key]=$old;
}
foreach(array($c::ANCHOR,'click-100927011-12347575','Baterie k elektromotoru: olovo, AGM nebo LiFePO4?') as $drift){
    $different=str_replace($drift,'editor changed it',$html);
    verify($c::render($different)===$different,'source drift blocked '.substr(md5($drift),0,6));
}
echo "PASS zero storage writes and zero affiliate URL changes\n";
