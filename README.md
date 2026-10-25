# Na-Ryby Git Publisher

Source: curated release from the private Prometheus growth repository.
This public repository contains only PHP plugin code and a bounded editorial manifest.
No WordPress passwords, API tokens, publisher IDs, affiliate credentials or private project files.

Install/update this repository as a WordPress plugin using Deployer for Git.
Branch: main; plugin folder: na-ryby-git-publisher.

Deploying plugin code is NOT equivalent to editing the WordPress database.
The plugin deliberately requires an admin to review the rows at
Tools > Na-Ryby Git Publisher and click Apply once after a new manifest.
No article mutation on activation, Git pull, webhook or plugin update.
Legacy schema v1 is immutable (posts 52975 and 118517 already applied).
Schema v2 supports 1-25 reviewed source-SHA-bound internal guidance inserts.
Schema v3 adds bounded context-specific decisions and plain-text factual corrections.
Schema v4 adds source-and-candidate-SHA-bound structural cleanup in batches of up to 25 posts.
Protected measurement IDs 38064 and 42671 are always excluded.

Future content batches must have exact reviewed IDs, slugs and safe anchors.
No public write endpoints or unattended editorial mutations are enabled.

## V5 — dva nákupní průvodce bez WPVibe

Manifest `buyer-rescue-two-50011-53630-20261025-v1` je uzamčen pouze na články
`50011` (starší kaprové pruty z roku 2020) a `53630` (výběr růžového prutu).

Git Publisher v0.5 získává původní obsah přímo přes `get_post()` **na serveru WordPressu**,
bez WPVibe nebo vnějších REST zápisů. Kontroluje stav publikace, slug, identitu,
datum poslední změny a přesnou bytovou délku původního textu. Jakýkoli nesoulad
blokuje **celou dvoučlánkovou dávku**, nic se nepublikuje. Před zápisem uloží
neměnnou zálohu obou původních HTML textů do neveřejné WP option
`nry_gp_backup_<md5(batch_id)>` (autoload vypnuto). Každý zápis přepočítá SHA
aktuálního serverového zdroje; po `wp_update_post()` ověří SHA výsledku.
V případě neúspěchu se pokusí obnovit původní obsah.

Obsah je jen předřazen na začátek existujícího článku; původní text, affiliate
odkazy, obrázky a shortcodes zůstávají doslova beze změny. Nové odkazy smějí
směřovat pouze na články v `https://na-ryby.eu/`. Žádné skripty, formuláře,
externí cíle, nepovolené HTML, změna URL ani automatický cron publishing.

**Nasazení není publikace.** Po aktualizaci repozitáře WordPress pluginu
z GitHub `main` otevřete ve správě WordPressu
`Nástroje > Na-Ryby Git Publisher`
(`/wp-admin/tools.php?page=nry-git-publisher`).
U obou položek musí být `ready`, až poté klikněte
`Zapsat ověřené změny do WordPressu`.
Po úspěšném zápisu je stav `applied_and_verified`, při dalším zobrazení
obě položky `already_applied`. Při `blocked` nezkoušejte vynucený zápis;
nejdřív zkontrolujte změnu původního článku a schválený manifest.

Zveřejnění v této verzi je úmyslně podmíněno jediným kliknutím administrátora.
