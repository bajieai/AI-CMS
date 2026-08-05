import requests, re
resp = requests.get('http://aicms.test/page/7')
idx = resp.text.find('breadcrumb-item active')
print(resp.text[max(0,idx-200):idx+100])
print('---')
idx2 = resp.text.find('<h1')
print(resp.text[idx2:idx2+100])
