import requests

s = requests.Session()

# First get login page to get token and captcha key
resp = s.get('http://aicms.test/member/login')
print('Login page:', resp.status_code)

# Try to get captcha key from the form
import re
captcha_key_match = re.search(r'captcha_key.*?value="([^"]*)"', resp.text)
captcha_key = captcha_key_match.group(1) if captcha_key_match else ''
token_match = re.search(r'__token__.*?value="([^"]*)"', resp.text)
token = token_match.group(1) if token_match else ''
print('Captcha key:', captcha_key)
print('Token:', token)

# Get captcha image
captcha_resp = s.get('http://aicms.test/captcha.html?id=' + captcha_key)
print('Captcha image:', captcha_resp.status_code)

# Try login WITHOUT captcha (if captcha is optional)
resp = s.post('http://aicms.test/member/login', data={
    'username': 'test2',
    'password': 't123456',
    'captcha': '',
    'captcha_key': captcha_key,
    '__token__': token
}, allow_redirects=False)
print('Login:', resp.status_code, 'Location:', resp.headers.get('location', ''))

# If redirect to /member, try profile
if resp.status_code == 302:
    resp = s.get('http://aicms.test/member/profile')
    print('Profile:', resp.status_code)
    if resp.status_code == 500:
        # Extract error from HTML
        content = resp.text
        print('Content preview:', content[:2000])
        # Find error in trace
        error_match = re.search(r'(SQLSTATE.*?|Class.*?not found|Call to.*?undefined|Error.*?</)', content, re.DOTALL)
        if error_match:
            print('ERROR:', error_match.group(1))
