# PLAN - Engagement + Distribution (muh5) [action-first]

> Ngày: 2026-06-27. Gộp 2 nghiên cứu (legacy engagement của bản thương mại mu.gcenter.fun + map kênh distribution VN).
> Đã validity-gate lại với repo muh5 thật - LOẠI các recommend thừa (cái muh5 đã có) trước khi viết. Không số liệu nội bộ -> KHÔNG chốt giá/mốc ở đây.

## Validity-gate đã chạy (vì sao plan ngắn lại)
Legacy report đề xuất "thêm thẻ tuần/tháng/trọn đời + daily deal". Check repo: `config/pshop.php` ĐÃ CÓ `monthly_card`, `lifetime_card`, `zen_card_1d/3d/7d`. -> **Cards KHÔNG phải gap. Bỏ.** Hệ in-game (arena/chuyển sinh/cánh/bang) nằm trong client -> không phải "build", bỏ. Gap THẬT owner kiểm soát ở web còn lại rất ít - liệt kê dưới.

---

## 1. TOP FEATURE NÊN VIBE (xếp theo ROI: hook cao / build nhỏ trước)

| # | Feature | Hook | Build | Bản chất | Vì sao đáng |
|---|---|---|---|---|---|
| 1 | **累充 Mốc tích lũy nạp (time-boxed mừng server)** | **CAO** | **Vừa** (service mới: sum nạp trong cửa sổ + trang claim, tái dùng GM-mail kiểu giftcode + transaction log có sẵn) | Monetization NHƯNG khung event-hype hợp lệ | Đòn bẩy mạnh nhất + genuine-fun THẬT khi làm dạng event: mốc minh bạch, hiển thị "đã nạp X/Y", deadline theo launch. Đây là pick duy nhất vừa fun vừa revenue - **lead bằng cái này.** Confirmed absent ở portal. |
| 2 | **Daily deal (mỗi ngày 1 gói giá sốc, đổi món)** | Vừa | **Nhỏ** (1 entry pshop xoay theo ngày + rebuy-guard/ngày, tái dùng flow buy-tom) | Monetization thuần (chấp nhận nếu tỉ giá minh bạch) | Tạo lý do mở web mỗi ngày, ghép với checkin sẵn có. Build rẻ nhất. |
| 3 | **Mission/quest tân thủ tuần đầu (D1-D7) gắn reward leo thang** | Vừa-CAO | Nhỏ-Vừa (đã có `/api/sdk/missions` - mở rộng tuyến tân thủ + claim mốc) | GENUINE (retention thật, không bán gì) | Giữ chân D1-D7 - cửa sổ churn cao nhất của player mới từ toplist. Tái dùng hệ mission đang có. |

> KHÔNG bịa thêm "fun" cho monetization SKU. Hệ engagement genuine (arena/rebirth/cánh) đã nằm trong client - không phải gap để vibe.

### Tránh các dark-pattern này (từ bản gốc, ĐỪNG lặp)
- **Mốc 累充 cao ghi số lượng quà "999.999.999"** = bait kích nạp tối đa. Mốc phải là quà thật, số thật.
- **Vòng quay rig `rate=0`**: bản gốc đặt jackpot hiển thị trên bánh xe nhưng `rate=0` (bất khả trúng) + ô "Mất lượt". muh5 có `SpinService.php` riêng -> **FLAG: audit `SpinService.php` xem có entry `rate=0` / near-miss giả không** (1 việc nhỏ, không mở rộng ở đây).
- Không ẩn drop rate, không paywall ẩn, không gacha lừa (theo stance).

---

## 2. DISTRIBUTION PLAYBOOK (xếp theo "ra player nhanh + rẻ")

> Quyết định: **làm TRƯỚC = FB group (free, đúng tệp H5) + giftcode tân thủ.** Trả VIP toplist là quyết định ngân sách của owner (mục 4), không phải nước đi mặc định.

