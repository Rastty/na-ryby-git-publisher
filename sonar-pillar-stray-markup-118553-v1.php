<?php
/** Render-only repair of one stray markup paragraph in sonar pillar #118553. */
if (!defined('ABSPATH')) exit;
final class NRY_Sonar_Pillar_Stray_Markup_118553_V1 {
 const ID=118553;
 const SLUG='jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce';
 const URL='https://na-ryby.eu/jak-vybrat-echolot-pro-rybareni-kompletni-pruvodce/';
 const GMT='2026-11-04 11:27:17';
 const SOURCE='data-nry-editorial-edit="echolot-pillar-answer-first-118553-v1"';
 const PRACTICAL='data-nry-sonar-owned-practical="v1"';
 const NEUTRAL='data-nry-sonar-merchant-neutral="v1"';
 const MARKER='<!-- nry-sonar-stray-markup-cleaned-118553-v1 -->';
 const CTA='Mrknout na dostupnost';
 const HEADING='Interní rozcestník: kam pokračovat podle toho, co řešíte dál';
 public static function boot(){
  if(function_exists('add_filter')&&function_exists('is_single'))
   add_filter('the_content',array(__CLASS__,'render'),57);
 }
 private static function eligible(){
  if(!function_exists('is_single')||!is_single(self::ID)
   ||!function_exists('is_singular')||!is_singular('post')
   ||!function_exists('get_queried_object_id')||(int)get_queried_object_id()!==self::ID
   ||!function_exists('is_main_query')||!is_main_query()
   ||!function_exists('in_the_loop')||!in_the_loop()
   ||!function_exists('is_admin')||is_admin()
   ||!function_exists('is_preview')||is_preview()
   ||!function_exists('home_url')||rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
   ||!function_exists('get_permalink')||get_permalink(self::ID)!==self::URL
   ||!function_exists('get_post'))return false;
  $p=get_post(self::ID);
  return is_object($p)&&(int)($p->ID??0)===self::ID
   &&($p->post_type??'')==='post'&&($p->post_status??'')==='publish'
   &&($p->post_name??'')===self::SLUG&&($p->post_modified_gmt??'')===self::GMT;
 }
 private static function hrefs($html){
  if(preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',$html,$m)===false)return null;
  return $m[2];
 }
 public static function transform($html){
  if(!is_string($html)||strlen($html)<1000||strlen($html)>250000
   ||strpos($html,self::MARKER)!==false
   ||substr_count($html,self::SOURCE)!==1||substr_count($html,self::PRACTICAL)!==1
   ||substr_count($html,self::NEUTRAL)!==1||substr_count($html,self::CTA)!==1
   ||substr_count($html,self::HEADING)!==1)return $html;
  // Match only an otherwise empty paragraph containing orphan quote + grave accent.
  $pattern='~<p\b[^>]*>\s*(?:„|&bdquo;|&#8222;)\s*(?:\x60|&#96;)\s*</p>~u';
  if(preg_match_all($pattern,$html,$m,PREG_OFFSET_CAPTURE)!==1)return $html;
  $bad=$m[0][0][0];$pos=$m[0][0][1];
  $heading=strpos($html,self::HEADING);
  $cta=strrpos(substr($html,0,$heading),self::CTA);
  if($cta===false||$pos<=$cta||$pos+strlen($bad)>=$heading
   ||$pos-$cta>900||$heading-($pos+strlen($bad))>350)return $html;
  $links=self::hrefs($html);
  if($links===null)return $html;
  $fixed=substr_replace($html,self::MARKER,$pos,strlen($bad));
  if(substr_count($fixed,self::MARKER)!==1||self::hrefs($fixed)!==$links
   ||str_replace(self::MARKER,$bad,$fixed)!==$html)return $html;
  return $fixed;
 }
 public static function render($html){return self::eligible()?self::transform($html):$html;}
}
NRY_Sonar_Pillar_Stray_Markup_118553_V1::boot();
