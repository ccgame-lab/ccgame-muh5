# Runbook - Khuyến mãi 72h: Nạp VietQR -> nhận item qua GM mail

> Mục đích: hướng dẫn admin xử lý đợt KM 72h "nạp tiền tươi VietQR -> nhận item thẳng qua GM mail".
> Phạm vi: chỉ kênh VietQR chuyển khoản tay + GM mail. KHÔNG bán bằng Tôm/Kim Cương (để né bẫy 4.889 Tôm tồn trong ví).
> SePay tự động bỏ qua đợt này (không bonus được).
>
> Trạng thái verify (2026-06-27): cơ chế GM gửi item qua mail = **XANH ở mức ghi DB**, chứng minh bằng đường giao production của pshop (mua bằng Tôm dùng đúng đường này). CHƯA quan sát tận mắt game-server nhả mail vào hòm thư -> **bắt buộc test-send ở Bước 0 trước khi đăng bài.**

---

## 0. BẮT BUỘC LÀM TRƯỚC KHI ĐĂNG BÀI (pre-launch)

### 0a. Test-send thật (chuyển XANH-suy-luận thành XANH-quan-sát)
Trước khi mở KM, tự gửi cho nhân vật của chính mình:
1. Vào portal admin (Filament) -> **Users** -> tìm chính mình -> action **"Gửi thư/vật phẩm"**.
2. Gửi thử **pet Lôi Trạch (500052)** số lượng 1.
3. Vào game, mở hòm thư, xác nhận **nhận được mail + nhận đúng item**.
4. Sau khi làm xong Bước 0b, gửi thử tiếp **Chí Tôn (380077)** -> xác nhận nhận được Lệnh Bài + kích hoạt được danh hiệu.

Chỉ khi cả 2 item về tới hòm thư mới được đăng bài KM. Đây là điều DUY NHẤT chưa verify được bằng đọc code (consumer game-server không nằm trong repo này).

### 0b. PREREQUISITE CỨNG - thêm Chí Tôn (380077) vào picklist `game_items.php`
**Vì sao bắt buộc:** cả 3 đường gửi mail của portal (action "Gửi thư/vật phẩm" trên Users, GM Operations > Global Mail, GM Operations > Event Reward) đều lấy danh sách vật phẩm từ `config('game_items')` qua dropdown (KHÔNG có ô nhập ID tự do). Hiện `game_items.php` **CÓ** key `500052` (pet) nhưng **KHÔNG có** key `380077` (Chí Tôn) -> admin không chọn được Chí Tôn -> **không gửi được mốc 30k / 50k / 100k.**

**Cách sửa (owner tự làm - 1 dòng config, KHÔNG đụng code logic):**
mở `config/game_items.php`, thêm 1 dòng (đặt cạnh nhóm 380xxx cho gọn):
```php
'380077' => ['name' => 'Danh Hiệu Chí Tôn'],
```
Sau đó `php artisan config:clear` (nếu có cache config).

**An toàn:** đây chỉ là danh sách hiển thị (picklist) của portal admin. 380077 vốn đã là item game hợp lệ (đã được giao thành công qua pshop khi mua bằng Tôm, payload `1,380077,1`). Thêm dòng này KHÔNG đụng pshop, KHÔNG đụng giá, KHÔNG ảnh hưởng khách. Payload sinh ra giống hệt đường pshop đã chạy thật.

> Không có đường khác để admin gửi Chí Tôn qua portal nếu thiếu dòng này. Làm xong 0b mới làm test-send Chí Tôn ở 0a.

### 0c. QUYẾT ĐỊNH - 50 Tôm bonus (mốc 50k) đi đường nào?
"Tôm" KHÔNG phải currency trong game -> **KHÔNG gửi được qua GM mail.** Tôm là số dư ví GreenJade (hệ ngoài game, repo GREENJADE). Quy đổi: 1.000đ = 1 Tôm (do GreenJade quyết). Có 2 lựa chọn, owner chọn 1 trước khi chạy:

