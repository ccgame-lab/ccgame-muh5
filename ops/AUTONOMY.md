# Autonomy ccgame-muh5 (đọc trước khi kỳ vọng "tự chạy 24/7")

## Sự thật về "agent tự vận hành"
- Subagent Claude Code (`.claude/agents/`) CHỈ sống trong 1 phiên đang mở. Không phải daemon.
- `CronCreate` trong Claude Code cũng KHÔNG phải daemon: chỉ fire khi REPL Claude đang mở + idle, tự hết hạn sau 7 ngày. Tắt Claude là cron chết.
- Muốn TỰ CHẠY thật khi không ngồi máy: dùng Windows Task Scheduler gọi `ops/daily-pulse.ps1` (phiên Claude headless `claude -p`).

## daily-pulse = nhịp ngày REPORT-ONLY
- Đọc repo + đề xuất 1-3 món ship-first + cờ đỏ, ghi `ops/reports/<ngày>.md`.
- Whitelist tool CHỈ Read/Grep/Glob + git đọc. KHÔNG Edit/Write/commit/push/deploy. An toàn để chạy tự động.
- Code thật KHÔNG tự chạy: owner đọc report, thấy món đáng làm thì mở phiên interactive ra lệnh, Claude chính code lên branch chờ duyệt (draft mode). Đụng ví/payment luôn hỏi trước.

## Test tay trước khi cắm lịch
```powershell
pwsh -NoProfile -File D:\10_Projects\CCGame\ccgame-muh5\ops\daily-pulse.ps1
# xem D:\10_Projects\CCGame\ccgame-muh5\ops\reports\<ngày>.md
```
Cần: `claude` CLI trong PATH (đã đăng nhập), `pwsh` (PowerShell 7).

## Cắm Windows Task Scheduler (owner tự chạy, KHÔNG để AI tự đăng ký)
Tự chạy = tốn quota + là autonomy hướng ngoài, nên owner bật có chủ đích:
```powershell
schtasks /create /tn "ccgame-muh5-pulse" /sc daily /st 09:07 /f `
  /tr "pwsh -NoProfile -File D:\10_Projects\CCGame\ccgame-muh5\ops\daily-pulse.ps1"
```
- Tắt: `schtasks /delete /tn "ccgame-muh5-pulse" /f`
- Chạy thử ngay: `schtasks /run /tn "ccgame-muh5-pulse"`

## Nhịp đề xuất (không bắt buộc)
- Hằng ngày 1 pulse là đủ cho quy mô hiện tại. Đừng để nhiều hơn (nhiễu + tốn quota).
- Mỗi tuần owner lướt 7 report, gom việc đáng làm, rồi 1 phiên code dồn. Quan sát vài tuần rồi mới tăng tự chủ.
