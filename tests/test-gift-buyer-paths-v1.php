<?php
define('ABSPATH',__DIR__);
$GLOBALS['env']=array('id'=>46852,'slug'=>'nejlepsi-darky-pro-rybare-tipy-na-nadcasove-a-prakticke-prezentace',
    'modified'=>'2023-05-13 16:02:20','status'=>'publish','type'=>'post','admin'=>false,
    'preview'=>false,'singular'=>true,'loop'=>true,'main'=>true,'site'=>'https://na-ryby.eu/');
function add_filter($name,$callback,$priority){$GLOBALS['filter']=compact('name','callback','priority');}
function add_action($name,$callback,$priority){$GLOBALS['action']=compact('name','callback','priority');}
function get_queried_object_id(){return $GLOBALS['env']['id'];}
function is_singular($type){return $type==='post' && $GLOBALS['env']['singular'];}
function is_admin(){return $GLOBALS['env']['admin'];}
function is_preview(){return $GLOBALS['env']['preview'];}
function is_main_query(){return $GLOBALS['env']['main'];}
function in_the_loop(){return $GLOBALS['env']['loop'];}
function home_url($path='/'){return $GLOBALS['env']['site'];}
function get_permalink($id){return 'https://na-ryby.eu/'.$GLOBALS['env']['slug'].'/';}
function get_post($id){$e=$GLOBALS['env'];return (object)array('ID'=>$id,'post_type'=>$e['type'],
    'post_status'=>$e['status'],'post_name'=>$e['slug'],'post_modified_gmt'=>$e['modified']);}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function wp_enqueue_style($h,$src,$deps,$v){$GLOBALS['assets']['css']=compact('h','src','deps','v');}
function wp_enqueue_script($h,$src,$deps,$v,$footer){$GLOBALS['assets']['js']=compact('h','src','deps','v','footer');}
function ensure($value,$label){if(!$value)throw new RuntimeException($label);echo "PASS $label\n";}
require __DIR__.'/../gift-buyer-paths-v1.php';
$c='NRY_Gift_Buyer_Paths_V1';
ensure($GLOBALS['filter']['name']==='the_content' && $GLOBALS['filter']['priority']===80,'page render hook');
ensure(count($c::routes())===4,'four distinct purchase decision paths');
function fixture($anchor){return '<h2>'.$anchor.'</h2>'
    .'<p><a href="https://www.rybarskepotreby.cz/old-item.html">Původní obchod</a></p>'
    .'<p><a href="https://na-ryby.eu/old-internal/">Starý článek</a></p>'
    .'<p><a href="https://example.net/info">Externí zdroj</a></p>'
    .str_repeat('<p>Historický text o nákupech a rybářském vybavení.</p>',180);}
foreach ($c::pages() as $id=>$set){
    $GLOBALS['env']['id']=$id;$GLOBALS['env']['slug']=$set['slug'];$GLOBALS['env']['modified']=$set['modified'];
    $src=fixture($set['anchor']);
    $next=$c::render($src);
    ensure($next!==$src && substr_count($next,$c::MARKER)===1,'gift panel exact post '.$id);
    ensure(strpos($next,'data-nry-gift-path="night"')!==false &&
        substr_count($next,'data-nry-gift-path=')===4,'four first-party buyer CTAs '.$id);
    ensure($c::render($next)===$next,'idempotent render '.$id);
    ensure(strpos($next,$src)!==false,'all archived source bytes preserved '.$id);
    ensure(strpos($next,'href="https://www.rybarskepotreby.cz/old-item.html"')!==false,
        'historic merchant href preserved '.$id);
    $GLOBALS['assets']=array();$c::enqueue();
    ensure(($GLOBALS['assets']['css']['v']??null)==='1.0.0' &&
        ($GLOBALS['assets']['js']['v']??null)==='1.0.0' &&
        $GLOBALS['assets']['js']['footer']===true,'CSS+JS only for exact post '.$id);
    foreach (array('id'=>20,'slug'=>'another-post','modified'=>'2030-01-01 00:00:00',
        'status'=>'draft','type'=>'page','admin'=>true,'preview'=>true,'singular'=>false,
        'loop'=>false,'main'=>false,'site'=>'https://wrong.example/') as $key=>$invalid){
        $old=$GLOBALS['env'][$key];$GLOBALS['env'][$key]=$invalid;
        ensure($c::render($src)===$src,'fail closed on '.$key.' '.$id);
        $GLOBALS['env'][$key]=$old;
    }
    ensure($c::render(str_replace($set['anchor'],'changed by editor',$src))===
        str_replace($set['anchor'],'changed by editor',$src),'source anchor guard '.$id);
    ensure($c::render('<p>'.$set['anchor'].'</p>')==='<p>'.$set['anchor'].'</p>',
        'source length guard '.$id);
}
echo "PASS bounded render-only seasonal chooser, zero stored edits and no affiliate mutation\n";
