import requests, re
s = requests.Session()
resp = s.get('http://aicms.test/news/11')
token = re.search(r'meta name="csrf-token" content="([^"]*)"', resp.text)
csrf = token.group(1) if token else ''
resp = s.post('http://aicms.test/home/comment/submit', 
    data={'content_id': '11', 'content': 'test', 'nickname': 'test', '__token__': csrf},
    headers={'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest'}
)
print(resp.text)
