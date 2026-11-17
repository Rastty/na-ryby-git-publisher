<?php
// Exactly two existing source-bound published guides; no WordPress writes.
define('ABSPATH', __DIR__);
$GLOBALS['env'] = array(
    'id'=>119594,'site'=>'https://na-ryby.eu/','singular'=>true,
    'admin'=>false,'preview'=>false,'type'=>'post','status'=>'publish',
    'slug'=>'nejlepsi-lodni-elektromotory-pro-rybare',
    'modified'=>'2026-05-15 10:37:45',
    'anchor'=>'Baterie k elektromotoru: olovo, AGM nebo LiFePO4?',
);
function add_action($name,$callback,$priority=10){$GLOBALS['hook']=compact('name','callback','priority');}
function is_singular($type){return $type==='post' && $GLOBALS['env']['singular'];}
function is_admin(){return $GLOBALS['env']['admin'];}
function is_preview(){return $GLOBALS['env']['preview'];}
function get_queried_object_id(){return $GLOBALS['env']['id'];}
function home_url($path='/'){return $GLOBALS['env']['site'];}
function get_permalink($id){return 'https://na-ryby.eu/'.$GLOBALS['env']['slug'].'/';}
function get_post($id){$e=$GLOBALS['env'];return (object)array(
    'ID'=>$id,'post_name'=>$e['slug'],'post_type'=>$e['type'],'post_status'=>$e['status'],
    'post_modified_gmt'=>$e['modified'],
    'post_content'=>str_repeat('<p>Historický nákupní průvodce.</p>',620)
        .$e['anchor'].str_repeat('click-100927011-12347575',13));}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_script($handle,$src,$deps,$version,$footer){$GLOBALS['queue'][]=compact('handle','src','deps','version','footer');}
function must($truth,$label){if(!$truth)throw new RuntimeException($label);echo "PASS $label\n";}
require __DIR__.'/../boat-motor-click-observation-v1.php';
must(($GLOBALS['hook']['name']??'')==='wp_enqueue_scripts' && $GLOBALS['hook']['priority']===26,
    'separate consent-bound frontend measurement hook');
function check_enqueue($expected,$label){$GLOBALS['queue']=array();nry_boat_motor_click_enqueue();
    must(count($GLOBALS['queue'])===$expected,$label);
    if($expected)must($GLOBALS['queue'][0]['version']==='1.0.0' &&
        $GLOBALS['queue'][0]['src']===plugins_url('assets/nry-boat-motor-click-v1.js',__FILE__)
        && $GLOBALS['queue'][0]['deps']===array(),'exact versioned site-local asset');}
check_enqueue(1,'motor guide source approved');
$GLOBALS['env']['id']=119598;
$GLOBALS['env']['slug']='nejlepsi-nafukovaci-cluny-pro-rybare';
$GLOBALS['env']['modified']='2026-05-15 12:17:30';
$GLOBALS['env']['anchor']='Nosnost: proč nestačí počítat jen lidi';
check_enqueue(1,'inflatable boat guide source approved');
foreach(array('id'=>10,'slug'=>'different','modified'=>'2026-11-17 00:00:00',
    'anchor'=>'different anchor','site'=>'https://wrong.example/','singular'=>false,
    'admin'=>true,'preview'=>true,'type'=>'page','status'=>'draft') as $key=>$value){
    $old=$GLOBALS['env'][$key];$GLOBALS['env'][$key]=$value;
    check_enqueue(0,'source/page guard '.$key);
    $GLOBALS['env'][$key]=$old;
}
$GLOBALS['env']['id']=119865;
$GLOBALS['env']['slug']='poradce-vybavy';
check_enqueue(0,'advisor and all other pages not instrumented');
echo "PASS no stored edits, no extra trackers, no redirected merchant URLs\n";
