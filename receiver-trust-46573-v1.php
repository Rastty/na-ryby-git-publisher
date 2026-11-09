<?php
/**
 * Single existing buyer guide #46573: factual, source-locked receiver selection.
 * Offline-reviewed from private Core #1588 source; do not commit source HTML.
 * Applies only when entire original WP post matches exact current SHA/bytes.
 * No affiliate URL changes, no post creation, no SEO metadata mutation.
 */
if (!defined('ABSPATH')) { exit; }

final class NRY_Receiver_Trust_46573 {
    const ID = 46573;
    const SOURCE_SHA = '4556c2db67cdae9dacc7b60300abada57e29a7d7fb1bf93b88e253b6222f62f4';
    const CANDIDATE_SHA = '99f77994dfc1a5716b713b21cf51dd96989c79ed53266518e9cff24bdd997e23';
    const SOURCE_BYTES = 10229;
    const SOURCE_MODIFIED = '2023-05-12 15:13:13';
    const CANONICAL = 'https://na-ryby.eu/pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare/';
    const SLUG = 'pipaky-na-ryby-s-priposlechem-ucinna-pomucka-pro-kazdeho-rybare';
    const MARKER = 'data-nry-receiver-guide="source-bound-46573-20261109-v1"';
    const BACKUP = 'nry_receiver_46573_20261109_original';
    const LOCK = 'nry_receiver_46573_20261109_lock';
    const RECEIPT = 'nry_receiver_46573_20261109_receipt';

    public static function boot() {
        add_action('wp_loaded', array(__CLASS__, 'run'), 46);
    }

