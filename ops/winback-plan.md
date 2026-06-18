# Chiến dịch Win-back + Hút máu "Mùa Hồi Quy" (mở 2026-06-19)

Mục tiêu: game tự nuôi chi phí AI ~$200 (~5tr VND ~ 5000 Tôm) / 30 ngày. Kéo người rời ~2 tháng + tăng chi người đang chơi. Đòn bẩy: hệ danh hiệu tăng lực chiến (Lua, không đụng binary) + comeback giftcode + outreach Zalo/FB + launcher.

## Quyết định đã chốt
- Lua thuần cho danh hiệu, KHÔNG hex binary (owner chốt). Trần `AttrActorSysId_Max=17` không liên quan (danh hiệu dồn chung slot 7). Xem [[gameserver-attr-slots]].
- Giữ MAX đeo = 5 (không tăng). UX client panel danh hiệu sửa compact (trường hợp B - EXML, sửa được).
- Kênh chạm người đã rời: Zalo/FB group + launcher banner (không có email/SMS infra).

## Thiết kế (sau phản biện deepseek, đã lọc an toàn ví)
- Danh hiệu sưu tập: 5-7 cái, 50-100 ATK mỗi cái, điều kiện tuổi acc chặn bot. (đạt-bằng-chơi)
- Danh hiệu mùa 2 tier: F2P 200 ATK qua quest/login; người nạp 500 ATK qua 5-10 Tôm. Tránh free-all, tránh pay-to-win lộ.
- Comeback giftcode: 300-500 POINT + danh hiệu 500 ATK 30 ngày, điều kiện `last_login > 60 ngày`.
- Gói Tôm comeback (CHỜ số liệu + duyệt): G1 10 Tôm (200 POINT + danh hiệu 500 ATK 30d), G2 50 Tôm (500 POINT + danh hiệu 1000 ATK 30d + EXP boost qua GM mail). Điều kiện mua: lapsed > 60d. Ghi RÕ thời hạn danh hiệu trong UI.
- Bỏ "VIP 3 ngày" + "chuyển đổi trang bị" deepseek gợi ý (game không có hệ tương ứng, chỉ 5 GM cmd).

## THỰC TẾ BASE (số liệu 2026-06-19)
- DB: total=290, active30(portal login)=0, lapsed30_60=35, lapsed60plus=156, never=99, payers(hệ Tôm mới)=0.
- S1 = GỘP từ 5 server kinh doanh trước. Base này ĐÃ TỪNG TRẢ TIỀN: tổng thu đời 5 server ~4tr VND (trong lịch sử giao dịch). => đây là WIN-BACK paying base, KHÔNG phải cold-start.
- payers=0 chỉ là hệ Tôm/GreenJade MỚI chưa chạy; doanh thu 4tr cũ ở hệ thanh toán cũ. Cần xác định nguồn data 4tr để nhắm whale.
- Lưu ý nhịp: 4tr = tổng tích luỹ đời 5 server; mục tiêu 5tr/30d cao hơn -> big update phải đủ mạnh, tháng đầu có thể chưa full.

## CƠ CHẾ DANH HIỆU (recon gameserver-ops, sẵn build)
- Cấp danh hiệu: hàm gốc `titlesystem.addTitle(actor, tId, isInit)`. 4 đường hiện có: GM @addtitle / rank (ranktitle.lua) / login re-check. CHƯA có hook theo mốc level-ZS.
- Hook SẴN để cắm: `aeLevel` (actorexp.lua:53) + `aeZhuansheng` (zhuanshengsystem.lua:79, actorexp.lua:286). Pattern: thêm file `functions/systems/title/titlemilestone.lua` reg 2 event + `require` trong systems.lua. KHÔNG sửa lõi.
- Lua thuần, KHÔNG chạm binary, KHÔNG sync client (client nhận tId+tên qua packet). ID trống từ 79. attr type: 1=HP,2=ATKmin,3=ATKmax,4=DEF. keepTime=0 vĩnh viễn / >0 giây có hạn.
- Nợ: player đã qua mốc cần cấp retroactive (qua aeInit hoặc GM batch).

## DOANH THU THẬT (admin dashboard GreenJade, đọc 2026-06-19) - NGUỒN ĐÚNG
LƯU Ý SAI LẦM ĐÃ SỬA: query payment_intents BỎ SÓT SePay (cổng tự động không ở bảng đó). Số đúng lấy từ admin dashboard:
- SePay đã xác nhận: **1.120.000đ** (1.120 Tôm) - cổng tự động, người chơi thật, CHẮC CHẮN. (Cần tìm bảng SePay để list payer.)
- Thủ công/GM xác nhận: **1.500.000đ** (1.500 Tôm) - 2 giao dịch manual (uid 546, 269), owner xác nhận thật/test.
- Hỗ trợ/tặng: 1.697 Tôm (= GJC, không phải tiền vào).
- **Tôm đang lưu hành = 4.889 Tôm** (trong ví người dùng), đã tiêu/đổi sang MUH5 chỉ 990 Tôm.
- => ĐÒN BẨY CHÍNH: 4.889 Tôm tồn chưa tiêu. Người chơi giữ Tôm mà thiếu món đáng mua. Big update tạo món đáng tiêu Tôm -> kích tiêu tồn + nạp thêm. KHÔNG phải xây từ 0.
- 4tr "đời 5 server" ở hệ thanh toán CŨ trước GreenJade (định tính, base từng trả tiền).

