with open('app/home/controller/CommentController.php', 'r', encoding='utf-8') as f:
    content = f.read()
idx = content.find('comment_enabled')
print(content[idx:idx+100] if idx >= 0 else 'comment_enabled NOT FOUND')
