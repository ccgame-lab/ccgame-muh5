#!/usr/bin/env python3
"""Global scan: find remaining garbled VN strings across ALL tables.

Heuristic for 'garbled name/label': the Chinese source has CJK, the current VN
is a short-ish string written ENTIRELY in lowercase (proper names/labels should
be Title Case) and has no MU keyword. Dedup by CN, sort by frequency.
Writes _garbage.json: [{cn, vn, count, tables, example_path}].
"""
import json, re, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
CJK = re.compile(r"[一-鿿]")
HAS_UPPER = re.compile(r"[A-ZÀ-Ỹ]")  # incl. Vietnamese uppercase
MAXLEN = 28  # focus on names/labels, not paragraphs

ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))
acc = collections.OrderedDict()

def walk(rn, cn, table, path):
    if isinstance(rn, str):
        if not CJK.search(rn):
            return
        if not isinstance(cn, str) or not cn.strip():
            return
        if CJK.search(cn):
            return  # still raw Chinese -> handled elsewhere
        if len(cn) > MAXLEN:
            return
        if HAS_UPPER.search(cn):
            return  # has a capital -> assume acceptable
        e = acc.setdefault(rn, {"cn": rn, "vn": cn, "count": 0,
                                "tables": set(), "path": path})
        e["count"] += 1
        e["tables"].add(table)
    elif isinstance(rn, dict):
        for k, v in rn.items():
            walk(v, cn.get(k) if isinstance(cn, dict) else None, table, f"{path}.{k}")
    elif isinstance(rn, list):
        for i, v in enumerate(rn):
            walk(v, cn[i] if isinstance(cn, list) and i < len(cn) else None, table, f"{path}[{i}]")

for t, v in ref.items():
    walk(v, cur.get(t), t, t)

rows = sorted(acc.values(), key=lambda e: -e["count"])
for r in rows:
    r["tables"] = sorted(r["tables"])
json.dump(rows, open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_garbage.json","w",encoding="utf-8"),
          ensure_ascii=False, indent=1)
print(f"garbled lowercase names: {len(rows)} unique, {sum(r['count'] for r in rows)} total")
print("top tables:", collections.Counter(t for r in rows for t in r["tables"]).most_common(12))
