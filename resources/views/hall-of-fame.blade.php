<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <title>Hall of Fame — MU Archangel H5</title>

    {{-- Premium Fonts (same as play.blade.php) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-dark: #07070a;
            --bg-card: rgba(17, 17, 24, 0.45);
            --bg-card-hover: rgba(17, 17, 24, 0.65);
            --gold-primary: #c9a94e;
            --gold-hover: #dfbe5e;
            --gold-glow: rgba(201, 169, 78, 0.25);
            --gold-dim: rgba(201, 169, 78, 0.10);
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --text-dim: #6b7280;
            --border-color: rgba(201, 169, 78, 0.15);
            --border-light: rgba(255, 255, 255, 0.06);
            --success: #22c55e;
            --danger: #ef4444;
            --font-display: 'Outfit', sans-serif;
            --font-body: 'Plus Jakarta Sans', sans-serif;
            --max-width: 42rem;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
            background: radial-gradient(ellipse at 50% 0%, #14141e 0%, #07070a 70%);
            color: var(--text-main);
            font-family: var(--font-body);
            -webkit-font-smoothing: antialiased;
            line-height: 1.5;
        }

        /* ── Layout ── */
        .hof-container {
            max-width: var(--max-width);
            margin: 0 auto;
            padding: 1.25rem 1rem 3rem;
        }

        /* ── Header ── */
        .hof-header {
            text-align: center;
            padding: 2rem 0 1.5rem;
        }

        .hof-header-title {
            font-family: var(--font-display);
            font-size: 1.625rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            margin: 0;
            background: linear-gradient(135deg, var(--gold-primary), #e8c96a, var(--gold-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px var(--gold-glow);
        }

        .hof-header-sub {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0.4rem 0 0;
            letter-spacing: 0.04em;
        }

        .hof-header-icon {
            font-size: 2rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        /* ── Server Section ── */
        .hof-server-section {
            margin-bottom: 1.5rem;
        }

        .hof-server-heading {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: var(--font-display);
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--gold-primary);
            padding: 0.75rem 1rem;
            background: var(--gold-dim);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem 0.75rem 0 0;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .hof-server-status {
            margin-left: auto;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .hof-server-status.ongoing {
            background: rgba(34, 197, 94, 0.15);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.25);
        }

        .hof-server-status.ended {
            background: rgba(107, 114, 128, 0.15);
            color: var(--text-dim);
            border: 1px solid rgba(107, 114, 128, 0.2);
        }

        /* ── Legend Cards ── */
        .hof-legend-list {
            list-style: none;
            margin: 0;
            padding: 0;
            border: 1px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 0.75rem 0.75rem;
            overflow: hidden;
        }

        .hof-legend-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border-light);
            transition: background 0.15s ease, border-color 0.15s ease, transform 0.15s ease;
        }

        .hof-legend-item:last-child {
            border-bottom: none;
        }

        .hof-legend-item:hover {
            background: var(--bg-card-hover);
        }

        .hof-rank {
            flex-shrink: 0;
            width: 1.75rem;
            text-align: center;
            font-family: var(--font-display);
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--text-dim);
        }

        .hof-rank.top-1 { color: #ffd700; }
        .hof-rank.top-2 { color: #c0c0c0; }
        .hof-rank.top-3 { color: #cd7f32; }

        .hof-legend-info {
            flex: 1;
            min-width: 0;
        }

        .hof-legend-name {
            font-family: var(--font-display);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .hof-legend-mystery {
            font-style: italic;
            color: var(--text-dim);
        }

        .hof-legend-meta {
            font-size: 0.75rem;
            color: var(--text-muted);
            margin-top: 0.15rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .hof-legend-meta span {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
        }

        .hof-legend-score {
            flex-shrink: 0;
            text-align: right;
        }

        .hof-legend-score-value {
            font-family: var(--font-display);
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gold-primary);
        }

        .hof-legend-score-label {
            font-size: 0.65rem;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .hof-legend-rewards {
            flex-shrink: 0;
            display: flex;
            gap: 0.2rem;
            font-size: 0.75rem;
        }

        /* ── Empty State ── */
        .hof-empty {
            text-align: center;
            padding: 3rem 1.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .hof-empty-icon {
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            opacity: 0.5;
        }

        .hof-empty-title {
            font-family: var(--font-display);
            font-size: 1.05rem;
            font-weight: 600;
            margin: 0 0 0.4rem;
        }

        .hof-empty-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin: 0;
            line-height: 1.6;
        }

        /* ── Trophy decorations for top 3 ── */
        .hof-trophy {
            font-size: 0.85rem;
        }

        /* ── Animations ── */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .hof-legend-item {
            animation: fadeInUp 0.35s ease both;
        }

        .hof-legend-item:nth-child(1)  { animation-delay: 0.02s; }
        .hof-legend-item:nth-child(2)  { animation-delay: 0.04s; }
        .hof-legend-item:nth-child(3)  { animation-delay: 0.06s; }
        .hof-legend-item:nth-child(4)  { animation-delay: 0.08s; }
        .hof-legend-item:nth-child(5)  { animation-delay: 0.10s; }
        .hof-legend-item:nth-child(6)  { animation-delay: 0.12s; }
        .hof-legend-item:nth-child(7)  { animation-delay: 0.14s; }
        .hof-legend-item:nth-child(8)  { animation-delay: 0.16s; }
        .hof-legend-item:nth-child(9)  { animation-delay: 0.18s; }
        .hof-legend-item:nth-child(10) { animation-delay: 0.20s; }

        /* ── Responsive ── */
        @media (min-width: 640px) {
            .hof-container {
                padding: 2rem 1.5rem 4rem;
            }
            .hof-header-title {
                font-size: 2rem;
            }
            .hof-legend-item {
                padding: 1rem 1.25rem;
            }
        }
    </style>
</head>
<body>

    <div class="hof-container">

        {{-- ════════════════════════════════════════════ --}}
        {{-- HEADER --}}
        {{-- ════════════════════════════════════════════ --}}
        <div class="hof-header">
            <span class="hof-header-icon">🏆</span>
            <h1 class="hof-header-title">MUH5 Hall of Fame</h1>
            <p class="hof-header-sub">Huyền thoại máy chủ — những người chơi xuất sắc nhất</p>
        </div>

        {{-- ════════════════════════════════════════════ --}}
        {{-- RANKINGS / LEGENDS --}}
        {{-- ════════════════════════════════════════════ --}}
        @forelse($legends as $serverKey => $serverLegends)
            <div class="hof-server-section">

                {{-- Server heading --}}
                @php
                    $firstLegend = $serverLegends->first();
                    $serverName = $firstLegend->server_name ?? $serverKey;
                    $serverStatus = $firstLegend->server_status ?? null;
                @endphp

                <div class="hof-server-heading">
                    <span>🖥️ {{ $serverName }}</span>
                    @if($serverStatus)
                        <span class="hof-server-status {{ $serverStatus === 'ongoing' ? 'ongoing' : 'ended' }}">
                            {{ $serverStatus === 'ongoing' ? 'Đang diễn ra' : 'Đã kết thúc' }}
                        </span>
                    @endif
                </div>

                {{-- Legend list --}}
                <ul class="hof-legend-list">
                    @foreach($serverLegends->sortBy('sort_order') as $idx => $legend)
                        @php
                            $rank = $idx + 1;
                            $rankClass = $rank <= 3 ? 'top-' . $rank : '';
                            $trophyMap = [1 => '🥇', 2 => '🥈', 3 => '🥉'];
                            $isMystery = is_null($legend->player_name);
                        @endphp
                        <li class="hof-legend-item">
                            {{-- Rank --}}
                            <div class="hof-rank {{ $rankClass }}">
                                @if($rank <= 3)
                                    <span class="hof-trophy">{{ $trophyMap[$rank] }}</span>
                                @else
                                    {{ $rank }}
                                @endif
                            </div>

                            {{-- Player info --}}
                            <div class="hof-legend-info">
                                <div class="hof-legend-name {{ $isMystery ? 'hof-legend-mystery' : '' }}">
                                    {{ $isMystery ? '🔮 Ẩn danh' : e($legend->player_name) }}
                                </div>
                                <div class="hof-legend-meta">
                                    @if($legend->category_label)
                                        <span>📂 {{ e($legend->category_label) }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Score --}}
                            @if($legend->score_value !== null)
                                <div class="hof-legend-score">
                                    <div class="hof-legend-score-value">
                                        {{ number_format((int) $legend->score_value) }}
                                    </div>
                                    @if($legend->score_label)
                                        <div class="hof-legend-score-label">{{ e($legend->score_label) }}</div>
                                    @endif
                                </div>
                            @endif

                            {{-- Rewards --}}
                            @if(!empty($legend->rewards))
                                <div class="hof-legend-rewards" title="Phần thưởng">
                                    @php
                                        $rewardList = is_array($legend->rewards) ? $legend->rewards : [];
                                    @endphp
                                    @foreach(array_slice($rewardList, 0, 3) as $reward)
                                        <span>{{ is_string($reward) ? e($reward) : '🎁' }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

        @empty
            {{-- ════════════════════════════════════════════ --}}
            {{-- EMPTY STATE --}}
            {{-- ════════════════════════════════════════════ --}}
            <div class="hof-empty">
                <div class="hof-empty-icon">🏆</div>
                <h2 class="hof-empty-title">Chưa có huyền thoại nào</h2>
                <p class="hof-empty-desc">
                    Hall of Fame hiện chưa có dữ liệu.<br>
                    Hãy quay lại sau để xem những người chơi xuất sắc nhất!
                </p>
            </div>
        @endforelse

    </div>

</body>
</html>
