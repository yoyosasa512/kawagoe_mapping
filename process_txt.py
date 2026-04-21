from decimal import Decimal, ROUND_HALF_UP

with open('kawagoe_heritage.txt', 'r', encoding='utf-8') as f:
    lines = f.readlines()
table = []
for line in lines:
    line = line.strip()
    if line:
        table.append(line.split(','))
for row in table:
    # Decimalオブジェクトを作成し、quantizeで小数点以下6桁(0.000001)に丸める
    row[1] = str(Decimal(row[1]).quantize(Decimal('0.000001'), rounding=ROUND_HALF_UP))
    row[2] = str(Decimal(row[2]).quantize(Decimal('0.000001'), rounding=ROUND_HALF_UP))

for row in table:
    name = row[0]
    # 簡易的な自動タグ付けロジック
    tags = []
    if any(k in name for k in ["神社", "宮", "大社", "大神"]): tags.append("神社")
    if "稲荷" in name: tags.append("稲荷神社")
    if any(k in name for k in ["寺", "院", "庵", "坊", "閣"]): tags.append("寺院")
    if any(k in name for k in ["住宅", "家", "旧"]): tags.append("古民家")
    if any(k in name for k in ["跡", "古墳", "城", "塚"]): tags.append("史跡")
    if any(k in name for k in ["塔", "碑", "鐘"]): tags.append("文化財")
    
    # カテゴリラベルを決定（最初のタグ、なければ「史跡」）
    category_label = f"【{tags[0]}】" if tags else "【史跡】"
    
    tag_str = f"'{','.join(tags)}'"
    row[0] = f"'{name}'"
    row.insert(1, f"'{category_label}{name}'") # display_nameにラベルを付与
    row.append(tag_str)   # tags用

# for row in table:
#     print(','.join(row))
with open('kawagoe_heritage_processed.txt', 'w', encoding='utf-8') as f:
    f.write("-- Supabase用初期化SQL\n")
    f.write("CREATE TABLE IF NOT EXISTS spots (\n")
    f.write("    id SERIAL PRIMARY KEY,\n")
    f.write("    name TEXT NOT NULL,\n")
    f.write("    display_name TEXT,\n")
    f.write("    latitude DECIMAL(9, 6),\n")
    f.write("    longitude DECIMAL(9, 6),\n")
    f.write("    tags TEXT\n")
    f.write(");\n\n")
    f.write("TRUNCATE TABLE spots;\n")
    f.write("INSERT INTO spots (name, display_name, latitude, longitude, tags) VALUES\n")
    for i, row in enumerate(table):
        # 最後の行以外は末尾にカンマ、最後だけセミコロンを付けます
        separator = ";" if i == len(table) - 1 else ","
        f.write(f"({','.join(row)}){separator}\n")