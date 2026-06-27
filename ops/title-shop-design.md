# DOSSIER THIẾT KẾ - Bán Danh Hiệu + Titleslot (muh5)

> Trạng thái: THIẾT KẾ (chưa implement). Map tổng hợp từ 3 nguồn (portal Laravel + server VPS). Mọi khảo sát đọc READ-ONLY, không sửa/restart/chạy gì làm đổi trạng thái server.
> Canonical: server `itemdata.lua` + `title.lua` là nguồn thật cho item->titleid->LC. Portal `game_items.php` lệch tên/offset ở vài dải - KHÔNG tin nhãn portal. Shop bán = `config/pshop.php` (list phẳng).
> Ngày: 2026-06-27.

---

## 0. TL;DR cho owner

- **Bán thêm danh hiệu: KHẢ THI NGAY, không sửa server, không sửa code portal logic** - chỉ thêm entry vào `config/pshop.php` với `game_item_id = 380xxx`. Cơ chế giao (GM mail item -> người chơi dùng item kích hoạt title) đã chạy sẵn cho Chí Tôn.
- **Kho an toàn để bán**: 57 danh hiệu "tự do" (tId 22-78, item 380020-380076) + tId 21 (item 380019). Đây là các title CHỈ có item, không phá phần thưởng event/rank/guild/VIP/ZS/boss nào.
- **Bán slot chồng danh hiệu (titleslot 1->5): KHÔNG có đường portal.** titleslot thuần server-side Lua. Phải làm cơ chế MỚI trên server (item-use handler gọi `UPDATE actors SET title_slots`). Đây là phần khó, phải động vào server (đang LOCKED) - làm sau, test trên dev shard server99.
- **Điểm sống còn về monetization**: lực chiến cộng từ title SỞ HỮU (vĩnh viễn, dù đeo hay không). Slot chỉ giới hạn SỐ title HIỂN THỊ trên đầu (cosmetic). **Bán slot = +0 lực chiến.** Bán title = +lực chiến vĩnh viễn.

---

## 1. Cơ chế titleslot - hoạt động thế nào

**Toàn bộ server-side (Lua). Portal không biết gì về titleslot** (grep `titleslot|title_slot` toàn repo portal = 0 hit trong code).

### File trên server (VPS `/opt/muh5/server1/`)
| File | Vai trò |
|---|---|
| `gameworld/data/functions/systems/title/titlesystem.lua` | LÕI - lưu/đeo/slot/attr |
| `gameworld/data/functions/systems/title/addtitlelogic.lua` | cấp title theo rank PvP/PK |
| `gameworld/data/functions/systems/title/titlemilestone.lua` | cấp title theo mốc Chuyển Sinh (ZS) |
| `gameworld/data/config/title/title.lua` | config 82 danh hiệu (id + tên + attrs + keepTime) |
| `gameworld/data/config/title/ranktitle.lua` | map rank -> titleId |
| `gameworld/data/config/item/itemdata.lua` | item 380xxx -> titleid (`useType=2, useArg={titleid=N}`) |
| `gameworld/data/functions/systems/actor/item.lua` (dòng ~105-116) | handler `useTitle`: dùng item -> `titlesystem.addTitle(actor, tId)` |
| `actor_dir/actor_db.sql` (dòng 90) | schema cột `title_slots` |

### Mở slot bằng gì
- Hằng số: `MAX_EQUIP_TITLES = 5`, `DEFAULT_TITLE_SLOTS = 1`, `MAX_TITLE_SLOTS = 5` (`titlesystem.lua:6-8`). Comment nói "max 6" + có field t6 nhưng **t6 là vestigial** - chỉ đọc/ghi t1..t5. **Trần cứng = 5.**
- Số slot lưu ở **cột DB `actors.title_slots`** (tinyint unsigned, DEFAULT 1, comment 'Title equip slots'). Query `SELECT title_slots FROM actors WHERE actorid=%d` (`titlesystem.lua:169`), clamp 1..5, cache RAM.
- **Hiện CHỈ có 1 đường mở slot: GM command `@addtitleslot <count>`** (`titlesystem.lua:629`). Nó `UPDATE actors SET title_slots=%d WHERE actorid=%d` (line 639), clamp 1..5, update cache, gửi lại client.
- **KHÔNG có item nào mở slot.** Item cao nhất hiện dùng = 380077.
- **`@addtitleslot` SET giá trị tuyệt đối, KHÔNG cộng dồn** (clamp 1..5). Đây là điểm thiết kế quan trọng cho phần "bán từng slot 2/3/4/5 riêng" (xem mục 4 + 7).

