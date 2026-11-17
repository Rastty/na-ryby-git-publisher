"""Offline regression: article copy must not be confused with SEO title text."""
import importlib.util
import sys
import unittest
from pathlib import Path


SCRIPT_DIR = Path(__file__).resolve().parents[1] / 'scripts'
sys.path.insert(0, str(SCRIPT_DIR))
SPEC = importlib.util.spec_from_file_location(
    'gift_proof', SCRIPT_DIR / 'verify-gift-buyer-paths-public.py')
proof = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(proof)


class GiftPublicOrder(unittest.TestCase):
    def test_matching_body_paragraph_ignores_head_title(self):
        title = 'Nejlepší dárky pro rybáře'
        source = '<p>' + title + ': praktické tipy</p>'
        page = ('<title>' + title + '</title><h1>' + title
                + '</h1><nav data-nry-gift-buyer="v1"></nav>' + source)
        self.assertLess(page.index('data-nry-gift-buyer="v1"'),
                        page.index(proof.article_anchor_fragment(source, title).decode()))

    def test_matching_body_heading_ignores_head_title(self):
        anchor = 'Nejzajímavější vánoční dárky'
        source = '<h2>Novinky pro rybáře: ' + anchor + ' na trhu</h2>'
        self.assertEqual(proof.article_anchor_fragment(source, anchor), source.encode())

    def test_escaped_source_text(self):
        self.assertEqual(proof.article_anchor_fragment('<p>Dárky &amp; vybavení</p>',
                                                        'Dárky & vybavení'),
                         b'<p>D\xc3\xa1rky &amp; vybaven\xc3\xad</p>')

    def test_no_body_anchor_rejected(self):
        with self.assertRaisesRegex(RuntimeError, 'SOURCE_ARTICLE_ANCHOR_ELEMENT_NOT_FOUND'):
            proof.article_anchor_fragment('<title>Nejlepší dárky</title>', 'Nejlepší dárky')


if __name__ == '__main__':
    unittest.main()
