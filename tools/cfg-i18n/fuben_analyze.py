#!/usr/bin/env python3
"""Analyze FubenConfig.name: quality buckets + base-name patterns + samples."""
import json, re, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
CJK = re.compile(r"[一-鿿]")
ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))
ri, ci = ref["FubenConfig"], cur["FubenConfig"]

buckets = collections.Counter()
bases = collections.Counter()        # name with trailing digits/层/关 stripped
ex = collections.defaultdict(list)

def has_upper(s): return any(c.isupper() for c in s)

for eid, ent in ri.items():
    if not isinstance(ent, dict): continue
    cn = ent.get("name")
    if not (isinstance(cn, str) and CJK.search(cn)): continue
    vn = ci.get(eid, {}).get("name") if isinstance(ci.get(eid), dict) else None
    # base: strip digits/roman-ish and 层/关/波/层
    base = re.sub(r"[0-9一二三四五六七八九十百]+", "", cn)
    base = re.sub(r"(第|层|关|波|阶|级|星|重)", "", base)
    bases[base] += 1
    if not isinstance(vn, str) or CJK.search(vn or ""):
        b = "raw_or_missing"
    elif not has_upper(vn):
        b = "all_lowercase"
    else:
        b = "has_upper"
    buckets[b] += 1
    if len(ex[b]) < 8:
        ex[b].append(f"{cn!r} -> {vn!r}")

out = ["=== FubenConfig.name quality ==="]
for b, c in buckets.most_common(): out.append(f"{c:>6}  {b}")
out.append("\n=== top 25 base-names (after stripping numbers/floor words) ===")
for b, c in bases.most_common(25): out.append(f"{c:>5}  {b!r}")
out.append("\n=== samples ===")
for b in buckets:
    out.append(f"--- {b} ---")
    out += ["   " + e for e in ex[b]]
open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_fuben.txt","w",encoding="utf-8").write("\n".join(out))
print("\n".join(out))
