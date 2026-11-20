#!/usr/bin/env python3
"""Offline guard against unrelated public HTTP audit bursts on every plugin PR.

Specialized workflows must run for their own module/test changes, not whenever
the common plugin bootstrap changes. The guarded production release remains
unchanged; post-deployment checks keep their workflow_run triggers.
"""
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
WORKFLOWS = ROOT / '.github' / 'workflows'
BOOTSTRAP = 'na-ryby-git-publisher.php'
CORE = {
    'guarded-deployer-push.yml',
    'guarded-auto-publisher-contract.yml',
    'v5-php-contract.yml',
    'v8-seo-contract.yml',
}
SCOPED = set('''
advisor-existing-page-contract.yml
baitboat-landing-contract.yml
baitboat-v2-contract.yml
baitboat-v3-contract.yml
baitboat-v6-compromise-contract.yml
bivak-budget-buyer-trust-v1.yml
boat-load-check-v1.yml
boat-motor-click-observation-v1.yml
brno-reservoir-source-proof-17591.yml
brno-reservoir-trust-stays-17591.yml
carp-pillar-choice-public.yml
chair-hub-historical-ratings-public.yml
checklist-v1-release-contract.yml
family-stays-59271-v1.yml
fishing-stays-cta-59891-v1.yml
fishing-stays-cz-affiliate-release-v9.yml
gift-buyer-paths-v1.yml
home-buyer-entry-public-v1.yml
kruzberk-fishing-trust-12877.yml
kruzberk-source-proof-12877.yml
motor-battery-capacity-v1.yml
nry-home-advisor-seo-public.yml
nry-home-public-readback.yml
nry-home-trust-contract.yml
nry-privacy-contact-public-v1.yml
sonar-budget-scenarios-public.yml
sonar-budget-trust-public.yml
sonar-pillar-fast-paths-v1.yml
sonar-pillar-practical-public-v1.yml
sonar-practical-owner-public.yml
sport-stays-offers-85384.yml
sport-stays-source-proof-85384.yml
stredni-cechy-stay-offer-public-92205.yml
stredni-cechy-stay-source-proof-92205.yml
'''.split())


def event_paths(text, event):
    """Read only top-level on.<event>.paths; ignores job commands and YAML text."""
    current = None
    in_paths = False
    paths = []
    for line in text.splitlines():
        if line.startswith('jobs:') or line.startswith('permissions:'):
            break
        if line.startswith('  ') and not line.startswith('    '):
            current = line.strip()[:-1] if line.endswith(':') else None
            in_paths = False
        elif current == event:
            if line == '    paths:':
                in_paths = True
            elif line.startswith('    ') and not line.startswith('      '):
                in_paths = False
            elif in_paths and line.startswith('      - '):
                paths.append(line.split(' - ', 1)[1].strip().strip("'\""))
    return paths


assert len(SCOPED) == 34
assert CORE.isdisjoint(SCOPED)
for filename in SCOPED:
    target = WORKFLOWS / filename
    assert target.is_file(), filename
    source = target.read_text(encoding='utf8')
    pr = event_paths(source, 'pull_request')
    push = event_paths(source, 'push')
    assert pr, f'{filename}: specialized PR coverage lost'
    assert BOOTSTRAP not in pr + push, f'{filename}: broad bootstrap burst trigger'
    assert f'.github/workflows/{filename}' not in pr + push, (
        f'{filename}: workflow-only edit triggers unrelated live GET checks')
    assert any(not p.startswith('tests/') for p in pr), (
        f'{filename}: specialized source trigger missing')

for filename in CORE:
    assert BOOTSTRAP in event_paths(
        (WORKFLOWS / filename).read_text(encoding='utf8'), 'pull_request'
    ), f'{filename}: core bootstrap coverage lost'

guard = (WORKFLOWS / 'guarded-deployer-push.yml').read_text(encoding='utf8')
assert "if: github.event_name != 'pull_request'" in guard
assert "if: github.event_name == 'pull_request'" in guard
assert 'needs: verify' in guard
assert 'environment: na-ryby-production' in guard
assert "vars.NRY_AUTO_DEPLOY_ENABLED == 'true'" in guard
assert "inputs.release_sha == github.sha" in guard
assert "vars.NRY_FTPS_RELEASE_ARMED" not in guard
print('PASS: 34 focused workflows do not fan out on shared plugin updates; '
      'offline PR and protected production gates preserved; public HTTP calls 0')
