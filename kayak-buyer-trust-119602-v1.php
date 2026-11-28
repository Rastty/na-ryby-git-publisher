<?php
/**
 * Public buyer-trust repair on the reviewed existing kayak article.
 * No database writes; no merchant URL changes; exact revision and text guard.
 */
if (!defined('ABSPATH')) { exit; }
final class NRY_Kayak_Buyer_Trust_119602_V1 {
    const ID = 119602;
    const SLUG = 'rybarsky-kajak-pro-koho-dava-smysl-a-jak-vybrat';
    const URL = 'https://na-ryby.eu/rybarsky-kajak-pro-koho-dava-smysl-a-jak-vybrat/';
    const GMT = '2026-05-24 22:01:58';
    const HEADING = 'Rybářský kajak: pro koho dává smysl a jak vybrat stabilitu, nosnost a výbavu';
    const NOTE = 'Poznámka k obrázkům: níže jsou připravené produktové bloky pro obrázky z Chyť a pusť. Do atributu src vlož URL konkrétní produktové fotky z Chyť a pusť nebo fotku nahraj do médií ve WordPressu. Vyhneš se tím nefunkčním obrázkům a problémům s hotlinkováním.';
    const MARKER = 'data-nry-kayak-buyer-trust="119602-v1"';
    private static function normalized($html) {
        return trim(preg_replace('~\s+~u',' ',html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8')));
    }
    private static function eligible($loop=false) {
        if (is_admin() || !is_singular('post') || (int)get_queried_object_id()!==self::ID
            || is_preview() || is_feed() || (function_exists('is_customize_preview') && is_customize_preview())
            || ($_SERVER['REQUEST_METHOD']??'GET')!=='GET'
            || rtrim(home_url('/'),'/')!=='https://na-ryby.eu'
            || ($loop && (!in_the_loop() || !is_main_query()))) return false;
        $p=get_post(self::ID);
        return is_object($p) && (int)$p->ID===self::ID && $p->post_type==='post'
            && $p->post_status==='publish' && $p->post_name===self::SLUG
            && $p->post_modified_gmt===self::GMT && get_permalink(self::ID)===self::URL
            && is_string($p->post_content) && strpos($p->post_content,'Poznámka k obrázkům:')!==false
            && strpos($p->post_content,'jak vybrat stabilitu, nosnost a výbavu')!==false;
    }
    private static function block() {
        return '<section class="nry-gear-archive-entry" '.self::MARKER.' aria-label="Praktický výběr rybářského kajaku">'
        .'<p class="nry-gear-archive-entry__eyebrow">Než koupíte kajak</p><h2>Spočítejte nosnost a kompletní výbavu</h2>'
        .'<p>Porovnejte vždy celou sestavu: vlastní hmotnost kajaku pro manipulaci na břehu, nejvyšší výrobcem povolené zatížení na vodě, způsob pohonu, sedadlo a přepravní rozměry. Pevný nebo nafukovací trup sám o sobě nezaručuje stabilitu; rozhoduje konstrukce a podmínky použití.</p>'
        .'<h3>Modelový příklad zatížení</h3><p>Rybář 85 kg + výbava a oblečení 12 kg + pití a další náklad 5 kg + elektronika a její napájení 8 kg = <strong>110 kg celkového nákladu</strong>. Pokud výrobce konkrétního kajaku výslovně povoluje 140 kg celkového nákladu, zbývá v tomto příkladu 30 kg do limitu. Nepřičítejte ani neodečítejte vlastní hmotnost plavidla bez ověření definice nosnosti v návodu. Rozdíl do limitu není zárukou bezpečnosti; záleží také na rozložení nákladu, počasí, určeném použití a zkušenostech.</p>'
        .'<h3>Co řešit podle způsobu lovu</h3><ul>'
        .'<li><strong>Častá přívlač:</strong> ověřte, zda pohodlně dosáhnete na prut a podběrák a zda držáky nepřekážejí pádlu.</li>'
        .'<li><strong>Přeprava v malém autě:</strong> porovnejte skutečné rozměry balení, hmotnost celého kompletu a možnost sušení doma.</li>'
        .'<li><strong>Elektronika:</strong> ověřte uchycení displeje a sondy, kompatibilní napájení, hmotnost a vedení kabelů dříve, než objednáte příslušenství.</li></ul>'
        .'<nav aria-label="Výběr elektroniky"><a class="nry-gear-archive-entry__choice" href="https://na-ryby.eu/echolot-na-kajak-a-belly-boat-jak-vybrat-kompaktni-sonar/"><strong>Echolot na kajak a belly boat</strong><span>Výběr kompaktního sonaru, napájení a montáže</span></a></nav>'
        .'<p class="nry-gear-archive-entry__note">Před koupí vyzkoušejte nastupování, posed a manipulaci s výbavou. Před vyplutím ověřte pravidla konkrétní vody a pokyny výrobce; používejte odpovídající záchrannou vestu. Článek obsahuje partnerské odkazy; nabídky a jejich dostupnost si ověřte u prodejce.</p></section>';
    }
    public static function candidate($html) {
        if (!is_string($html) || strpos($html,self::MARKER)!==false) return $html;
        if (preg_match_all('~<h1\b[^>]*>.*?</h1\s*>~isu',$html,$headings)!==false
            && count($headings[0])===1 && self::normalized($headings[0][0])===self::HEADING
            && !preg_match('~<(?:a|img|iframe|script)\b~i',$headings[0][0])) {
            $heading=$headings[0][0];
        } else return $html;
        if (preg_match_all('~<p\b[^>]*>.*?</p\s*>~isu',$html,$paras)===false) return $html;
        $matches=array_values(array_filter($paras[0],function($p){
            return self::normalized($p)===self::NOTE && !preg_match('~<(?:a|img|iframe|script|video|picture|object)\b|\b(?:href|src)\s*=~i',$p);
        }));
        if (count($matches)!==1 || substr_count($html,$matches[0])!==1 || substr_count($html,$heading)!==1) return $html;
        $h2=preg_replace(array('~^<h1\b~i','~</h1\s*>$~i'),array('<h2','</h2>'),$heading);
        // Exact public wrapper observed around the editor note; do not nest
        // a second panel in it or remove a div with unrelated contents.
        $wrap='<div style="background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 16px; padding: 18px 20px; margin: 0 0 28px 0;">';
        $pattern='~'.preg_quote($wrap,'~').'\s*'.preg_quote($matches[0],'~').'\s*</div>~u';
        if (preg_match_all($pattern,$html,$wrappers)!==1) return $html;
        $h2=str_replace('font-size: 36px;','font-size: 28px;',$h2);
        $candidate=str_replace(array($heading,$wrappers[0][0]),array($h2,self::block()),$html);
        if (substr_count($candidate,self::MARKER)!==1) return $html;
        return $candidate;
    }
    public static function render($html) { return self::eligible(true) ? self::candidate($html) : $html; }
    public static function enqueue() {
        if (self::eligible()) wp_enqueue_style('nry-gear-archive-entry-v1',plugins_url('assets/nry-gear-archive-entry-v1.css',__FILE__),array(),'1.0.0');
    }
    public static function boot() {
        add_filter('the_content',array(__CLASS__,'render'),80);
        add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),27);
    }
}
NRY_Kayak_Buyer_Trust_119602_V1::boot();