    private static function line_edits() {
        $json=<<<'NRY_RECEIVER_EDITS'
{
  "3": "<h2>Hlásiče záběru s příposlechem: jak vybrat kompatibilní sadu</h2><span hidden data-nry-receiver-guide=\"source-bound-46573-20261109-v1\"></span>",
  "5": "<p><strong>Stručně:</strong> Elektronický hlásič záběru upozorňuje na pohyb vlasce v místě snímače. Příposlech (přijímač) přenáší upozornění od kompatibilních hlásičů na jiné místo. Sám o sobě nepotvrzuje, že je ryba na háčku, ani nezvyšuje počet záběrů.</p>",
  "7": "<p><strong>Rychlá volba:</strong> pro jeden prut může stačit samotný hlásič. Při lovu na dva nebo tři pruty zvažte kompletní sadu 2+1 nebo 3+1 (dva či tři hlásiče a jeden přijímač). Počet prutů a způsob lovu vždy přizpůsobte místním pravidlům.</p>",
  "9": "<p>Před nákupem ověřte, zda jsou hlásiče a přijímač součástí stejné kompatibilní řady. Příposlech různých výrobců zpravidla nelze libovolně spárovat. Zjistěte také typ napájení, nastavitelnost citlivosti, hlasitosti, tónu a signalizaci zpětného pohybu vlasce, pokud ji potřebujete.</p>",
  "11": "<p>Dosah přijímače uvedený výrobcem bývá měřen v ideálních podmínkách. U vody jej mohou snížit překážky a rušení. Sadu proto po sestavení vyzkoušejte na obvyklou vzdálenost; hlásič nenahrazuje dohled nad pruty a bezpečné zacházení s vybavením.</p>",
  "16": "<h2>Jak funguje hlásič a bezdrátový příposlech</h2>",
  "20": "<p>Hlásič bývá umístěn na stojanu či vidličce pod prutem a při posunu vlasce přes snímač spustí zvukové nebo světelné upozornění. Kompatibilní přijímač může stejnou událost ohlásit bezdrátově. Zařízení nepozná druh ryby ani to, zda byla ryba skutečně zaseknuta.</p>",
  "24": "<p>Příposlech zvuk mechanicky nezesiluje: přijímá signál z hlásiče. Citlivost nastavte podle větru, proudu, napnutí vlasce a použitého swingeru nebo jiného indikátoru. Zvuk, světlo a případnou vibraci přijímače vyzkoušejte před začátkem lovu.</p>",
  "26": "<h3>Co signalizátor pozná — a co ne</h3>",
  "28": "<p>Hlásič reaguje na změnu pohybu vlasce, nikoli na samotnou přítomnost ryby u nástrahy. Falešný poplach může způsobit vítr, proud nebo manipulace se sestavou. Reagujte podle situace u prutu; zvukový signál sám o sobě nezaručuje úlovek.</p>",
  "30": "<h3>Jak vybrat sadu 2+1 nebo 3+1</h3>",
  "32": "<p>Nejdřív si určete počet používaných prutů. Potom ověřte shodný komunikační systém hlásičů a přijímače, možnost rozlišit jednotlivé pruty, baterie, odolnost proti dešti a způsob párování. Větší deklarovaný dosah není automaticky lepší volba, pokud jej ve svých podmínkách nevyužijete.</p>",
  "34": "<h3>Praktické doporučení před nákupem</h3>",
  "36": "<p>U vybraného modelu si stáhněte návod a potvrďte kompatibilitu všech součástí. Srovnávejte funkce, které skutečně potřebujete, nikoli sliby o vyšším počtu úlovků. Pro přímé rozhodnutí mezi samostatným hlásičem a sadou s přijímačem použijte také <a href=\"https://na-ryby.eu/nejlepsi-signalizatory-zaberu-jak-vybrat-spravny-hlasic-nebo-sadu/\">průvodce výběrem signalizátorů záběru</a>.</p>",
  "47": "<p>Jde o hlásiče pohybu vlasce doplněné kompatibilním bezdrátovým přijímačem. Přijímač upozornění přenáší, ale nepotvrzuje zaseknutou rybu ani úspěšný zásek.</p>",
  "50": "<p>Pomáhají všimnout si pohybu vlasce i ve chvíli, kdy nestojíte bezprostředně u zvukového hlásiče. Nezaručují více úlovků a nenahrazují povinný dohled nad pruty.</p>",
  "53": "<p>Hlásič upevněte na stojan pod prut, správně veďte vlasec přes snímač a podle návodu spárujte přijímač. Před lovem vyzkoušejte pohyb vlasce, hlasitost, citlivost i rozlišení prutů na přijímači.</p>",
  "56": "<p>Vyberte specializovaného prodejce a ověřte přesnou modelovou řadu, obsah sady, typ baterií a servisní podmínky. U doplňkového přijímače vyžadujte výslovně potvrzenou kompatibilitu s vašimi hlásiči.</p>",
  "61": "<h2>Nastavení u vody: citlivost, dosah a falešné poplachy</h2>",
  "63": "<p>Signalizátor sleduje pohyb vlasce a může upozornit zvukem či světlem. Příposlech umožní toto upozornění přijmout na kompatibilním přijímači, ale nepozná, zda se ryba opravdu zasekla. Používejte jej jako pomůcku při kontrole prutů.</p>",
  "65": "<h3>Jak omezit falešné poplachy?</h3>",
  "67": "<p>Vliv mají napnutí vlasce, vítr, proud i správné vedení vlasce snímačem. Začněte střední citlivostí a podle situace ji upravte; při neopodstatněných signálech nejprve zkontrolujte sestavu.</p>",
  "69": "<p>Dosah a spolehlivost příposlechu si ověřte na místě a s bateriemi v dobrém stavu. Mezi hlásičem a přijímačem mohou být překážky; žádná sada nezaručuje spolehlivé upozornění za všech podmínek.</p>",
  "73": "<p>Hlásič musí být pevně připevněn na stojanu a vlasec musí správně procházet snímačem. Zkontrolujte umístění prutu, indikátoru napnutí a chod brzdy navijáku podle způsobu lovu.</p>",
  "75": "<p>Nastavte přiměřenou hlasitost, abyste upozornění slyšeli a současně nerušili okolí. Přijímač před použitím otestujte na vzdálenost, ve které jej skutečně budete nosit.</p>",
  "77": "<p>Krátký test pohybem vlasce a ověření baterií před každým lovem pomohou odhalit špatné párování nebo slabé napájení. Respektujte místní pravidla a nenechávejte pruty bez potřebného dozoru.</p>",
  "84": "<h2>Kontrolní seznam před výběrem příposlechu</h2>",
  "85": "<h3>Co sestava skutečně dělá</h3>",
  "87": "  <li> Hlásič upozorňuje na pohyb vlasce, nikoli na přítomnost ryby u nástrahy. </li>",
  "88": "  <li> Zvukové a světelné signály pomáhají rozpoznat změnu pohybu vlasce. </li>",
  "89": "  <li> Kompatibilní přijímač předává upozornění bezdrátově; dosah závisí na podmínkách. </li>",
  "92": "<h3>Komu může příposlech usnadnit lov</h3>",
  "94": "  <li> Při lovu na více povolených prutů umožní rozlišit signály podle konkrétního hlásiče. </li>",
  "95": "  <li> Pomůže zaznamenat upozornění, i když samotný hlásič není dobře slyšet. </li>",
  "96": "  <li> Neznamená to povolení nechat pruty bez dozoru; dodržujte pravidla revíru. </li>",
  "99": "<h3>Co zkontrolovat před zaplacením</h3>",
  "101": "  <li> Nastavení citlivosti, hlasitosti, tónu a případně optické signalizace. </li>",
  "102": "  <li> Pro dvě či tři udice hledejte sadu 2+1 nebo 3+1 místo více hlásičů na jeden prut. </li>",
  "103": "  <li> Shodu přijímače s hlásiči, napájení, odolnost vůči počasí a reálný dosah. </li>"
}
NRY_RECEIVER_EDITS;
        return json_decode($json, true);
    }

