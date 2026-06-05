#!/usr/bin/env python3
"""Apply glossary['strings'] (exact CN->VN) into the current VN config1.json.

Walks the Chinese reference and the current VN config in parallel by path.
Wherever the REF leaf equals a glossary CN key, the matching VN leaf is set to
the glossary value. Exact-string match only -> safe (no substring/grammar risk),
applied across the WHOLE config so every occurrence is fixed consistently.

Writes the edited config back to the r2-release work copy (ready for `r2.py push`)
after backing up the pre-edit file to config1.json.preVH-<stamp>.
Prints/saves a change report.
"""
import json, time, shutil, io, re, collections
from pathlib import Path

# Game theme = MU Online. The user's existing English franchise terms are
# intentional and MUST NOT be edited. Vietnamese is ALSO written in Latin script,
# so "has A-Za-z" can't tell English from garbled-VN. Instead, preserve a leaf
# only when it contains a known MU keyword; everything else (raw Chinese or
# garbled Vietnamese) is fair game to fix.
# Only true MU proper nouns / mechanics that must stay English. Deliberately
# NOT generic words (VIP/Boss/EXP/PK/Box) — those appear in both languages and
# would wrongly block legit fixes.
MU_KEYWORDS = re.compile(
    r"\b("
    r"Blood\s*Castle|Devil\s*Square|Chaos\s*Castle|Castle\s*Siege|Illusion\s*Temple|"
    r"Kalima|Kanturu|Crywolf|Cursed\s*Temple|Kundun|Selupan|"
    r"Lorencia|Noria|Devias|Atlans|Tarkan|Aida|Icarus|Karutan|Vulcanus|Arca|"
    r"Reset|RS"
    r")\b"
)  # case-sensitive: "rs"/"reset" inside Vietnamese words must not match

REF = Path("D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json")
CUR = Path("D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json")
GLOSS = Path("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/glossary.json")
REPORT = Path("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_applied_report.txt")

D = Path("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n")
GEAR, BATCH2, BATCH3 = D / "gear_strings.json", D / "batch2_strings.json", D / "batch3_strings.json"
ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))
strings = dict(json.load(open(GLOSS, encoding="utf-8"))["strings"])
for extra in (GEAR, BATCH2, BATCH3):
    if extra.exists():
        strings.update(json.load(open(extra, encoding="utf-8")))

changes = []  # (path, cn, old_vn, new_vn)
skipped = []  # (path, cn, old_vn) — left untouched because it has English

# Distinctive garbage phrases to fix as SUBSTRINGS inside longer VN strings
# (only obscure machine-translation artifacts unlikely to appear correctly).
SUBSTR = {
    "hạng liên": "dây chuyền",   # 项链 = necklace, mistranslated
}
substr_hits = collections.Counter()

def apply_substrings():
    def walk(node, parent, key):
        v = parent[key] if _has(parent, key) else None
        if isinstance(v, str):
            new = v
            for bad, good in SUBSTR.items():
                if bad in new and not MU_KEYWORDS.search(new):
                    new = new.replace(bad, good)
                    substr_hits[bad] += 1
            if new != v:
                parent[key] = new
        elif isinstance(node, dict):
            for k in list(node.keys()):
                walk(node[k], node, k)
        elif isinstance(node, list):
            for i in range(len(node)):
                walk(node[i], node, i)
    root = {"_": cur}
    walk(cur, root, "_")

def walk(rnode, cparent, ckey, path):
    """rnode = ref subtree; cparent[ckey] = matching cur leaf/subtree."""
    if isinstance(rnode, str):
        new = strings.get(rnode)
        if new is None:
            return
        if not isinstance(cparent, (dict, list)):
            return
        try:
            old = cparent[ckey]
        except (KeyError, IndexError, TypeError):
            return
        if isinstance(old, str) and MU_KEYWORDS.search(old):
            skipped.append((path, rnode, old))  # keep user's MU/English term untouched
            return
        if old != new:
            changes.append((path, rnode, old, new))
            cparent[ckey] = new
    elif isinstance(rnode, dict):
        cnode = cparent[ckey] if _has(cparent, ckey) else None
        if not isinstance(cnode, dict):
            return
        for k, v in rnode.items():
            walk(v, cnode, k, f"{path}.{k}")
    elif isinstance(rnode, list):
        cnode = cparent[ckey] if _has(cparent, ckey) else None
        if not isinstance(cnode, list):
            return
        for i, v in enumerate(rnode):
            if i < len(cnode):
                walk(v, cnode, i, f"{path}[{i}]")

def _has(parent, key):
    if isinstance(parent, dict):
        return key in parent
    if isinstance(parent, list):
        return isinstance(key, int) and 0 <= key < len(parent)
    return False

# root wrapper
root_r = {"_": ref}
root_c = {"_": cur}
walk(ref, root_c, "_", "")
# root_c["_"] now holds edited cur (same object as cur since dict is mutable)
apply_substrings()

if not changes and not substr_hits:
    print("No changes (config already matches glossary).")
else:
    stamp = time.strftime("%Y%m%d-%H%M%S")
    bak = CUR.with_suffix(f".json.preVH-{stamp}")
    shutil.copy2(CUR, bak)
    # Minified (match the original) — indent would ~double the file size.
    json.dump(cur, open(CUR, "w", encoding="utf-8"), ensure_ascii=False, separators=(",", ":"))

    out = io.StringIO()
    out.write(f"Applied {len(changes)} replacement(s) + "
              f"{sum(substr_hits.values())} substring fix(es) {dict(substr_hits)}. "
              f"Preserved {len(skipped)} leaf(s) with existing English (MU terms). "
              f"Backup: {bak.name}\n\n")
    if skipped:
        sk = {}
        for p, cn, old in skipped:
            sk.setdefault((cn, old), 0)
            sk[(cn, old)] += 1
        out.write("-- preserved (your English, untouched) --\n")
        for (cn, old), c in sorted(sk.items(), key=lambda x: -x[1]):
            out.write(f"  [{c:>3}x] {cn}  ==  {old!r}\n")
        out.write("\n-- changed --\n")
    # group by cn
    by_cn = {}
    for p, cn, old, new in changes:
        by_cn.setdefault((cn, new), []).append((p, old))
    for (cn, new), occ in sorted(by_cn.items(), key=lambda x: -len(x[1])):
        out.write(f"[{len(occ):>3}x] {cn}  ->  {new}\n")
        out.write(f"        was: {occ[0][1]!r}\n")
    REPORT.write_text(out.getvalue(), encoding="utf-8")
    print(out.getvalue())
    print(f"-> wrote {CUR}\n-> report {REPORT}")
