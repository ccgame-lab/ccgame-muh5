#!/usr/bin/env python3
"""Find garbled NAME-like strings across ALL tables, grouped by table.

Heuristic: CN source has CJK; VN is short (<=24 chars, i.e. a name/label not a
paragraph), fully translated (no CJK left), no MU keyword, and its first
alphabetic character is lowercase — proper names/labels should start uppercase,
so a lowercase start signals machine-translation junk.
"""
import json, re, collections

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"
CJK = re.compile(r"[一-鿿]")
MU = re.compile(r"\b(Blood Castle|Devil Square|Kalima|Kanturu|Crywolf|Reset|RS)\b")
FIRST_ALPHA = re.compile(r"[A-Za-zÀ-ỹ]")
MAXLEN = 24

ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))

by_table = collections.Counter()
uniq_by_table = collections.defaultdict(set)
ex = collections.defaultdict(list)

def first_is_lower(s):
    m = FIRST_ALPHA.search(s)
    return bool(m) and m.group(0).islower()

def walk(rn, cn, table, path):
    if isinstance(rn, str):
        if not CJK.search(rn) or not isinstance(cn, str) or not cn.strip():
            return
        if CJK.search(cn) or len(cn) > MAXLEN or MU.search(cn):
            return
        if first_is_lower(cn):
            by_table[table] += 1
            uniq_by_table[table].add((rn, cn))
            if len(ex[table]) < 4:
                ex[table].append(f"{rn!r} -> {cn!r}")
    elif isinstance(rn, dict):
        for k, v in rn.items():
            walk(v, cn.get(k) if isinstance(cn, dict) else None, table, f"{path}.{k}")
    elif isinstance(rn, list):
        for i, v in enumerate(rn):
            walk(v, cn[i] if isinstance(cn, list) and i < len(cn) else None, table, f"{path}[{i}]")

for t, v in ref.items():
    walk(v, cur.get(t), t, t)

out = [f"=== garbled name-like strings (first char lowercase, <= {MAXLEN} chars) ==="]
total = sum(by_table.values())
out.append(f"TOTAL hits={total}, unique={sum(len(s) for s in uniq_by_table.values())}\n")
for t, c in by_table.most_common():
    out.append(f"{c:>5} ({len(uniq_by_table[t]):>4} uniq)  {t}")
    for e in ex[t]:
        out.append(f"         {e}")
open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_scan_all.txt","w",encoding="utf-8").write("\n".join(out))
# dump uniques grouped by table for translation
dump = {t: [{"cn": cn, "vn": vn} for cn, vn in sorted(s)] for t, s in uniq_by_table.items()}
json.dump(dump, open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_scan_all.json","w",encoding="utf-8"),
          ensure_ascii=False, indent=1)
print("\n".join(out[:40]))
