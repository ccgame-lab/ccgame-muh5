import json, io

REF = r"D:\40_Reference\MuH5\angel\www\wwwroot\angel\resource\resource\cfg\config1.json"
LIVE = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\cfg_live.json"
OUT = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\report3.txt"

def load(p):
    with open(p, encoding="utf-8") as f:
        return json.load(f)

ref = load(REF); live = load(LIVE)
lines=[]; w=lines.append

def content_status(cfg, atype, aid):
    t = cfg.get(f"ActivityType{atype}Config")
    if t is None: return "noTable"
    if isinstance(t, list):
        return f"list[{len(t)}]"
    e = t.get(str(aid))
    if e is None: return "MISSING"
    if e in ({}, [], "", None): return "EMPTY"
    if isinstance(e,(dict,list)): return f"ok[{len(e)}]"
    return "ok"

rc = ref.get("ActivityConfig", {}); lc = live.get("ActivityConfig", {})
rb = ref.get("ActivityBtnConfig", {}); lb = live.get("ActivityBtnConfig", {})

ids = sorted(set(rc)|set(lc), key=lambda x:(int(rc.get(x,lc.get(x,{})).get("activityType",0)), int(x)))
w("id  | type | inLive | btnLive | contentLive | contentRef | desc / tabName")
w("-"*100)
for k in ids:
    src = rc.get(k, lc.get(k, {}))
    atype = src.get("activityType","?")
    inlive = "Y" if k in lc else "no"
    btn = "Y" if k in lb else ("REFonly" if k in rb else "-")
    cl = content_status(live, atype, k) if k in lc or k in rc else "-"
    crf = content_status(ref, atype, k)
    desc = (rc.get(k,{}).get("desc") or lc.get(k,{}).get("desc") or "")
    tab = (rc.get(k,{}).get("tabName") or lc.get(k,{}).get("tabName") or "")
    flag = ""
    if (inlive=="no") or cl in ("EMPTY","MISSING","noTable"):
        flag=" <== SUSPECT"
    w(f"{k:>4} | {str(atype):>4} | {inlive:>6} | {btn:>7} | {str(cl):>11} | {str(crf):>10} | {desc} / {tab}{flag}")

# search names hinting 'new server / open server'
w("\n=== activities whose desc/tabName hint NEW/OPEN SERVER ===")
import re
for k in ids:
    s = json.dumps(rc.get(k, lc.get(k,{})), ensure_ascii=False)
    if re.search(r"新服|开服|新區|新区|server|kaifu|开服|开服", s, re.I):
        w(f"  [{k}] {s}")

with io.open(OUT,"w",encoding="utf-8") as f: f.write("\n".join(lines))
print("written", OUT)
