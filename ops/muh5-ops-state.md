# muh5 - Trạng thái ops (checkpoint resume)

> Cập nhật 2026-06-27 (phiên design+distribution). Ảnh chụp để resume sạch.

## KÊNH PHÂN PHỐI (canonical - đã thiết lập + wire landing)
| Kênh | Vai trò | Link | Trạng thái |
|---|---|---|---|
| **Game** | Sản phẩm | https://muh5.ccgame.org | Live (landing dopamine, OG, 3 class) |
| **FB Page** | Brand/announcement | **facebook.com/ccgameprivate** ("CCGame - Game Private", vanity đã đặt) | Live, bài ghim, **QC 99k đang chạy** |
| **FB Group** | Acquisition/cộng đồng | facebook.com/groups/gcenter.vn ("Chia Sẻ Game Lậu Cày Cuốc Miễn Phí", admin owner, 622+) | Live, tái dùng nhóm cũ |
| **Zalo Group** | Support/giftcode chat | https://zalo.me/g/naa2cur0bgwtessdzhon ("CCGame - MU Archangel H5") | Live, tin chào ghim |
| **Telegram** | Announcement/giftcode | https://t.me/ccgameorg ("CCGame - ccgame.org", + Lobby group discussion) | Live, post ghim |

Tất cả 4 kênh social ĐÃ wire vào landing section "Cộng đồng" (`fbUrl`/`fanpageUrl`/`zaloUrl`/`tgUrl`). Người vào từ QC → có đủ đường: chơi game + 4 kênh cộng đồng.

## OPS AUTOMATION (teammates + kênh nội bộ - 2026-06-27)
- **2 teammate subagent** (`.claude/agents/`, project source of truth - KHÔNG ở brain vì mang kiến thức dự án):
  - `ccgame-content` - soạn bài social/giftcode/announcement, giọng dân cày MU honest, khoá cứng 3 class + no-p2w-false-claim. Report-back, KHÔNG tự đăng/commit.
  - `ccgame-growth` - đọc metrics QC (CTR/CPC/cost-per-join), phán scale/kill, theo dõi growth kênh. Read-only, KHÔNG tiêu tiền.
  - README roster đã update (domain Distribution thêm, justified = trọn 1 phiên làm tay).
- **Kênh Telegram nội bộ "CCGame Ops (nội bộ)"** (private, chỉ owner): nơi đẩy draft/giftcode/metrics chờ duyệt trước khi ra public. Invite link nội bộ `t.me/+huS9VfYH6iM4M2Fl` (KHÔNG public). Note quy trình đã ghim.
- **Quy trình hiện tại (tay):** agent soạn → Claude chính đẩy draft (kênh nội bộ / chat) → owner duyệt → đăng public/hẹn lịch. Claude chính = bàn tay duy nhất (đăng/commit/deploy).
- **Bot tự đẩy (deferred):** cần owner tạo bot ở BotFather + token → script + scheduled task tự post theo lịch. Token giữ ngoài repo (`~/.secrets/`), KHÔNG commit. (Owner có lịch sử tạo/xoá bot: KiemTheM-BOT, GreenJade Storage BOT - hiện `/mybots` = none.)
**Còn cosmetic:** username page FB (bỏ profile.php?id); preview link Zalo trong post Telegram hơi xấu (id.zalo.me login) - bỏ qua được.

