<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit.prevent="processReport">
            {{ $this->form }}
            
            <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(156, 163, 175, 0.2); display: flex; justify-content: flex-end; gap: 12px;">
                <x-filament::button type="submit" color="primary">
                    Proses Laporan
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>

    @if($this->isProcessed)
        <x-filament::card class="mt-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">Hasil Laporan Permintaan Unit</h3>
                    <p class="text-sm text-gray-500">Periode: {{ \Carbon\Carbon::parse($this->startDate)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($this->endDate)->format('d M Y') }}</p>
                </div>
                <div class="flex gap-2">
                    <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-document-arrow-down">
                        Export Excel (.xlsx)
                    </x-filament::button>
                    <x-filament::button color="danger" icon="heroicon-o-printer" tag="a" href="{!! $this->getPdfUrl() !!}" target="_blank">
                        Export PDF / Cetak
                    </x-filament::button>
                </div>
            </div>
            
            {{ $this->table }}
        </x-filament::card>
    @else
        <div class="mt-6 flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-900 border border-dashed border-gray-300 dark:border-gray-700 rounded-xl">
            <x-filament::icon icon="heroicon-o-calendar" class="w-12 h-12 text-gray-400 mb-2" />
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Silakan tentukan periode tanggal dan klik "Proses Laporan" untuk menampilkan data.</p>
        </div>
    @endif
</x-filament-panels::page>