### Chống tới 5 ra sao
- Title đã đeo lưu ở **BSON trên actor** (`getStaticVar(actor).titleData.roleTitle`), mỗi entry flat `{id, t1..t5}`.
- Chặn khi đeo: `if #arr >= getTitleSlots(actor) then return end` ở `setTitle` (line 535) + `autoWear` (line 353). `title_slots=1` chỉ đeo 1; mở lên 5 thì đeo 5.
- **Slot per-actor, áp cho cả account; đeo chỉ trên main role** (`setRoleTitle` reject `roleId ~= 0`).

### Cộng dồn lúc chiến KHÔNG - điểm then chốt (đừng định giá sai)
`updateAttr` (titlesystem.lua) duyệt `var.titles` = tập **SỞ HỮU**, KHÔNG phải mảng equipped:
```lua
local titles = var.titles            -- tập SỞ HỮU
for k,v in pairs(conf) do
    if titles[k] then for _,attr in pairs(v.attrs) do attrs:Add(attr.type, attr.value) end end
end
```
- **Sở hữu 1 title = +lực chiến vĩnh viễn, mãi mãi**, dù đeo hay không. (title keepTime=0 = vĩnh viễn.)
- **Slot chỉ giới hạn SỐ title HIỂN THỊ trên đầu** (cosmetic). KHÔNG liên quan lực chiến.
- => Bán slot = bán "chỗ hiện danh hiệu" (+0 lực). Bán title (item) mới là bán lực.

### Lưu DB cột nào
- Slot: `actors.title_slots` (đã tồn tại sẵn - **không cần đổi schema**).
- Title sở hữu/đeo: BSON trên actor (game engine quản, không phải cột SQL phẳng).

---

## 2. Kiểm kê danh hiệu BÁN ĐƯỢC

**Nguồn canonical = server `itemdata.lua` (item->titleid) + `title.lua` (titleid->tên/LC/keepTime).** Tên dưới đây là **tên server** (canonical). Portal `game_items.php` có dải lệch tên/offset (đặc biệt 380009-380014) - không dùng nhãn portal làm chuẩn.

> **Lưu ý LC**: số "LC" dưới là **số nguyên trọng-số Lua** (`LC = floor(Σ power[type]×value /100)`), KHÔNG phải con số lực chiến hiển thị in-game. Portal quảng cáo Chí Tôn "~1.5 triệu" trong khi trọng-số Lua = 15600 (~100x lệch) + còn caveat atAtk-doubling chưa xác định. **Chỉ dùng LC để xếp HẠNG tương đối (std 6600 < Chí Tôn 15600), đừng anchor giá Tôm vào "LC = X" như con số người chơi thấy.**

### 2a. NHÓM TỰ DO BÁN ĐƯỢC (an toàn - chỉ có item, không phá thưởng nào)

| tId | Tên (server) | Item | LC | Giữ | Ghi chú |
|----|-----|------|----|-----|---------|
| 21 | Roland dũng sĩ | 380019 | 900 | ∞ | chỉ có item, không reward nào trỏ tId21 |
| 22-78 | (xem nhóm dưới) | 380020-380076 | **6600** (std) | xem dưới | chỉ có item, không reward/rank/event/ZS nào trỏ tới |

**Map id->item nhóm 22-78**: tId N -> item `380000+(N-1)` cho N=22..41 (vd tId22=380020, tId41=380039), rồi liên tục tId42=380040 ... tId78=380076.

Profile chuẩn nhóm 22-78: `{type1=10000, type2=100, type3=100, type4=100}` = **6600 LC đồng nhất**. Phân theo keepTime (định giá khác nhau - vĩnh viễn vs hết hạn là 2 sản phẩm):

- **Vĩnh viễn (keepTime=0)** - kho lớn nhất để bán shop: tId 22, 25, 26, 27, 28, 30, 32-41, 43-60.
- **7 ngày (604800)**: tId 23 (item 380021), 24 (380022).
- **3 ngày (259200)**: tId 29 (380027), 31 (380029).
- **1 ngày (86400)**: tId 42 (380040), 61-78 (380059-380076) - đều là bản "- 1 ngày" của các danh hiệu vĩnh viễn 43-60. Dùng làm gói rẻ / dùng thử.

> Caveat lọc trước khi list public: nhóm này có vài tên TRÙNG nhau và nhiều cặp "vĩnh viễn" + "- N ngày" cùng tên. Chọn cẩn thận để shop không hiện 2 entry trùng tên.