- **Option A - cộng Tôm tay ở GreenJade:** vào admin GreenJade -> **Ví Hệ Thống** -> tìm user -> nút **"Cộng ví để sửa sai lệch"** -> nhập `50`, lý do `KM72h bonus`. Nút này có ledger đúng + idempotency + audit log. *Lưu ý:* nút nằm trong domain ví/payment (FROZEN) và modal ghi "điều chỉnh sai lệch" - dùng cho bonus là đúng cơ chế nhưng hơi lệch nhãn. Đây là thao tác trên hệ tiền -> owner tự cân nhắc.
- **Option B - thay 50 Tôm bằng 1 item game** (vd thêm Bình EXP / vật phẩm hiếm có sẵn trong `game_items.php`): giữ mọi thứ trong 1 hệ (chỉ GM mail), không đụng domain ví FROZEN. Đơn giản và an toàn hơn cho vận hành.

> Khuyến nghị thiên về Option B nếu muốn vận hành gọn 1 hệ. Nhưng đây là quyết định của owner.

### 0d. Chốt item mốc 100k (whale tier)
Mốc 100k = combo + 1 item hiếm "owner chọn sau". Trước khi chạy:
- Chọn item, **verify ID đó CÓ trong `game_items.php`** (grep ID). Nếu chưa có -> thêm 1 dòng như Bước 0b.
- Điền vào bảng mapping bên dưới (đang để TODO).

---

## 1. Bảng mapping phần thưởng -> item ID -> lệnh gửi (đã verify trong repo)

| Phần thưởng | Đường giao | item ID | Payload game (tham khảo) | Verify |
|---|---|---|---|---|
| Pet Lôi Trạch | GM mail | **500052** | `1,500052,1` | `config/pshop.php:163`; CÓ trong `config/game_items.php:2464` |
| Danh hiệu Chí Tôn (Lệnh Bài) | GM mail | **380077** | `1,380077,1` | `config/pshop.php:149`; **THIẾU** trong `game_items.php` -> phải làm Bước 0b |
| 50 Tôm bonus | NGOÀI game (ví GreenJade) | n/a | n/a (không qua mail) | `config/economy.php:256` "muh5 ... KHÔNG xử lý tiền; 1.000đ = 1 Tôm" -> xem Bước 0c |
| Item hiếm 100k | GM mail | **TODO** | `1,<id>,1` | owner chọn + verify Bước 0d |

> Admin KHÔNG cần gõ payload bằng tay - chỉ chọn item trong dropdown + nhập số lượng, portal tự dựng payload. Cột payload chỉ để đối chiếu/kiểm tra.

Tên item hiển thị: Chí Tôn = "Danh Hiệu Chí Tôn" (`pshop.php`), pet = "★Truyền Thuyết★ Lôi Trạch" (`game_items.php:2464`).

---

## 2. Cấu trúc mốc (VND -> phần thưởng)

| Mốc | Phần thưởng | Item mail | Tôm/khác |
|---|---|---|---|
| 20k | Pet Lôi Trạch | 500052 | - |
| 30k | Danh hiệu Chí Tôn | 380077 | - |
| 50k | Combo cả 2 + 50 Tôm | 500052 + 380077 | 50 Tôm (Bước 0c) |
| 100k (whale) | Combo + 1 item hiếm | 500052 + 380077 + TODO | (theo combo) |

---

## 3. Quy trình admin xử lý 1 đơn

Thu của khách trước khi giao: **username tài khoản portal/game** (KHÔNG phải tên nhân vật, KHÔNG phải số ĐT). Công cụ GM tra theo `accountname` -> tự suy ra nhân vật. Sai username = không tìm thấy nhân vật, đơn kẹt.

