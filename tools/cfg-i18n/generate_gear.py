#!/usr/bin/env python3
"""Generate consistent Hán-Việt names for the 768 class-tier gear items.

CN template: [N星]<class><slot><tier>阶<M>  ->  "<Slot> <Class> <tier>-<M>"
Writes gear_strings.json (cn -> vn). Does NOT modify config1.json.
"""
import json, re

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
OUT = "D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/gear_strings.json"

# Verified from CreateRolSkin1.exml (role art sm/mg/ac) + weapon-job data + elimination:
#   魔剑士=job4=MG(role2) ; 圣导师=job5=Dark Knight(role1/sm) ; 召唤师=job6=Elf Master(role3/ac)
# Class = English MU names (what the game displays); slots stay Hán-Việt.
CLASS_FULL = {"召唤师": "Elf Master", "魔剑士": "Magic Gladiator", "圣导师": "Dark Knight"}
CLASS = {"召唤师": "EM", "魔剑士": "MG", "圣导师": "DK"}  # chosen: abbreviated (UI length)
CLASS_ABBR = CLASS
SLOT = {"头盔": "Mũ", "铠甲": "Giáp", "护手": "Bao Tay", "护腿": "Quần",
        "靴": "Giày", "武器": "Vũ Khí", "戒指": "Nhẫn", "项链": "Dây Chuyền"}
SLOTS = ["头盔", "铠甲", "护手", "护腿", "项链", "武器", "戒指", "靴"]  # longest-first
TIER = re.compile(r"(\d+)阶(\d*)")

ref = json.load(open(REF, encoding="utf-8"))
gear = {}
preview = []

for eid, ent in ref["ItemConfig"].items():
    if not isinstance(ent, dict): continue
    cn = ent.get("name")
    if not (isinstance(cn, str) and TIER.search(cn)): continue
    m = TIER.search(cn)
    tier, mi = m.group(1), m.group(2) or "0"
    head = re.sub(r"^\d+星", "", cn[:m.start()])
    slot = next((s for s in SLOTS if head.endswith(s)), None)
    cls = head[:-len(slot)] if slot else None
    if slot is None or cls not in CLASS:
        continue
    vn = f"{SLOT[slot]} {CLASS[cls]} {tier}-{mi}"
    abbr = f"{SLOT[slot]} {CLASS_ABBR[cls]} {tier}-{mi}"
    gear[cn] = vn
    preview.append((cn, vn, abbr))

json.dump(gear, open(OUT, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
maxlen = max(len(v) for v in gear.values())
print(f"generated {len(gear)} gear names -> {OUT}  (max len = {maxlen} chars)\n")
print(f"{'CN':<16}{'Full (chọn)':<34}{'Abbrev (nếu sợ dài)'}")
# one of each slot for DK as a representative spread
shown = set()
for cn, vn, abbr in preview:
    key = vn.split()[0] + vn.split()[1]
    if key in shown:
        continue
    shown.add(key)
    print(f"{cn:<15} {vn:<33} {abbr}")
    if len(shown) >= 24:
        break