### 2b. NHÓM ĐỪNG ĐỘNG VÀO (đang là phần thưởng - bán = phá hệ thống thưởng)

| tId | Tên | Lý do KHÔNG bán |
|----|-----|------------------|
| 1-7 | Sắt thép chi tâm ... Vũ khí đại sư | THƯỞNG event mở server 7 ngày (`activity/type4.lua`) |
| 8 | Không người địch | THƯỞNG rank JJC (đấu trường) |
| 9 | Kỳ tích cao thủ | THƯỞNG rank JJC top |
| 10-15 | (rank top1 mỗi class/rId) | THƯỞNG rank PvP. **Item 380009-380014 còn HỎNG** (xem mâu thuẫn dưới) |
| 16 | Phú khả địch quốc | THƯỞNG quà VIP6 (`vip/vipgift.lua:48`) - bán rẻ = phá perk VIP6 |
| 17 | Côn... kẻ huỷ diệt | THƯỞNG giết boss Kalima (`kalimacommon.lua`) - không có item, GM-only |
| 18 | Roland thành chủ | THƯỞNG guild battle - chủ chiếm (`guildbattleconst.lua:44`) |
| 19 | Roland tinh anh | THƯỞNG guild battle - member award |
| 20 | Chiến thần quân đoàn | THƯỞNG rank (có item 380018 nhưng là prize) |
| 79 | Hồi Quy Sơn Lâm | THƯỞNG mốc ZS1 (`titlemilestone.lua`) |
| 80 | Hồi Quy Bách Chiến | THƯỞNG mốc ZS4 |
| 81 | Hồi Quy Vô Song | THƯỞNG mốc ZS8 |

> **Mâu thuẫn giữa 2 map VPS về 380009-380014** (ghi nhận để không trình bày false-precision): map titleslot nói "titleid 10-15 KHÔNG có item"; map kiểm kê nói "item 380009-380014 CÓ tồn tại nhưng `useArg={}` rỗng = no-op (hỏng)". Upshot thực tế GIỐNG nhau: 10-15 = THƯỞNG rank, không bán sạch được -> loại. Khác biệt chỉ ảnh hưởng "tạo item mới cho 10-15", mà ta cũng không nên làm (vẫn là phần thưởng).
>
> **Cảnh báo dùng map titleslot sai cách**: map titleslot có liệt kê "titleid chưa có item (10,11,12,13,14,15,17,79,80,81) - cần tạo item mới nếu muốn bán". Đó là DANH SÁCH KHE CƠ HỌC (title nào thiếu item), KHÔNG phải đèn xanh để bán. Map kiểm kê là nguồn thẩm quyền: 79-81 = ZS milestone, 17 = boss Kalima, 10-15 = rank - **tất cả là THƯỞNG, KHÔNG bán, KHÔNG tạo item mới cho chúng.**

### 2c. ĐANG BÁN
| tId | Tên | Item | LC | Kênh |
|----|-----|------|----|------|
| 82 | Hồi Quy Chí Tôn | 380077 | 15600 | pshop `tom_title_chiton`, 80 Tôm, limit 1/user |

---

## 3. Shop costume - bán ở đâu + giao thế nào

### Portal có category costume chưa?
**CHƯA.** `config/pshop.php` là **list phẳng** (`'items' => [...]`), KHÔNG có khái niệm category/costume/group/section (grep = 0). "Shop costume" hiện không tồn tại như 1 cấu trúc - chỉ là các entry rời trong list.

=> Nếu muốn gom thành "Shop Danh Hiệu" có UI riêng thì cần thêm grouping ở portal (UI/blade, không phải logic giao hàng). Còn nếu chỉ cần BÁN thì thêm entry phẳng là đủ.

### Giao danh hiệu cho buyer = qua GM mail item 380xxx (giống Chí Tôn)
Flow đã chạy sẵn (mẫu Chí Tôn 380077):
1. Mua bằng Tôm -> `PointShopService::deliverTomItem()`.
2. `buildDeliveryPayload()` nhánh `if ($gameItemId)` tạo `'item_payload' => '1,'.$gameItemId.',1'` (format `1,[ITEM_ID],[AMOUNT]`).
3. `dispatchGmMail()` tạo `GmAction` type `send_mail`, dispatch job `ExecuteGmCommand` async.
4. Job -> `GmApiService::sendItemMail()` -> **INSERT bảng `gmcmd` của game DB** (`cmdid=1, cmd='sendMail'`). Server đọc bảng này, phát mail kèm item.
5. Người chơi nhận **item 380xxx trong thư**, **dùng item -> kích hoạt danh hiệu** (item.lua `useTitle` -> `addTitle`).
- An toàn sẵn: idempotent (`TomPurchaseLog` status), retry `tom:reconcile-deliveries`, `limit_per_user`.

