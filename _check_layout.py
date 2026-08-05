with open('template/themes/default/pc/layout.html', 'r', encoding='utf-8') as f:
    content = f.read()
idx = content.find('{block name="js"}')
print('Block js at:', idx)
if idx >= 0:
    print(content[idx:idx+500])
