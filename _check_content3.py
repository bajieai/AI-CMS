import pymysql
conn = pymysql.connect(host='127.0.0.1', port=3306, user='aicms_v2', password='aicms_v2', database='aicms_v2', charset='utf8mb4')
cur = conn.cursor()
cur.execute("SELECT id, title, type, cate_id FROM i8j_content WHERE id = 3")
r = cur.fetchone()
print(f'Content 3: id={r[0]} title={r[1]} type={r[2]} cate_id={r[3]}')
cur.execute("SELECT id, title, type, cate_id FROM i8j_content WHERE cate_id = 7 AND type = 6")
for r in cur.fetchall():
    print(f'Content cate_id=7: id={r[0]} title={r[1]} type={r[2]}')
cur.close()
conn.close()
