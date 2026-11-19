#!/usr/bin/env python3
"""Offline parser proof of visible offers, exact URLs and source placement."""
import runpy
from pathlib import Path

root=Path(__file__).resolve().parents[1]
m=runpy.run_path(str(root/'scripts/verify-lipno-pair-offers-public-119692.py'))
verify_public=m['verify_public']
site,page=m['SITE'],m['PAGE']
jbx,vyhlidka,stays=m['JBX'],m['VYHLIDKA'],m['STAYS']
start=('<link rel="canonical" href="'+site+page+'">'
       '<body class="single postid-119692"><h1>Rybářský víkend na Lipně pro dva</h1>'
       '<section id="decision-box"></section>')
end='<section id="tabulka"><h2>Tabulka</h2></section>'
ready=verify_public(start+end,deployed=False)
assert ready['status']=='LIPNO_PAIR_119692_PREFLIGHT_READY'
assert ready['plannedMerchantLinks']==2 and ready['wordpressWrites']==0
block=('<section data-nry-lipno-pair="v1"><h2>Partnerské odkazy</h2>'
       '<article data-nry-lipno-pair-offer="jbx"><h3>JBX Resort Albatros</h3>'
       '<a href="'+jbx.replace('&','&amp;')+'" target="_blank"'
       ' rel="sponsored nofollow noopener">JBX</a></article>'
       '<article data-nry-lipno-pair-offer="vyhlidka"><h3>Pension Vyhlídka</h3>'
       '<a href="'+vyhlidka.replace('&','&amp;')+'" target="_blank"'
       ' rel="sponsored nofollow noopener">Vyhlídka</a></article>'
       '<a href="'+stays+'">Pobyty</a><p>Ubytování nezahrnuje povolenku ani právo rybařit</p></section>')
good=start+block+end
done=verify_public(good,deployed=True)
assert done['status']=='LIPNO_PAIR_119692_PUBLIC_VERIFIED'
assert done['completedReservations'] is None


def blocked(text):
    try:
        verify_public(text,deployed=True)
    except AssertionError:
        return
    raise AssertionError('MUTATION_WAS_NOT_BLOCKED')


blocked(good.replace('data-nry-lipno-pair="v1"','data-nry-lipno-pair="v2"'))
blocked(good.replace('data-nry-lipno-pair-offer="jbx"','data-nry-lipno-pair-offer="other"'))
blocked(good.replace('sponsored nofollow noopener','nofollow',1))
blocked(good.replace(jbx.replace('&','&amp;'),stays))
blocked(good.replace('povolenku ani právo rybařit','zahrnuje povolenku'))
blocked(good.replace('postid-119692','postid-119693'))
blocked(start+end+block)
print('LIPNO_PAIR_119692_PUBLIC_PARSER_OK')
