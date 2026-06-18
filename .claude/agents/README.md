# Đội agent ccgame-muh5

Đây là **subagents** (report-back, mỗi đứa 1 context riêng, gọi qua Agent tool) - KHÔNG phải agent-teams (experimental, tốn token). Quy mô nhỏ solo: mặc định Claude chính tự làm; chỉ triệu agent khi đúng cửa dưới.

Claude chính = bàn tay DUY NHẤT (Edit/Write/Commit/Deploy) + người tổng hợp. Agent là bộ não/bàn tay mở rộng theo domain, KHÔNG tự commit/deploy.

## 3 đội theo domain

| Đội | Agent | Khi gọi | Cấm |
|---|---|---|---|
| **Portal** (Laravel/PHP + Vue SDK + Filament) | `muh5-scout` | Cần map cấu trúc/call flow/hotspot module lớn, trả `file:line` | đề xuất, thiết kế, kết luận patch, phản biện kinh tế |
| **Portal - kinh tế** | `economy-architect` | Proposal lớn về tiền/economy (gói Tôm, pricing, sink/source, reward, anti-abuse) - phản biện qua deepseek-v4-pro | đọc nguyên code (gọi scout), viết code, deploy |
| **Game server** (core Lua/C++ VPS `/opt/muh5`) | `muh5-gameserver-ops` | Áp patch Lua/config, restart shard, đọc log, dựng shard - qua SSH | binary/DB schema/cross-server, ví, client Egret |
| **Game client** (Egret config1.json + assets R2) | `muh5-client-ops` | Việt hoá/sửa config1.json, pipeline cfg-i18n, phân tích cfg, đẩy assets R2 | server core Lua, ví, game.min.js logic |

## Ranh giới quan trọng
- **Server vs Client tách bạch**: gameserver-ops lo core Lua/C++ trên VPS (cái server chạy). client-ops lo config/assets Egret (cái người chơi tải). Đụng ZS tier/level cap = phải sync CẢ 2 -> đứa nào đụng phải cờ đỏ "cần sync phía kia", không tự đẩy lệch.
- **Ví GreenJade / payment**: không đội nào đụng. Safety-first, hỏi owner trước.
- Giá trị độc lập THẬT chỉ ở `economy-architect` (model deepseek, ngoài Claude). 3 đứa kia chạy Claude = bàn tay/kính lúp theo domain.

## Không thêm agent trừ khi có bằng chứng thiếu
Thêm agent = thêm overhead + dễ drift. Chỉ thêm khi production evidence cho thấy 1 domain đang thiếu người thật sự, không phải vì "cho đủ bộ".
