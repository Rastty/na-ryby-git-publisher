#!/usr/bin/env python3
"""Offline negative public readback cases; no network or state mutation."""
import runpy
from pathlib import Path

root = Path(__file__).resolve().parents[1]
m = runpy.run_path(str(root / 'scripts/verify-orlik-expired-stay-public-119551.py'))
verify_public = m['verify_public']
SITE, PAGE = m['SITE'], m['PAGE']
CATEGORY, EXPIRED, HPORTAL = m['CATEGORY'], m['EXPIRED'], m['HPORTAL']
prefix = ('<link rel="canonical" href="' + SITE + PAGE + '">'
          '<body class="single postid-119551"><h1>Kde přespat u Orlíku na rybářský víkend</h1>')
old = (prefix + '<a href="' + CATEGORY + '">Zjistit cenu a termín</a>'
       '<a href="' + EXPIRED + '">Projít další možnost</a>')
ready = verify_public(old, deployed=False)
assert ready['status'] == 'ORLIK_EXPIRED_STAY_119551_PREFLIGHT_READY'
assert ready['expiredCTAStillLive'] and ready['syntheticClicks'] == 0
new = (prefix + '<a href="' + CATEGORY + '">Prohlédnout pobyty ve středních Čechách</a>'
       '<a href="' + HPORTAL.replace('&','&amp;') + '" target="_blank"'
       ' rel="sponsored nofollow noopener">Liškův Mlýn u Orlíku – prověřit pobyt</a>'
       '<span data-nry-orlik-stay="v1">První vede na širší nabídku pobytů ve středních Čechách. '
       'Rezervace noclehu automaticky nezahrnuje rybářský lístek</span>')
done = verify_public(new, deployed=True)
assert done['status'] == 'ORLIK_EXPIRED_STAY_119551_PUBLIC_VERIFIED'
assert done['completedReservations'] is None


def blocked(html):
    try:
        verify_public(html, deployed=True)
    except AssertionError:
        return
    raise AssertionError('DRIFT_NOT_BLOCKED')


blocked(new.replace(HPORTAL.replace('&','&amp;'), EXPIRED))
blocked(new.replace('sponsored nofollow noopener', 'nofollow'))
blocked(new.replace('data-nry-orlik-stay="v1"', 'data-nry-orlik-stay="v2"'))
blocked(new.replace('První vede na širší nabídku', 'Druhý vede na širší nabídku'))
blocked(new.replace('postid-119551','postid-119552'))
blocked(new + '<span data-nry-orlik-stay="v1"></span>')

# Never claim deployment from a push-only publisher contract run. The public
# verification is eligible only after an owner-dispatched guarded FTPS release.
workflow = (root / '.github/workflows/orlik-expired-stay-119551.yml').read_text(encoding='utf-8')
assert "github.event.workflow_run.event == 'workflow_dispatch'" in workflow
assert "github.event.workflow_run.event == 'push'" not in workflow

print('ORLIK_EXPIRED_STAY_119551_PUBLIC_PARSER_OK')
