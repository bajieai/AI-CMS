with open('runtime/ai_cms_error.log', 'r', encoding='utf-8') as f:
    content = f.read()

# Find where the actual error message starts (after the CSS)
idx = content.find('exception')
if idx >= 0:
    # Get 5000 chars from there
    section = content[idx:idx+5000]
else:
    # Just get the last 5000 chars
    section = content[-5000:]

with open('runtime/parsed_error.txt', 'w', encoding='utf-8') as f:
    f.write(section)
