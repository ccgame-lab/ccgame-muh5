<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Diamond Mining Economy Configuration
    |--------------------------------------------------------------------------
    |
    | All economy constants externalized for tuning without code changes.
    |
    | Calibration reference (2026-03-15):
    |   1 RMB = 1,000 Diamond (feecallback rate)
    |   chongzhi1 (daily first recharge) = 3,000,000 Diamond
    |   Mid-tier fruit (360008-360010) = ~500,000 Diamond each
    |
    | Target invariants:
    |   casual (1 machine, 2 claims/day)  ≈ 400,000 Diamond/day
    |   active (1 machine, 6 claims/day)  ≈ 1,200,000 Diamond/day
    |   hardcore (4 machines, max)        < 3,000,000 Diamond/day = chongzhi1
    |   mining MUST NOT bypass daily recharge value
    |
    */

    'max_diamond_per_day' => [
        0 => 2_800_000,
        1 => 3_500_000,
        2 => 4_500_000,
        3 => 6_000_000,
        4 => 8_000_000,
        5 => 10_000_000,
    ],
    'min_claim_interval' => env('ECONOMY_MIN_CLAIM_INTERVAL', 60), // seconds
    'max_offline_hours' => env('ECONOMY_MAX_OFFLINE_HOURS', 6),

    // Safety: dynamic cap = base + ascension_level × per_ascension
    'max_total_multiplier_base' => 5.0,
    'max_total_multiplier_per_ascension' => 0.5,

    /*
    |--------------------------------------------------------------------------
    | Machine Unlock Costs
    |--------------------------------------------------------------------------
    | Machine 1 is free. Higher machines require significant diamond investment.
    | Machine 4 requires roughly 150 days of casual mining = serious commitment.
    |
    */

    'base_machine_costs' => [
        1 => 0,
        // 2 => 15000000,   // UI removed — multi-machine slots hidden
        // 3 => 60000000,
        // 4 => 200000000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Production Rates (Diamond per hour)
    |--------------------------------------------------------------------------
    |
    | Machine 1 rate calibration:
    |   STORAGE_MAX = rate × CYCLE_TIME
    |   200,000 = 50,000 × 4h (casual 4h cycle time)
    |
    |   casual (2 claims): 2 × 200,000 = 400,000 Diamond/day
    |   active (6 claims): 6 × 200,000 = 1,200,000 Diamond/day
    |   → mid-tier fruit (500,000 Diamond) needs ~2.5 claims = ~10h of play
    |
    */

    'base_rates' => [
        1 => 50000,    // 200,000 Diamond / 4h cycle
        2 => 65000,    // 260,000 Diamond / 4h cycle
        3 => 80000,    // 320,000 Diamond / 4h cycle
        4 => 100000,   // 400,000 Diamond / 4h cycle
    ],

    /*
    |--------------------------------------------------------------------------
    | Base Capacities (Diamond storage per machine)
    |--------------------------------------------------------------------------
    |
    | STORAGE_MAX per machine. This is the ceiling per claim.
    | Calibrated so that base machine 1 at 50,000 Diamond/h fills in 4 hours.
    | All 4 machines fully upgraded casual = 200K+312K+448K+600K = 1,560,000 Diamond
    | → still under chongzhi1 per claim
    |
    */

    'base_capacities' => [
        1 => 200000,    // 50K/h × 4h
        2 => 260000,    // 65K/h × 4h
        3 => 320000,    // 80K/h × 4h
        4 => 400000,    // 100K/h × 4h
    ],

    /*
    |--------------------------------------------------------------------------
    | Upgrade Multipliers
    |--------------------------------------------------------------------------
    | Speed multipliers increase production rate.
    | Storage multipliers increase capacity (STORAGE_MAX).
    | Both are moderate — max multiplier ≤ 1.5× to avoid economy blowout.
    |
    */

    'speed_multipliers' => [
        1 => 1.0,
        2 => 1.08,
        3 => 1.16,
        4 => 1.25,
        5 => 1.35,
        6 => 1.50,
    ],

    'storage_multipliers' => [
        1 => 1.0,
        2 => 1.15,
        3 => 1.30,
        4 => 1.50,
        5 => 1.75,
    ],

    'efficiency_bonuses' => [
        1 => 0.0,
        2 => 0.03,
        3 => 0.06,
        4 => 0.10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Ascension (Prestige Reset)
    |--------------------------------------------------------------------------
    | Moderate multipliers. Worst-case single machine (max everything + boost 2×):
    |   rate = 50,000 × 1.50 (speed) × 1.40 (ascension) × 2.0 (boost) = 210,000/h
    |   capacity = 200,000 × 1.75 (storage) = 350,000
    |   claim = min(210,000 × 6h, 350,000) × 1.10 (eff) = 385,000
    |   4 claims/day = 1,540,000 — well under daily cap 2,800,000 ✓
    |
    */

    'ascension_multipliers' => [
        0 => 1.0,
        1 => 1.05,
        2 => 1.10,
        3 => 1.18,
        4 => 1.28,
        5 => 1.40,
    ],

    /*
    |--------------------------------------------------------------------------
    | Lucky Drop Configuration
    |--------------------------------------------------------------------------
    | Drop rate 3% keeps lucky drops rare but viable.
    | Game items: Jewels (Bless/Soul/Life/Chaos)
    |
    */

    'lucky_drop_chance' => 0.03, // 3%
    'lucky_drop_min_production' => 10000, // cần claim ≥ 10K Diamond mới roll lucky drop (chống spam)
    'lucky_drop_scale_by_fill' => true, // chance *= (produced / capacity) — chống micro-claim spam

    'lucky_drop_table' => [
        ['item' => 'jewel_of_bless', 'weight' => 60, 'game_item_id' => 104],
        ['item' => 'jewel_of_soul', 'weight' => 25, 'game_item_id' => 105],
        ['item' => 'jewel_of_life', 'weight' => 10, 'game_item_id' => 106],
        ['item' => 'jewel_of_chaos', 'weight' => 5, 'game_item_id' => 107],
    ],

    'lucky_drop_table_version' => '1.1',

    /*
    |--------------------------------------------------------------------------
    | Point Economy Configuration
    |--------------------------------------------------------------------------
    |
    | WPoint is the portal-layer currency used for upgrades, unlocks, and ascension.
    | Players earn WPoint through daily check-in. Diamond remains mining-only.
    |
    */

    'point_upgrade_base_costs' => [
        'speed' => 20,
        'storage' => 24,
        'efficiency' => 36,
    ],

    'point_cost_exponent' => 1.5,

    'point_machine_costs' => [
        1 => ['wp' => 0,   'min_ascension' => 0],
        2 => ['wp' => 200,  'min_ascension' => 1],
        3 => ['wp' => 500,  'min_ascension' => 2],
        4 => ['wp' => 800,  'min_ascension' => 3],
    ],

    'ascension_costs' => [
        1 => ['wp' => 200,  'min_lifetime_mined' => 500_000],
        2 => ['wp' => 350,  'min_lifetime_mined' => 1_500_000],
        3 => ['wp' => 550,  'min_lifetime_mined' => 4_000_000],
        4 => ['wp' => 800,  'min_lifetime_mined' => 10_000_000],
        5 => ['wp' => 1200, 'min_lifetime_mined' => 25_000_000],
    ],

    'ascension_upgrade_retention' => 0.5, // Giữ lại 50% upgrade levels sau ascension (ceil)
    'ascension_wp_refund_rate' => 0.2,   // Hoàn 20% WP đã dùng upgrade khi ascend

    'missions_completion_bonus' => 5,

    'point_checkin_amount' => 3,
    'point_streak_bonus' => 10,
    'point_streak_threshold' => 7,
    'point_streak_monthly_bonus' => 30,
    'point_streak_monthly_threshold' => 30,

    /*
    |--------------------------------------------------------------------------
    | Boost Configuration
    |--------------------------------------------------------------------------
    |
    | Temporary production rate multipliers purchased with WPoint.
    | Only one boost can be active at a time.
    |
    */

    'boosts' => [
        [
            'label' => '2x · 12 giờ',
            'multiplier' => 2.0,
            'hours' => 12,
            'wp_cost' => 120,
        ],
        [
            'label' => '2x · 24 giờ',
            'multiplier' => 2.0,
            'hours' => 24,
            'wp_cost' => 200,
        ],
        [
            'label' => '2x · 3 ngày',
            'multiplier' => 2.0,
            'hours' => 72,
            'wp_cost' => 450,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Point Shop Packages
    |--------------------------------------------------------------------------
    |
    | Portal Coin (Tôm) → WPoint conversion packages.
    | 1 Tôm = 1 WP = 1,000 VND.
    | Purchase flow via PortalClient::spend() (not yet implemented).
    |
    */

    'point_packages' => [
        [
            'wp' => 80,
            'label' => '☕ Ủng hộ vận hành',
            'tom_cost' => 50,
            'price_vnd' => 50000,
        ],
        [
            'wp' => 350,
            'label' => '🍜 Hỗ trợ phát triển',
            'tom_cost' => 200,
            'price_vnd' => 200000,
            'tag' => 'Phổ biến',
        ],
        [
            'wp' => 1000,
            'label' => '🖥️ Duy trì Server hoạt động',
            'tom_cost' => 500,
            'price_vnd' => 500000,
            'tag' => 'Siêu tiết kiệm',
        ],
    ],

    'first_purchase_bonus' => 2.0, // x2 WP cho lần mua đầu tiên

    /*
    |--------------------------------------------------------------------------
    | WCoin Economy
    |--------------------------------------------------------------------------
    |
    | EV Analysis (2026-03-19 v3 retention tuning):
    |   Total weight = 208
    |   EV_wcoin  = (40/208×1)+(30/208×3)+(20/208×7)+(4/208×20) = 1.683 WCoin/spin
    |   Net EV    = 1.683 - 10 = -8.317 WCoin/spin ← spin is a HARD SINK
    |
    |   v3 changes: lose_turn 40→25 (18.3%→12.0%), extra_turn 8→12 (3.7%→5.8%)
    |   Rationale: reduce frustration, increase excitement, minimal drain impact
    |
    | Daily F2P income:
    |   login  = 25 WCoin avg (7-day cycle: 15→20→20→25→25→30→40)
    |   spin prizes = ~34 WCoin (20 spins × 1.68 avg)
    |   spin milestones = ~13.5 WCoin (avg of random ranges)
    |   total ≈ 72–77 WCoin/day
    |
    */

    'wcoin_login_rewards' => [
        1 => 30,
        2 => 35,
        3 => 35,
        4 => 40,
        5 => 40,
        6 => 45,
        7 => 55,
    ],

    'spin_cost' => env('ECONOMY_SPIN_COST', 10),
    'spin_daily_limit' => env('ECONOMY_SPIN_DAILY_LIMIT', 20),
    'wcoin_spin_reward_cap' => env('ECONOMY_WCOIN_SPIN_REWARD_CAP', 55),

    'spin_milestones' => [
        10 => [3, 7],
        20 => [5, 12],
    ],

    /*
    | Diminishing returns: after spin_diminish_after spins/day,
    | cost increases by spin_diminish_multiplier per extra spin.
    | Formula: cost = base_cost × multiplier^(spins_today - threshold)
    | Spin 11 = 10×1.3 = 13, spin 12 = 10×1.3² ≈ 17, spin 15 = 10×1.3⁵ ≈ 37
    */
    'spin_diminish_after' => 10,
    'spin_diminish_multiplier' => 1.3,

    /*
    | Extra turn chain limit: max consecutive free spins from extra_turn.
    | Prevents lucky chain exploits. After max_chain, extra_turn converts to lose_turn.
    */
    'spin_extra_turn_max_chain' => 2,

    'spin_prizes' => [
        ['type' => 'wcoin',      'value' => 1,      'weight' => 40, 'label' => '1 PT'],
        ['type' => 'wcoin',      'value' => 3,      'weight' => 30, 'label' => '3 PT'],
        ['type' => 'wcoin',      'value' => 7,      'weight' => 20, 'label' => '7 PT'],
        ['type' => 'wcoin',      'value' => 20,     'weight' => 4,  'label' => '20 PT'],
        ['type' => 'yuanbao',    'value' => 30,     'weight' => 60, 'label' => '30K KC'],
        ['type' => 'yuanbao',    'value' => 80,     'weight' => 15, 'label' => '80K KC'],
        ['type' => 'yuanbao',    'value' => 300,    'weight' => 2,  'label' => '300K KC'],
        ['type' => 'lose_turn',  'value' => 0,      'weight' => 25, 'label' => 'Trật'],
        ['type' => 'extra_turn', 'value' => 0,      'weight' => 12, 'label' => '+Lượt'],
    ],

    'gm_alert_threshold' => env('ECONOMY_GM_ALERT_THRESHOLD', 500000),

    /*
    |--------------------------------------------------------------------------
    | Mid Fruit Incremental System
    |--------------------------------------------------------------------------
    |
    | cost(n) = floor(base_cost × exponent^(n-1))
    |
    | Level  Cost    Cumulative  Days (F2P @ 76/day)
    |   1    500       500         6.6
    |   2    560     1,060        13.9
    |   3    627     1,687        22.2
    |   5    787     3,327        43.8
    |  10  1,387     8,757       115.2
    |
    */

    'mid_fruit' => [
        'base_cost' => 500,
        'cost_exponent' => 1.12,
        'max_level' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | High Fruit Webshop
    |--------------------------------------------------------------------------
    |
    | Players can spend WCoin to buy High Fruit (premium progression items)
    | delivered via in-game mail.
    |
    | Pricing intent:
    |   1 fruit ≈ 1 day of whale progression value (~5 fruits/day × 50 days = 300)
    |   At ~25 avg login WP/day + 50 spin WP/day = 75 WP total:
    |   Cheapest fruit (60 WP) costs ~19 hours of income → feels expensive but fair
    |
    | Daily limit (high_fruit_daily_limit):
    |   Prevents buying more than N fruits/day across all item_ids combined.
    |   Set to 0 to disable.
    |
    */

    'high_fruit_prices' => [
        360011 => env('ECONOMY_FRUIT_PRICE_360011', 400),  // rarest
        360012 => env('ECONOMY_FRUIT_PRICE_360012', 350),
        360013 => env('ECONOMY_FRUIT_PRICE_360013', 350),   // most common
    ],

    'high_fruit_weekly_limit' => env('ECONOMY_FRUIT_WEEKLY_LIMIT', 3),

    /*
    |--------------------------------------------------------------------------
    | S1 Legacy Shop
    |--------------------------------------------------------------------------
    |
    | Items sold in the S1 Legacy tab of PShop. Config-driven, seeded to DB
    | on first boot by S1ShopSeeder. delivery_config varies by delivery_type:
    |   mail      → title, body, items[{item_id, amount}]
    |   boost     → boost_category, value, duration_hours
    |   boost_slot → duration_hours
    |   claim_reset → null
    |
    */

    's1_shop' => [
        'items' => [
            [
                'slug' => 'batch_upgrade_pack',
                'name' => 'Gói Nâng Cấp Hàng Loạt',
                'track' => 'whale',
                'currency' => 'kc',
                'price' => 5000000,
                'unlock_week' => 1,
                'limit_type' => 'weekly',
                'limit_count' => 1,
                'delivery_type' => 'mail',
                'delivery_config' => [
                    'title' => 'Quà từ S1 Legacy Shop',
                    'body' => 'Cảm ơn đã ủng hộ server!',
                    'items' => [
                        ['item_id' => 330003, 'amount' => 5], // Bình Nhân EXP x5
                    ],
                ],
            ],
            [
                'slug' => 'kc_claim_reset',
                'name' => 'Reset Thời Gian Đào',
                'track' => 'whale',
                'currency' => 'kc',
                'price' => 3000000,
                'unlock_week' => 3,
                'limit_type' => 'weekly',
                'limit_count' => 1,
                'delivery_type' => 'claim_reset',
                'delivery_config' => null,
            ],
            [
                'slug' => 'second_boost_slot',
                'name' => 'Slot Boost Thứ 2',
                'track' => 'whale',
                'currency' => 'kc',
                'price' => 8000000,
                'unlock_week' => 5,
                'limit_type' => 'weekly',
                'limit_count' => 1,
                'delivery_type' => 'boost_slot',
                'delivery_config' => ['duration_hours' => 168], // 1 tuần
            ],
            [
                'slug' => 'kc_regen_boost',
                'name' => 'Tăng Tốc Đào +20%',
                'track' => 'casual',
                'currency' => 'points',
                'price' => 50,
                'unlock_week' => 1,
                'limit_type' => 'weekly',
                'limit_count' => 2,
                'delivery_type' => 'boost',
                'delivery_config' => ['boost_category' => 'regen', 'value' => 0.20, 'duration_hours' => 24],
            ],
            [
                'slug' => 'kc_daily_cap_boost',
                'name' => 'Tăng Giới Hạn Đào +200k',
                'track' => 'casual',
                'currency' => 'points',
                'price' => 40,
                'unlock_week' => 1,
                'limit_type' => 'weekly',
                'limit_count' => 2,
                'delivery_type' => 'boost',
                'delivery_config' => ['boost_category' => 'daily_cap', 'value' => 200000, 'duration_hours' => 24],
            ],
            [
                'slug' => 'offline_gain_boost',
                'name' => 'Tăng Thời Gian Offline +1h',
                'track' => 'casual',
                'currency' => 'points',
                'price' => 25,
                'unlock_week' => 1,
                'limit_type' => 'daily',
                'limit_count' => 1,
                'delivery_type' => 'boost',
                'delivery_config' => ['boost_category' => 'offline', 'value' => 1, 'duration_hours' => 24],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Mining — simplified maintenance-based idle faucet
    |--------------------------------------------------------------------------
    |
    | Replaces complex multi-machine/upgrade/ascend model.
    | One user = one mining state.
    | Core loop: claim KC → efficiency decays → maintain to restore → optional timed boost.
    |
    | Formula:
    |   effective_rate = base_rate_per_hour * efficiency * boost_multiplier
    |   daily_cap      = base_daily_cap * cap_multiplier
    |
    | Efficiency:
    |   - Starts 1.0 after maintenance.
    |   - Decays by efficiency_decay_per_hour per hour since last_maintained_at.
    |   - Never below min_efficiency.
    |
    | Boost:
    |   - Tiered multipliers + cap multipliers with timed expiry.
    |   - Empty/expired = multiplier 1.0
    |
    | Daily cap:
    |   - base_daily_cap × active cap_multiplier
    |   - Always enforced at claim time
    |
    */

    'legacy_mining' => [
        'enabled' => true,

        'base_rate_per_hour' => 2_000,
        'base_daily_cap' => 30_000,

        'maintenance_cooldown_hours' => 6,
        'efficiency_decay_per_hour' => 0.03,
        'min_efficiency' => 0.35,

        'boosts' => [
            'small' => [
                'multiplier' => 1.2,
                'cap_multiplier' => 1.5,
                'hours' => 12,
            ],
            'medium' => [
                'multiplier' => 1.5,
                'cap_multiplier' => 2.5,
                'hours' => 24,
            ],
            'whale' => [
                'multiplier' => 2.0,
                'cap_multiplier' => 5.0,
                'hours' => 72,
            ],
        ],

        /*
        | Legacy power — compensation for old upgrade/ascend/unlock investments.
        |
        | Users who had machines/upgrades/ascensions in the old system receive
        | a permanent rate bonus. This bonus affects rate_per_hour only —
        | daily_cap is never increased by legacy power.
        |
        | Formula:
        |   legacy_power_bonus = speed_bonus + capacity_bonus + ascension_bonus + machine_bonus
        |   capped at max_bonus
        |   legacy_power_multiplier = 1 + legacy_power_bonus
        |   effective_rate = base_rate × efficiency × boost × legacy_power_multiplier
        |
        | Each old DiamondMachine contributes:
        |   speed_bonus      = (speed_level - 1) × speed_level_bonus
        |   capacity_bonus   = (storage_level - 1) × capacity_level_bonus
        |
        | Ascension bonus (from diamond_wallets.ascension_level):
        |   ascension_bonus  = ascension_level × ascension_level_bonus
        |
        | Machine count bonus (beyond first machine):
        |   machine_bonus    = (extra_machines) × extra_machine_bonus
        |
        */
        'legacy_power' => [
            'enabled' => true,
            'max_bonus' => 0.50,
            'speed_level_bonus' => 0.03,
            'capacity_level_bonus' => 0.01,
            'ascension_level_bonus' => 0.05,
            'extra_machine_bonus' => 0.05,
        ],
    ],

];