| # | Kênh | Loại | Bước đăng | Content mẫu |
|---|---|---|---|---|
| 1 | **FB group đúng tệp H5/mobile**: "MU mobile lậu Vn" (groups/1525864370817919) + "Game MU ONLINE new 2026" (groups/2250847274963198) | Nhóm player thật | Đăng clip/ảnh gameplay + **giftcode tân thủ**; KHÔNG spam QC trực tiếp (dễ ban) -> đăng dạng "ra mắt server + tặng code", trả lời comment, để member review hộ | "Server MU H5 [tên] open [ngày] - cày phê, đồ dễ, không p2w PvP. Code tân thủ: [CODE] (100 suất đầu). Link vào game: [url]. AE vào sớm chọn top." + 1 clip PK/boss 15-20s |
| 2 | **Viral share-loop (thưởng item)** | Player tự phát tán | Trong game/web: player share bài fanpage sang N nhóm được duyệt -> nhận thưởng KC/xu/ngày. Tái dùng giftcode/mail có sẵn | "Share bài này + tag 3 bạn -> nhận [X] Kim Cương/ngày. Mỗi mốc share toàn server mở quà chung." (con số chốt sau, mục 4) |
| 3 | **webgamelau.com** (toplist H5, đúng sản phẩm hơn mumoira) | Toplist H5 | Cần account; có sẵn thread MU H5 web. Free listing + VIP trả phí để nổi | Tiêu đề "MU H5 Web [tên] - Open [ngày] - Xanh chín, không p2w PvP, code tân thủ" + mô tả tính năng + lịch open đếm ngược |
| 4 | **mumoira.tv/.vip** (toplist MU lớn nhất, burst) | Toplist (thiên PC) | Nút "Đăng MU Mới" + form; VIP/banner trả phí (gọi 0767.778.788 hỏi giá). Free bị 80+ server đè | Như #3, nhấn "open beta" + giờ mở chính xác để bắt sóng search "MU mới ra" |

**Bỏ qua**: forum forumvi (gameprivate/infogame) - bài mới nhất ~12/2025, gần như chết, chỉ tốt backlink SEO.

**Burst vs bền**: muốn đông tức thì giờ-open = trả VIP webgamelau/mumoira (player churn cao, server-hopper). Muốn player bền + rẻ = FB group + share-loop (compound, gần 0 chi phí). Cho server nhỏ mới: **FB+share-loop là xương sống, VIP chỉ là gia vị ngày open.**

---

## 3. NƯỚC ĐI TUẦN NÀY (2 cái ROI cao nhất - ship được)

1. **VIBE: Mission/quest tân thủ D1-D7** (mục 1 #3) - tái dùng `/api/sdk/missions` đang có, build nhỏ-vừa, GENUINE, đỡ churn player mới ngay. (Nếu muốn revenue-first thay bằng Daily deal #2 - build nhỏ hơn nhưng là monetization thuần.)
2. **DISTRO: Đăng 2 FB group đúng tệp + phát giftcode tân thủ** (mục 2 #1) - chi phí 0, ra player chất ngay tuần này. Chuẩn bị: 1 clip gameplay 15-20s + 1 batch giftcode tân thủ (đã có hệ giftcode).

> 累充 (mục 1 #1) hook cao nhất nhưng build Vừa -> đưa vào sprint kế, không phải "tuần này" nếu muốn ship gọn.

---

## 4. QUYẾT ĐỊNH CẦN OWNER CHỐT

- **[Số liệu - validity-gate]** Mốc giá + mốc 累充 + số xu share-loop + giá daily deal: **CẦN số liệu nội bộ (payer %, ARPU, source/sink)** trước khi chốt. Plan này cố ý KHÔNG ghi con số để tránh critique rác.
- **[Scope tuần này]** Chọn 1: Mission tân thủ (genuine, đỡ churn) HAY Daily deal (build nhỏ hơn, revenue) làm feature vibe đầu.
- **[Ngân sách kênh]** Có chi VIP toplist (webgamelau/mumoira) cho burst ngày open không? Gọi 0767.778.788 hỏi bảng giá mumoira nếu có.
- **[Share-loop]** Duyệt cơ chế thưởng share + danh sách nhóm được phép share (tránh ban acc player).
- **[Audit]** Duyệt việc audit `SpinService.php` (check `rate=0`/near-miss giả) - 1 việc nhỏ, làm để chắc không dính dark-pattern.
