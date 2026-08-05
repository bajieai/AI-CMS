import requests
resp = requests.get('http://aicms.test/page/7')
# Find the template name from HTML comment or body class
import re
# Search for "公司简介" in context
for m in re.finditer(r'公司简介', resp.text):
    start = max(0, m.start()-100)
    end = min(len(resp.text), m.end()+100)
    print(f'Context: {resp.text[start:end]}')
    print('---')
    break
