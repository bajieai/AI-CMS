from playwright.sync_api import sync_playwright
import time

with sync_playwright() as p:
    browser = p.chromium.launch(headless=False)
    context = browser.new_context()
    page = context.new_page()
    
    # 监听控制台和网络错误
    errors = []
    page.on('console', lambda msg: errors.append(f'CONSOLE: {msg.type}: {msg.text}') if msg.type == 'error' else None)
    page.on('pageerror', lambda err: errors.append(f'PAGE ERROR: {err}'))
    
    # 监听响应
    def on_response(response):
        if response.status >= 500:
            errors.append(f'HTTP {response.status}: {response.url}')
    page.on('response', on_response)
    
    # 先访问登录页
    page.goto('http://aicms.test/member/login')
    print('Login page loaded')
    
    # 填表登录
    page.fill('input[name="username"]', 'test2')
    page.fill('input[name="password"]', 't123456')
    
    # 截图验证码
    captcha_elem = page.locator('img[alt="验证码"]')
    captcha_elem.screenshot(path='captcha.png')
    print('Captcha saved, please look at captcha.png')
    
    # 等待用户输入验证码
    captcha_code = input('Enter captcha code: ')
    page.fill('input[name="captcha"]', captcha_code.strip())
    page.click('button:has-text("登录")')
    page.wait_for_timeout(3000)
    print('After login:', page.url)
    
    # 访问 profile
    page.goto('http://aicms.test/member/profile')
    page.wait_for_timeout(2000)
    print('Profile page:', page.url)
    print('Profile status:', page.evaluate('document.title'))
    
    # 获取页面内容
    content = page.content()
    print('Content length:', len(content))
    
    # 检查是否 500
    if '500' in page.evaluate('document.title') or page.url.endswith('/member/profile'):
        print('Checking for errors...')
        for err in errors:
            print(err)
    
    # 截图
    page.screenshot(path='profile_page.png')
    print('Profile screenshot saved')
    
    input('Press Enter to close browser...')
    browser.close()
