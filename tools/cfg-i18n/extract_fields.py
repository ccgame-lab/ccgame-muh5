#!/usr/bin/env python3
"""Extract unique CJK values per (table, field) for targeted translation.
Writes _fields_todo.json: {section: [{cn, vn, count}]} sorted by count.
"""
import json, re, collections, sys

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
CJK = re.compile(r"[一-鿿]")
ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))

# (label, table, field, only_bad_names)
TARGETS = [
    ("ItemConfig.output", "ItemConfig", "output", False),
    ("ItemConfig.desc",   "ItemConfig", "desc",   False),
    ("ItemConfig.name_bad","ItemConfig","name",   True),
]

def collect(table, field, only_bad):
    r, c = ref.get(table, {}), cur.get(table, {})
    acc = collections.OrderedDict()
    for eid, ent in r.items():
        if not isinstance(ent, dict): continue
        cn = ent.get(field)
        if not (isinstance(cn, str) and CJK.search(cn)): continue
        vn = c.get(eid, {}).get(field) if isinstance(c.get(eid), dict) else None
        if only_bad:
            # keep only names whose VN is still raw-CJK or all-lowercase junk
            if isinstance(vn, str) and not CJK.search(vn) and any(ch.isupper() for ch in vn):
                continue
        e = acc.setdefault(cn, {"cn": cn, "vn": vn, "count": 0})
        e["count"] += 1
        if e["vn"] is None and vn: e["vn"] = vn
    return sorted(acc.values(), key=lambda x: -x["count"])

out = {}
for label, t, f, only_bad in TARGETS:
    rows = collect(t, f, only_bad)
    out[label] = rows
    print(f"{label}: {len(rows)} unique")
json.dump(out, open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_fields_todo.json","w",encoding="utf-8"),
          ensure_ascii=False, indent=1)
