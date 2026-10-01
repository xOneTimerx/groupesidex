#!/usr/bin/env python3
# Affiche un relevé survey.js filtré par plage de y : python3 show.py f.json ymin ymax [motif]
import json, sys, re
d = json.load(open(sys.argv[1])); y0, y1 = int(sys.argv[2]), int(sys.argv[3]); pat = sys.argv[4] if len(sys.argv) > 4 else ''
short = {'font-family':'ff','font-size':'fs','font-weight':'fw','line-height':'lh','letter-spacing':'ls','text-transform':'tt','color':'c',
 'background-color':'bg','background-image':'bgi','padding':'p','margin':'m','border':'bd','border-radius':'br','display':'d','gap':'gap',
 'justify-content':'jc','align-items':'ai','max-width':'maxw','position':'pos','text-align':'ta','text-decoration-line':'td','box-shadow':'sh',
 'opacity':'op','object-fit':'of','grid-template-columns':'gtc','flex-direction':'fd'}
def col(v):
    m = re.match(r'rgba?\((\d+), (\d+), (\d+)(?:, ([\d.]+))?\)', v)
    if not m: return v
    h = '#%02x%02x%02x' % tuple(int(x) for x in m.groups()[:3])
    return h + (f'/{m.group(4)}' if m.group(4) else '')
for r in d['rows']:
    if not (y0 <= r['y'] < y1): continue
    line = f"{r['tag']}#{r['id'] or '-'} .{r['cls'][:40]} [{r['x']},{r['y']} {r['w']}x{r['h']}]"
    if pat and not re.search(pat, line + r['text']): continue
    s = ' '.join(f"{short.get(k,k)}={col(v) if 'color' in k or k=='border' else v}" for k, v in r['s'].items() if k != 'font-family' or 'Lato' not in v)
    t = f' "{r["text"]}"' if r['text'] else ''
    src = f" src={r['src']}" if r['src'] else ''
    print(line + t + src + '  ' + s.replace('rgb(', '(').replace('Lato, sans-serif', ''))
print('hauteur page', d['height'])