## WHALE / PAYER THẬT (ảnh ví hệ thống, balance Tôm)
hanoi(uid12)=2500, vuthan00(74)=525, darkmage(255)=129, smkreg03(270)=113, mayxaydanang(92)=55, jivaynhii(71)=33, dangkhoa(35)=30.
Nghi acc test owner (loại khi nhắm): testvps02(538)=990, quocquoc9999(269)=500, quocquoc(1)=9. -> CẦN owner xác nhận acc nào test.

## MONETIZATION - HƯỚNG ĐÚNG (sau khi check kho KC, đã sửa sai)
- Top thừa KC: yuanbao avg 2.9 TỶ, max 331 TỶ (chinsu); 21 người >1 tỷ. GOLD avg 705tr. Xem [[economy-whale-kc]].
- => ĐỪNG bán gói KC/yuanbao (vô nghĩa, đã xoá 2 gói KC sai). Whale chỉ mua thứ KC KHÔNG tạo được.
- Bán bằng Tôm phải là: danh hiệu lực chiến độc quyền (cần wire GM addtitle), pet huyền thoại (pet_loi_trach có sẵn id 500052, đổi sang Tôm - DỄ), đặc quyền/cap mới.
- Top max + thừa KC = LÝ DO RỜI. Big update cần: mục tiêu mới + SINK tiêu KC tồn (331 tỷ vô dụng) + món độc quyền hút máu.
- Người mới/yếu (151 ZS0) thiếu KC nhưng không phải payer.

## OWNER CHỐT BÁN TÔM (2026-06-19): danh hiệu lực chiến độc quyền + pet/trang bị huyền thoại + sink KC khổng lồ.
- LOẠI "cap mới ZS13+": binary GS KHÔNG mở được ZS12+ (trần cứng). Mục tiêu mới cho whale phải nằm trong ZS<=12 + hệ ngoài ZS.

## PHASE A XONG (s99, test danh hiệu): power đo thật 79=64k, 80=290k, 81=680k (113-116% target, OK). aeZhuansheng + aeInit backfill wired, boot sạch. Sẵn đẩy S1.

## CỜ ĐỎ / nợ
- Bán danh hiệu/pet bằng Tôm: pet (game_item_id) giao được liền; danh hiệu cần wire GM addtitle. Định giá item độc quyền cho whale = high-stakes, trình owner số.
- Bán danh hiệu bằng Tôm = cần wire cầu nối GM addtitle (game server + GmApiService). Đáng làm vì là thứ hút whale.
- Snowball power "sở hữu càng nhiều càng mạnh" = rủi ro lạm phát, cần cap tổng power danh hiệu.
- Anti-abuse: infra hiện KHÔNG chặn multi-acc/bot hoàn toàn. Tối thiểu: điều kiện tuổi acc + last_login.
- Outreach phải có link nạp Tôm trực tiếp trong bài post (không thì funnel gãy).

