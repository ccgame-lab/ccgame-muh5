#!/usr/bin/env python3
"""Batch 3: clean up garbled name-like strings across many tables.
Writes batch3_strings.json (cn -> vn). Title Case Hán-Việt; MU terms in English.
Pure number/time-range labels (e.g. '1-39 cấp') are intentionally left alone.
"""
import json

OUT = "D:/10_Projects/CCGame/ccgame-muh5/tools/cfg-i18n/batch3_strings.json"
s = {}

# --- CsttDanConfig: 玛法<rank><N>  (玛法 = Marfa, the MU continent) ---
MARFA_RANKS = {
    "主宰": "Chúa Tể", "圣王": "Thánh Vương", "圣皇": "Thánh Hoàng",
    "天宗": "Thiên Tông", "天尊": "Thiên Tôn", "战将": "Chiến Tướng",
    "战师": "Chiến Sư", "战神": "Chiến Thần", "新星": "Tân Tinh", "精英": "Tinh Anh",
}
for cn_rank, vn_rank in MARFA_RANKS.items():
    for n in range(1, 6):
        s[f"玛法{cn_rank}{n}"] = f"Marfa {vn_rank} {n}"

# --- SuitAttrConfig: <owner>的<material><slot> SET pieces ---
# 汉斯=Hans, 帕西=Pasi ; 皮/革=Da, 暴风=Bão Tố ; slots
s.update({
    "汉斯的皮铠": "Giáp Da Hans", "汉斯的皮盔": "Mũ Da Hans",
    "汉斯的皮护手": "Bao Tay Da Hans", "汉斯的皮护腿": "Quần Da Hans",
    "汉斯的皮戒指": "Nhẫn Da Hans", "汉斯的皮靴": "Giày Da Hans",
    "汉斯的暴风之盔": "Mũ Bão Tố Hans", "汉斯的暴风戒指": "Nhẫn Bão Tố Hans",
    "汉斯的暴风护腿": "Quần Bão Tố Hans",
    "帕西的革铠": "Giáp Da Pasi", "帕西的革盔": "Mũ Da Pasi",
    "帕西的革护腿": "Quần Da Pasi", "帕西的革戒指": "Nhẫn Da Pasi",
    "帕西的革靴": "Giày Da Pasi",
})

# --- Currencies (high visibility) ---
s.update({
    "元素精华": "Tinh Hoa Nguyên Tố", "先知之魂": "Hồn Tiên Tri", "功勋": "Công Huân",
    "守护值": "Điểm Thủ Hộ", "成就点": "Điểm Thành Tựu", "粉末": "Bột Phấn",
    "荣誉": "Vinh Dự", "魔晶": "Ma Tinh",
})

# --- NPCConfig (NPC names / nav labels) ---
s.update({
    "神秘矿者": "Thợ Mỏ Thần Bí", "绝望圣殿": "Thánh Điện Tuyệt Vọng",
    "血色交任务": "Giao N.Vụ Blood Castle", "血色副本": "PB Blood Castle",
    "试炼BOSS": "BOSS Thử Luyện", "恶魔副本": "PB Ác Ma",
    "打转生": "Đến Reset", "打万魔巨塔": "Đến Vạn Ma Tháp", "打恶魔副本": "Đến PB Ác Ma",
    "打我是npc": "NPC", "打打我是npc": "NPC", "打日常副本": "Đến PB Hằng Ngày",
    "打绝望圣殿": "Đến Thánh Điện Tuyệt Vọng", "打血色副本": "Đến PB Blood Castle",
    "打装备副本": "Đến PB Trang Bị", "打试炼BOSS": "Đến BOSS Thử Luyện",
    "打黄金部队": "Đến Quân Đoàn Hoàng Kim",
})

# --- IslandFubenConfig (demon bosses; keep MU boss names) ---
s.update({
    "咒怨恶魔": "Ác Ma Nguyền Rủa", "恶魔召唤者": "Ác Ma Triệu Hồi",
    "恶魔巨蛛": "Ác Ma Nhện Khổng Lồ", "恶魔巴洛克": "Ác Ma Balrog",
    "恶魔希特拉": "Ác Ma Hitra", "恶魔戈登": "Ác Ma Gorgon",
})

# --- SkillsConfig ---
s.update({
    "天堂之箭": "Mũi Tên Thiên Đường", "毁灭烈焰": "Liệt Diệm Hủy Diệt",
    "试炼3-1冰爆雪兽": "Thử Luyện 3-1 Elite Yeti", "试炼3-3冰后": "Thử Luyện 3-3 Ice Queen",
})