### Kết luận giao hàng
**Mọi title 380019-380076 bán được NGAY bằng đúng cơ chế này** - chỉ thêm entry `pshop.php` với `game_item_id` = ID title. Không cần code logic mới, không cần shop in-game server-side. In-game shop (`cstiantishop.lua`, `storesecret.lua`...) KHÔNG bán title 380xxx (grep = 0), nên không đụng tới.

---

## 4. Bán slot chồng danh hiệu

### Cơ chế mở slot là gì
Chỉ có `@addtitleslot <count>` (GM command) -> `UPDATE actors SET title_slots`. **Không item, không shop, không portal-path.**

### Bán bằng cách nào -> CẦN CƠ CHẾ MỚI (server-side)
Portal chỉ biết gửi item qua mail. title_slots là thuộc tính server-side. 2 hướng:

- **Hướng A (khuyến nghị) - item kích hoạt slot qua mail** (đồng bộ với cơ chế bán title hiện có):
  - Tạo **item mới id >= 380078** (380077 là cao nhất đang dùng).
  - Viết **item-use handler MỚI** trên server (mẫu code đã có sẵn trong `@addtitleslot`): khi dùng item -> `UPDATE actors SET title_slots = <target> WHERE actorid`, clamp 1..5, update cache + đẩy client.
  - Bán qua pshop y hệt title: thêm entry `game_item_id = 380078+`, payload `1,<id>,1`.
- **Hướng B - GM command set flag thủ công**: owner/admin chạy `@addtitleslot` cho từng người mua. Không scale, dễ sai, chỉ hợp giai đoạn "vài người" hiện tại.

### Từng slot (2->5) bán riêng được không
**Được, nhưng phải xử lý cẩn thận vì `@addtitleslot` SET tuyệt đối (không cộng dồn):**
- Nếu mỗi item set cứng 1 giá trị (item "slot 3" -> set=3), thì mua **out-of-order** sẽ tụt slot (mua slot 5 trước rồi mua slot 3 -> tụt về 3). SAI.
- Handler mới PHẢI dùng `target = max(current_title_slots, value_of_item)` (hoặc increment +1) để mua lẻ/đảo thứ tự không tụt.
- Phương án sạch nhất: bán **theo bậc đích** (item "Mở Slot 2/3/4/5"), handler set `max(current, đích)`. Hoặc bán **1 loại item "+1 slot"** dùng nhiều lần tới trần 5 (đơn giản hơn, nhưng người chơi không chọn được đích).

---

## 5. Đề xuất giá (OPTIONS - owner chốt, đừng tự quyết)

> Giá tính bằng Tôm. Mốc tham chiếu hiện có: Chí Tôn (LC 15600, vĩnh viễn, top) = **80 Tôm**; pet Lôi Trạch = 40 Tôm; lifetime card = 20 Tôm. Dùng làm trần/đáy. **LC chỉ để xếp hạng tương đối, không phải con số người chơi thấy.**

### 5.1 Giá mỗi danh hiệu

**PA-A (Phẳng theo loại - đơn giản nhất):**
- Title vĩnh viễn std (LC 6600): **25-35 Tôm**.
- Title "- 1 ngày" / hết hạn ngắn: **5-8 Tôm** (gói dùng thử / rẻ).
- Title tId 21 (LC 900, vĩnh viễn): **15-20 Tôm** (yếu hơn nên rẻ).

**PA-B (Bậc theo LC tương đối):**
- LC ~900 (tId21): 15 Tôm.
- LC 6600 vĩnh viễn: 30 Tôm.
- LC 6600 "- 1 ngày": 6 Tôm.
- (Chí Tôn 15600 giữ 80 Tôm làm đỉnh - không đụng.)

**PA-C (Combo / gói costume):**
- Bán lẻ vĩnh viễn: 30 Tôm/cái.
- "Gói 5 danh hiệu tự chọn": 120 Tôm (≈24/cái, giảm để kích stack).
- Lưu ý: gói càng rẻ stack càng nhiều -> bơm LC sở hữu càng lớn (xem mục 6). PA-C đẩy p2w mạnh nhất.

### 5.2 Giá mở từng slot (2/3/4/5 - tăng dần)

