with open('app/home/controller/CateController.php', 'r', encoding='utf-8') as f:
    content = f.read()
idx = content.find('singlePage')
if idx >= 0:
    print(content[idx:idx+1500])
else:
    print('NOT FOUND')