### Các bước
1. **Nhận tiền VietQR.** Khách chuyển khoản, ghi nội dung gồm username portal. Mở app ngân hàng, xác nhận **tiền đã về thật** (đúng số tiền, đúng nội dung). KHÔNG tin ảnh chụp - phải thấy giao dịch vào tài khoản.
2. **Ghi đơn vào bảng theo dõi** (mục 5): thời gian, tên Zalo, username portal, số tiền, mốc, item.
3. **Kiểm tra trùng:** trước khi gửi, dò bảng theo dõi xem username này + mốc này đã ở trạng thái "đã giao" chưa. (Hệ thống KHÔNG tự chống trùng - xem mục 4.)
4. **Gửi item qua portal admin:**
   - Vào **Users** -> tìm đúng `username` -> action **"Gửi thư/vật phẩm"**.
   - Chọn **Máy chủ** -> hệ thống tự tra nhân vật (hiện "✅ tên nhân vật ... ID #..."). Nếu hiện "⚠️ KHÔNG TÌM THẤY NHÂN VẬT" -> dừng, hỏi lại khách username/đã tạo nhân vật chưa.
   - **Tiêu đề thư:** dùng tiêu đề CỐ ĐỊNH theo mốc để dễ dò trùng, vd `KM72h-20k`, `KM72h-30k`, `KM72h-50k`, `KM72h-100k`.
   - **Nội dung:** vd "Quà KM 72h - cảm ơn bạn đã ủng hộ server".
   - **Vật phẩm đính kèm:** chọn item theo mốc (mục 1), số lượng 1. Combo: thêm nhiều dòng (Chí Tôn + pet).
   - Bấm gửi -> hiện "Đã gửi vào queue".
5. **Mốc có Tôm (50k):** làm thêm Bước 0c Option đã chọn (cộng 50 Tôm ở GreenJade, hoặc đã thay bằng item).
6. **Đánh dấu đã giao** trong bảng: tick cột `item mail xong?`, và (nếu mốc có Tôm) tick `Tôm cộng xong?`. Đơn chỉ DONE khi cả 2 cột (với mốc combo) đều tick.
7. **Báo khách** (Zalo): "Đã gửi quà vào hòm thư trong game, bạn vào nhận nhé. Danh hiệu Chí Tôn nhận Lệnh Bài trong thư rồi dùng để kích hoạt."

---

## 4. Chống gửi trùng (idempotency) - QUAN TRỌNG

**Hệ thống KHÔNG có chống trùng tự động cho gửi tay.** Mỗi lần bấm gửi sinh 1 `action_uuid` mới + 1 job mới -> bấm 2 lần = khách nhận 2 lần. `gm_actions` chỉ là audit log để tra cứu, KHÔNG chặn double-send.

Chống trùng = **thủ tục, do admin tự kỷ luật:**
1. **Bảng theo dõi là nguồn sự thật.** Luôn dò bảng trước khi gửi. Đã có dòng "đã giao" cho username+mốc này -> KHÔNG gửi lại.
2. **Tiêu đề cố định per-mốc** (`KM72h-<mốc>`) giúp dò chéo: trong DB, `gm_actions.target_user` + tiêu đề này là cách duy nhất hệ thống cho phép kiểm "đơn này gửi chưa".
3. **Gửi xong tick ngay**, đừng để cuối buổi mới ghi -> tránh quên rồi gửi lại.
4. **Combo:** tick đủ cả 2 cột. Đừng coi đơn xong khi mới gửi item mà chưa cộng Tôm (hoặc ngược lại).

> Vì cấm sửa code (ví/payment FROZEN, chỉ đọc), không tự động hóa dedup được trong đợt này. Kỷ luật bảng theo dõi là chốt chặn.

---

## 5. Template bảng theo dõi đơn

Tách 2 cột hoàn tất riêng vì mốc combo đụng 2 hệ (mail trong game + ví GreenJade) - 1 ô tick không phản ánh được "đã gửi item nhưng chưa cộng Tôm".