## Tiến độ
- [x] **TÍNH NĂNG 1 LIVE S1 (2026-06-19 04:12)**: danh hiệu Hồi Quy ZS1/4/8 (power 64k/290k/680k), tự cấp khi ZS + backfill khi đăng nhập. Boot sạch, backup bak-titlemilestone-20260619-0407. Changelog game ghi. Release notes QC: ops/release-notes-hoiquy.md.
- [x] Pet Lôi Trạch (id 500052) đổi sang bán Tôm 40, limit 1/người - BUILT config, CHƯA deploy portal.
- [ ] Danh hiệu Chí Tôn bán Tôm 80 - cần wire GM addtitle (game server + GmApiService). Chưa làm.
- [x] BXH đua TOP: backend donate theo kỳ (DonateRankingService + /api/sdk/donate-ranking) + cờ bootstrap (popup + has_donated) + popup SDK (RankingPopup.vue, hiện khi mở game, X đóng từng lần cho mọi người, "tắt cả ngày" gate người đã nạp) + tích hợp App.vue. Pint PASS, build SDK PASS. CHƯA deploy.
- [~] Chí Tôn KHÉP: item 380077 'Chí Tôn Lệnh Bài' (useType=2,titleid=82) + title 82 đẩy S1 (gameserver-ops nền). Portal pshop entry tom_title_chiton (game_item_id=380077, 80 Tôm, limit 1) BUILT. Client TitleConfig 79-82 (ảnh ch_mu_50/49/40/72) + đẩy R2 (client-ops nền, fix danh hiệu live thiếu ảnh).
- [x] **DEPLOY PORTAL LIVE 2026-06-19** (commit 3aa3060 PHP + da542b4 SDK, push master, prod pull+cache). Verify: donate-ranking {week,[]} + bootstrap.ranking_popup{show:true} live. BXH + popup + pet Lôi Trạch LIVE. test 13/13 + pint pass.
- [!] CHÍ TÔN ĐẨY S1 -> GAMEWORLD KHÔNG LÊN -> ROLLBACK (2026-06-19 ~05:42). S1 đã khôi phục OK (restore .bak-chitontitle-20260619-0509, danh hiệu 79/80/81 còn nguyên). Chẩn đoán: KHÔNG thấy lỗi Lua parse; lần restart đầu sau áp không lên (port 6001=0), restart sau lên OK. Chưa chắc lỗi config hay race restart. Cùng config ĐÃ boot OK trên s99.
  ĐỂ ÁP LẠI AN TOÀN: (1) test s99 áp ĐỒNG THỜI title 82 + item 380077 + restart, verify boot [ok] LẶP LẠI 2-3 lần (loại trừ race); (2) nếu s99 ổn, áp S1 lúc vắng, restart kỹ, nếu lần 1 không [ok] -> retry start 1 lần trước khi rollback (có thể chỉ là race start_server). Pshop tom_title_chiton vẫn ẨN (price_tom=null) - không ai mua nhầm.
- [x] CHÍ TÔN LIVE (2026-06-19 06:06): đẩy lại S1 lần 2 -> boot [ok] ngay lần đầu. NGUYÊN NHÂN crash lần 1: agent đọc nhầm PID gameworld của S99 thành S1 (cùng tên process gameworld_24_5) -> tưởng S1 chưa stop -> start quá sớm -> race port 6001. KHÔNG phải lỗi config. Verify độc lập: S1 port6001 LISTEN + title82 grep=1 + item380077 grep=1. Bật pshop tom_title_chiton 80 Tôm (commit 8800aa3, config:cache). API /api/pshop/items xác nhận 4 món (10/20/40/80 Tôm).

## BUG (2026-06-19): mở tile spin/đào KC -> SDK biến mất ("nextSibling null").
- Fix #1 SAI (đoán Teleport popup, commit b3f42f5) - không phải nguyên nhân, bug vẫn còn + mining cũng dính.
- Fix #2 ĐÚNG nguyên nhân (commit 59dbb69, deployed): `defineAsyncComponent` (SpinCard/MiningCard) bọc trong `<transition>`+v-if gây crash mount. Bằng chứng: GiftcodeCard/DonatePanel EAGER cùng transition chạy ổn. Fix = eager import SpinCard/MiningCard.
- Teleport popup (b3f42f5) giữ (good practice modal).
- CHƯA verify render thật (game cần auth) -> CẦN owner F5 + bấm spin/đào KC xác nhận. Bài học fix-mù -> feedback rule 9.

## MÙA HỒI QUY = LIVE HOÀN CHỈNH. Còn: catch-up tân thủ (phase 2) + bài Zalo (owner, từ release-notes-hoiquy.md) + nợ token CF purge. Lưu ý: build SDK trên Windows -> `git checkout -- resources/sdk/node_modules` trước commit; commit SDK build TÁCH khỏi PHP.
- [ ] Danh hiệu Chí Tôn bán Tôm 80 - ĐỔI HƯỚNG (owner 2026-06-19): danh hiệu có ITEM riêng ăn vào để nhận; giao qua MAIL offline được. => KHÔNG cần handler gmcmd addTitle (bỏ, thừa); KHÔNG lo online-only. Chỉ cần pshop entry game_item_id = item-danh-hiệu Chí Tôn (giao mail như pet, self-heal sẵn). CẦN: item_id cấp danh hiệu Chí Tôn (owner cho hoặc recon game item->title). Title 82 entry trong title.lua vẫn cần (item ăn vào cấp title 82).
- [~] Chí Tôn: title 82 'Hồi Quy Chí Tôn' (power ~1.56tr) + handler gmcmd addTitle BUILT trên s99 (chưa S1). LƯU Ý: handler addTitle giờ THỪA (đổi sang item-mail), KHÔNG cần đẩy S1; chỉ đẩy title 82. CÒN THIẾU: item cấp danh hiệu (item ăn vào -> title 82) + ảnh danh hiệu (đợi client-ops recon chenghao). Power 82 cần test thực tế (s99 không có player online).
- [ ] Catch-up tân thủ: popup đã có hint "lên gần top 10 nhận quà"; cơ chế quà thật chưa làm (phase 2).

