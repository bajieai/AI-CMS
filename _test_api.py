import requests
import hashlib
import time

# 直接通过API登录（在验证码配置关闭的情况下）
s = requests.Session()

# 方法1：直接POST带空captcha（试试看验证码是否必须）
resp = s.post('http://aicms.test/member/login', data={
    'username': 'test2',
    'password': 't123456',
    'captcha': '',
    'captcha_key': '',
    '__token__': ''
}, allow_redirects=False)
print('Login:', resp.status_code, resp.headers.get('location', ''))
print('Set-Cookie:', resp.headers.get('set-cookie', '')[:100])

# Try accessing profile with session
resp = s.get('http://aicms.test/member/profile', allow_redirects=False)
print('Profile:', resp.status_code)
if resp.status_code == 200:
    # Got 500 - show the error page content
    print('Content:', resp.text[:2000])
elif resp.status_code == 302:
    print('Redirect to:', resp.headers.get('location', ''))
