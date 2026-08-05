import os
files = [f for f in os.listdir('runtime/home/temp') if f.endswith('.php')]
for f in files:
    print(f)
