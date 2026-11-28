<?php
/** Six real buying choices on the root equipment archive. Frontend only. */
if (!defined('ABSPATH')) exit;
final class NRY_Equipment_Hub_Decision_V1 {
 const ROOT='https://na-ryby.eu/category/vybaveni-pro-rybare/';
 const MARKER='data-nry-equipment-hub="v1"';
 private static $printed=false;
 public static function choices() {return array(
  array('carp_rods','Kaprové pruty','Délka, akce a použití','https://na-ryby.eu/jak-vybrat-kaprovy-prut-kompletni-pruvodce-od-zacatecnika-po-profika/'),
  array('reels','Rybářské navijáky','Velikost, brzda a sladění s prutem','https://na-ryby.eu/jak-vybrat-rybarsky-navijak-velikost-prevod-brzda-a-typy-navijaku/'),
  array('sonar','Echoloty a sonary','Sonda, GPS a rozumný rozpočet','https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/'),
  array('alarms','Signalizátory záběru','Příposlech, dosah a volba sady','https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/'),
  array('bivvy','Rybářské bivaky','Prostor a ochrana podle výpravy','https://na-ryby.eu/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/'),
  array('baitboat','Zavážecí loďky','GPS, kapacita a potřeba sonaru','https://na-ryby.eu/zavazeci-lodky-pro-rybare/')
 );}
 private static function eligible() {
  foreach (array('is_category','is_admin','is_feed','is_paged','get_queried_object','get_term_link','home_url') as $f)
   if (!function_exists($f)) return false;
  if (is_admin()||is_feed()||is_paged()||(function_exists('is_preview')&&is_preview())
      ||(function_exists('is_customize_preview')&&is_customize_preview())
      ||(function_exists('wp_doing_ajax')&&wp_doing_ajax())
      ||($_SERVER['REQUEST_METHOD']??'GET')!=='GET'
      ||rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
      ||!is_category('vybaveni-pro-rybare')) return false;
  $t=get_queried_object();
  return is_object($t)&&($t->taxonomy??'')==='category'
      &&($t->slug??'')==='vybaveni-pro-rybare'
      &&(int)($t->term_id??0)>0&&(int)($t->parent??-1)===0
      &&get_term_link($t)===self::ROOT;
 }
 public static function block() {
  $s='<section class="nry-gear-archive-entry" '.self::MARKER
    .' aria-label="Rychlý výběr rybářského vybavení">'
    .'<p class="nry-gear-archive-entry__eyebrow">Vyberte podle toho, co řešíte</p>'
    .'<h2>Šest rychlých cest ke správné výbavě</h2>'
    .'<p>Než otevřete starší recenze, projděte si hlavní parametry. '
    .'Každá karta vede přímo na tematický návod.</p>'
    .'<nav aria-label="Nákupní průvodci rybářskou výbavou">';
  foreach(self::choices() as $c) $s.='<a class="nry-gear-archive-entry__choice" '
    .'data-nry-equipment-choice="'.$c[0].'" href="'.$c[3].'">'
    .'<strong>'.$c[1].'</strong><span>'.$c[2].'</span></a>';
  return $s.'</nav><p class="nry-gear-archive-entry__note">'
    .'Starší ceny a nabídky nemusí platit. Před nákupem ověřte variantu, cenu a dostupnost u prodejce.'
    .'</p></section>';
 }
 public static function move_after_h1($html) {
  if(!self::eligible()||!is_string($html)||strlen($html)<5000
     ||substr_count($html,self::MARKER)!==1) return $html;
  $block=self::block();
  if(substr_count($html,$block)!==1) return $html;
  if(preg_match_all('~<h1\\b[^>]{0,800}>([\\s\\S]{1,400}?)</h1>~u',
         $html,$m,PREG_OFFSET_CAPTURE)!==1) return $html;
  $raw=trim($m[1][0][0]);
  if(preg_match('~\\A<span>([^<>]{1,300})</span>\\z~u',$raw,$x)) $raw=$x[1];
  if(strpos($raw,'<')!==false||strpos($raw,'>')!==false) return $html;
  $title=preg_replace('~[\\s\\x{00A0}\\x{202F}]+~u',' ',
          html_entity_decode($raw,ENT_QUOTES|ENT_HTML5,'UTF-8'));
  if(!is_string($title)||trim($title)!=='Vybavení pro rybáře') return $html;
  $h=$m[0][0][0]; $end=$m[0][0][1]+strlen($h); $at=strpos($html,$block);
  if($at===false||$at<=$end) return $html;
  $rest=substr_replace($html,'',$at,strlen($block));
  $done=substr($rest,0,$end).$block.substr($rest,$end);
  return strlen($done)===strlen($html)&&substr_count($done,self::MARKER)===1?$done:$html;
 }
 public static function buffer() {
  if(self::eligible()&&function_exists('ob_start'))
    ob_start(array(__CLASS__,'move_after_h1'));
 }
 public static function render($q) {
  if(self::$printed||!self::eligible()||!is_object($q)
     ||!method_exists($q,'is_main_query')||!$q->is_main_query()) return;
  self::$printed=true; echo self::block();
 }
 public static function assets() {
  if(!self::eligible()||!function_exists('plugins_url')) return;
  if(function_exists('wp_enqueue_style')) wp_enqueue_style(
    'nry-gear-archive-entry-v1',plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');
  if(function_exists('wp_enqueue_script')) wp_enqueue_script(
    'nry-equipment-hub-click-v1',plugins_url('assets/nry-equipment-hub-click-v1.js',__FILE__),array(),'1.0.0',true);
 }
 public static function boot() {
  if(!function_exists('add_action'))return;
  add_action('template_redirect',array(__CLASS__,'buffer'),1);
  add_action('loop_start',array(__CLASS__,'render'),8,1);
  add_action('wp_enqueue_scripts',array(__CLASS__,'assets'),25);
 }
}
NRY_Equipment_Hub_Decision_V1::boot();
