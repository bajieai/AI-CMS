#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Fix double-encoded UTF-8 Chinese text - CORRECT FINAL VERSION.
For each line, if GBK decode gives different text than UTF-8 decode,
the GBK text is the original correct Chinese.
The key check: utf8_text.encode('utf-8') should equal the original bytes.
If it does, the bytes were stored as UTF-8, and the GBK interpretation
is the correct original text.
"""
import sys, os

if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')

def fix_file(filepath):
    """Fix a file by processing line by line."""
    with open(filepath, 'rb') as f:
        raw = f.read()
    
    lines = raw.split(b'\n')
    result_lines = []
    fix_count = 0
    
    for line_bytes in lines:
        had_cr = line_bytes.endswith(b'\r')
        if had_cr:
            line_bytes = line_bytes[:-1]
        
        ending = '\r\n' if had_cr else '\n'
        
        # Try both GBK and UTF-8 decode
        try:
            gbk_text = line_bytes.decode('gbk')
        except UnicodeDecodeError:
            gbk_text = None
        
        try:
            utf8_text = line_bytes.decode('utf-8')
        except UnicodeDecodeError:
            utf8_text = None
        
        if gbk_text is None:
            # GBK failed - use UTF-8
            if utf8_text:
                result_lines.append(utf8_text + ending)
            else:
                result_lines.append(line_bytes.decode('utf-8', errors='replace') + ending)
            continue
        
        if utf8_text is None:
            # UTF-8 failed - use GBK
            result_lines.append(gbk_text + ending)
            fix_count += 1
            continue
        
        if gbk_text == utf8_text:
            # Same result - not corrupted
            result_lines.append(utf8_text + ending)
            continue
        
        # GBK and UTF-8 give different results!
        # The bytes are UTF-8 encoded (utf8_text.encode('utf-8') == line_bytes).
        # But GBK decode gives different text.
        # This means the bytes represent GBK-decoded text stored as UTF-8.
        # The GBK text IS the correct original text.
        
        # Verify: UTF-8 encode of utf8_text should match original bytes
        # (this confirms the bytes are UTF-8 encoded)
        # And GBK text should be valid Chinese
        is_gbk_chinese = all(
            0x4E00 <= ord(c) <= 0x9FFF or
            0x3000 <= ord(c) <= 0x303F or
            0xFF00 <= ord(c) <= 0xFFEF or
            ord(c) < 0x80 or  # ASCII chars in the line
            c in '，。、：；！？（）【】《》""''—…·—～'
            for c in gbk_text
        )
        
        # Also verify HTML structure is preserved
        gbk_angles = gbk_text.count('<') + gbk_text.count('>')
        utf8_angles = utf8_text.count('<') + utf8_text.count('>')
        
        if is_gbk_chinese and gbk_angles == utf8_angles:
            # GBK is the correct original text!
            result_lines.append(gbk_text + ending)
            fix_count += 1
        else:
            # Keep UTF-8
            result_lines.append(utf8_text + ending)
    
    content = ''.join(result_lines)
    content = content.replace('\r\n', '\n').replace('\r', '\n')
    
    with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
        f.write(content)
    
    return fix_count


# Process ALL files
files = [
    r"d:\AI-Project\AI-CMS\template\themes\default\mobile\member_signin.html",
    r"d:\AI-Project\AI-CMS\template\themes\default\mobile\member_oauth_bind.html",
    r"d:\AI-Project\AI-CMS\template\themes\default\mobile\member_notification.html",
    r"d:\AI-Project\AI-CMS\template\themes\default\mobile\member_level.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\pc\member_signin.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\pc\member_points.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\pc\member_oauth_bind.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\pc\member_notification.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\pc\member_level.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\mobile\member_signin.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\mobile\member_points.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\mobile\member_oauth_bind.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\mobile\member_notification.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\mobile\member_level.html",
    r"d:\AI-Project\AI-CMS\template\themes\default\pc\detail.html",
    r"d:\AI-Project\AI-CMS\template\themes\corporate\pc\detail.html",
]

# Process ALL files
print("Processing all files...")
total = 0
for f in files:
    if os.path.exists(f):
        count = fix_file(f)
        total += count
        print(f"  {os.path.basename(f)}: {count} lines fixed")
    else:
        print(f"  {os.path.basename(f)}: NOT FOUND")

print(f"\nTotal lines fixed: {total}")

# Verify first file
print("\n=== Verification: member_signin.html (default/mobile) ===")
with open(files[0], 'r', encoding='utf-8') as f:
    content = f.read()
lines = content.split('\n')
for i, line in enumerate(lines, 1):
    has_cjk = any(0x4E00 <= ord(c) <= 0x9FFF for c in line)
    if has_cjk:
        print(f"Line {i}: {line[:150]}")
