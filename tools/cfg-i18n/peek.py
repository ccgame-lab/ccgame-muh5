#!/usr/bin/env python3
"""Dump sample entries (CN ref vs VN cur) for high-visibility tables."""
import json, sys, io

REF = "D:/40_Reference/MuH5/angel/www/wwwroot/angel/resource/resource/cfg/config1.json"
CUR = "D:/10_Projects/CCGame/ccgame-muh5/tools/r2-release/work/resource/cfg/config1.json"

ref = json.load(open(REF, encoding="utf-8"))
cur = json.load(open(CUR, encoding="utf-8"))
out = io.StringIO()

def show(table, n=4):
    r = ref.get(table, {})
    c = cur.get(table, {})
    out.write(f"\n===== {table}  (ref {len(r)} entries) =====\n")
    keys = list(r.keys())[:n]
    for k in keys:
        out.write(f"-- entry id={k} --\n")
        rv, cv = r[k], c.get(k, {})
        if isinstance(rv, dict):
            for f in rv:
                a = rv.get(f); b = cv.get(f) if isinstance(cv, dict) else None
                if isinstance(a, str) and a:
                    out.write(f"   {f:<16} CN: {a!r}\n   {'':<16} VN: {b!r}\n")
        else:
            out.write(f"   CN: {rv!r}\n   VN: {cv!r}\n")

for t in ["ActivityConfig", "StoreItemConfig"]:
    show(t)

open("D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/_peek.txt", "w", encoding="utf-8").write(out.getvalue())
print("wrote _peek.txt")
