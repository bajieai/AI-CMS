with open('runtime/ai_cms_error.log', 'r', encoding='utf-8') as f:
    content = f.read()

entries = [e for e in content.split('\n---\n') if e.strip()]

for idx in range(max(0, len(entries)-3), len(entries)):
    e = entries[idx]
    # Find any HTML with error info
    import re
    h1 = re.search(r'<h1>(.*?)</h1>', e, re.DOTALL)
    title = re.search(r'<title>(.*?)</title>', e, re.DOTALL)
    if h1 or title:
        print(f'\n--- Entry {idx} ---')
        if title: print('TITLE:', title.group(1).strip())
        if h1: print('H1:', h1.group(1).strip())
        # Find trace
        traces = re.findall(r'<li>(.*?)</li>', e, re.DOTALL)
        for t in traces[:5]:
            print(' ', t.strip()[:200])
