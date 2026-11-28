<?php
/**
 * Render-only, exact-page correction of unsupported bivak rankings.
 * No stored WordPress edits, merchant URL changes or new external clickouts.
 * Rollback: remove this require from na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Bivak_Budget_Buyer_Trust_119575_V1 {
    const ID = 119575;
    const SLUG = 'nejlepsi-rybarske-bivaky-do-10-000-kc';
    const MODIFIED = '2026-05-13 14:09:39';
    const LINK = 'https://na-ryby.eu/jak-vybrat-rybarsky-bivak-pruvodce-materialy-konstrukci-a-velikosti/';
    const MARKER = 'data-nry-bivak-budget-buyer="v1"';
    const ANCHOR = '<div style="background: linear-gradient(135deg,#ecfeff,#f8fafc); border: 1px solid #67e8f9;';
    const HISTORICAL_CJ = 'click-100927011-12347575';

    public static function changes() {
        return array(
            'Nejlevnější 2man volba' => array('Dvouosobový bivak – porovnejte aktuální cenu', 2),
            'Cena / výkon' => array('Univerzálnější použití', 1),
            'Za mě nejsilnější univerzál do 10 000 Kč pro většinu kaprařů.'
                => array('Kandidát pro delší výpravy; ověřte vnitřní rozměry, hmotnost a cenu konkrétní varianty.', 1),
            'Nejlepší ochrana a soukromí' => array('Ochrana a soukromí – ověřte provedení', 1),
            'Nejlevnější 2man bivak' => array('2man bivak – cenu porovnejte', 1),
            'Nejlepší univerzál' => array('Univerzální použití – ověřte rozměry', 1),
            'Nejlepší přístřešek / základna' => array('Přístřešek pro delší pobyt – ověřte výbavu', 1),
        );
    }

    private static function eligible() {
        if (!function_exists('get_queried_object_id') || (int)get_queried_object_id() !== self::ID
            || !function_exists('is_singular') || !is_singular('post')
            || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/') !== 'https://na-ryby.eu'
            || !function_exists('get_post') || !function_exists('get_permalink')
            || get_permalink(self::ID) !== 'https://na-ryby.eu/'.self::SLUG.'/') return false;
        $p=get_post(self::ID);
        return is_object($p) && (int)($p->ID ?? 0) === self::ID
            && ($p->post_type ?? '') === 'post' && ($p->post_status ?? '') === 'publish'
            && ($p->post_name ?? '') === self::SLUG
            && ($p->post_modified_gmt ?? '') === self::MODIFIED;
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',$html,$matches)===false) return null;
        return $matches[2];
    }

    private static function guidance() {
        return '<aside '.self::MARKER.' role="note" style="background:#f0fdfa;border-left:4px solid #0f766e;'
            .'border-radius:8px;padding:14px 18px;margin:18px 0 26px">'
            .'<p><strong>Než vyberete konkrétní bivak:</strong> Změřte lehátko i potřebný průchod kolem něj;'
            .' zvažte počet nocí, počet osob, ventilaci a hmotnost při přesunech.'
            .' Do rozpočtu počítejte také případný přehoz, podlážku a dopravu.</p>'
            .'<p>Modely níže jsou kandidáti k porovnání, ne vítězové doloženého nezávislého testu.'
            .' Cenu a skladovost konkrétní varianty ověřte v obchodě.'
            .'</p><h2>Jak sestavit rozpočet bivaku do 10 000 Kč</h2>'
            .'<p>Nejdříve oddělte cenu samotného přístřešku od ceny kompletní sestavy. Dva stejně drahé bivaky mohou mít různý obsah balení. U konkrétní varianty ověřte, zda jsou v ceně podlážka, kotvicí kolíky, napínací prvky, přední panel a přepravní obal. Přehoz má smysl podle podmínek, není automaticky nutný pro každého rybáře.</p>'
            .'<div style="overflow-x:auto"><table><caption>Modelové rozpočty (nikoli aktuální nabídky prodejců)</caption><thead><tr><th scope="col">Položka</th><th scope="col">Sestava A</th><th scope="col">Sestava B</th></tr></thead><tbody>'
            .'<tr><td>Bivak</td><td>6 500 Kč</td><td>8 500 Kč</td></tr><tr><td>Potřebné příslušenství</td><td>1 800 Kč</td><td>1 000 Kč</td></tr><tr><td>Doprava</td><td>300 Kč</td><td>300 Kč</td></tr><tr><td>Celkem</td><td>8 600 Kč</td><td>9 800 Kč</td></tr><tr><td>Zbývá do 10 000 Kč</td><td>1 400 Kč</td><td>200 Kč</td></tr></tbody></table></div>'
            .'<p>Do tohoto příkladu není zahrnuto nové lehátko ani spacák. Jestliže je potřebujete také, počítejte celý nákup jako jednu výpravu. Změřte vnější rozměry lehátka včetně rámu a spacáku, nikoli pouze matraci. Z katalogových rozměrů bivaku nelze přímo vyvodit využitelný prostor kvůli tvaru stěn.</p>'
            .'<p>Například lehátko 210 × 80 cm, rezerva 10 cm u každé stěny a průchod 50 cm potřebuje v zadaném rovnoběžném uspořádání alespoň 230 × 150 cm. Dvě stejná lehátka potřebují za stejných předpokladů 230 × 230 cm. Tento model neřeší šikmé stěny ani skutečný bezpečný východ. Než zaplatíte, porovnejte skutečné rozměry a ověřte kompatibilitu.</p>'
            .'<p><strong><a href="'.self::LINK.'">Otevřít kalkulačku rozměrů bivaku a lehátka</a></strong> – zadejte vlastní sestavu a zjistěte potřebný prostor. V prodejním detailu pak ověřte cenu, konkrétní variantu a její obsah balení.</p></aside>';
    }

    public static function transform($html) {
        if (!is_string($html) || strlen($html) < 25000
            || strpos($html,self::MARKER)!==false
            || substr_count($html,self::ANCHOR)!==1
            || substr_count($html,self::HISTORICAL_CJ)!==22
            || strpos($html,'Prologic C-Series 2 Man')===false
            || strpos($html,'Mivardi Entrix XL')===false
            || strpos($html,'Delphin Panorama Eazy')===false
            || strpos($html,self::LINK)!==false) return $html;
        $before=self::hrefs($html);
        if ($before===null || count($before)<22) return $html;
        $next=$html;
        foreach (self::changes() as $old=>$replacement) {
            if (substr_count($next,$old)!==$replacement[1]) return $html;
            $next=str_replace($old,$replacement[0],$next);
        }
        $next=str_replace(self::ANCHOR,self::guidance().self::ANCHOR,$next);
        $after=self::hrefs($next);
        if ($after===null || count($after)!==count($before)+1
            || count(array_keys($after,self::LINK,true))!==1) return $html;
        $unchanged=array_values(array_filter($after,function($href){return $href!==self::LINK;}));
        return $unchanged===$before && substr_count($next,self::MARKER)===1 ? $next : $html;
    }

    public static function render($html) {
        return self::eligible() ? self::transform($html) : $html;
    }

    public static function boot() {
        if (function_exists('add_filter') && function_exists('is_singular')) {
            add_filter('the_content',array(__CLASS__,'render'),78);
        }
    }
}
NRY_Bivak_Budget_Buyer_Trust_119575_V1::boot();
