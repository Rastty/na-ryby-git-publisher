<?php
/** V5 stock guard regression; no WordPress or outbound merchant access. */
define('ABSPATH', __DIR__);
define('OBJECT', 'OBJECT');
$GLOBALS['nry_hooks']=array();
$GLOBALS['nry_options']=array();
function add_action($name,$handler,$priority=10){$GLOBALS['nry_hooks'][]=array($name,$handler,$priority);}
function get_option($name,$default=false){return $GLOBALS['nry_options'][$name]??$default;}
function home_url($path='/'){return 'https://na-ryby.eu'.$path;}
function get_page_by_path($a,$b,$c){return false;}
function get_post_types($a,$b){return array('post','page');}
function get_post_stati($a,$b){return array('publish');}
function get_posts($args){return array();}
function check($value,$message){if(!$value){fwrite(STDERR,"FAIL: $message\n");exit(1);}}
require dirname(__DIR__).'/baitboat-release-v3.php';
require dirname(__DIR__).'/baitboat-affiliate-release-v1.php';
require dirname(__DIR__).'/baitboat-stock-guard-v1.php';
$v3=NRY_Baitboat_Release_V3::asset_sha();
$GLOBALS['nry_options'][NRY_Baitboat_Release_V3::APPROVAL]=array('image_rights_confirmed'=>true,'sha256'=>$v3);
$source=NRY_Baitboat_Release_V3::approved_asset_bytes($v3);
$v4=NRY_Baitboat_Affiliate_Release_V1::selector_html($source);
check(is_string($v4),'unchanged V4 source is still valid');
check(!NRY_Baitboat_Stock_Guard_V1::inspection()['ready'],'V5 may not bypass the V4 approval');
$v4state=NRY_Baitboat_Affiliate_Release_V1::inspection();
$GLOBALS['nry_options'][NRY_Baitboat_Affiliate_Release_V1::APPROVAL]=array('sha256'=>$v4state['sha256'],'source_sha'=>$v3);
$state=NRY_Baitboat_Stock_Guard_V1::inspection();
check($state['ready']&&!$state['approved']&&!$state['conflict'],'owner must opt in');
$v5=NRY_Baitboat_Stock_Guard_V1::selector_html($v4);
check(is_string($v5)&&hash('sha256',$v5)===$state['sha256'],'V5 exact hash');
check(strpos($v5,'data-nry-baitboat-rc="v5-stock-guard"')!==false,'V5 release marker');
check(strpos($v5,'unavailableProducts=new Set(["vulcan"])')!==false,'known unavailable model excluded');
check(strpos($v5,'products.filter(p=>!unavailableProducts.has(p.id)&&')!==false,'filters exclude unavailable');
check(strpos($v5,'Boatman Vulcan PRO</h3><span class="pill honest">U prodejce nedostupné při kontrole')!==false,'unavailable label');
check(strpos($v5,'Zobrazit nabídku u Chyť a pusť ↗')===false,'no unverified buying assertion');
check(strpos($v5,'Ověřit dostupnost u Chyť a pusť ↗')!==false,'availability first CTA');
check(strpos($v5,'click-8287231-15734253')!==false,'own-property affiliate retained');
$vulcan=NRY_Baitboat_Affiliate_Release_V1::cj_link('https://www.chytapust.cz/Zavazeci-lodky-2165/Boatman-Zavazeci-lodicka-Vulcan-Pro-s-GPS-a-Echolotem-Cerna');
check(strpos($v5,$vulcan)===false,'no outbound link to out-of-stock exact product');
check(NRY_Baitboat_Stock_Guard_V1::selector_html(str_replace('data-nry-baitboat-rc="v4-cj"','unreviewed',$v4))===false,'source drift fails closed');
check(strpos($v4,'data-nry-baitboat-rc="v4-cj"')!==false,'V4 stays intact');
$GLOBALS['nry_options'][NRY_Baitboat_Stock_Guard_V1::APPROVAL]=array('sha256'=>$state['sha256'],'source_sha'=>$v3);
check(NRY_Baitboat_Stock_Guard_V1::inspection()['approved'],'exact owner approval enables V5');
$GLOBALS['nry_options'][NRY_Baitboat_Stock_Guard_V1::APPROVAL]['sha256']=str_repeat('f',64);
check(NRY_Baitboat_Stock_Guard_V1::inspection()['conflict'],'mismatched SHA blocks V5');
check(in_array(array('template_redirect',array('NRY_Baitboat_Stock_Guard_V1','serve'),-4),$GLOBALS['nry_hooks'],true),'V5 before V4 with rollback');
if (($argv[1]??'')==='--render') { echo $v5; exit; }
echo "PASS: V5 stock exclusion, honest CTA, CJ attribution, owner gate, reversible V4 fallback\n";
