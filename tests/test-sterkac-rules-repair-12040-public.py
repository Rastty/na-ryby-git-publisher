#!/usr/bin/env python3
"""Offline-only public verifier contract; no site or merchant requests."""
import runpy
from pathlib import Path

mod = runpy.run_path(str(Path(__file__).resolve().parents[1]
                         / 'scripts/verify-sterkac-rules-repair-12040-public.py'))
post = {'id': 12040, 'slug': 'soukromy-revir-sterkac-zajeci',
        'status': 'publish', 'modified_gmt': '2026-04-14T10:33:15',
        'link': mod['PAGE']}
article = (mod['ARTICLE_HEAD'] + '<section ' + mod['MARKER'] + '>'
           + '500 Kč. Noční povolenku nelze koupit samostatně. '
           + 'Lov dravců je celoročně zakázán. '
           + 'https://vinarstviukaplicky.cz/rybarina/ tel:+420725924195 '
           + 'platný český rybářský lístek</section>'
           + '<p>Bezpečné aktuální rady.</p>' + mod['ARTICLE_END'] + 'footer</div>')
verify = mod['verify']
assert verify(article, post)['status'] == 'STERKAC_RULES_LIVE_VERIFIED'


def reject(page=article, data=post):
    try:
        verify(page, data)
    except (AssertionError, KeyError, ValueError):
        return
    raise AssertionError('accepted visitor/source drift')


reject(article.replace('500 Kč', '300 Kč'))
reject(article.replace(mod['MARKER'], ''))
reject(article.replace('Lov dravců je celoročně zakázán', ''))
reject(article.replace('</section>', 'bez protihrotu</section>'))
reject(article.replace('tel:+420725924195', ''))
reject(data={**post, 'modified_gmt': '2026-04-15T00:00:00'})
print('PASS: Sterkac staged public readback contract is fail-closed')
