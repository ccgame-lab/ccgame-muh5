#!/usr/bin/env python3
"""Extract unique CJK strings (with current VN + count) from target tables.

Usage: python extract.py Table1 Table2 ...   (default: a curated priority set)
Writes _to_translate.json (list of {cn, vn, count, tables, example_path}).
"""
import json, re, sys, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
OUT = "D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_to_translate.json"

CJK = re.compile(r"[一-鿿]")
ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))

tables = sys.argv[1:] or ["ActivityConfig", "StoreItemConfig"]

# cn -> {vn, count, tables:set, path}
acc = collections.OrderedDict()

def walk(rnode, cnode, table, path):
    if isinstance(rnode, str):
        if CJK.search(rnode):
            e = acc.get(rnode)
            vn = cnode if isinstance(cnode, str) else None
            if e is None:
                acc[rnode] = {"cn": rnode, "vn": vn, "count": 1,
                              "tables": {table}, "path": path}
            else:
                e["count"] += 1
                e["tables"].add(table)
                if e["vn"] is None and vn:
                    e["vn"] = vn
    elif isinstance(rnode, dict):
        for k, v in rnode.items():
            cv = cnode.get(k) if isinstance(cnode, dict) else None
            walk(v, cv, table, f"{path}.{k}")
    elif isinstance(rnode, list):
        for i, v in enumerate(rnode):
            cv = cnode[i] if isinstance(cnode, list) and i < len(cnode) else None
            walk(v, cv, table, f"{path}[{i}]")

for t in tables:
    walk(ref.get(t), cur.get(t), t, t)

rows = sorted(acc.values(), key=lambda e: -e["count"])
for r in rows:
    r["tables"] = sorted(r["tables"])
json.dump(rows, open(OUT, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
print(f"tables={tables} unique_cn={len(rows)} total={sum(r['count'] for r in rows)} -> {OUT}")
