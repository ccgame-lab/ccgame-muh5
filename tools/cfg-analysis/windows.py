import json, io, re

LIVE = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\cfg_live.json"
OUT  = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\report4.txt"

with open(LIVE, encoding="utf-8") as f:
    live = json.load(f)
ac = live.get("ActivityConfig", {})

def dayoff(s):
    m = re.match(r"^(\d+)-(\d+):(\d+)$", str(s))
    return int(m.group(1)) if m else None

DAY_OPEN = 76     # 05/06 - 21/03 (timeType1: theo open server)
DAY_HEFU = 1240   # ~ 05/06/2026 - 13/01/2023 (timeType3: theo hefu)

lines=[]; w=lines.append
w("id  | type | timeType | startDay | endDay | OPEN now? | desc")
w("-"*80)
openlist=[]
for k,v in sorted(ac.items(), key=lambda kv:int(kv[0])):
    tt = v.get("timeType")
    sd, ed = dayoff(v.get("startTime","")), dayoff(v.get("endTime",""))
    base = {1:DAY_OPEN, 3:DAY_HEFU}.get(tt)
    if tt==2:
        status="fixed(time)"
    elif base is None or sd is None or ed is None:
        status=f"?? tt={tt} st={v.get('startTime')} et={v.get('endTime')}"
    else:
        isopen = sd <= base < ed
        status = "YES" if isopen else "no"
        if isopen: openlist.append(k)
    w(f"{k:>4} | {str(v.get('activityType')):>4} | {str(tt):>8} | {str(sd):>8} | {str(ed):>6} | {status:>10} | {v.get('desc')}")

w(f"\n=> Activity MO bay gio: {openlist}")
with io.open(OUT,"w",encoding="utf-8") as f: f.write("\n".join(lines))
print("written", OUT)
