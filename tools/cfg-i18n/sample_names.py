#!/usr/bin/env python3
"""Sample ItemConfig CN names + current VN, spread across the table."""
import json, re, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
CJK = re.compile(r"[一-鿿]")
ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))

ri = ref["ItemConfig"]; ci = cur["ItemConfig"]
rows = []
for eid, ent in ri.items():
    if isinstance(ent, dict):
        nm = ent.get("name")
        if isinstance(nm, str) and CJK.search(nm):
            rows.append((eid, nm, ci.get(eid, {}).get("name")))
# spread sample: every Nth
step = max(1, len(rows)//50)
out = [f"ItemConfig names: {len(rows)} total, sampling every {step}"]
for eid, cn, vn in rows[::step][:50]:
    out.append(f"  id={eid:<6} CN={cn!r:<22} VN={vn!r}")
open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_names_sample.txt","w",encoding="utf-8").write("\n".join(out))
print(out[0]); print("wrote _names_sample.txt")