## ĐÃ SHIP LIVE prod (muh5.ccgame.org)
- **Landing dopamine** (`resources/views/landing.blade.php`): thay màn need-auth cho khách organic (case `no_session` ở `PlayController::entry`). Hero art knight thật + headline "Vào là có đồ. Cày là ra ngọc." + 6 layout family (đặc sắc, class-selector tương tác **3 class: MG Đấu Sĩ / DK Chiến Binh / ELF Tiên Nữ** - muh5 CHỈ có 3 class, không phải 6), sự kiện x10+giftcode+mốc nạp, gallery mosaic, cộng đồng) + SEO/OG. Thiết kế qua claude.ai/design -> port blade. Theme dark #07070a + gold #c9a94e, font Be Vietnam Pro + Playfair Display. Validate full (hero/class JS/responsive) + verify live. Commit 35205ad.
- **OG image landscape** (`public/assets/landing/og-image.jpg`, 1200x630): banner knight + wordmark CCGAME gold (render canvas). FB preview chuẩn. Commit 242644c.
- **Ảnh brand fanpage** (`public/assets/brand/`): `ccgame-cover.jpg` (1640x624) + `ccgame-profile.jpg` (600x600, emblem CC). Tải từ `muh5.ccgame.org/assets/brand/*`.
- (Trước đó) Chí Tôn picklist, pet Đọa Thiên Sứ, 10 danh hiệu, SDK panel width-bump tạm.

