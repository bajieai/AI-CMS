import requests

resp = requests.get('http://aicms.test/page/7', headers={'Cache-Control': 'no-cache'})
print(f'Status: {resp.status_code}')
# Find breadcrumb and h1
import re
breadcrumb = re.search(r'breadcrumb-item active">(.*?)<', resp.text)
h1 = re.search(r'<h1[^>]*>(.*?)<', resp.text)
title = re.search(r'<title>(.*?)<', resp.text)
print(f'Title: {title.group(1) if title else "NOT FOUND"}')
print(f'H1: {h1.group(1).strip() if h1 else "NOT FOUND"}')
print(f'Breadcrumb: {breadcrumb.group(1) if breadcrumb else "NOT FOUND"}')
