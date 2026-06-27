# Fanpage CCGame mới - brand kit + runbook

> Quyết định 2026-06-27: page cũ **GreenJade** (vanity `gcenter.vn`, 42K follower) reach chết (post muh5/MU6 chỉ 1 share, reels 30-80 view) + dính **Facebook Gaming** (sản phẩm FB đã khai tử -> routing `/gaming/` hỏng khi logout = "ẩn danh không thấy"). -> Lập page mới sạch, brand **CCGame umbrella**, đăng bài qua **Meta Business Suite hẹn giờ** (KHÔNG auto-post browser-bot vì FB ban page).
> KHÔNG xoá page GreenJade cũ - giữ để cross-post kéo 42K sang page mới.

## 1. Tạo page (tay owner - 2 phút)
- **Tên page:** `CCGame - Game Private`
- **Category:** chọn **"Trò chơi điện tử"** (Video Game) hoặc **"Cộng đồng game"** (Community).
  - ⛔ TUYỆT ĐỐI KHÔNG chọn **"Người tạo video trò chơi" / "Gaming Video Creator"** - chính nó đẩy page cũ vào Facebook Gaming (đã khai tử) làm reach chết + ẩn danh không thấy. Đây là lỗi gốc của page cũ.
- **Bio/Tiểu sử:**
  ```
  🎮 CCGame - cổng game private cày cuốc, chơi thẳng trên trình duyệt (H5), không cần tải.
  ⚔️ Đang mở: MU Archangel H5 - vào là có đồ, cày là ra ngọc. Free tân thủ, auto reset, không pay-to-win.
  🔗 muh5.ccgame.org
  ```
- **Username (vanity):** thử claim `ccgame.org` / `ccgameprivate` / `ccgame.vn` (FB cho đặt khi page đủ điều kiện; KHÔNG tái dùng được `gcenter.vn` vì page cũ đang giữ).
- **Nút CTA:** "Chơi ngay" (Play game / Sử dụng ứng dụng) -> `https://muh5.ccgame.org`
- **Liên kết web:** `https://muh5.ccgame.org` (+ `https://ccgame.org` khi có)
- **Ảnh đại diện + ảnh bìa:** xem mục 2 (tôi render).

## 2. Ảnh brand (tôi chuẩn bị từ art MU thật, không generate)
- **Profile (1:1):** emblem "CCGame" gold-on-dark (hoặc icon game).
- **Cover (820x312 hiển thị, render 1640x624):** banner MU - chiến binh giáp vàng-xanh + wordmark "CCGame" + tagline "Vào là có đồ. Cày là ra ngọc." (gold #c9a94e / nền #07070a).
- File xuất ở `public/assets/landing/` hoặc bàn giao trực tiếp.

## 3. Cơ chế "agents vận hành" (an toàn, không ban)
- Agents (workflow `ccgame-fanpage-content`) sinh **batch bài** (hook + body + CTA + art + hashtag) -> lưu thành lịch nội dung.
- Đăng qua **Meta Business Suite** (business.facebook.com/latest/) -> **Lên lịch** (Scheduler). FB chính chủ cho phép hẹn giờ, KHÔNG tính là bot -> không ban.
- ⛔ KHÔNG dùng browser-automation bắn bài liên tục (FB phát hiện -> khoá page).
- Cadence gợi ý: 1-2 bài/ngày, khung 11-13h hoặc 19-22h (giờ game thủ online).

## 4. Lịch nội dung
- Batch đầu: sinh bởi workflow (xem file đính kèm / `ops/fanpage-content-batch.md` khi xong).
- Trộn loại: ra mắt (ghim) > class spotlight x6 > tính năng x3 > promo x2 > cộng đồng > tương tác x2 > khoe hình. Không spam 1 loại liên tiếp.

## 5. TODO owner
- [ ] Tạo page theo mục 1 (đúng category chuẩn, KHÔNG Gaming Creator).
- [ ] Gửi lại link page mới + link NHÓM FB để tôi wire vào landing (`fbUrl`/`fanpageUrl`) + CTA bài.
- [ ] Bật Meta Business Suite cho page (để hẹn giờ).