## FANPAGE CCGame - ĐÃ LIVE
- **Page "CCGame - Game Private"** đã tạo + brand xong: `facebook.com/profile.php?id=61591520487111`. Cover banner CCGAME + profile CC emblem (render canvas) + category "Trò chơi điện tử" (KHÔNG Gaming Creator) + CTA "Chơi game" -> muh5.ccgame.org.
- **Bài ra mắt #1 đã đăng + ghim** (đúng 3 class, sạch p2w).
- **QC ĐÃ DUYỆT + ĐANG CHẠY** (2026-06-27): boost bài ghim ra mắt, đối tượng Game (VN 18-44 MMORPG), **1 ngày** (hết 28/6), **90k + 9k VAT = 99k**. Page status "không có vấn đề nào". Mai xem kết quả ở Trung tâm quảng cáo (impression/click vào muh5.ccgame.org). Đây là TEST 1 ngày từ quỹ 117k - đo CPC/CTR rồi quyết scale hay đổi creative.
- **Nhóm FB acquisition = TÁI DÙNG nhóm cũ** `facebook.com/groups/gcenter.vn` ("Chia Sẻ Game Lậu Cày Cuốc Miễn Phí", admin owner, **công khai 622+**, KHÔNG dính Facebook Gaming nên không bị ẩn). Reach yếu (bài cũ ~11) nhưng hơn start-0 + tên generic SEO tốt. Đã wire link vào landing (`fbUrl`) + bài #9/#10/#12 (`facebook.com/groups/gcenter.vn`). `fanpageUrl` = page CCGame `profile.php?id=61591520487111`.
- **Wiring group-link ĐÃ DEPLOY LIVE** (commit 53a7e7d, verify HTML có `groups/gcenter.vn` + `profile.php?id=61591520487111`). Landing giờ có CTA nhóm/fanpage hoạt động thật.
- **Nhóm Zalo support ĐÃ TẠO + init** (2026-06-27): "CCGame - MU Archangel H5" (avatar CC, 3 thành viên seed). Tin chào + OG card đã đăng + ghim. **Link tham gia: https://zalo.me/g/naa2cur0bgwtessdzhon** (công khai, không cần duyệt). Kênh secondary: giftcode/support. CHƯA wire vào landing/content.
- **Còn:** wire link Zalo vào landing (thêm CTA Zalo ở section Cộng đồng) + content; đặt username page (đổi landing `fanpageUrl` sang vanity sau); đặt lịch 12 bài còn lại qua Business Suite (đã có link nhóm FB thật trong bài #9/#10/#12).

## 2 LỖI ĐÃ SỬA (owner bắt) - đã redeploy landing live
- **Class:** muh5 CHỈ 3 class = **MG Đấu Sĩ / DK Chiến Binh / ELF Tiên Nữ** (KHÔNG DW/DL/SUM). Sửa landing class-selector + headline "Ba class" + 13 bài content (bỏ 3 bài class không tồn tại).
- **P2W:** bỏ claim "KHÔNG pay-to-win / không bán sức mạnh" khỏi landing + 13 bài (muh5 BÁN pet+danh hiệu lấy Tôm = CÓ p2w, xem title-shop-design.md). Giữ phần đúng: "đồ mạnh nhất từ boss/sự kiện, không khoá sau webshop, cày là có". Internal ops docs giữ honest.

## FANPAGE - chi tiết
- Page cũ **GreenJade** (vanity gcenter.vn, 42K follower) reach chết + dính **Facebook Gaming** (FB khai tử -> ẩn danh không thấy). -> Lập page mới sạch. KHÔNG xoá page cũ (cross-post kéo 42K).
- Brand kit + spec tạo page: `ops/fanpage-ccgame.md`. Tên "CCGame - Game Private", **category CHUẨN (KHÔNG Gaming Video Creator)**, bio, CTA, vanity.
- 16 bài content + lịch 8 ngày: `ops/fanpage-content-batch.md` (sinh bởi workflow, giọng dân cày MU honest).
- Cơ chế: agents soạn -> **hẹn giờ Meta Business Suite** (KHÔNG auto-post browser-bot = tránh ban).
- **CHỜ OWNER:** tạo page (đúng category) + gửi link page mới + **link NHÓM FB** -> wire vào landing (`fbUrl`/`fanpageUrl` đang placeholder facebook.com) + bài #12/#13/#15.

## ĐANG LÀM / CHỜ
- **SDK redesign**: đã thiết kế xong ở claude.ai/design (project "MU Online SDK Redesign", file `MU SDK Overlay.dc.html`). PC = dashboard nhiều cột (trái player+ví+điểm danh, phải tài sản+feed+nhiệm vụ), gold accent đơn, currency = dot ngữ nghĩa. **CHƯA PORT**. Port = restyle 16 component Vue (`resources/sdk/src`) + `sdk.css` sang layout dashboard responsive, GIỮ data-binding/API. Job lớn, đụng UI live player -> làm cẩn thận. Sau port: `cd resources/sdk && npm run build` -> deploy.
- **Art pass landing - SPRITE XONG (LIVE)**: wire 3 sprite class thật vào class-selector. Mapping ĐÚNG (verify qua `job.lua`: body=jobId): **MG=body104, DK=body102, ELF=body103** (body101=job1=DW, muh5 KHÔNG dùng - summary cũ ghi DK=101 SAI). Crop frame idle từ sprite-sheet bằng node+sharp (đọc JSON res lấy rect frame đầu + trim). File `public/assets/landing/class-{mg,dk,elf}.png`. Commit 137807f, verify served 200. **Gallery CHƯA**: kho client chỉ UI+map-tiles vụn, cần 4-6 screenshot gameplay thật (owner chụp) hoặc dùng loading.jpg key-art.
- **SDK port dashboard (SCOPED, chưa làm)**: theo `resources/sdk/SDK_CONTRACT.md` "frontend = dumb renderer, re-theme qua sdk.css theme vars" -> port = re-skin `src/styles/sdk.css` + layout shell `src/App.vue`, GIỮ data-binding/API 15 component. Design target: PC = dashboard nhiều cột (trái player+ví+điểm danh, phải tài sản+feed+nhiệm vụ), gold accent, currency = dot. Build Vite `npm run build` -> output `public/assets/sdk/`. Làm kỹ (UI live player): restyle -> build -> smoke -> deploy.

## RÀNG BUỘC
- Ví/payment/settlement = FROZEN, owner duyệt. config:cache active prod (dùng config()). Không em-dash. Game server Lua/VPS = Locked Mode (SDK web KHÔNG thuộc khoá này - sửa được). Push master cần owner authorize (harness chặn auto).
- Deploy = `bash deploy.sh` (preflight tree sạch + HEAD==origin/master -> clone+rsync prod -> composer --no-dev -> optimize:clear -> smoke). Prod KHÔNG phải git checkout.
- Ship-fast: ưu tiên rollback hơn audit kỹ.
