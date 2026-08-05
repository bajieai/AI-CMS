import re

with open('runtime/ai_cms_error.log', 'r', encoding='utf-8') as f:
    content = f.read()

# Find exception message  
msg_match = re.search(r'class="message">(.*?)</div>', content, re.DOTALL)
if msg_match:
    msg = msg_match.group(1).strip()
    # Remove HTML tags
    msg = re.sub(r'<.*?>', '', msg)
    print('ERROR:', msg)

# Find file info
info_match = re.search(r'class="info"[^>]*>(.*?)</div>', content, re.DOTALL)
if info_match:
    info = info_match.group(1).strip()
    info = re.sub(r'<.*?>', '', info)
    print('INFO:', info)

# Find trace
trace_match = re.search(r'<ol>(.*?)</ol>', content, re.DOTALL)
if trace_match:
    trace = trace_match.group(1)
    items = re.findall(r'<li>(.*?)</li>', trace, re.DOTALL)
    for i, item in enumerate(items[:15]):
        item = re.sub(r'<.*?>', '', item).strip()
        print(f'  {i}: {item[:200]}')
