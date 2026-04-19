import subprocess

result = subprocess.run(['docker', 'exec', 'aicms_php', 'sh', '-c', 
    'cd /var/www/html && php test_init.php 2>&1'], capture_output=True, encoding='utf-8', errors='ignore')
html = result.stdout

# Find and print the error content area
start = html.find('<div class="exception-info">')
if start > 0:
    end = html.find('</div>', start)
    if end > 0:
        chunk = html[start:end+6]
    else:
        chunk = html[start:start+2000]
else:
    # Try to find any h1/h2
    for tag in ['<h1>', '<h2>']:
        idx = html.find(tag)
        if idx >= 0:
            chunk = html[idx:idx+300]
            break
    else:
        chunk = html[1000:2000]  # Just show a middle chunk

# Save to file for inspection
with open(r'd:\AI-Project\AI-CMS\backend\error_output.html', 'w', encoding='utf-8') as f:
    f.write(html)

print(f"Total HTML length: {len(html)}")
print(f"Saved to error_output.html")
print("\n--- Key snippet ---")
print(chunk[:1500])
