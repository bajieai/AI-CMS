with open('app/home/controller/CateController.php', 'r', encoding='utf-8') as f:
    content = f.read()
idx = content.find('singlePage')
end = content.find('}', idx + 1500)
print(content[idx:end+5])
