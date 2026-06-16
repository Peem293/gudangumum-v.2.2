<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AdjustmentRequest;
use BackedEnum;
use UnitEnum;

class AdjustmentReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Koreksi Stok';
    protected static ?string $title = 'Laporan Koreksi Stok (Adjustment)';
    protected string $view = 'filament.pages.reports.adjustment-report';
    protected static ?int $navigationSort = 3;

    public ?array $data = [];
    public bool $isProcessed = false;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->hasRole(['administrator', 'direktur', 'admin_gudang', 'manager_keuangan']);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->afterOrEqual('start_date'),
                    ])
            ])
            ->statePath('data');
    }

    public function processReport(): void
    {
        $this->validate();

        $this->startDate = $this->data['start_date'];
        $this->endDate = $this->data['end_date'];
        $this->isProcessed = true;
    }

    public function getPdfUrl(): string
    {
        return route('reports.adjustments.print', [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]);
    }

    public function exportExcel()
    {
        if (!$this->isProcessed || !$this->startDate || !$this->endDate) {
            return null;
        }

        $startDateFormatted = Carbon::parse($this->startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::parse($this->endDate)->format('Y-m-d');
        $filename = "Laporan_Koreksi_Stok_{$startDateFormatted}_s_d_{$endDateFormatted}.xlsx";

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Headers
        $headers = [
            'No. Koreksi', 
            'Nama Barang', 
            'Jenis', 
            'Qty Koreksi', 
            'Pembuat', 
            'Status',
            'Alasan', 
            'Tanggal'
        ];

        foreach ($headers as $colIndex => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $headerText);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
        }

        $adjs = AdjustmentRequest::query()
            ->whereBetween('created_at', [
                Carbon::parse($this->startDate)->startOfDay(),
                Carbon::parse($this->endDate)->endOfDay()
            ])
            ->with(['item', 'user'])
            ->orderBy('created_at', 'asc')
            ->get();

        $row = 2;
        foreach ($adjs as $adj) {
            $sheet->setCellValue('A' . $row, $adj->adjustment_number ?? '-');
            $sheet->setCellValue('B' . $row, $adj->item->name ?? '-');
            $sheet->setCellValue('C' . $row, $adj->type === 'in' ? 'Masuk (In)' : 'Keluar (Out)');
            $sheet->setCellValue('D' . $row, $adj->qty);
            $sheet->setCellValue('E' . $row, $adj->user->name ?? '-');
            $sheet->setCellValue('F' . $row, str_replace('_', ' ', $adj->status ?? '-'));
            $sheet->setCellValue('G' . $row, $adj->reason ?? '-');
            $sheet->setCellValue('H' . $row, $adj->created_at ? $adj->created_at->format('Y-m-d H:i:s') : '-');
            $row++;
        }

        // Auto size columns
        foreach (range(1, count($headers)) as $colIndex) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = AdjustmentRequest::query();

                if (!$this->isProcessed || !$this->startDate || !$this->endDate) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->whereBetween('created_at', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay()
                ])->with(['item', 'user']);
            })
            ->columns([
                TextColumn::make('adjustment_number')
                    ->label('No. Koreksi')
                    ->sortable(),
                TextColumn::make('item.name')
                    ->label('Nama Barang'),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => $state === 'in' ? 'Masuk' : 'Keluar'),
                TextColumn::make('qty')
                    ->label('Qty Koreksi')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Pembuat'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'approved_by_manager' => 'info',
                        'executed_by_admin' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', $state)),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(30),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}
