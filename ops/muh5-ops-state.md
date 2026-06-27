# muh5 - Trạng thái ops (checkpoint resume)

> Cập nhật 2026-06-27 (phiên design+distribution). Ảnh chụp để resume sạch.

## ĐÃ SHIP LIVE prod (muh5.ccgame.org)
- **Landing dopamine** (`resources/views/landing.blade.php`): thay màn need-auth cho khách organic (case `no_session` ở `PlayController::entry`). Hero art knight thật + headline "Vào là có đồ. Cày là ra ngọc." + 6 layout family (đặc sắc, class-selector tương tác **3 class: MG Đấu Sĩ / DK Chiến Binh / ELF Tiên Nữ** - muh5 CHỈ có 3 class, không phải 6), sự kiện x10+giftcode+mốc nạp, gallery mosaic, cộng đồng) + SEO/OG. Thiết kế qua claude.ai/design -> port blade. Theme dark #07070a + gold #c9a94e, font Be Vietnam Pro + Playfair Display. Validate full (hero/class JS/responsive) + verify live. Commit 35205ad.
- **OG image landscape** (`public/assets/landing/og-image.jpg`, 1200x630): banner knight + wordmark CCGAME gold (render canvas). FB preview chuẩn. Commit 242644c.
- **Ảnh brand fanpage** (`public/assets/brand/`): `ccgame-cover.jpg` (1640x624) + `ccgame-profile.jpg` (600x600, emblem CC). Tải từ `muh5.ccgame.org/assets/brand/*`.
- (Trước đó) Chí Tôn picklist, pet Đọa Thiên Sứ, 10 danh hiệu, SDK panel width-bump tạm.

## FANPAGE CCGame (việc distribution mới)
- Page cũ **GreenJade** (vanity gcenter.vn, 42K follower) reach chết + dính **Facebook Gaming** (FB khai tử -> ẩn danh không thấy). -> Lập page mới sạch. KHÔNG xoá page cũ (cross-post kéo 42K).
- Brand kit + spec tạo page: `ops/fanpage-ccgame.md`. Tên "CCGame - Game Private", **category CHUẨN (KHÔNG Gaming Video Creator)**, bio, CTA, vanity.
- 16 bài content + lịch 8 ngày: `ops/fanpage-content-batch.md` (sinh bởi workflow, giọng dân cày MU honest).
- Cơ chế: agents soạn -> **hẹn giờ Meta Business Suite** (KHÔNG auto-post browser-bot = tránh ban).
- **CHỜ OWNER:** tạo page (đúng category) + gửi link page mới + **link NHÓM FB** -> wire vào landing (`fbUrl`/`fanpageUrl` đang placeholder facebook.com) + bài #12/#13/#15.

## ĐANG LÀM / CHỜ
- **SDK redesign**: đã thiết kế xong ở claude.ai/design (project "MU Online SDK Redesign", file `MU SDK Overlay.dc.html`). PC = dashboard nhiều cột (trái player+ví+điểm danh, phải tài sản+feed+nhiệm vụ), gold accent đơn, currency = dot ngữ nghĩa. **CHƯA PORT**. Port = restyle 16 component Vue (`resources/sdk/src`) + `sdk.css` sang layout dashboard responsive, GIỮ data-binding/API. Job lớn, đụng UI live player -> làm cẩn thận. Sau port: `cd resources/sdk && npm run build` -> deploy.
- **Art pass landing**: wire sprite 6 class (class-selector) + 6 screenshot gallery (boss/pvp/map/cánh/ui/bxh) từ kho art (E:\40_Reference\MuH5, F:\Storage). Hiện để placeholder (nhìn intentional). Sau wire -> redeploy.

## RÀNG BUỘC
- Ví/payment/settlement = FROZEN, owner duyệt. config:cache active prod (dùng config()). Không em-dash. Game server Lua/VPS = Locked Mode (SDK web KHÔNG thuộc khoá này - sửa được). Push master cần owner authorize (harness chặn auto).
- Deploy = `bash deploy.sh` (preflight tree sạch + HEAD==origin/master -> clone+rsync prod -> composer --no-dev -> optimize:clear -> smoke). Prod KHÔNG phải git checkout.
- Ship-fast: ưu tiên rollback hơn audit kỹ.
