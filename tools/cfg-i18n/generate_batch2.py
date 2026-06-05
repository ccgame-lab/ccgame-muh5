#!/usr/bin/env python3
"""Batch 2: fix garbled ItemConfig.output (rankings/sources) + templated
skill-activation desc strings. Writes batch2_strings.json (cn -> vn)."""
import json

OUT = "D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/batch2_strings.json"

# Class abbreviations (consistent with gear): 魔剑士=MG 圣导师=DK 召唤师=EM
GEAR_CLS = {"魔剑士": "MG", "圣导师": "DK", "召唤师": "EM"}
# Ranking class names (legacy weapon-job names): 剑士=DK 魔法师=MG 弓箭手=EM
RANK_CLS = {"剑士": "DK", "魔法师": "MG", "弓箭手": "EM"}

strings = {
    # 7-day ranking event, <category> rank-1 reward
    "7天排行活动强化等级第一名奖励": "Top 1 Cường Hóa (BXH 7 Ngày)",
    "7天排行活动翅膀总战力第一名奖励": "Top 1 Cánh (BXH 7 Ngày)",
    "7天排行活动宝石总战力第一名奖励": "Top 1 Bảo Thạch (BXH 7 Ngày)",
    "7天排行活动元素总战力第一名奖励": "Top 1 Nguyên Tố (BXH 7 Ngày)",
    "7天排行活动梅林之书总战力第一名奖励": "Top 1 Sách Merlin (BXH 7 Ngày)",
    "7天排行活动武器聚魂总战力第一名奖励": "Top 1 Tụ Hồn (BXH 7 Ngày)",
    "7天排行活动精灵总战力第一名奖励": "Top 1 Tinh Linh (BXH 7 Ngày)",
    # arena / leaderboards
    "竞技场第一名奖励": "Top 1 Đấu Trường",
    "竞技场第二至第二十名奖励": "Top 2-20 Đấu Trường",
    "战力排行榜第一名奖励": "Top 1 BXH Chiến Lực",
    "战力排行榜第二至第二十名奖励": "Top 2-20 BXH Chiến Lực",
    "等级排行榜第一名奖励": "Top 1 BXH Cấp Độ",
    "剑士排行榜第一名奖励": "Top 1 BXH DK",
    "魔法师排行榜第一名奖励": "Top 1 BXH MG",
    "弓箭手排行榜第一名奖励": "Top 1 BXH EM",
    "翅膀排行榜第一名奖励": "Top 1 BXH Cánh",
    # gift / guild sources
    "预约礼包获得": "Lễ Bao Đặt Trước",
    "VIP礼包购买获得": "Mua Lễ Bao VIP",
    "罗兰城战占领盟成员": "Thành Viên Guild Chiếm Roland",
    "罗兰城战占领盟盟主奖励": "Minh Chủ Guild Chiếm Roland",
    # misc lowercase item names -> Title Case Hán-Việt
    "战神军团": "Chiến Thần Quân Đoàn",
    "富可敌国": "Phú Khả Địch Quốc",
    # FubenConfig stragglers
    "天梯": "Thiên Thê",
    "铸造副本": "PB Chế Tạo",
}

# Skill-book item NAMES:  <class>技能<N>  ->  "Kỹ Năng <abbr> <N>"
for cn_cls, abbr in GEAR_CLS.items():
    for n in range(1, 7):
        strings[f"{cn_cls}技能{n}"] = f"Kỹ Năng {abbr} {n}"

# Templated skill-activation books:  激活技能【<class>技能<N>】用
for cn_cls, abbr in GEAR_CLS.items():
    for n in range(1, 7):
        strings[f"激活技能【{cn_cls}技能{n}】用"] = f"Kích hoạt kỹ năng {abbr} {n}"

json.dump(strings, open(OUT, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
print(f"batch2: {len(strings)} strings -> {OUT}")
