#!/usr/bin/env node
// Serialize one fixed offline release candidate. This never writes manifest.json or WordPress.
import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {readFileSync} from 'node:fs';

const evidence = JSON.parse(readFileSync(new URL('../editorial/fishing-stays-cz-20261118.json', import.meta.url), 'utf8'));
const html = readFileSync(new URL('../editorial/fishing-stays-cz-20261118-recommendation-blocks.html', import.meta.url), 'utf8');
const match = html.match(/<section data-nry-editorial-edit="fishing-stays-cz-59891-20261118-v1"[\s\S]*?<\/section>/);
assert(match, 'primary source-checked recommendation section missing');
const post = evidence.target_pages.find(page => page.post_id === 59891);
const binding = post?.authenticated_source_binding;
assert(binding, 'authenticated source binding required');
assert.equal(binding.candidate_block_sha256, createHash('sha256').update(match[0]).digest('hex'));
assert.equal(binding.candidate_block_bytes, Buffer.byteLength(match[0]));
assert.equal(binding.release_state, 'needs_private_wordpress_rollback_authorized_external_link_guard_and_public_readback');

const candidate = {
  schema_version: 9,
  batch_id: 'fishing-stays-cz-59891-affiliate-20261118-v1',
  site: evidence.site,
  edits: [{
    post_id: post.post_id,
    slug: new URL(post.url).pathname.slice(1, -1),
    source_modified_gmt: post.source_modified_gmt.replace('T', ' '),
    source_min_bytes: binding.raw_post_content_bytes,
    source_max_bytes: binding.raw_post_content_bytes + 1,
    source_sha256: binding.raw_post_content_sha256,
    edit_id: binding.candidate_block_marker,
    placement: 'prepend',
    insert_html: match[0]
  }]
};

assert.equal(candidate.edits.length, 1);
// Explicit one-shot stdout contract for test/review; NEVER swaps the active manifest.
if (process.argv.slice(2).join(' ') !== '--stdout') {
  console.error('Usage: node scripts/build-fishing-stays-cz-manifest-v9.mjs --stdout (review only; does not publish)');
  process.exitCode = 2;
} else {
  process.stdout.write(`${JSON.stringify(candidate, null, 2)}\n`);
}
