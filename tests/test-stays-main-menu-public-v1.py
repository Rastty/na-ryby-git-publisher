#!/usr/bin/env python3
"""Offline public parser fixtures, including compromised/duplicated menu variants."""
import importlib.util
from pathlib import Path
p=Path(__file__).resolve().parents[1]/'scripts/verify-stays-main-menu-public-v1.py'
s=importlib.util.spec_from_file_location('nry_stays_menu_public',p)
m=importlib.util.module_from_spec(s);s.loader.exec_module(m)

def doc(routes=None,parent=None,marker=True,nested=True,script=None):
    if routes is None:routes=m.ROUTES
    if parent is None:parent=m.PARENT
    children=''.join('<li class="menu-item '+(m.MARKER if marker else '')+'">'
                     '<a href="'+href+'">'+title+'</a></li>'
                     for title,href in routes)
    parent_node=('<li class="menu-item menu-item-has-children">'
                 '<a href="'+parent[1]+'">'+parent[0]+'</a>')
    if nested:parent_node+='<ul class="sub-menu">'+children+'</ul>'
    parent_node+='</li>'
    if not nested:parent_node+=children
    return '<html><body>'+'x'*2600+'<nav><ul>'+parent_node+'</ul></nav>'+\
      '<script src="'+(script or m.JS)+'"></script></body></html>'

def rejected(body):
    try:m.verify_html(body)
    except ValueError:return
    raise AssertionError('unsafe/missing public nav accepted')

assert m.verify_html(doc())['menuInstances']==1
single=doc()
menu_markup='<nav>'+single.split('<nav>',1)[1].split('</nav>',1)[0]+'</nav>'
assert m.verify_html(single.replace('</nav>', '</nav>'+menu_markup,1))['menuInstances']==2
rejected(doc(nested=False))
rejected(doc(marker=False))
rejected(doc(routes=m.ROUTES[:1]))
rejected(doc(routes=m.ROUTES+(m.ROUTES[0],)))
rejected(doc(routes=tuple(reversed(m.ROUTES))))
rejected(doc(routes=((m.ROUTES[0][0],'https://merchant.example/click'),m.ROUTES[1])))
rejected(doc(routes=((m.ROUTES[0][0],m.ROUTES[0][1]+'?utm=wrong'),m.ROUTES[1])))
rejected(doc(parent=('Ubytování','https://na-ryby.eu/ubytovani-2/')))
rejected(doc(parent=('Pobyty',m.PARENT[1])))
rejected(doc(script=m.ORIGIN+'/wrong.js'))
rejected('<a href="'+m.PARENT[1]+'">Ubytování</a>'
        +'<a href="'+m.ROUTES[0][1]+'">Cyklochata Vranov</a>')
rejected(doc().replace('</body>','<script src="'+m.JS+'"></script></body>'))
print('STAYS_SITEWIDE_MENU_PUBLIC_NEGATIVE_CONTRACT_VERIFIED')
