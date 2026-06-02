<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            System Alerts
        </x-slot>

        @php
            $alerts = $this->getAlerts();
        @endphp

        @if (empty($alerts))
            <div class="flex items-center gap-2 text-sm font-medium text-success-600 dark:text-success-400">
                <x-filament::icon
                    alias="panels::widgets.system-alerts.ok"
                    icon="heroicon-o-check-circle"
                    class="h-5 w-5"
                />
                All systems normal — không có cảnh báo nào.
            </div>
        @else
            <ul class="space-y-3">
                @foreach ($alerts as $alert)
                    @php
                        $colorMap = [
                            'danger' => 'text-danger-600 dark:text-danger-400 bg-danger-50 dark:bg-danger-950 border-danger-200 dark:border-danger-800',
                            'warning' => 'text-warning-600 dark:text-warning-400 bg-warning-50 dark:bg-warning-950 border-warning-200 dark:border-warning-800',
                            'info' => 'text-info-600 dark:text-info-400 bg-info-50 dark:bg-info-950 border-info-200 dark:border-info-800',
                        ];
                        $iconMap = [
                            'danger' => 'heroicon-o-exclamation-circle',
                            'warning' => 'heroicon-o-exclamation-triangle',
                            'info' => 'heroicon-o-information-circle',
                        ];
                        $classes = $colorMap[$alert['level']] ?? $colorMap['warning'];
                        $icon = $iconMap[$alert['level']] ?? $iconMap['warning'];
                    @endphp

                    <li class="flex items-start gap-3 rounded-lg border p-3 {{ $classes }}">
                        <x-filament::icon
                            :alias="'panels::widgets.system-alerts.' . $alert['type']"
                            :icon="$icon"
                            class="mt-0.5 h-5 w-5 shrink-0"
                        />
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $alert['title'] }}</p>
                            <p class="mt-0.5 text-sm opacity-80">{{ $alert['message'] }}</p>
                        </div>
                        <span class="ml-auto shrink-0 rounded-full px-2 py-0.5 text-xs font-bold ring-1 ring-current">
                            {{ $alert['count'] }}
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