    public static function build_candidate($source) {
        if (!is_string($source) || strlen($source)!==self::SOURCE_BYTES
            || hash('sha256', $source)!==self::SOURCE_SHA) return null;
        $lines=explode("\n", $source);
        $changes=self::line_edits();
        if (!is_array($changes) || count($changes)!==39) return null;
        foreach ($changes as $ordinal=>$replacement) {
            $idx=(int)$ordinal-1;
            if (!is_int($ordinal) && !ctype_digit((string)$ordinal)) return null;
            if ($idx<0 || !array_key_exists($idx,$lines)
                || !is_string($lines[$idx]) || trim($lines[$idx])===''
                || !is_string($replacement) || trim($replacement)==='')
                return null;
            $lines[$idx]=$replacement;
        }
        $candidate=implode("\n", $lines);
        if (hash('sha256',$candidate)!==self::CANDIDATE_SHA
            || substr_count($candidate,self::MARKER)!==1) return null;
        return $candidate;
    }

    private static function safe_write($content) {
        if (!function_exists('wp_slash') || !function_exists('has_filter'))
            return new WP_Error('missing_guarded_writer');
        $removed=array();
        foreach (array(
            array('content_save_pre','wp_filter_post_kses'),
            array('content_save_pre','wp_filter_global_styles_post'),
            array('content_filtered_save_pre','wp_filter_post_kses'),
            array('content_filtered_save_pre','wp_filter_global_styles_post')
        ) as $entry) {
            $priority=has_filter($entry[0],$entry[1]);
            if ($priority!==false) {
                remove_filter($entry[0],$entry[1],$priority);
                $removed[]=array($entry[0],$entry[1],$priority);
            }
        }
        try {
            return wp_update_post(array('ID'=>self::ID,'post_content'=>wp_slash($content)),true);
        } finally {
            foreach($removed as $entry) add_filter($entry[0],$entry[1],$entry[2]);
        }
    }

    private static function receipt($status) {
        update_option(self::RECEIPT,array('status'=>$status,'at'=>gmdate('c')),false);
    }

    public static function run() {
        if (rtrim(home_url('/'),'/')!=='https://na-ryby.eu') return;
        $post=get_post(self::ID);
        if (!$post || (int)$post->ID!==self::ID || $post->post_type!=='post'
            || $post->post_status!=='publish'
            || $post->post_name!==self::SLUG
            || get_permalink(self::ID)!==self::CANONICAL
            || !is_string($post->post_content)) return;
        $hash=hash('sha256',$post->post_content);
        if ($hash===self::CANDIDATE_SHA || $hash!==self::SOURCE_SHA
            || strlen($post->post_content)!==self::SOURCE_BYTES
            || $post->post_modified_gmt!==self::SOURCE_MODIFIED) return;
        $lock=wp_generate_uuid4();
        if (!add_option(self::LOCK,$lock,'','no')) return;
        try {
            $post=get_post(self::ID);
            if (!$post || hash('sha256',$post->post_content)!==self::SOURCE_SHA
                || $post->post_modified_gmt!==self::SOURCE_MODIFIED) return;
            $source=$post->post_content;
            $candidate=self::build_candidate($source);
            if ($candidate===null) { self::receipt('candidate_mismatch_no_write'); return; }
            $backup=array('id'=>self::ID,'source_sha'=>self::SOURCE_SHA,'source'=>$source);
            if (!add_option(self::BACKUP,$backup,'','no')
                && get_option(self::BACKUP,null)!==$backup) {
                self::receipt('immutable_backup_conflict_no_write'); return;
            }
            if (get_option(self::BACKUP,null)!==$backup) {
                self::receipt('backup_readback_failed_no_write'); return;
            }
            $fresh=get_post(self::ID);
            if (!$fresh || hash('sha256',$fresh->post_content)!==self::SOURCE_SHA
                || $fresh->post_modified_gmt!==self::SOURCE_MODIFIED) {
                self::receipt('concurrent_editor_no_write'); return;
            }
            $result=self::safe_write($candidate);
            $after=get_post(self::ID);
            if (!is_wp_error($result) && (int)$result===self::ID
                && $after && hash('sha256',$after->post_content)===self::CANDIDATE_SHA) {
                self::receipt('applied_and_verified_local');
            } else {
                // Lock and immutable backup preserve the original if a server
                // filter or concurrent change prevents exact checked readback.
                // Never overwrite an unexpected editor's source during rollback.
                self::receipt('write_or_readback_failed_manual_review');
            }
        } catch (\Throwable $e) {
            self::receipt('unexpected_exception_manual_review');
        } finally {
            if (get_option(self::LOCK,null)===$lock) delete_option(self::LOCK);
        }
    }
}
NRY_Receiver_Trust_46573::boot();
