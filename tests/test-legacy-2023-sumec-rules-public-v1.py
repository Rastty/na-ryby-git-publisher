"""No-network exact DOM positive and fail-closed fixtures."""
from pathlib import Path
import runpy
m=runpy.run_path(str(Path(__file__).resolve().parents[1]/
 'scripts/verify-legacy-2023-sumec-rules-public-v1.py'))
def page(role):
 c=m['PAGES'][role]
 marker='<section data-nry-legacy-decision="v1" data-nry-legacy-role="'+role+'">'
 anchors=''.join('<a href="'+url+'">Ověřit</a>' for url in c['links'])
 note='<h2>'+c['required'][0]+'</h2><p>'+c['required'][1]+'</p>'
 original='<main><p>'+' | '.join(c['old'])+'</p>'+('<p>Historické revíry a povolenky.</p>'*140)+'</main>'
 css='<link rel="stylesheet" href="'+m['BASE']+'wp-content/plugins/na-ryby-git-publisher/assets/'+m['CSS']+'?ver=1.0.0">'
 return '<html><head><link rel="canonical" href="'+c['url']+'">'+css+'</head><body>'+marker+note+anchors+'</section>'+original+'</body></html>'
def reject(html,role):
 try:m['verify'](html,role)
 except ValueError:return
 raise AssertionError('unsafe public fixture wrongly accepted')
for role in m['PAGES']:
 html=page(role);c=m['PAGES'][role]
 assert m['verify'](html,role)['state']=='NRY_LEGACY_2023_DECISION_PUBLIC_VERIFIED'
 reject(html.replace(m['MARKER'],'data-nry-legacy-decision="v2"'),role)
 reject(html.replace('data-nry-legacy-role="'+role+'"','data-nry-legacy-role="wrong"'),role)
 reject(html.replace(c['url'],'https://invalid.example/',1),role)
 reject(html.replace(m['CSS'],'invalid.css'),role)
 reject(html.replace('ver=1.0.0','ver=1.0.9'),role)
 reject(html.replace(c['links'][0],'https://invalid.example/',1),role)
 reject(html.replace(c['required'][0],'wrong claim',1),role)
 reject(html.replace(c['old'][0],'missing original',1),role)
 reject(html.replace('</body>','<section '+m['MARKER']+'></section></body>'),role)
 reject(html.replace('</head>','<link rel="canonical" href="'+c['url']+'"></head>'),role)
print('PASS TWO_2023_PUBLIC_SOURCE_GUARDS_OFFLINE')