**PA-1 (Tuyến tính nhẹ):** slot2=20, slot3=30, slot4=40, slot5=50 Tôm. Tổng full = 140 Tôm.

**PA-2 (Lũy tiến mạnh - slot cao là flex/đẳng cấp):** slot2=20, slot3=40, slot4=70, slot5=120 Tôm. Tổng full = 250 Tôm. Phù hợp vì slot = thuần cosmetic flex, người chịu chi mới lên 5.

**PA-3 (Bán trọn gói):** "Mở Full 5 Slot" 1 lần = 150-180 Tôm; không bán lẻ từng slot. Đơn giản nhất về implement (1 item, set=5).

> Vì slot = +0 lực chiến, định giá slot theo "giá trị flex/khoe", không theo sức mạnh. Có thể mạnh dạn đặt cao cho slot 4-5.

---

## 6. Lưu ý p2w (ghi nhận, không phán)

Chồng 5 danh hiệu KHÔNG cộng thêm lực (slot = cosmetic). Nhưng **lực chiến đến từ SỐ title SỞ HỮU** -> bán cả lô 22-78 không giới hạn cho phép 1 người stack ~57 × 6600 ≈ **376k LC trọng-số** (vĩnh viễn), slot vô can = p2w PvP mạnh. muh5 đang ở giai đoạn harvest -> đây là lựa chọn của owner; chỉ ghi nhận. Muốn kìm: giới hạn số title mua/người, hoặc giảm attr nhóm bán, hoặc chỉ bán bản "- N ngày".

---

## 7. Plan IMPLEMENT (chưa làm - bám Locked Mode)

### Phần A - Bán thêm danh hiệu (DỄ, không động server)
- **Sửa duy nhất**: `config/pshop.php` - thêm entry/title chọn bán (mẫu copy từ `tom_title_chiton`: `game_item_id`, `feecallback_item_id`, `price_tom`, `limit_per_user`, ảnh `chenghao/ch_mu_XX.png`).
- (Tùy chọn) thêm grouping UI "Shop Danh Hiệu" ở blade portal nếu muốn category costume.
- **Không** đụng server VPS, **không** đụng `game_items.php` logic (chỉ là registry lookup - cập nhật tên nếu lệch).
- Test: tài khoản test mua -> nhận mail item -> dùng -> title hiện. Idempotent + limit kiểm tra.

### Phần B - Bán slot (KHÓ, động server - làm sau)
1. **Test trên DEV SHARD server99 trước**, KHÔNG đụng server1 (prod, đang Locked read-only).
2. **Backup file trước khi sửa**: `itemdata.lua` (thêm item 380078+), file handler title (thêm use-handler set `title_slots`). Copy `.bak` (đã có tiền lệ `.bak` khi thêm Chí Tôn).
3. Viết handler: dùng item -> `UPDATE actors SET title_slots = max(current, target) WHERE actorid`, clamp 1..5, update cache, đẩy client. (Mẫu trong `@addtitleslot`.)
4. **`actors.title_slots` đã tồn tại sẵn -> KHÔNG đổi DB schema** (nằm trong ràng buộc Locked "không DB schema"). Bán slot chỉ GHI giá trị vào cột có sẵn, không tạo cột/bảng.
5. Thêm item slot vào `pshop.php` (giao qua mail như title).
6. Ghi changelog. Rollback = restore file `.bak` (Lua/config thuần, không binary).
- **KHÔNG đụng**: ví/payment, binary C++, DB schema, vi (chỉ Lua + config).

---

## 8. QUYẾT ĐỊNH CẦN OWNER CHỐT

1. **Danh hiệu nào đưa vào bán**: cả 57 (22-78) hay lọc một phần? Có bán bản "- N ngày" không? (ảnh hưởng p2w + độ rối shop do trùng tên).
2. **Giá mỗi danh hiệu**: PA-A / PA-B / PA-C (mục 5.1) + con số cụ thể.
3. **Có bán slot không, và cơ chế**: Hướng A (item-use handler mới, scale) hay Hướng B (GM thủ công, tạm)? Vì A phải động server -> chốt sau khi xong phần danh hiệu.
4. **Giá slot**: PA-1 / PA-2 / PA-3 (mục 5.2) + bán lẻ từng slot hay trọn gói.
5. **Bán ở đâu**: chỉ thêm entry phẳng vào pshop, hay làm category "Shop Danh Hiệu" có UI riêng?
6. **Giới hạn p2w**: có cap số title mua/người không (mục 6)?