## TRỤ MỚI: BXH ĐUA TOP + POPUP (owner thêm 2026-06-19)
Yêu cầu: popup BXH mỗi khi mở game (lực chiến/donate/đo đếm được), đua TOP tuần/tháng/mùa, support newbie lên gần top 10 cho hứng thú. Nút "tắt cả ngày" CHỈ cho người đã nạp; người chưa nạp đóng từng lần (X).
Recon (scout): ranking power/zs all-time có sẵn (GameRankingService, /api/sdk/ranking, RankingPane top5). KHÔNG có: cache server, kỳ tuần/tháng, popup/modal SDK, snapshot. Donate aggregate được từ tom_purchase_logs (user_id,tom_spent,status,created_at). "Đã nạp" = tom_purchase_logs status in spent/dispatched/delivered.
Thiết kế v1: BXH lực chiến all-time (sẵn) + BXH DONATE theo kỳ (mới, group tom_purchase_logs) + popup modal mới (X đóng từng lần cho mọi người, "tắt cả ngày" gate người đã nạp) + catch-up quà cho người ngoài top.
LƯU Ý đạo đức/retention: newbie PHẢI đóng được popup từng lần (đừng kẹt) - mục tiêu là GIỮ newbie, ép quá phản tác dụng.

- [x] Recon portal monetization + win-back infra (scout)
- [x] Recon hệ danh hiệu + trần binary (gameserver-ops)
- [x] Phản biện kinh tế + thiết kế gói (economy-architect/deepseek)
- [x] UX client panel danh hiệu compact (client-ops): TitleItemSkin.exml row 86->68, icon 0.8->0.66, căn lại y các phần tử; titlepanelskin giữ nguyên. Backup .bak-20260619024025. CHƯA push R2. Lưu ý: button height=50 trong row 68 khá khít, xem thực tế khi phát hành, nếu clip thì row->72.
- [ ] Số liệu DB (lapsed buckets + payers) - owner chạy query (password ở .env, Claude không đọc secret)
- [~] Recon cơ chế CẤP danh hiệu (gameserver-ops, read-only) -> chốt spec v1 -> áp s99 dev shard test
- [ ] Giftcode comeback (portal code)
- [ ] Gói Tôm pshop (ĐỤNG TIỀN - làm cuối, owner duyệt giá/scale)
- [ ] Launcher banner + outreach copy

## BIG UPDATE - 4 trụ (owner chốt FLY hết 2026-06-19)
- TRỤ 1 danh hiệu mốc tăng lực chiến (Lua s99) - SPINE, đang build.
- TRỤ 2 sự kiện Hồi Quy + quà đăng nhập lại + comeback code (portal + Lua nhẹ).
- TRỤ 3 content nặng (item/boss/map) - cần RECON trước (khối lượng + sync client?), rủi ro cao.
- TRỤ 4 QoL + cân bằng - cần biết pain-point vì sao họ rời (recon + hỏi owner).

## Thứ tự fly (reversible/dev trước, live+tiền sau)
1. [đang] Trụ 1 build s99 dev: titlemilestone.lua + title 79-81 sample + hook aeZhuansheng. Verify load + test cấp. Số ATK là PLACEHOLDER, tinh chỉnh sau khi có data power/4tr.
2. Recon trụ 3 (content nặng) song song khi gameserver-ops rảnh.
3. Trụ 2 portal: comeback giftcode + quà mốc login.
4. Tinh chỉnh số danh hiệu theo power budget thật -> áp S1 LIVE (backup/verify/rollback, lúc vắng). [OWNER ỦY QUYỀN deploy S1 + tiền 2026-06-19, chấp nhận rủi ro có kiểm soát. Vẫn test dev pass trước.]
5. Trụ 4 QoL theo pain-point.
6. Gói Tôm pshop (ĐỤNG TIỀN, duyệt giá/scale) -> phát hành R2/purge + outreach Zalo/FB (kèm link nạp).

## Client R2 (uploaded 2026-06-19, CHỜ PURGE TAY)
config1.json (TitleConfig 79-82 ảnh ch_mu_50/49/40/72) + TitleItemSkin.exml compact ĐÃ push R2. Backup R2 .bak-20260619 + local .preVH-20260619050227.
PURGE FAIL: token CF trong .env.local hết hạn (code 9109). Owner purge TAY 2 URL trên Cloudflare dashboard:
  - https://cdn.ccgame.org/h5/muh5/resource/resource/cfg/config1.json
  - https://cdn.ccgame.org/h5/muh5/resource/resource/skins/zhuangban/title/TitleItemSkin.exml
NỢ: tạo token CF mới (Zone:Cache Purge) bỏ .env.local cho purge tự động lần sau.
