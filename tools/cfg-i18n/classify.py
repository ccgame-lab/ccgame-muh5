#!/usr/bin/env python3
"""Classify ItemConfig names into quality buckets to scope the fix."""
import json, re, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
CJK = re.compile(r"[一-鿿]")
# lowercase Vietnamese slot/filler words that signal machine junk when mid-name
LOWER_JUNK = re.compile(r"\b(giày|mũ|quần|áo|giáp|bao tay|vũ khí|hạng liên|nhẫn|"
                        r"giai|bậc|sao|đai|trượng|kiếm|đao|cung|tr\w*ng)\b")
TIER = re.compile(r"\d+阶")  # class-tier template gear

ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))
ri, ci = ref["ItemConfig"], cur["ItemConfig"]

buckets = collections.Counter()
examples = collections.defaultdict(list)

def has_upper(s): return any(c.isupper() for c in s)
def has_lower(s): return any(c.islower() for c in s)

for eid, ent in ri.items():
    if not isinstance(ent, dict): continue
    cn = ent.get("name")
    if not (isinstance(cn, str) and CJK.search(cn)): continue
    vn = ci.get(eid, {}).get("name")
    is_tier = bool(TIER.search(cn))
    if not isinstance(vn, str) or CJK.search(vn or ""):
        b = "raw_or_missing"
    elif not has_upper(vn) and has_lower(vn):
        b = "all_lowercase"           # definitely junk
    elif LOWER_JUNK.search(vn):
        b = "mixed_lowercase"         # partial junk (word-order/case)
    else:
        b = "looks_ok"
    if is_tier: b += "+tier"
    buckets[b] += 1
    if len(examples[b]) < 6:
        examples[b].append(f"{cn!r} -> {vn!r}")

out = ["=== ItemConfig name quality ==="]
for b, c in buckets.most_common():
    out.append(f"{c:>6}  {b}")
out.append("\n=== examples ===")
for b in buckets:
    out.append(f"--- {b} ---")
    out += ["   " + e for e in examples[b]]
open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_classify.txt","w",encoding="utf-8").write("\n".join(out))
print("\n".join(out[:12]))
print("...wrote _classify.txt")
