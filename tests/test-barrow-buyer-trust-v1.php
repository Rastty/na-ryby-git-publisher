<?php
// Pure-PHP acceptance tests: exact four-product scope and no CTA rewrites.
define('ABSPATH',__DIR__);
$GLOBALS['mode']=array('id'=>0,'singular'=>true,'admin'=>false,'preview'=>false,
    'slug'=>'','modified'=>'','status'=>'publish','type'=>'product','permalink'=>'');
function add_filter($n,$cb,$priority=10){$GLOBALS['filter']=compact('n','cb','priority');}
function add_action($n,$cb,$priority=10){$GLOBALS['action']=compact('n','cb','priority');}
function get_queried_object_id(){return $GLOBALS['mode']['id'];}
function is_singular($type){return $GLOBALS['mode']['singular'] && $type==='product';}
function is_admin(){return $GLOBALS['mode']['admin'];}
function is_preview(){return $GLOBALS['mode']['preview'];}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return $GLOBALS['mode']['permalink'];}
function get_post($id){return (object)array('ID'=>$id,'post_type'=>$GLOBALS['mode']['type'],
    'post_status'=>$GLOBALS['mode']['status'],'post_name'=>$GLOBALS['mode']['slug'],
    'post_modified_gmt'=>$GLOBALS['mode']['modified']);}
function wp_enqueue_style($name,$url,$deps,$version){$GLOBALS['style']=compact('name','url','deps','version');}
function plugins_url($path,$file){return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function check($ok,$message){if(!$ok)throw new RuntimeException($message);echo "PASS $message\n";}
require __DIR__.'/../barrow-buyer-trust-v1.php';
$c='NRY_Barrow_Buyer_Trust_V1'; $sets=$c::cohorts();
check(count($sets)===4,'four verified transport product pages, no broad site rewrite');
foreach($sets as $id=>$set){
    $GLOBALS['mode']['id']=$id;
    $GLOBALS['mode']['slug']=$set['slug'];
    $GLOBALS['mode']['modified']=$set['modified'];
    $GLOBALS['mode']['permalink']='https://na-ryby.eu/produkt/'.$set['slug'].'/';
    $src=$c::RATING.'<p>'.$set['proof'].'</p>';
    foreach($set['fixes'] as $old=>$new){$src.='<div>'.$old.'</div>';}
    $src.='<a href="https://www.tkqlhce.com/click-8287231-12347575?url=https://www.chytapust.cz/vozik-1">Offer 1</a>';
    $src.='<a href="https://www.dpbolvw.net/click-8287231-12923227?url=https://www.chytapust.cz/vozik-2">Offer 2</a>';
    $src.=str_repeat('<p>Důležité jsou rozměry, nosnost a průchodnost cesty.</p>',140);
    $next=$c::render($src);
    check($next!==$src && substr_count($next,$c::MARKER)===1,'one buyer panel '.$id);
    check(strpos($next,$c::RATING)===false,'unsubstantiated five-star review removed '.$id);
    foreach($set['fixes'] as $old=>$new){
        check(strpos($next,$old)===false && ($new==='' || substr_count($next,$new)===1),
            'material error corrected '.substr(md5($old),0,6).' '.$id);
    }
    check(substr_count($next,'click-8287231-')===2,'two example merchant tracking URLs preserved '.$id);
    check(substr_count($next,'<li><a href="https://na-ryby.eu/produkt/')===3,
        'three contextual links to other barrows '.$id);
    check($c::render($next)===$next,'idempotent '.$id);
    $c::enqueue(); check(($GLOBALS['style']['version']??null)==='1.0.0','style enqueued '.$id);
    foreach(array('singular'=>false,'admin'=>true,'preview'=>true,'status'=>'draft',
        'type'=>'post','modified'=>'2030-01-01 00:00:00','slug'=>'edited',
        'permalink'=>'https://na-ryby.eu/unrelated/') as $field=>$bad){
        $saved=$GLOBALS['mode'][$field];$GLOBALS['mode'][$field]=$bad;
        check($c::render($src)===$src,'rejects source identity drift '.$field.' '.$id);
        $GLOBALS['mode'][$field]=$saved;
    }
    foreach(array($c::RATING,$set['proof'],array_keys($set['fixes'])[0]) as $required){
        $drift=str_replace($required,'editor updated source',$src);
        check($c::render($drift)===$drift,'rejects missing source anchor '.$id);
    }
    $drift=str_replace('https://www.tkqlhce.com/click-8287231-12347575?url=https://www.chytapust.cz/vozik-1',
        'https://example.com/changed-affiliate',$src);
    // This safety module preserves any approved affiliate link as-is, even
    // when a separate authorized component replaces the historical URL.
    $updated=$c::render($drift);
    check($updated!==$drift && substr_count($updated,'href="https://example.com/changed-affiliate"')===1,
        'preserves newly authorized original offer URL unchanged '.$id);
}
$GLOBALS['mode']['id']=4;
unset($GLOBALS['style']);$c::enqueue();
check(!isset($GLOBALS['style']),'no CSS for other products');
echo "PASS render-only: no stored post changes, no invented rating, no affiliate rewrite\n";
