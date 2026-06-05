#!/usr/bin/env python3
"""Per-field CJK analysis of a table: which fields hold translatable text."""
import json, re, sys, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
CJK = re.compile(r"[一-鿿]")
ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))
table = sys.argv[1] if len(sys.argv) > 1 else "ItemConfig"

r = ref.get(table, {})
field_cjk = collections.Counter()
field_uniq = collections.defaultdict(set)
sample = {}
for eid, ent in r.items():
    if not isinstance(ent, dict):
        continue
    for f, v in ent.items():
        if isinstance(v, str) and CJK.search(v):
            field_cjk[f] += 1
            field_uniq[f].add(v)
            if f not in sample:
                sample[f] = (v, cur.get(table, {}).get(eid, {}).get(f))

out = []
out.append(f"=== {table}: {len(r)} entries ===")
out.append(f"{'field':<16}{'cjk':>7}{'unique':>8}   sample CN -> current VN")
for f, c in field_cjk.most_common():
    cn, vn = sample[f]
    out.append(f"{f:<16}{c:>7}{len(field_uniq[f]):>8}   {cn!r} -> {vn!r}")
open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_fields.txt", "w", encoding="utf-8").write("\n".join(out))
print("\n".join(out))
