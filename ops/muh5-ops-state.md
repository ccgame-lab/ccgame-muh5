# muh5 - Trạng thái ops (checkpoint resume sau compact)

> Cập nhật 2026-06-27. Ảnh chụp để resume sạch sau khi compact context. Plan canonical ở các file ops/ khác.

## ĐÃ SHIP (live prod muh5.ccgame.org)
- **Chí Tôn 380077**: thêm vào picklist `config/game_items.php` (admin chọn gửi mail được). Verified live.
- **Pet Đọa Thiên Sứ**: `config/pshop.php` `pet_doa_thien_su` -> game_item_id 500053, image pet_021, 60 Tôm, limit 1, bỏ coming-soon. (Clone Lôi Trạch, stats honest = Lôi Trạch, chuẩn hóa sau.)
- **10 danh hiệu bán**: `config/pshop.php` - bộ Hắc Ám 7 cấp (380030-380036, 20-55 Tôm) + 3 premium (Ngạo Thị 380023, Thiên Sứ 380024, Kì Tích Vương Giả 380038). Giao GM-mail như Chí Tôn. Giá = lead-call, owner chỉnh được.
- **SDK panel width-bump tạm**: `resources/sdk/src/styles/sdk.css` - desktop 480px (>=768), 560px (>=1280), mobile giữ 290px. (Mới band-aid, sẽ redesign full.)
- Deploy = `bash deploy.sh` (push master -> clone+rsync prod -> composer/optimize:clear -> smoke curl). prod KHÔNG phải git checkout.

## ĐANG CHỜ OWNER
- **Promo 72h** (nạp tiền tươi -> item qua GM-mail, KHÔNG bán Tôm): bài Zalo + bài FB đã soạn (Đọa Thiên Sứ ở mốc 100k). Owner: điền "admin" + đăng + tạo giftcode tân thủ. Runbook fulfillment: `ops/promo-72h-fulfillment.md`.
- **Mốc giá**: pet 60 Tôm + 10 danh hiệu giá ladder = lead-call, owner duyệt/chỉnh.

## ĐANG LÀM (phase design)
- **Redesign SDK responsive** (PC dashboard 2-3 cột, mobile thoáng, KHÔNG ẩn content - content vốn đã không ẩn, chỉ cramped) + **landing dopamine muh5.ccgame.org** (thay màn "need auth" cụt + thêm SEO).
- Index hiện: route `/` -> redirect `/play` -> `PlayController::entry` -> `play.blade.php`. Khách chưa-auth = màn fallback 🛡️ + nút "Về CCGame". Đây là chỗ buff dopamine.
- **Workflow design**: claude.ai/design (lean, không design-sync full) -> handoff -> implement. Bí kíp: brain `playbooks/claude-design-workflow.md`. Art mới = Gemini tab. Theme: dark #07070a + gold #c9a94e, font Outfit/Jakarta.
- **Art có sẵn (0 generate)**: hero `F:\Storage\10_Projects\MU Archangel H5\old_backup\mu.gcenter.fun\static\images\game\bzsch5\bg1-6.jpg`; logo `E:\40_Reference\MuH5\angel\...\icon\logo_web.png`; sprite `...\res\body\body101_*`; icon skill `...\image\qjzlicon\qjzl_icon_1-105.png`.
- Sau muh5 mới sang **trang hoàng index ccgame.org**.

## PLAN/DOSSIER (file)
- `ops/title-shop-design.md`: bán DANH HIỆU = +lực vĩnh viễn (đáng tiền); bán SLOT = cosmetic +0 lực (server-locked, hoãn). 57 danh hiệu free bán được (tId 22-78). Cơ chế titleslot server-side Lua, `@addtitleslot` GM cmd.
- `ops/engage-distro-plan.md`: feature 累充 (mốc nạp tích lũy event) = hook cao; FB group + giftcode tân thủ = distribution ROI cao (đúng directive "FB không Zalo"); **cờ dark-pattern `SpinService.php`** (nghi rate=0/near-miss giả) - cần audit 1 lần.

## RÀNG BUỘC
- Đụng ví/payment/settlement = FROZEN, owner duyệt. config:cache active prod (dùng config() không env()). Không em-dash. Game server (Lua/VPS) = Locked Mode, test server99 trước.
- Ship-fast: ưu tiên rollback hơn audit kỹ, bớt overbuild.
