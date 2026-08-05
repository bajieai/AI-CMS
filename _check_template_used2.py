import requests, re
resp = requests.get('http://aicms.test/page/7')
# Find all occurrences
positions = [m.start() for m in re.finditer('公司简介', resp.text)]
for i, pos in enumerate(positions):
    start = max(0, pos-80)
    end = min(len(resp.text), pos+80)
    print(f'=== Match {i+1} at pos {pos} ===')
    print(resp.text[start:end])
    print()
