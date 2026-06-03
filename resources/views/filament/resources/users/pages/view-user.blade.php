<x-filament-panels::page>
    {{-- SECTION A: User Identity --}}
    <x-filament::section compact>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <span class="text-lg font-bold">{{ $record->username }}</span>
                <span class="text-sm text-gray-500">#{{ $record->id }}</span>
            </div>
        </x-slot>
        <x-slot name="description">
            portal_uid: <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1 rounded">{{ $record->portal_uid }}</code>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {{-- Email --}}
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</span>
                <p class="text-sm font-medium truncate" title="{{ $record->email ?? '' }}">{{ $record->email ?? '—' }}</p>
            </div>

            {{-- Tier (inline edit) --}}
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tier</span>
                <div class="mt-0.5">
                    <select
                        wire:change="updateTier($event.target.value)"
                        class="text-sm font-medium rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 px-2 py-1"
                    >
                        <option value="free" @selected($record->tier === 'free')>Free</option>
                        <option value="vip" @selected($record->tier === 'vip')>VIP</option>
                    </select>
                </div>
            </div>

            {{-- Checkin boost expires (inline edit) --}}
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Boost hết hạn</span>
                <div class="mt-0.5">
                    <input
                        type="datetime-local"
                        value="{{ $record->checkin_boost_expires_at?->format('Y-m-d\TH:i') ?? '' }}"
                        wire:change="updateCheckinBoost($event.target.value ? $event.target.value + ':00' : null)"
                        class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-primary-500 focus:border-primary-500 px-2 py-1 w-full"
                    >
                </div>
            </div>

            {{-- Last login IP --}}
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">IP cuối</span>
                <p class="text-sm font-mono">{{ $record->last_login_ip ?? '—' }}</p>
            </div>

            {{-- Last login at --}}
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Đăng nhập cuối</span>
                <p class="text-sm">{{ $record->last_login_at?->diffForHumans() ?? '—' }}</p>
            </div>

            {{-- Created at --}}
            <div>
                <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tạo tài khoản</span>
                <p class="text-sm">{{ $record->created_at?->format('d/m/Y') ?? '—' }}</p>
            </div>
        </div>
    </x-filament::section>

    {{-- SECTION B: Point Balance + Quick Actions --}}
    <x-filament::section compact>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <span class="text-lg font-bold">POINT</span>
                <span class="text-2xl font-mono tabular-nums {{ $record->points >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                    {{ number_format($record->points) }}
                </span>
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Add Points --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Cộng điểm</span>
                <div class="mt-2 flex gap-2">
                    <input
                        type="number"
                        min="1"
                        placeholder="Số lượng"
                        wire:model="pointForm.amount"
                        class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-2 py-1.5 w-24"
                    >
                    <x-filament::button
                        size="sm"
                        color="success"
                        wire:click="creditPoints"
                        icon="heroicon-m-plus"
                    >
                        Cộng
                    </x-filament::button>
                </div>
                <input
                    type="text"
                    placeholder="Lý do (không bắt buộc)"
                    wire:model="pointForm.reason"
                    class="mt-1.5 w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-2 py-1"
                >
            </div>

            {{-- Deduct Points --}}
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Trừ điểm</span>
                <div class="mt-2 flex gap-2">
                    <input
                        type="number"
                        min="1"
                        placeholder="Số lượng"
                        wire:model="pointForm.amount"
                        class="flex-1 text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-2 py-1.5 w-24"
                    >
                    <x-filament::button
                        size="sm"
                        color="danger"
                        wire:click="debitPoints"
                        icon="heroicon-m-minus"
                    >
                        Trừ
                    </x-filament::button>
                </div>
                <input
                    type="text"
                    placeholder="Lý do (không bắt buộc)"
                    wire:model="pointForm.reason"
                    class="mt-1.5 w-full text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 px-2 py-1"
                >
            </div>
        </div>
    </x-filament::section>

    {{-- SECTION C: Point Transaction Log --}}
    <x-filament::section compact>
        <x-slot name="heading">
            <span class="text-base font-bold">Lịch sử giao dịch Point</span>
            <x-slot name="description">50 gần nhất</x-slot>
        </x-slot>

        @php
            $transactions = $this->getPointTransactions();
        @endphp

        @if ($transactions->isEmpty())
            <p class="text-sm text-gray-400 italic">Chưa có giao dịch nào.</p>
        @else
            <div class="overflow-x-auto -mx-3">
                <table class="w-full text-xs whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Thời gian</th>
                            <th class="text-right px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Số lượng</th>
                            <th class="text-left px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Loại</th>
                            <th class="text-left px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Lý do</th>
                            <th class="text-right px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Số dư sau</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $tx)
                            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-3 py-1.5 text-gray-500">{{ $tx->created_at->format('H:i d/m') }}</td>
                                <td class="px-3 py-1.5 text-right font-mono tabular-nums {{ $tx->amount > 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                    {{ $tx->amount > 0 ? '+' : '' }}{{ number_format($tx->amount) }}
                                </td>
                                <td class="px-3 py-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                        {{ $tx->type }}
                                    </span>
                                </td>
                                <td class="px-3 py-1.5 max-w-[200px] truncate text-gray-600 dark:text-gray-400" title="{{ $tx->reference ?? '' }}">
                                    {{ $tx->reference ?? '—' }}
                                </td>
                                <td class="px-3 py-1.5 text-right font-mono tabular-nums">{{ number_format((int) $tx->balance_after) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- SECTION D: GM Action Log --}}
    <x-filament::section compact>
        <x-slot name="heading">
            <span class="text-base font-bold">GM Action Log</span>
            <x-slot name="description">20 gần nhất — target: {{ $record->username }}</x-slot>
        </x-slot>

        @php
            $gmActions = $this->getGmActions();
        @endphp

        @if ($gmActions->isEmpty())
            <p class="text-sm text-gray-400 italic">Chưa có GM action nào cho user này.</p>
        @else
            <div class="overflow-x-auto -mx-3">
                <table class="w-full text-xs whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="text-left px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Thời gian</th>
                            <th class="text-left px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Action</th>
                            <th class="text-left px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Admin</th>
                            <th class="text-left px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Trạng thái</th>
                            <th class="text-left px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Payload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gmActions as $gm)
                            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-3 py-1.5 text-gray-500">{{ $gm->created_at->format('H:i d/m') }}</td>
                                <td class="px-3 py-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-xs font-medium
                                        @switch($gm->action_type)
                                            @case('ban') bg-danger-100 text-danger-700 dark:bg-danger-900/30 dark:text-danger-400 @break
                                            @case('kick') bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400 @break
                                            @case('lookup') bg-info-100 text-info-700 dark:bg-info-900/30 dark:text-info-400 @break
                                            @case('send_mail') bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400 @break
                                            @default bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300
                                        @endswitch
                                    ">
                                        {{ $gm->action_type }}
                                    </span>
                                </td>
                                <td class="px-3 py-1.5 text-gray-600 dark:text-gray-400">{{ $gm->admin?->name ?? $gm->admin_id }}</td>
                                <td class="px-3 py-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-xs font-medium
                                        @switch($gm->status)
                                            @case('pending') bg-warning-100 text-warning-700 @break
                                            @case('executing') bg-info-100 text-info-700 @break
                                            @case('executed') bg-success-100 text-success-700 @break
                                            @case('failed') bg-danger-100 text-danger-700 @break
                                            @default bg-gray-100 text-gray-700
                                        @endswitch
                                    ">
                                        {{ $gm->status }}
                                    </span>
                                </td>
                                <td class="px-3 py-1.5 max-w-[250px] truncate font-mono text-gray-500" title="{{ json_encode($gm->payload, JSON_UNESCAPED_UNICODE) }}">
                                    @if ($gm->payload)
                                        {{ json_encode($gm->payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
