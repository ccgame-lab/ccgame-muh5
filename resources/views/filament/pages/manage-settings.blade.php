<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-4 text-right">
                <x-filament::button type="submit" icon="heroicon-m-check">
                    Lưu cài đặt
                </x-filament::button>
            </div>
        </form>

        <x-filament-actions::modals />
    </x-filament::card>
</x-filament-panels::page>
