<?php
// Deterministic offline contract. No WordPress, network or raw private source.
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=array();$GLOBALS['writes']=0;
function add_action($name,$fn,$priority=10){$GLOBALS['hooks'][]=$name;}
function get_post($id){return (object)array('ID'=>118541,'post_type'=>'post',
 'post_status'=>'publish','post_name'=>NRY_Carp_Pillar_Trust_118541::SLUG,
 'post_modified_gmt'=>'2026-03-21 11:02:25','post_content'=>'unknown-current-source');}
function home_url($path='/'){return 'https://na-ryby.eu/';}
function get_permalink($id){return NRY_Carp_Pillar_Trust_118541::URL;}
function wp_generate_uuid4(){return 'uuid-test';}
function wp_update_post($data,$error=true){$GLOBALS['writes']++;return 118541;}
function ok($x,$s){if(!$x)throw new RuntimeException($s);echo "PASS: $s\n";}
require dirname(__DIR__).'/carp-pillar-trust-cleanup-118541-v1.php';
ok(in_array('wp_loaded',$GLOBALS['hooks'],true),'owner-approved single-post native hook');
$source='<h2>'.NRY_Carp_Pillar_Trust_118541::OLD_HEADING.'</h2>'.
 '<p>'.NRY_Carp_Pillar_Trust_118541::OLD_PARAGRAPH.'</p>'.
 '<p>'.str_repeat(chr(96),3).'</p>'.
 '<a href="https://www.chytapust.cz/kaprove-pruty/?test=1">Prověřit nabídku</a>'.
 '<img src="https://na-ryby.eu/wp-content/uploads/original.webp" alt="Kaprový prut">'.
 '[su_button url="https://na-ryby.eu/navijaky/"]Navijáky[/su_button]';
ok(NRY_Carp_Pillar_Trust_118541::candidate($source)===null,'unknown source cannot pass SHA gate');
$fix=NRY_Carp_Pillar_Trust_118541::candidate($source,false);
ok(is_string($fix) && strpos($fix,NRY_Carp_Pillar_Trust_118541::NEW_PARAGRAPH)!==false,
 'editorial text replaced with visitor answer');
ok(substr_count($fix,NRY_Carp_Pillar_Trust_118541::MARKER)===1
 && strpos($fix,NRY_Carp_Pillar_Trust_118541::OLD_HEADING)===false
 && strpos($fix,NRY_Carp_Pillar_Trust_118541::OLD_PARAGRAPH)===false
 && strpos($fix,str_repeat(chr(96),3))===false,
 'markup fence, draft heading and editorial instructions removed exactly once');
ok(strpos($fix,'https://www.chytapust.cz/kaprove-pruty/?test=1')!==false
 && strpos($fix,'https://na-ryby.eu/wp-content/uploads/original.webp')!==false
 && strpos($fix,'[su_button url="https://na-ryby.eu/navijaky/"]')!==false,
 'existing merchant href, media src and shortcode stay identical');
ok(NRY_Carp_Pillar_Trust_118541::candidate($source.$source,false)===null,
 'duplicate old editorial strings fail closed');
ok(NRY_Carp_Pillar_Trust_118541::candidate($fix,false)===null,
 'already edited page is idempotent');
NRY_Carp_Pillar_Trust_118541::release();
ok($GLOBALS['writes']===0,'source mismatch means no WordPress write');
echo "PASS: safe carp pillar exact-source release offline contract\n";
