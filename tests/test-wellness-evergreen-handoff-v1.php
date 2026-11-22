<?php
define('ABSPATH',true);
$GLOBALS['eligible']=false;
function add_filter($tag,$callback,$priority=10){}
function add_action($tag,$callback,$priority=10){}
function is_single($slug){return $GLOBALS['eligible'];}
function is_singular($type){return $type==='post';}
function get_queried_object_id(){return 12345;}
function get_post($id){return (object)array('ID'=>$id,'post_type'=>'post','post_status'=>'publish','post_name'=>'unrelated','post_content'=>'');}
function get_permalink($id){return 'https://na-ryby.eu/unrelated/';}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function is_admin(){return false;}
function is_preview(){return false;}
require __DIR__.'/../wellness-evergreen-handoff-v1.php';
function okay($yes,$label){if(!$yes){fwrite(STDERR,"FAIL ".$label."\n");exit(1);}}
$c='NRY_Wellness_Evergreen_Handoff_V1';
foreach ($c::ROUTES as $slug=>$set) {
    $src='<h1>'.htmlspecialchars($slug,ENT_QUOTES,'UTF-8').'</h1>'
        .'<p>'.$set['sourceAnchor'].'</p>'.str_repeat('<p>Stará faktická část, bez dalších zásahů.</p>',85)
        .'<p><a href="https://example.test/old">Původní archivní zdroj</a></p>';
    $render=$c::transform($src,$set);
    okay($render!==$src && substr_count($render,$c::MARKER)===1,'positive render '.$slug);
    okay($c::transform($render,$set)===$render,'idempotent '.$slug);
    $panel=$c::panel($set['role']);
    okay(str_replace($panel."\n",'',$render)===$src,'source content unmodified '.$slug);
    okay(substr_count($render,'href="https://example.test/old"')===1,'original link preserved '.$slug);
    okay(substr_count($render,'data-nry-wellness-path=')===2,'two first-party routes '.$slug);
    okay(strpos($render,'tracking.')===false,'no affiliate or merchant request URLs');
    $changed=str_replace($set['sourceAnchor'],'Edited by owner',$src);
    okay($c::transform($changed,$set)===$changed,'source anchor drift '.$slug);
    $already=str_replace('<p>'.$set['sourceAnchor'].'</p>',
        '<p>'.$set['sourceAnchor'].' <a href="'.$c::SRUB.'">Already cited</a></p>',$src);
    okay($c::transform($already,$set)===$already,'no duplicate existing links '.$slug);
}
okay($c::render('old post')==='old post','strict not-selected');
echo "WELLNESS_EVERGREEN_HANDOFF_OFFLINE_PASS\n";
