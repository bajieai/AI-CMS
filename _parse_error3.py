with open('runtime/ai_cms_error.log', 'r', encoding='utf-8') as f:
    content = f.read()

# Find the exception message (after the CSS)
import re
# Look for the <div class="exception"> block
idx = content.find('<div class="exception">')
if idx >= 0:
    # Get from there to the end of log entry
    end = content.find('\n---\n', idx)
    if end < 0:
        end = len(content)
    section = content[idx:end]
else:
    # Try finding the h1 with the error message
    h1_match = re.search(r'<h1>(.*?)</h1>', content)
    if h1_match:
        section = h1_match.group(1)
    else:
        section = 'No exception found'

with open('runtime/parsed_error.txt', 'w', encoding='utf-8') as f:
    f.write(section)