| Thời gian | Tên Zalo | Username portal/game | Số tiền | Item/mốc | item mail xong? | Tôm cộng xong? |
|---|---|---|---|---|---|---|
| | | | 20k | Pet Lôi Trạch | ☐ | (n/a) |
| | | | 30k | Chí Tôn | ☐ | (n/a) |
| | | | 50k | Combo + 50 Tôm | ☐ | ☐ |
| | | | 100k | Combo + item hiếm | ☐ | (theo 0c) |

- **Username portal/game**: định danh để gửi (tra `accountname`). KHÔNG dùng tên nhân vật hay SĐT.
- **item mail xong?**: tick sau khi gửi mail thành công + (nên) đã thấy khách xác nhận nhận.
- **Tôm cộng xong?**: chỉ mốc có Tôm. Tick sau khi cộng ví GreenJade (nếu chọn Option A). Nếu chọn Option B (thay bằng item) thì ghi "n/a" và gộp vào cột item.

---

## 6. Sự cố thường gặp

| Triệu chứng | Nguyên nhân | Xử lý |
|---|---|---|
| "⚠️ KHÔNG TÌM THẤY NHÂN VẬT" | sai username, hoặc khách chưa tạo nhân vật trên server đó | hỏi lại username; bảo khách vào game tạo nhân vật rồi gửi lại |
| Chí Tôn không có trong dropdown | chưa làm Bước 0b | thêm `'380077'` vào `game_items.php` + `config:clear` |
| Khách báo chưa nhận sau vài phút | queue `gm` chưa chạy / game-server chưa tiêu lệnh | kiểm tra worker queue `gm` đang chạy; bảng `gmcmd` ở game DB có dòng chưa xử lý không |
| Lỡ bấm gửi 2 lần | không có dedup tự động | xác nhận với khách; nếu đã nhận 2 thì thu hồi tay trong game (GM) hoặc bỏ qua nếu giá trị nhỏ |

---

## Phụ lục - tham chiếu file:line (verify 2026-06-27)

- Action gửi mail tay: `app/Filament/Resources/Users/Actions/SendItemMailAction.php` (dropdown từ `config('game_items')` dòng 154; payload `1,{id},{count}` dòng 112; dispatch `SendGameMailJob` dòng 127).
- Job: `app/Jobs/SendGameMailJob.php:36` -> `GmApiService::sendItemMail`.
- Ghi DB game (cơ chế lõi): `app/Services/Game/GmApiService.php:36-52` insert bảng `gmcmd` (`cmd='sendMail'`, `param3`=playerId, `param4`=itemPayload).
- Đường giao production (chứng minh chạy thật): `app/Services/PointShopService.php:227-262` (`buildDeliveryPayload`) + `:271-303` (`dispatchGmMail`). Item thường -> `1,{game_item_id},1` (dòng 257). Đây là đường pshop dùng khi khách mua Chí Tôn/pet bằng Tôm.
- GM Operations (global + batch, cũng dropdown `game_items`): `app/Filament/Pages/GMOperations.php` (Global Mail :77-109, Event Reward :111-161, Topup List đã khóa :162-172).
- Mapping item: `config/pshop.php:140-185` (Chí Tôn 380077 dòng 149-150, pet 500052 dòng 163/169).
- `game_items.php`: pet `500052` có ở dòng 2464; **380077 KHÔNG có** (đã grep toàn repo, 380077 chỉ xuất hiện ở `pshop.php`).
- Tôm = ví GreenJade ngoài game: `config/economy.php:256`.
- Cộng Tôm tay (repo GREENJADE): `apps/server/app/Filament/Resources/WalletResource.php:94-155` nút "Cộng ví để sửa sai lệch" -> `WalletService::credit(AdminCredit)` (`apps/server/app/Domain/Ledger/Services/WalletService.php:19-89`, có idempotency + audit).
