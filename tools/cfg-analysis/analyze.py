import json, io

REF = r"D:\40_Reference\MuH5\angel\www\wwwroot\angel\resource\resource\cfg\config1.json"
LIVE = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\cfg_live.json"
OUT = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\report.txt"

def load(p):
    with open(p, encoding="utf-8") as f:
        return json.load(f)

ref = load(REF)
live = load(LIVE)

def summ(v):
    if isinstance(v, dict): return f"dict[{len(v)}]"
    if isinstance(v, list): return f"list[{len(v)}]"
    if isinstance(v, str): return f"str[{len(v)}]"
    return type(v).__name__

def empty(v):
    return v in ({}, [], "", None)

lines = []
w = lines.append
w(f"REF  top-level keys = {len(ref)}")
w(f"LIVE top-level keys = {len(live)}")
kr, kl = set(ref), set(live)
w(f"\nKEYS only in REF  ({len(kr-kl)}): {sorted(kr-kl)}")
w(f"KEYS only in LIVE ({len(kl-kr)}): {sorted(kl-kr)}")

w("\n=== keys EMPTY in LIVE but POPULATED in REF (nghi event hỏng) ===")
for k in sorted(kr & kl):
    if empty(live[k]) and not empty(ref[k]):
        w(f"  {k}: LIVE={summ(live[k])}  REF={summ(ref[k])}")

w("\n=== keys EMPTY in both ===")
both_empty = [k for k in sorted(kr & kl) if empty(live[k]) and empty(ref[k])]
w("  " + ", ".join(both_empty))

w("\n=== keys differ in size (top 40 by |delta|) ===")
diffs = []
for k in kr & kl:
    a, b = ref[k], live[k]
    la = len(a) if isinstance(a, (dict, list, str)) else 0
    lb = len(b) if isinstance(b, (dict, list, str)) else 0
    if la != lb:
        diffs.append((abs(la - lb), k, summ(a), summ(b)))
diffs.sort(reverse=True)
for d, k, ra, lv in diffs[:40]:
    w(f"  {k}: REF={ra}  LIVE={lv}  (delta {d})")

w("\n=== ALL top-level keys: REF vs LIVE summary ===")
for k in sorted(kr | kl):
    w(f"  {k}: REF={summ(ref.get(k,'<missing>')) if k in ref else '<missing>'}  LIVE={summ(live.get(k,'<missing>')) if k in live else '<missing>'}")

with io.open(OUT, "w", encoding="utf-8") as f:
    f.write("\n".join(lines))
print("report written:", OUT, "lines:", len(lines))
