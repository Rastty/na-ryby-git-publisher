<?php
/** V6 compromise recovery PHP contract; never connects to production. */
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');
$GLOBALS['nry_hooks'] = array(); $GLOBALS['nry_options'] = array();
function add_action($name,$handler,$priority=10) {$GLOBALS['nry_hooks'][]=array($name,$handler,$priority);}
function get_option($name,$default=false) {return $GLOBALS['nry_options'][$name] ?? $default;}
function home_url($path='/') {return 'https://na-ryby.eu'.$path;}
function get_page_by_path($a,$b,$c) {return false;}
function get_post_types($a,$b) {return array('post','page');}
function get_post_stati($a,$b) {return array('publish');}
function get_posts($args) {return array();}
function check($condition,$label) {if (!$condition) {fwrite(STDERR,"FAIL: $label\n");exit(1);}}
require dirname(__DIR__).'/baitboat-release-v3.php';
require dirname(__DIR__).'/baitboat-affiliate-release-v1.php';
require dirname(__DIR__).'/baitboat-stock-guard-v1.php';
require dirname(__DIR__).'/baitboat-compromise-recovery-v1.php';
$v3=NRY_Baitboat_Release_V3::asset_sha();
$GLOBALS['nry_options'][NRY_Baitboat_Release_V3::APPROVAL]=array('image_rights_confirmed'=>true,'sha256'=>$v3);
$v3src=NRY_Baitboat_Release_V3::approved_asset_bytes($v3);
$v4=NRY_Baitboat_Affiliate_Release_V1::selector_html($v3src);
$s4=NRY_Baitboat_Affiliate_Release_V1::inspection();
$GLOBALS['nry_options'][NRY_Baitboat_Affiliate_Release_V1::APPROVAL]=array('sha256'=>$s4['sha256'],'source_sha'=>$v3);
$v5=NRY_Baitboat_Stock_Guard_V1::selector_html($v4);
check(!NRY_Baitboat_Compromise_Recovery_V1::inspection()['ready'],'no bypass when V5 not approved');
$s5=NRY_Baitboat_Stock_Guard_V1::inspection();
$GLOBALS['nry_options'][NRY_Baitboat_Stock_Guard_V1::APPROVAL]=array('sha256'=>$s5['sha256'],'source_sha'=>$v3);
$s6=NRY_Baitboat_Compromise_Recovery_V1::inspection();
check($s6['ready']&&!$s6['approved']&&!$s6['conflict'],'V6 independent owner gate');
$v6=NRY_Baitboat_Compromise_Recovery_V1::selector_html($v5);
check(is_string($v6)&&hash('sha256',$v6)===$s6['sha256'],'exact V6 source hash');
check(strpos($v6,'data-nry-baitboat-rc="v6-compromise-recovery"')!==false,'V6 public marker');
check(strpos($v6,'lowerCapacityAlternatives=products.filter(p=>!unavailableProducts.has(p.id)')!==false,'excluded products never suggested');
check(strpos($v6,'change.addEventListener("click"')!==false,'deliberate visitor action');
check(strpos($v6,'click-8287231-15734253')!==false,'existing approved CJ property retained');
check(strpos($v6,'data-nry-baitboat-rc="v5-stock-guard"')===false,'V6 active marker only');
check(strpos($v5,'data-nry-baitboat-rc="v5-stock-guard"')!==false,'approved V5 remains unchanged');
check(NRY_Baitboat_Compromise_Recovery_V1::selector_html(str_replace('v5-stock-guard','untrusted',$v5))===false,'reject stale source');
check(in_array(array('template_redirect',array('NRY_Baitboat_Compromise_Recovery_V1','serve'),-5),$GLOBALS['nry_hooks'],true),'V6 higher priority than V5');
$GLOBALS['nry_options'][NRY_Baitboat_Compromise_Recovery_V1::APPROVAL]=array('sha256'=>$s6['sha256'],'source_sha'=>$v3);
check(NRY_Baitboat_Compromise_Recovery_V1::inspection()['approved'],'V6 exact approval');
$GLOBALS['nry_options'][NRY_Baitboat_Compromise_Recovery_V1::APPROVAL]['sha256']=str_repeat('f',64);
check(NRY_Baitboat_Compromise_Recovery_V1::inspection()['conflict'],'changed SHA fails closed');
if (($argv[1]??'')==='--render') {echo $v6;exit;}
echo "PASS: V6 explicit compromise, stock exclusions, CJ and reversible owner gate\n";
