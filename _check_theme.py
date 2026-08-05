import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3306, user='aicms_v2', password='aicms_v2', database='aicms_v2', charset='utf8mb4')
cur = conn.cursor()
cur.execute("SELECT name, value FROM i8j_config WHERE name LIKE '%theme%' LIMIT 10")
for r in cur.fetchall():
    print(r)
cur.close()
conn.close()
