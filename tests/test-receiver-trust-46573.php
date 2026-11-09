<?php
// Existing source is private: production fails closed on drift; tests never
// commit real WordPress raw HTML or tracked affiliate URLs.
define('ABSPATH',__DIR__);
$GLOBALS['hooks']=array(); $GLOBALS['writes']=0; $GLOBALS['options']=array();
function add_action($name,$cb,$priority=10){ $GLOBALS['hooks'][]=$name; }
function home_url($path='/'){ return 'https://na-ryby.eu/'; }
function get_post($id){return (object)array('ID'=>46573,'post_type'=>'post',
 'post_status'=>'publish','post_name'=>'pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare',
 'post_modified_gmt'=>'2023-05-12 15:13:13','post_content'=>'different editor changed this document');}
function get_permalink($id){ return 'https://na-ryby.eu/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/'; }
function wp_update_post($data,$wp_error=false){$GLOBALS['writes']++;return 46573;}
function ok($assert,$message){if(!$assert)throw new RuntimeException($message); echo "PASS: $message\n";}
require __DIR__.'/../receiver-trust-46573-v1.php';
ok(in_array('wp_loaded',$GLOBALS['hooks'],true),'one-post trust boot hook registered');
ok(NRY_Receiver_Trust_46573::ID===46573,'only exact 46573 allowed');
ok(NRY_Receiver_Trust_46573::CANONICAL==='https://na-ryby.eu/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/','exact canonical bound');
ok(NRY_Receiver_Trust_46573::SOURCE_BYTES===10229,'real authenticated 10229-byte source bound');
ok(NRY_Receiver_Trust_46573::SOURCE_SHA!==
 NRY_Receiver_Trust_46573::CANDIDATE_SHA,'candidate differs from original');
ok(preg_match('/^[a-f0-9]{64}$/D',NRY_Receiver_Trust_46573::SOURCE_SHA)===1,'source SHA valid');
ok(preg_match('/^[a-f0-9]{64}$/D',NRY_Receiver_Trust_46573::CANDIDATE_SHA)===1,'candidate SHA valid');
ok(NRY_Receiver_Trust_46573::build_candidate('different source')===null,'unknown source cannot build change');
$method=new ReflectionMethod(NRY_Receiver_Trust_46573::class,'line_edits');
$method->setAccessible(true);$changes=$method->invoke(null);
ok(is_array($changes)&&count($changes)===39,'39 explicit source-locked factual updates');
ok(count(array_unique(array_keys($changes)))===39,'no duplicate line edits');
ok(min(array_keys($changes))===3 && max(array_keys($changes))===103,'no unrelated post tail rewritten');
foreach ($changes as $line=>$html){
 ok(is_int($line) && $line>0 && $line<=103 && is_string($html) &&
    stripos($html,'<script')===false && stripos($html,'<iframe')===false &&
    stripos($html,'DOPLNIT-URL')===false &&
    strpos($html,'www.tkqlhce.com')===false,
    'line '.$line.' safe editorial text only');
}
ok(strpos($changes[36],'https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/')!==false,
  'one verified existing decision-guide link');
ok(substr_count($changes[3],NRY_Receiver_Trust_46573::MARKER)===1,
  'one exact source-bound release marker');
NRY_Receiver_Trust_46573::run();
ok($GLOBALS['writes']===0,'source drift refuses every WordPress write');
ok($GLOBALS['options']===array(),'drift does not alter WP options');
echo "PASS: receiver guide #46573 trust-correction contract; zero writes\n";
