<?php
/** Offline exact menu and enqueue contract, no real WP/database/network. */
define('ABSPATH', __DIR__);
$GLOBALS['menu_hooks'] = array();
$GLOBALS['menu_actions'] = array();
$GLOBALS['menu_scripts'] = array();
$GLOBALS['menu_admin'] = false;
$GLOBALS['menu_preview'] = false;
$GLOBALS['menu_customize'] = false;
$GLOBALS['menu_home'] = 'https://na-ryby.eu/';
function add_filter($name, $callback, $priority, $accepted_args = 1) {
    $GLOBALS['menu_hooks'][$name] = array($priority, $accepted_args);
}
function add_action($name,$callback,$priority=10){
    $GLOBALS['menu_actions'][$name]=array($callback,$priority);
}
function wp_enqueue_script($handle,$src,$deps,$version,$footer) {
    $GLOBALS['menu_scripts'][]=compact('handle','src','deps','version','footer');
}
function plugins_url($path,$file) {return 'https://na-ryby.eu/wp-content/plugins/na-ryby-git-publisher/'.$path;}
function home_url($path = '/') { return $GLOBALS['menu_home']; }
function is_admin() { return $GLOBALS['menu_admin']; }
function is_preview() { return $GLOBALS['menu_preview']; }
function is_customize_preview() { return $GLOBALS['menu_customize']; }
function check_menu($ok, $message) { if (!$ok) throw new RuntimeException($message); }
require dirname(__DIR__).'/stays-main-navigation-bridge-v1.php';
$C = 'NRY_Stays_Main_Navigation_Bridge_V1';
check_menu($GLOBALS['menu_hooks']['wp_nav_menu_objects'] === array(50, 2), 'original WP menu hook contract');
check_menu($GLOBALS['menu_actions']['wp_enqueue_scripts'][1] === 26, 'sitewide consent observer registered');
check_menu($C::OLD_URL !== $C::NEW_URL && strpos($C::NEW_URL, 'https://na-ryby.eu/') === 0, 'same-site parent destination');
$old = (object)array('title'=>'Ubytování','url'=>$C::OLD_URL,'menu_item_parent'=>'0','ID'=>123,'classes'=>array('menu-item'));
$other = (object)array('title'=>'Revíry','url'=>'https://na-ryby.eu/category/soukrome-rybarske-reviry/','menu_item_parent'=>'0','ID'=>124);
$initial = array($old, $other);
$result = $C::filter_menu($initial);
check_menu(count($result)===4,'two exact stay choices appended');
check_menu($result[0]->url === $C::NEW_URL, 'parent still links to full current stay hub');
check_menu($result[1] === $other && $result[0]->title === 'Ubytování', 'other items and parent text preserved');
check_menu($initial[0] === $old && $old->url === $C::OLD_URL, 'original WP menu object remains immutable');
check_menu($result[0] !== $old && $result[0]->ID === $old->ID
   && in_array('menu-item-has-children',$result[0]->classes,true)
   && !in_array('menu-item-has-children',$old->classes,true),'clone preserves owner + marks submenu');
check_menu($result[2]->url===$C::CYCLO && $result[2]->title==='Cyklochata Vranov'
   && $result[3]->url===$C::WELLNESS && $result[3]->title==='Wellness srub Vranov',
   'exact source verified two stay pages');
foreach(array_slice($result,2) as $child) {
    check_menu($child->ID < -2000000 && $child->menu_item_parent==='123'
        && $child->db_id===0 && $child->type==='custom'
        && $child->target==='' && !$child->current
        && in_array($C::CLASS_NAME,$child->classes,true),'transient nav child only');
}
check_menu($C::filter_menu($result) === $result, 'already changed link is idempotent');
$duplicate = array($old, clone $old);
check_menu($C::filter_menu($duplicate) === $duplicate, 'duplicate candidates fail closed');
$renamed = clone $old; $renamed->title = 'Pobyty';
check_menu($C::filter_menu(array($renamed))[0] === $renamed, 'editor title update wins');
$relinked = clone $old; $relinked->url = 'https://na-ryby.eu/other/';
check_menu($C::filter_menu(array($relinked))[0] === $relinked, 'editor URL update wins');
$child = clone $old; $child->menu_item_parent = '19';
check_menu($C::filter_menu(array($child))[0] === $child, 'submenu item itself cannot be changed');
$editorChild=(object)array('title'=>'Editor child','url'=>'https://na-ryby.eu/my-stay/','menu_item_parent'=>'123','ID'=>130);
check_menu($C::filter_menu(array($old,$editorChild))===array($old,$editorChild),'human edited submenu wins');
$existingChoice=(object)array('title'=>'Cyklochata elsewhere','url'=>$C::CYCLO,'menu_item_parent'=>'0','ID'=>131);
check_menu($C::filter_menu(array($old,$existingChoice))===array($old,$existingChoice),'existing exact destination wins');
$bad=(object)array('title'=>'Bad','ID'=>132);
check_menu($C::filter_menu(array($old, 'not a menu item'))===array($old,'not a menu item'),'malformed menu fails closed');
$C::click_js();
check_menu(count($GLOBALS['menu_scripts'])===1
   && $GLOBALS['menu_scripts'][0]['handle']==='nry-stays-menu-click-v1'
   && str_ends_with($GLOBALS['menu_scripts'][0]['src'],'/assets/nry-stays-menu-click-v1.js')
   && $GLOBALS['menu_scripts'][0]['deps']===array()
   && $GLOBALS['menu_scripts'][0]['version']==='1.0.0'
   && $GLOBALS['menu_scripts'][0]['footer']===true,'one locally hosted observer and existing consent');
foreach(array('menu_admin','menu_preview','menu_customize','menu_home') as $key){
    $oldValue=$GLOBALS[$key];
    $GLOBALS[$key]=$key==='menu_home'?'https://other.example/':true;
    check_menu($C::filter_menu($initial) === $initial,'menu no-op under '.$key);
    $GLOBALS['menu_scripts']=array();$C::click_js();
    check_menu(count($GLOBALS['menu_scripts'])===0,'no JS under '.$key);
    $GLOBALS[$key]=$oldValue;
}
echo "STAYS_MAIN_MENU_TWO_CHOICES_OFFLINE_VERIFIED\n";
