<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NarrativeService
{
    // Layer 1: System Prefixes
    private static array $prefixesSys = ['[SYS]', '[CORE]', '[FEED]', '[ALERT]'];

    private static array $prefixesWhale = ['[GLOBAL]', '[CROWN]', '[VIP]', '[WHALE]'];

    private static array $prefixesItem = ['[GIFT]', '[REWARD]', '[LOOT]'];

    // Layer 2.2: Micro Storyline (Streak)
    private static array $streak2 = ['tiếp tục nạp', 'đang giữ nhịp nạp', 'lại vừa bổ sung', 'vẫn đang bứt tốc với'];

    private static array $streak3 = ['vẫn chưa có dấu hiệu dừng lại với', 'tiếp tục dội bom hệ thống với', 'đang thống trị phiên giao dịch với'];

    private static array $verbsReplay = ['từng nạp', 'đã từng bổ sung', 'từng gia tăng', 'đã nạp'];

    // Layer 3: Whale System
    private static array $whaleTop1 = ['đang thống trị BXH', 'giữ vững vị trí Đại Gia', 'bỏ xa phần còn lại', 'duy trì quyền lực tuyệt đối'];

    private static array $whaleNormal = ['đang bám đuổi TOP server', 'sở hữu khối tài sản khổng lồ', 'tiếp tục chi tiêu lượng WPoint'];

    private static array $whaleSuffixes = [
        '— toàn server đang bị bỏ xa',
        '— khoảng cách đang bị nới rộng',
        '— chưa có dấu hiệu dừng lại',
        '— áp đảo hoàn toàn',
    ];

    /**
     * Render the narrative event for the social feed
     *
     * @param array<string, mixed> $event
     * @return array<string, mixed>
     */
    public static function render(array $event, string $clientIp = 'global'): array
    {
        $template = (string) ($event['template'] ?? '');
        $isReplay = (bool) ($event['is_replay'] ?? false);
        $userId = $event['user_id'] ?? null;
        $amount = (float) ($event['amount'] ?? 0.0);

        if ($userId && $amount > 0.0 && Cache::pull("rivalry_rage_watch:{$userId}")) {
            Log::info('loser_recharge_within_5min', ['user_id' => $userId, 'amount' => $amount]);
        }

        $event['format'] = 'standard';
        $serverId = (int) ($event['server_id'] ?? $event['metadata']['server_id'] ?? 1);

        if ($template === 'echo_effect') {
            $pool = [
                'khoảng cách đang bị nới rộng',
                'áp lực đang tăng dần toàn server',
                'chưa có dấu hiệu dừng lại',
                'cuộc đua đang cực kỳ nóng',
                'hỏa lực đang đè nặng lên các cựu Top',
            ];

            // V5 Echo Layer (Mirror Effect)
            $worldBuffData = Cache::get('world_buff:global');
            if (is_array($worldBuffData) && isset($worldBuffData['source'])) {
                $timeLeft = (int) ($worldBuffData['decay'] ?? time()) - time();

                if ($timeLeft > 0 && $timeLeft <= 180) { // Last 3 minutes
                    $pool[] = 'Sức nóng từ '.$worldBuffData['source'].' đang giảm dần. Nhanh tay!';
                    $pool[] = 'Cơ hội cuối cùng khám phá Hỏa lực của '.$worldBuffData['source'];
                } elseif ($timeLeft > 180) {
                    $pool[] = 'Server đang được ép hỏa lực siêu tốc từ '.$worldBuffData['source'];
                    $pool[] = 'Nhịp khai thác hầm mỏ đang được tăng tốc bởi '.$worldBuffData['source'];
                }
            }

            $event['prefix'] = '[SYS] ';
            $event['message'] = self::rotate('sf:nar:echo', $pool);
            $event['tone_type'] = 'normal';
            $event['value'] = '';
            $event['unit'] = '';
            $event['valueClass'] = 'text-sys';
            $event['isHighlight'] = false;
            $event['priority'] = 700;

        } elseif ($template === 'whale_presence') {
            $isTop1 = (bool) ($event['metadata']['is_top_1'] ?? false);

            if ($isTop1 && ! $isReplay && $userId !== null) {
                // Rule 2: Rivalry Flip Detection
                $lastTop1 = Cache::get("server_top1_user:{$serverId}");
                if ($lastTop1 && (int) $lastTop1 !== (int) $userId) {
                    // Auto-arm echo response on current user's session natively
                    Cache::put('sf:echo:'.md5($clientIp), true, 20);
                }
                Cache::put("server_top1_user:{$serverId}", $userId, 86400);
            }

            $pool = $isTop1 ? self::$whaleTop1 : self::$whaleNormal;
            $verb = self::rotate('sf:nar:whale_'.($isTop1 ? '1' : 'n'), $pool, $userId !== null ? (int) $userId : null);

            // Suffix Layer
            $suffix = '';
            if ($isTop1) {
                $suffix = ' '.self::rotate('sf:nar:wsuf', self::$whaleSuffixes);
            }

            $event['prefix'] = self::rotate('sf:nar:wpref', self::$prefixesWhale).' ';
            $event['message'] = $verb.$suffix.' với ';
            $event['tone_type'] = 'whale';
            $event['value'] = number_format((float) ($event['metadata']['amount'] ?? 0.0));
            $event['unit'] = ' WPoint';
            $event['valueClass'] = 'text-warning glow-hard';
            $event['isHighlight'] = true;
            $event['priority'] = 999;

        } elseif (in_array($template, ['user_recharge_wcoin', 'user_recharge'], true)) {
            $amount = (float) ($event['metadata']['amount'] ?? 0.0);

            // Micro Storyline Layer (Streak Counter) 10 mins TTL
            $streak = 0;
            if ($userId !== null && ! $isReplay) {
                $streakKey = 'sf:nar:streak:'.$userId;
                $streak = (int) Cache::get($streakKey, 0);
                Cache::put($streakKey, $streak + 1, 600);
            }

            if ($isReplay) {
                $verb = self::rotate('sf:nar:rech_rep', self::$verbsReplay, $userId !== null ? (int) $userId : null);
                $event['prefix'] = '[HIST] ';
            } else {
                $showPrefix = self::rotate('sf:nar:pshow', [true, true, true, true, false]);
                $event['prefix'] = $showPrefix ? (self::rotate('sf:nar:spref', self::$prefixesSys).' ') : '';

                if ($amount >= 500 && $streak === 1) {
                    $verb = self::rotate('sf:nar:strk2', self::$streak2, $userId !== null ? (int) $userId : null);
                    $event['priority'] = (int) max((int) ($event['priority'] ?? 0), 600);
                } elseif ($amount >= 500 && $streak >= 2) {
                    $verb = self::rotate('sf:nar:strk3', self::$streak3, $userId !== null ? (int) $userId : null);
                    $event['priority'] = (int) max((int) ($event['priority'] ?? 0), 800);
                } else {
                    if ($amount >= 10000) {
                        $verb = self::rotate('sf:nar:rech_dom', self::getPersonaVerbs($userId !== null ? (int) $userId : null, 'dom'), $userId !== null ? (int) $userId : null);
                        $event['priority'] = 900;
                    } elseif ($amount >= 2000) {
                        $verb = self::rotate('sf:nar:rech_agg', self::getPersonaVerbs($userId !== null ? (int) $userId : null, 'agg'), $userId !== null ? (int) $userId : null);
                        $event['priority'] = 600;
                    } elseif ($amount >= 500) {
                        $verb = self::rotate('sf:nar:rech_act', self::getPersonaVerbs($userId !== null ? (int) $userId : null, 'act'), $userId !== null ? (int) $userId : null);
                    } else {
                        $verb = self::rotate('sf:nar:rech_cas', self::getPersonaVerbs($userId !== null ? (int) $userId : null, 'cas'), $userId !== null ? (int) $userId : null);
                    }
                }
            }

            // Limit Modifier Overstacking: Persona Dominance and high Streaks are dramatic enough
            $modifier = '';
            if ($isReplay && $amount >= 200) {
                $addMod = self::rotate('sf:nar:rmodshow', [true, false, false]);
                if ($addMod) {
                    $modifier = ' '.self::rotate('sf:nar:rmod', ['(gần đây)', '(trước đó)', '(cách đây không lâu)']);
                }
            } elseif (! $isReplay && $amount >= 500) {
                if ($amount >= 10000 && $userId !== null) {
                    // World Buff Burst Drop - disabled (ProcessWhaleImpact removed)
                }
                if ($streak >= 3 && $userId !== null) {
                    // Personal Streak Reward - disabled (ProcessWhaleImpact removed)
                }

                if ($amount >= 10000 || $streak >= 2) {
                    $modifier = ''; // Prevent being over-dramatic
                } else {
                    $temporalMods = self::getTemporalModifiers($clientIp);
                    if ($amount >= 5000) {
                        $modifier = ' '.self::rotate('sf:nar:wmod', $temporalMods['whale']);
                    } else {
                        $modifier = ' '.self::rotate('sf:nar:nmod', $temporalMods['normal']);
                    }
                }
            }

            $event['message'] = $verb.$modifier;
            $event['tone_type'] = $isReplay ? 'replay' : 'normal';
            $event['value'] = number_format($amount);
            $event['unit'] = ' WPoint';
            $event['valueClass'] = 'text-warning glow-hard';
            $event['isHighlight'] = $amount >= 200;

            // Rhythm Variation Layer (Micro Jitter Format Rotation)
            if (! $isReplay && $amount >= 200) {
                $noise = abs(crc32($clientIp.((string) microtime(true)))) % 2;
                $index = (int) Cache::get('sf:nar:fmt', 0);
                Cache::put('sf:nar:fmt', $index + 1, 86400);

                $formats = ['standard', 'reverse', 'standard'];
                $formatType = $formats[($index + $noise) % 3];

                if ($formatType === 'reverse') {
                    $event['format'] = 'reverse';
                    $event['message'] = ' '.$verb.$modifier;
                }
            }

        } elseif ($template === 'user_total_spend') {
            $amount = (float) ($event['metadata']['amount'] ?? 0.0);
            $pool = $isReplay ? ['từng chi tiêu', 'đã chi tiêu tổng cộng', 'từng tích lũy'] : ['đã chi tiêu tổng cộng', 'vừa tích lũy đạt'];
            $verb = self::rotate('sf:nar:spend', $pool, $userId !== null ? (int) $userId : null);

            $event['prefix'] = $isReplay ? '[HIST] ' : self::rotate('sf:nar:spref', self::$prefixesSys).' ';
            $event['message'] = $verb;
            $event['tone_type'] = $isReplay ? 'replay' : 'normal';
            $event['value'] = number_format($amount);
            $event['unit'] = ' WPoint';
            $event['valueClass'] = 'text-warning glow-hard';
            $event['isHighlight'] = $amount >= 1000;

        } elseif ($template === 'user_purchase_item') {
            $itemName = (string) ($event['metadata']['item_name'] ?? 'Vật phẩm');
            $isPet = (bool) ($event['metadata']['is_pet'] ?? false);

            $pool = $isReplay ? ['từng sở hữu', 'đã nhận được', 'từng rinh về'] : ['vừa sở hữu', 'vừa chốt đơn', 'đã tung lụa rinh'];
            $verb = self::rotate($isReplay ? 'sf:nar:item_rep' : 'sf:nar:item_norm', $pool, $userId !== null ? (int) $userId : null);

            $event['prefix'] = $isReplay ? '[HIST] ' : self::rotate('sf:nar:ipref', self::$prefixesItem).' ';
            $event['message'] = $verb;
            $event['tone_type'] = $isReplay ? 'replay' : 'normal';
            $event['value'] = $itemName;
            $event['unit'] = '';
            $event['valueClass'] = $isPet ? 'text-purple' : 'text-warning';

        } elseif ($template === 'user_milestone') {
            $amount = (int) ($event['metadata']['tier'] ?? 0);
            $pool = $isReplay ? ['đã từng đạt mốc', 'từng chạm mốc', 'đã chinh phục mốc'] : ['vừa phá đảo mốc nạp', 'vừa chạm đỉnh mốc', 'vừa xác lập kỷ lục mốc'];
            $verb = self::rotate('sf:nar:stone', $pool, $userId !== null ? (int) $userId : null);

            $event['prefix'] = $isReplay ? '[HIST] ' : '[MILESTONE] ';
            $event['message'] = $verb;
            $event['tone_type'] = $isReplay ? 'replay' : 'normal';
            $event['value'] = number_format($amount);
            $event['unit'] = '';
            $event['valueClass'] = 'text-danger glow-hard';
            if ($amount >= 10000) {
                $event['priority'] = 800;
            }

        } elseif ($template === 'user_jackpot') {
            $pool = $isReplay ? ['từng nổ', 'đã ôm trọn', 'từng giật'] : ['vừa trúng', 'vừa kích nổ', 'đã ôm trọn'];
            $verb = self::rotate('sf:nar:jack', $pool, $userId !== null ? (int) $userId : null);

            $event['prefix'] = $isReplay ? '[HIST] ' : '[JACKPOT] ';
            $event['message'] = $verb.' — toàn server chú ý ';
            $event['tone_type'] = $isReplay ? 'replay' : 'normal';
            $event['value'] = 'JACKPOT';
            $event['unit'] = '';
            $event['valueClass'] = 'text-warning glow-hard';
            $event['isHighlight'] = true;
            $event['priority'] = 700;
        }

        return $event;
    }

    /**
     * @return array{normal: array<int, string>, whale: array<int, string>}
     */
    private static function getTemporalModifiers(string $clientIp): array
    {
        $hourOffset = crc32($clientIp) % 6 - 3;
        $hour = (date('H') + $hourOffset + 24) % 24;

        if ($hour >= 0 && $hour < 6) { // Night Mode
            return [
                'normal' => ['(âm thầm)', '(lặng lẽ)', '(không ồn ào)'],
                'whale' => ['(xuyên đêm)', '(khuya vẫn nạp)', '(không ngủ)'],
            ];
        } elseif ($hour >= 18 && $hour <= 23) { // Rush Hour
            return [
                'normal' => ['(tốc độ)', '(dứt khoát)', '(mạnh tay)'],
                'whale' => ['(dồn dập)', '(cực gắt)', '(rực lửa)', '(bùng nổ)'],
            ];
        }

        // Standard Day Mode
        return [
            'normal' => ['(mạnh tay)', '(âm thầm)', '(dứt khoát)'],
            'whale' => ['(dồn dập)', '(không ngừng nghỉ)', '(chớp nhoáng)'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function getPersonaVerbs(?int $userId, string $type): array
    {
        if (! $userId) {
            $userId = 1;
        }

        $personaSeed = $userId + (int) floor(time() / 86400); // Changes daily
        $persona = $personaSeed % 3;

        if ($persona === 0) { // Aggressive
            if ($type === 'dom') {
                return ['đang đè bẹp hệ thống bằng', 'đang oanh tạc máy chủ với', 'bóp nghẹt bảng xếp hạng nhờ'];
            }
            if ($type === 'agg') {
                return ['tiếp tục dội bom bằng', 'đang tăng tốc hủy diệt bằng', 'đẩy mạnh đàn áp với'];
            }
            if ($type === 'act') {
                return ['vừa bơm thêm', 'đã quyết đoán nạp', 'gia tăng hỏa lực'];
            }

            return ['vừa nạp', 'vừa bổ sung', 'đã thêm'];
        } elseif ($persona === 1) { // Silent
            if ($type === 'dom') {
                return ['âm thầm tích lũy vươn đỉnh với', 'lặng lẽ thâu tóm hệ thống nhờ', 'đang thống trị hệ thống nhờ'];
            }
            if ($type === 'agg') {
                return ['tiếp tục bơm tích lũy', 'vẫn âm thầm nới rộng bằng', 'duy trì nhịp nạp'];
            }
            if ($type === 'act') {
                return ['lặng lẽ gia tăng quỹ', 'vừa âm thầm bơm', 'đã lặng lẽ nạp'];
            }

            return ['vừa bổ sung', 'tích lũy thêm', 'đã đóng góp'];
        } else { // Flex
            if ($type === 'dom') {
                return ['khẳng định đẳng cấp Đại Gia bằng', 'phô diễn sức mạnh nhờ', 'đang trình diễn quyền lực bằng'];
            }
            if ($type === 'agg') {
                return ['đang phô trương thanh thế với', 'tiếp tục lên tiếng bằng', 'vừa thể hiện đẳng cấp với'];
            }
            if ($type === 'act') {
                return ['vừa khoe thế lực bằng', 'đã vung tiền nạp', 'vừa chứng tỏ độ chịu chơi thêm'];
            }

            return ['vừa nạp nhẹ', 'vừa sắm thêm', 'đã thư giãn nạp'];
        }
    }

    private static function rotate(string $key, array $pool, ?int $userId = null): string
    {
        $index = (int) Cache::get($key, 0);
        $phrase = $pool[$index % count($pool)];

        if ($userId !== null) {
            $lastKey = 'sf:nar:last:'.$userId.':'.$key;
            $lastPhrase = Cache::get($lastKey);

            $attempts = 0;
            while ($phrase === $lastPhrase && $attempts < count($pool)) {
                $index++;
                $phrase = $pool[$index % count($pool)];
                $attempts++;
            }
            Cache::put($lastKey, $phrase, 60);
        }

        Cache::put($key, $index + 1, 86400);

        return $phrase;
    }
}
