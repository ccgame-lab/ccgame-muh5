import json, io

REF = r"D:\40_Reference\MuH5\angel\www\wwwroot\angel\resource\resource\cfg\config1.json"
LIVE = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\cfg_live.json"
OUT = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\report2.txt"

def load(p):
    with open(p, encoding="utf-8") as f:
        return json.load(f)

ref = load(REF); live = load(LIVE)
lines=[]; w=lines.append

def js(v): return json.dumps(v, ensure_ascii=False, indent=2)

w("===== ActivityType17Config =====")
w("REF :"); w(js(ref.get("ActivityType17Config")))
w("LIVE:"); w(js(live.get("ActivityType17Config")))

# ActivityConfig: find missing entry in live + any type==17 entry
rc = ref.get("ActivityConfig", {}); lc = live.get("ActivityConfig", {})
w("\n===== ActivityConfig key diff =====")
w("only in REF: " + str(sorted(set(rc)-set(lc))))
w("only in LIVE: " + str(sorted(set(lc)-set(rc))))

w("\n===== ActivityConfig entries referencing type 17 (REF) =====")
for k,v in rc.items():
    s=json.dumps(v, ensure_ascii=False)
    if '"type":17' in s or '"Type":17' in s or ':17,' in s or ':17}' in s:
        w(f"  [{k}] {s}")

w("\n===== ActivityConfig entry that's MISSING in live (full) =====")
for k in sorted(set(rc)-set(lc)):
    w(f"  REF[{k}] = {js(rc[k])}")

# ActivityBtnConfig diff
rb=ref.get("ActivityBtnConfig",{}); lb=live.get("ActivityBtnConfig",{})
w("\n===== ActivityBtnConfig key diff =====")
w("only in REF: " + str(sorted(set(rb)-set(lb))))
w("only in LIVE: " + str(sorted(set(lb)-set(rb))))
for k in sorted(set(rb)-set(lb)):
    w(f"  REF btn[{k}] = {js(rb[k])}")

with io.open(OUT,"w",encoding="utf-8") as f: f.write("\n".join(lines))
print("written", OUT)
