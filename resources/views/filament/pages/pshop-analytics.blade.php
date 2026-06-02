<x-filament-panels::page>
    <div style="background: rgba(30,41,59,0.5); border: 1px solid #334155; border-radius: 8px; padding: 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; gap: 24px;">
            <div>
                <div style="font-size: 0.85rem; color: #94a3b8; font-weight: bold; text-transform: uppercase;">Sự Kiện Đang Chạy</div>
                <div style="font-size: 1.1rem; color: #fff; font-weight: bold;">
                    @php
                        $eventName = $activeRace->name ?? ($activeMilestone->name ?? ($activeBoost->name ?? 'Không có sự kiện'));
                    @endphp
                    🎉 {{ $eventName }}
                </div>
            </div>            
            <div style="display: flex; gap: 12px; align-items: center;">
                <span class="fi-badge" style="background: {{ $activeBoost ? 'rgba(16,185,129,0.1)' : 'rgba(100,116,139,0.1)' }}; color: {{ $activeBoost ? '#10b981' : '#64748b' }}; border: 1px solid {{ $activeBoost ? 'rgba(16,185,129,0.3)' : 'rgba(100,116,139,0.3)' }}; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem;">
                    Boost: {{ $activeBoost ? 'ON' : 'OFF' }}
                </span>
                <span class="fi-badge" style="background: {{ $activeRace ? 'rgba(239,68,68,0.1)' : 'rgba(100,116,139,0.1)' }}; color: {{ $activeRace ? '#ef4444' : '#64748b' }}; border: 1px solid {{ $activeRace ? 'rgba(239,68,68,0.3)' : 'rgba(100,116,139,0.3)' }}; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem;">
                    Race: {{ $activeRace ? 'ON' : 'OFF' }}
                </span>
                <span class="fi-badge" style="background: {{ $activeMilestone ? 'rgba(245,158,11,0.1)' : 'rgba(100,116,139,0.1)' }}; color: {{ $activeMilestone ? '#f59e0b' : '#64748b' }}; border: 1px solid {{ $activeMilestone ? 'rgba(245,158,11,0.3)' : 'rgba(100,116,139,0.3)' }}; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.8rem;">
                    Milestone: {{ $activeMilestone ? 'ON' : 'OFF' }}
                </span>
            </div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 0.85rem; color: #94a3b8; font-weight: bold; text-transform: uppercase;">Thời gian còn lại</div>
            <div style="font-size: 1.1rem; color: #facc15; font-weight: bold;" wire:poll.60s>
                @php
                    $endTime = null;
                    if ($activeBoost && $activeBoost->end_time) $endTime = $activeBoost->end_time;
                    if ($activeRace && $activeRace->end_time) $endTime = $activeRace->end_time;
                    if (now() && isset($endTime) && now()->lt($endTime)) {
                        echo now()->diff($endTime)->format('%Hh %Im %Ss');
                    } else {
                        echo '--';
                    }
                @endphp
            </div>
        </div>
    </div>
</x-filament-panels::page>
