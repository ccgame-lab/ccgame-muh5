# daily-pulse.ps1 - Nhịp ngày tự vận hành (REPORT-ONLY) cho ccgame-muh5
#
# Chạy 1 phiên Claude headless ở chế độ CHỈ ĐỌC: đọc repo + đề xuất, ghi báo cáo.
# KHÔNG sửa code, KHÔNG git, KHÔNG deploy (whitelist tool chỉ có Read/Grep/Glob + git đọc).
# Code thật chỉ chạy khi owner đọc report rồi mở phiên interactive ra lệnh.
#
# Cắm vào Windows Task Scheduler để tự chạy: xem ops/AUTONOMY.md.
# Tự chạy tay để test:  pwsh -File ops/daily-pulse.ps1

$ErrorActionPreference = 'Stop'
$repo = Split-Path -Parent $PSScriptRoot   # ops/ -> repo root
$reportDir = Join-Path $repo 'ops\reports'
if (-not (Test-Path $reportDir)) { New-Item -ItemType Directory -Path $reportDir | Out-Null }
$stamp = Get-Date -Format 'yyyy-MM-dd'
$report = Join-Path $reportDir "$stamp.md"

$sys = @'
Bạn là agent nhịp-ngày REPORT-ONLY cho ccgame-muh5. Tiếng Việt, KHÔNG ký tự em-dash.
Bạn CHỈ được đọc và phân tích. KHÔNG sửa file, KHÔNG git, KHÔNG deploy, KHÔNG đụng ví.
Mọi đề xuất là DRAFT chờ owner duyệt. Xuất Markdown ra stdout, không hỏi lại.
'@

$prompt = @'
Đọc CLAUDE.md và code repo ccgame-muh5. Tổng hợp "nhịp ngày" gọn:

1. TRẠNG THÁI: 3-5 commit gần nhất (git log), file đang dở (git status), có gì bất thường.
2. ĐỀ XUẤT (1-3 món, ship-first, nhỏ, rollback dễ): cải tiến game hoặc đường bán Tôm tuần này. Mỗi món kèm: việc cụ thể, ROI 1 dòng, rủi ro 1 dòng, có chạm ví/payment không (nếu có thì gắn cờ MODE safety-first).
3. CỜ ĐỎ: bug/nợ kỹ thuật/rủi ro kinh tế thấy được khi đọc (nếu có).

Bám invariant: ví GreenJade FROZEN, NEVER auto-refund, slug muh5, không dark pattern. Đề xuất kinh tế lớn thì ghi rõ "cần economy-architect + số liệu owner trước khi code".
Ngắn gọn, không dài dòng. Kết bằng 1 dòng: việc đáng làm NHẤT tuần này.
'@

Push-Location $repo
try {
  claude -p $prompt `
    --append-system-prompt $sys `
    --allowedTools "Read" "Grep" "Glob" "Bash(git log:*)" "Bash(git status:*)" "Bash(git diff:*)" `
    --disallowedTools "Edit" "Write" "Bash(git push:*)" "Bash(git commit:*)" `
    | Out-File -FilePath $report -Encoding utf8
}
finally { Pop-Location }

Write-Output "Report ghi tại: $report"
