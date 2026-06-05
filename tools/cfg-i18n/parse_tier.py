#!/usr/bin/env python3
"""Parse class-tier gear names; list distinct class/slot tokens + the M index."""
import json, re, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CJK = re.compile(r"[一-鿿]")
TIER = re.compile(r"(\d+)阶(\d*)")
ref = json.load(open(REF, encoding="utf-8"))
ri = ref["ItemConfig"]

# pattern: optional N星, then class+slot, then N阶M
classes = collections.Counter()
slots = collections.Counter()
tiers = collections.Counter()
ms = collections.Counter()
unmatched = []

# known slot tokens (longest-first matching)
SLOTS = ["头盔", "铠甲", "护手", "护腿", "项链", "武器", "戒指", "靴"]

for eid, ent in ri.items():
    if not isinstance(ent, dict): continue
    cn = ent.get("name")
    if not (isinstance(cn, str) and TIER.search(cn)): continue
    m = TIER.search(cn)
    tiers[m.group(1)] += 1
    ms[m.group(2)] += 1
    head = cn[:m.start()]
    head = re.sub(r"^\d+星", "", head)  # strip star prefix
    slot = next((s for s in SLOTS if head.endswith(s)), None)
    if slot:
        slots[slot] += 1
        classes[head[:-len(slot)]] += 1
    else:
        unmatched.append(cn)

out = []
out.append("=== distinct CLASS tokens ===")
for c, n in classes.most_common(): out.append(f"  {n:>5}  {c}")
out.append("=== distinct SLOT tokens ===")
for s, n in slots.most_common(): out.append(f"  {n:>5}  {s}")
out.append(f"=== tiers === {dict(tiers)}")
out.append(f"=== M index === {dict(ms)}")
out.append(f"=== unmatched ({len(unmatched)}) ===")
out += ["   " + u for u in unmatched[:20]]
open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_parse_tier.txt","w",encoding="utf-8").write("\n".join(out))
print("\n".join(out))
