import sys, re
JS = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\main.min.js"
src = open(JS, encoding="utf-8", errors="replace").read()

sym = sys.argv[1] if len(sys.argv) > 1 else "isOpenActivity"
before = int(sys.argv[2]) if len(sys.argv) > 2 else 60
after = int(sys.argv[3]) if len(sys.argv) > 3 else 500

OUT = r"C:\Users\QuangQuoc\AppData\Local\Temp\muh5cfg\ex_out.txt"
idxs = [m.start() for m in re.finditer(re.escape(sym), src)]
import io
with io.open(OUT, "w", encoding="utf-8") as f:
    f.write(f"### {sym}: {len(idxs)} hits\n")
    for i, p in enumerate(idxs):
        seg = src[max(0, p-before):p+after]
        f.write(f"\n--- hit {i} @ {p} ---\n{seg}\n")
print("wrote", len(idxs), "hits to", OUT)
