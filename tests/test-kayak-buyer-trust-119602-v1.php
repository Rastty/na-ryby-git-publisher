<?php
define('ABSPATH',__DIR__);
function add_filter(...$args) {}
function add_action(...$args) {}
require __DIR__.'/../kayak-buyer-trust-119602-v1.php';
$c='NRY_Kayak_Buyer_Trust_119602_V1';
$heading='<h1 style="font-size: 36px;">'.$c::HEADING.'</h1>';
$note='<div style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px 20px; margin: 0 0 28px 0;"><p>'.str_replace('src','<code>src</code>',$c::NOTE).'</p></div>';
$tail='<a href="https://example.test/unchanged">Original</a><img src="local.jpg" alt="">';
$source=$heading.$note.$tail;
$actual=$c::candidate($source);
if (strpos($actual,$c::MARKER)===false || strpos($actual,'<h1')!==false
    || strpos($actual,'<h2 style="font-size: 28px;">')===false
    || strpos($actual,'Poznámka k obrázkům:')!==false
    || substr($actual,-strlen($tail))!==$tail
    || substr_count($actual,'href="https://na-ryby.eu/echolot-na-kajak-a-belly-boat-jak-vybrat-kompaktni-sonar/"')!==1
    || $c::candidate($actual)!==$actual
    || $c::candidate($heading.$note.$note.$tail)!==$heading.$note.$note.$tail
    || $c::candidate($heading.str_replace('hotlinkováním','odlišným textem',$note).$tail)!==$heading.str_replace('hotlinkováním','odlišným textem',$note).$tail) {
    fwrite(STDERR,"KAYAK_BUYER_TRUST_FAILED\n"); exit(1);
}
echo "KAYAK_BUYER_TRUST_PASS\n";
