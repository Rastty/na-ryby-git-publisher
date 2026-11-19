#!/usr/bin/env python3
"""Offline exact-meta semantic contract, no production HTTP in regression tests."""
import importlib.util
from html import escape
from pathlib import Path

source = Path(__file__).resolve().parents[1] / 'scripts/verify-romantic-stay-meta-public.py'
spec = importlib.util.spec_from_file_location('romantic_meta', source)
romantic = importlib.util.module_from_spec(spec)
spec.loader.exec_module(romantic)


def document(description, og=None, title=romantic.TITLE, canonical=romantic.URL,
             robots='index, follow'):
    return ('<html><head><title>' + escape(title) + '</title>'
            + '<meta name="robots" content="' + escape(robots) + '">'
            + '<meta name="description" content="' + escape(description, quote=True) + '">'
            + '<meta property="og:description" content="'
            + escape(og if og is not None else description, quote=True) + '">'
            + '<link rel="canonical" href="' + escape(canonical, quote=True) + '">'
            + '</head></html>')


def rejects(html, preflight=False):
    try:
        romantic.verify(html, preflight)
    except AssertionError:
        return
    raise AssertionError('unsafe readback passed')


assert romantic.verify(document(romantic.OLD), preflight=True) == 'BASELINE_PUBLIC_VERIFIED'
assert romantic.verify(document(romantic.NEW)) == 'UPDATED_META_PUBLIC_VERIFIED'
rejects(document(romantic.OLD))
rejects(document(romantic.NEW), preflight=True)
rejects(document(romantic.NEW, og=romantic.OLD))
rejects(document(romantic.NEW, title='Changed title'))
rejects(document(romantic.NEW, canonical='https://wrong.example/'))
rejects(document(romantic.NEW, robots='noindex,nofollow'))
rejects(document(romantic.NEW).replace('rel="canonical"', 'rel="alternate"'))
rejects(document(romantic.NEW).replace('</head>',
       '<meta name="description" content="duplicate"></head>'))
print('PASS romantic exact baseline vs new meta, og, title, canonical, noindex guards')
