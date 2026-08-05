with open('runtime/ai_cms_error.log', 'r', encoding='utf-8') as f:
    content = f.read()

# The log has two entries. Get the second one (latest).
entries = content.split('\n---\n')
print(f'Total entries: {len(entries)}')

# Get the last entry
last = entries[-1] if len(entries) > 1 else entries[0]

# Find the h1 with actual error
import re
h1 = re.search(r'<h1>(.*?)</h1>', last, re.DOTALL)
if h1:
    print('H1:', h1.group(1).strip())

# Find div.info
info = re.search(r'<div class="info"[^>]*>(.*?)</div>', last, re.DOTALL)
if info:
    print('INFO:', info.group(1).strip())

# Find div.message
msg = re.search(r'<div class="message"[^>]*>(.*?)</div>', last, re.DOTALL)
if msg:
    print('MESSAGE:', msg.group(1).strip())

# Find all trace lines
traces = re.findall(r'<li>(.*?)</li>', last, re.DOTALL)
if traces:
    print(f'TRACE ({len(traces)} items):')
    for t in traces[:20]:
        print(' ', t.strip()[:300])

# If nothing found, dump last 2000 chars
if not h1 and not info and not msg:
    print('\n---RAW LAST 2000 CHARS---')
    print(last[-2000:])
