<?php
/**
 * Source-pinned, render-only factual corrections for four fishing barrow products.
 * Preserves every pre-existing outbound href and stored WordPress record.
 * Roll back by removing the require from na-ryby-git-publisher.php.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Barrow_Buyer_Trust_V1 {
    const MARKER = 'data-nry-barrow-buyer="v1"';
    const RATING = '<p><strong>HODNOCENÍ: </strong>5/5 ⭐⭐⭐⭐⭐</p>';

    public static function cohorts() {
        return array(
            115565 => array(
                'slug'=>'trakker-prepravni-vozik-x-trail-t1-barrow-recenze',
                'modified'=>'2026-03-02 13:19:24',
                'name'=>'Trakker X-Trail T1',
                'scenario'=>'Jednokolový vozík s nastavitelnou ložnou plochou: zvažte rozložení nákladu a náročnost povrchu.',
                'spec'=>'Hmotnost 9,4 kg; nastavitelná délka ložné plochy 74–97 cm podle tabulky produktu.',
                'proof'=>'Délka nakládací plochy',
                'fixes'=>array(
                    'přívěsný vozík.'=>'přepravní rybářský vozík.',
                ),
            ),
            114330 => array(
                'slug'=>'mivardi-prepravni-vozik-executive-recenze',
                'modified'=>'2025-12-29 02:30:15',
                'name'=>'Mivardi Executive',
                'scenario'=>'Jednokolová konstrukce s větší ložnou plochou: ověřte šířku trasy a místo v autě.',
                'spec'=>'Hmotnost 13,5 kg a nosnost 120 kg podle produktové tabulky; v textu byl chybný údaj 150 kg.',
                'proof'=>'13,5 kg',
                'fixes'=>array(
                    'Maximální nosnost vozíku dosahuje až 150 kg, což vám umožní vzít vše potřebné.'
                        =>'V produktové tabulce výše je uvedena nosnost 120 kg. Před zatížením ji ověřte v aktuálních pokynech výrobce.',
                ),
            ),
            107077 => array(
                'slug'=>'mivardi-transportni-vozik-premium-recenze',
                'modified'=>'2025-08-09 02:56:38',
                'name'=>'Mivardi Premium',
                'scenario'=>'Dvoukolová konstrukce: zkontrolujte celkovou šířku i způsob skládání pro převoz autem.',
                'spec'=>'Hmotnost 11,5 kg a nosnost 120 kg podle produktové tabulky; další část textu chybně uváděla 100 kg.',
                'proof'=>'11,5 kg',
                'fixes'=>array(
                    'Díky své nosnosti až 100 kg'=>'Podle produktové tabulky s nosností 120 kg',
                    '<li><strong>Nosnost:</strong> 100 kg</li>'=>'<li><strong>Nosnost:</strong> 120 kg (údaj z produktové tabulky výše; ověřte u prodejce)</li>',
                    '<li><strong>Hmotnost:</strong> 15 kg</li>'=>'<li><strong>Hmotnost:</strong> 11,5 kg (údaj z produktové tabulky výše)</li>',
                    '<li><strong>Rozměry:</strong> D x Š x V: 100 x 60 x 110 cm</li>'
                        =>'<li><strong>Rozměry:</strong> transportní 65 × 65 × 12,5 cm (údaj z produktové tabulky výše)</li>',
                    '<li><strong>Materiál:</strong> Hliník a polyester</li>'
                        =>'<li><strong>Materiál a průměr kol:</strong> tyto údaje ověřte u výrobce; produktová tabulka je nedokládá.</li>',
                    '<li><strong>Kola:</strong> 30 cm s protiskluzovou úpravou</li>'=>'',
                    'Nošenost Mivardi Transportního vozíku Premium je až 100 kg, což ho činí ideálním pro převoz většího vybavení.'
                        =>'Produktová tabulka výše uvádí nosnost 120 kg. Hodnotu zkontrolujte v aktuálních podkladech výrobce.',
                ),
            ),
            97537 => array(
                'slug'=>'delphin-vozik-moove-recenze',
                'modified'=>'2025-02-20 11:36:19',
                'name'=>'Delphin MOOVE',
                'scenario'=>'Skládací čtyřkolový vozík na kratší výpravy: jeho menší nosnost není určena pro těžký bivakový náklad.',
                'spec'=>'Nosnost 25 kg a transportní rozměry 67 × 45 × 12,5 cm podle produktové tabulky; nevozí děti.',
                'proof'=>'25kg',
                'removed_href'=>'https://www.uswebsite.com/relevantpost',
                'fixes'=>array(
                    'Delphin Vozík Moove je inovativní prostředek pro pohodlné přepravování dětí a zároveň skvělý společník pro aktivní rodinný život. Díky své lehkosti, kompaktnosti a bezpečnosti si získal srdce mnoha rodičů.'
                        =>'Delphin MOOVE je skládací vozík určený k přepravě rybářské výbavy, nikoli osob nebo dětí.',
                    'V tomto článku se podrobně podíváme na všechny výhody, které tento vozík nabízí a jakým způsobem usnadňuje každodenní život rodin s malými dětmi.'
                        =>'Před nákupem se zaměřte na nosnost, počet kol, průchodnost terénu a přepravní rozměry.',
                    'Maximální nosnost vozíku Moove je 150 kg.'
                        =>'V produktové tabulce výše je uvedena nosnost 25 kg. Před zatížením ji ověřte u výrobce nebo prodejce.',
                    '<p>Pro více informací navštivte <a href="https://www.uswebsite.com/relevantpost">relevantní článek</a>.</p>'
                        =>'<p>Před nákupem ověřte dostupnost náhradních dílů, rozměry po složení a skutečnou nosnost.</p>',
                    '<li>Delphin Vozík Moove je multifunkční vozík, který umožňuje snadné přepravování dětí a nákupů.</li>'
                        =>'<li>Delphin MOOVE je určen pro převoz výbavy, nikoli pro přepravu dětí.</li>',
                    '<li>Vozík je vyroben z kvalitních materiálů a disponuje odnímatelným sedadlem pro děti.</li>'
                        =>'<li>Podle popisu má vyjímatelnou textilní úložnou část; sedadlo pro děti není doloženo.</li>',
                    '<li>Moove je ideálním doplňkem pro rodiny s malými dětmi, kteří potřebují pohodlný a bezpečný způsob přepravy svých potomků.</li>'
                        =>'<li>Před použitím dodržte nosnost a určení vozíku uvedené výrobcem.</li>',
                ),
            ),
        );
    }

    private static function selected() {
        if (!function_exists('get_queried_object_id') || !function_exists('is_singular')
            || !is_singular('product') || !function_exists('is_admin') || is_admin()
            || !function_exists('is_preview') || is_preview()
            || !function_exists('home_url') || rtrim(home_url('/'), '/')!=='https://na-ryby.eu'
            || !function_exists('get_post') || !function_exists('get_permalink')) return false;
        $id=(int)get_queried_object_id(); $sets=self::cohorts();
        if (!isset($sets[$id])) return false;
        $set=$sets[$id]; $p=get_post($id);
        if (!is_object($p) || (int)($p->ID??0)!==$id
            || ($p->post_type??'')!=='product' || ($p->post_status??'')!=='publish'
            || ($p->post_name??'')!==$set['slug']
            || ($p->post_modified_gmt??'')!==$set['modified']
            || get_permalink($id)!=='https://na-ryby.eu/produkt/'.$set['slug'].'/') return false;
        $set['id']=$id;
        return $set;
    }

    private static function hrefs($html) {
        if (preg_match_all('~<a\b[^>]*\bhref\s*=\s*(["\x27])([^"\x27]*)\1~is',$html,$m)===false) return null;
        return $m[2];
    }

    private static function panel($set) {
        $links='';
        foreach (self::cohorts() as $id=>$other) {
            if ($id===$set['id']) continue;
            $links.='<li><a href="https://na-ryby.eu/produkt/'.$other['slug'].'/">'
                .htmlspecialchars($other['name'],ENT_QUOTES,'UTF-8').'</a></li>';
        }
        return '<aside class="nry-barrow-buyer" '.self::MARKER.' aria-label="Pomůcka k výběru rybářského vozíku">'
            .'<p class="nry-barrow-buyer__label">Výběr podle použití, ne podle hvězdiček</p>'
            .'<h3>Hodí se tento vozík k vašemu místu lovu?</h3>'
            .'<p>'.htmlspecialchars($set['scenario'],ENT_QUOTES,'UTF-8').'</p>'
            .'<p><strong>Údaje k ověření:</strong> '.htmlspecialchars($set['spec'],ENT_QUOTES,'UTF-8').'</p>'
            .'<ul class="nry-barrow-buyer__checks"><li>Změřte šířku nejužšího průchodu a místo v kufru.</li>'
            .'<li>Zvažte povrch cesty, průměr a počet kol, těžiště a jištění nákladu.</li>'
            .'<li>Nosnost, dostupnost a cenu ověřte v aktuální nabídce prodejce; redakce vozík netestovala.</li></ul>'
            .'<p><strong>Porovnat další vozíky:</strong></p><ul class="nry-barrow-buyer__links">'.$links.'</ul>'
            .'</aside>';
    }

    public static function transform($html,$set) {
        if (!is_string($html) || !is_array($set) || strlen($html)<4500
            || strpos($html,self::MARKER)!==false
            || substr_count($html,self::RATING)!==1
            || substr_count($html,$set['proof'])<1) return $html;
        $old_links=self::hrefs($html);
        if ($old_links===null || count($old_links)<2) return $html;
        $next=str_replace(self::RATING,'',$html);
        foreach ($set['fixes'] as $old=>$new) {
            if (substr_count($next,$old)!==1) return $html;
            $next=str_replace($old,$new,$next);
        }
        $next=self::panel($set).$next;
        $new_links=self::hrefs($next);
        if ($new_links===null) return $html;
        $expected=array();
        foreach (self::cohorts() as $id=>$other) {
            if ($id!==$set['id']) $expected[]='https://na-ryby.eu/produkt/'.$other['slug'].'/';
        }
        $preserved=$old_links;
        if (!empty($set['removed_href'])) {
            // One obsolete, unrelated placeholder link was explicitly removed;
            // affiliate and all other original hrefs must remain byte-identical.
            if (count(array_keys($preserved,$set['removed_href'],true))!==1) return $html;
            $preserved=array_values(array_filter($preserved,function($href) use ($set) {
                return $href!==$set['removed_href'];
            }));
        }
        if (count($new_links)!==count($preserved)+3
            || array_slice($new_links,0,3)!==$expected
            || array_slice($new_links,3)!==$preserved) return $html;
        return $next;
    }

    public static function render($html) {
        $set=self::selected();
        return $set ? self::transform($html,$set) : $html;
    }

    public static function enqueue() {
        if (self::selected() && function_exists('wp_enqueue_style') && function_exists('plugins_url')) {
            wp_enqueue_style('nry-barrow-buyer-v1',plugins_url('assets/nry-barrow-buyer-v1.css',__FILE__),array(),'1.0.0');
        }
    }

    public static function boot() {
        if (function_exists('is_singular') && function_exists('add_filter')) {
            add_filter('the_content',array(__CLASS__,'render'),79);
        }
        if (function_exists('is_singular') && function_exists('add_action')) {
            add_action('wp_enqueue_scripts',array(__CLASS__,'enqueue'),31);
        }
    }
}
NRY_Barrow_Buyer_Trust_V1::boot();