# --- MainTaskConfig (quest objectives) ---
s.update({
    "使用一个果实": "Dùng 1 Trái Cây", "使用一次果实": "Dùng Trái Cây 1 lần",
    "兑换护符": "Đổi Bùa Hộ Mệnh", "学习新技能": "Học Kỹ Năng Mới",
    "抽取精灵1次": "Quay Tinh Linh 1 lần", "换地图击杀小哥布林": "Đổi Map Giết Goblin",
    "换地图击杀雪人": "Đổi Map Giết Yeti", "换地图击杀雪虫": "Đổi Map Giết Worm",
    "提升翅膀到1星": "Nâng Cánh lên 1 Sao", "提升翅膀到2星": "Nâng Cánh lên 2 Sao",
    "提升翅膀到3星": "Nâng Cánh lên 3 Sao", "提升翅膀到4星": "Nâng Cánh lên 4 Sao",
    "提升翅膀到6星": "Nâng Cánh lên 6 Sao", "武器强化+2": "Cường Hóa Vũ Khí +2",
    "武器强化+4": "Cường Hóa Vũ Khí +4", "激活翅膀": "Kích Hoạt Cánh",
    "熔炼装备1次": "Luyện Trang Bị 1 lần", "穿戴护手": "Mặc Bao Tay",
    "装备培养1次": "Bồi Dưỡng Trang Bị 1 lần",
})

# --- DailyConfig (daily tasks) ---
s.update({
    "星魂提升1次": "Nâng Chòm Sao 1 lần", "炼取元素1次": "Luyện Nguyên Tố 1 lần",
    "翅膀提升1次": "Nâng Cánh 1 lần", "膜拜6次": "Cúng Bái 6 lần",
    "装备培养5次": "Bồi Dưỡng Trang Bị 5 lần", "装备强化1次": "Cường Hóa Trang Bị 1 lần",
})

# --- Misc small tables ---
s.update({
    # CsgwBufConfig / buffs / talents
    "攻击加成": "Tăng Tấn Công", "生命": "Sinh Mệnh", "生命加成": "Tăng Sinh Mệnh",
    "防御穿透": "Xuyên Phòng Ngự", "减少伤害": "Giảm Sát Thương",
    "无": "Không", "防御力": "Phòng Ngự", "附加伤害": "Sát Thương Cộng Thêm",
    "生命增加300%": "Tăng Sinh Mệnh 300%", "生命增加500%": "Tăng Sinh Mệnh 500%",
    "减速": "Giảm Tốc",
    # ShilianBossConfig slot pairs
    "戒指/项链": "Nhẫn/Dây Chuyền", "战靴/护腿": "Giày/Quần",
    "护手/头盔": "Bao Tay/Mũ", "武器": "Vũ Khí", "铠甲": "Giáp",
    # WayConfig system names
    "元素系统": "Hệ Thống Nguyên Tố", "翅膀系统": "Hệ Thống Cánh",
    "装备强化": "Cường Hóa Trang Bị", "精魄守护神": "Thần Thủ Hộ Tinh Phách",
    "第二技能": "Kỹ Năng 2", "第三技能": "Kỹ Năng 3", "第四技能": "Kỹ Năng 4",
    "第五技能": "Kỹ Năng 5",
    # CollectTypeConfig (unbreakable body)
    "不破金身": "Kim Thân Bất Phá", "不破银身": "Ngân Thân Bất Phá",
    "不破铜身": "Đồng Thân Bất Phá",
    # MineFBConfig crystals
    "1号水晶": "Thủy Tinh Số 1", "2号水晶": "Thủy Tinh Số 2", "3号水晶": "Thủy Tinh Số 3",
    "4号水晶": "Thủy Tinh Số 4", "5号水晶": "Thủy Tinh Số 5", "6号水晶": "Thủy Tinh Số 6",
    "7号水晶": "Thủy Tinh Số 7", "8号水晶": "Thủy Tinh Số 8", "9号水晶": "Thủy Tinh Số 9",
    "10号水晶": "Thủy Tinh Số 10",
    # HeartAttrPlusConfig
    "恶魔邪能1": "Tà Năng Ác Ma 1", "恶魔邪能2": "Tà Năng Ác Ma 2",
    "恶魔邪能3": "Tà Năng Ác Ma 3", "恶魔邪能4": "Tà Năng Ác Ma 4",
    "恶魔邪能5": "Tà Năng Ác Ma 5",
    # MountConfig (mount skills)
    "庇护": "Che Chở", "强体": "Cường Thể", "炫目": "Chói Mắt", "狂热": "Cuồng Nhiệt",
    # MinerConfig
    "皮革矿工": "Thợ Mỏ Da", "红龙矿工": "Thợ Mỏ Hồng Long", "铂金矿工": "Thợ Mỏ Bạch Kim",
    # SixiangRingCommonConfig
    "玄武灵戒": "Nhẫn Huyền Vũ", "青龙灵戒": "Nhẫn Thanh Long",
    # ZhuanshengLevelConfig
    "重生1": "Reset 1", "重生2": "Reset 2", "重生3": "Reset 3",
    # others
    "上阵2个精灵": "Ra Trận 2 Tinh Linh", "购买VIP礼包4次": "Mua Lễ Bao VIP 4 lần",
    "弓箭手排行榜": "BXH EM", "恶魔副本": "PB Ác Ma",
    "击杀死神骑士": "Giết Death Knight", "特殊称号": "Danh Hiệu Đặc Biệt",
    "选中页面": "Chọn Trang", "选择日常玩法": "Chọn Tính Năng Hằng Ngày",
})

json.dump(s, open(OUT, "w", encoding="utf-8"), ensure_ascii=False, indent=1)
print(f"batch3: {len(s)} strings -> {OUT}")
