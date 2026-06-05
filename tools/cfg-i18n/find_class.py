#!/usr/bin/env python3
"""Find where the 3 class tokens appear (to discover canonical class naming)."""
import json, re, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))
CLASSES = ["召唤师", "魔剑士", "圣导师"]

# tables (other than ItemConfig) where these appear, with a sample value + VN
hits = collections.Counter()
samples = {}
def walk(rn, cn, table, path):
    if isinstance(rn, str):
        for c in CLASSES:
            if c in rn:
                hits[table] += 1
                if table not in samples and len(rn) < 30:
                    samples[table] = (path, rn, cn if isinstance(cn, str) else None)
    elif isinstance(rn, dict):
        for k, v in rn.items():
            walk(v, cn.get(k) if isinstance(cn, dict) else None, table, f"{path}.{k}")
    elif isinstance(rn, list):
        for i, v in enumerate(rn):
            walk(v, cn[i] if isinstance(cn, list) and i < len(cn) else None, table, f"{path}[{i}]")

for t, v in ref.items():
    if t == "ItemConfig": continue
    walk(v, cur.get(t), t, t)

out = ["=== tables containing class tokens (excl ItemConfig) ==="]
for t, n in hits.most_common():
    p, rn, vn = samples[t]
    out.append(f"  {n:>4}  {t}\n         {p}\n         CN={rn!r}  VN={vn!r}")
# also dump job-ish tables fully
for t in ["AllotJobConfig", "ActorConfig", "JobConfig"]:
    if t in ref:
        out.append(f"\n=== {t} keys === {list(ref[t].keys())[:8] if isinstance(ref[t],dict) else type(ref[t])}")
open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_find_class.txt","w",encoding="utf-8").write("\n".join(out))
print("\n".join(out))
