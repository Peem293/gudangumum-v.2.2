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
use App\Models\PurchaseOrderDetail;
use BackedEnum;
use UnitEnum;

class PurchaseOrderReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Pengadaan';
    protected static ?string $title = 'Laporan Pengadaan Barang (PO)';
    protected string $view = 'filament.pages.reports.purchase-order-report';
    protected static ?int $navigationSort = 2;

    public ?array $data = [];
    public bool $isProcessed = false;
    public ?string $startDate = null;
    public ?string $endDate = null;

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        return $user->hasRole(['administrator', 'direktur', 'admin_gudang', 'manager_keuangan'])
            || $user->isPurchasingManager();
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
        $this->endDate   = $this->data['end_date'];
        $this->isProcessed = true;
    }

    public function getPdfUrl(): string
    {
        return route('reports.purchase-orders.print', [
            'start_date' => $this->startDate,
            'end_date'   => $this->endDate,
        ]);
    }

    public function exportExcel()
    {
        if (!$this->isProcessed || !$this->startDate || !$this->endDate) {
            return null;
        }

        $startDateFormatted = Carbon::parse($this->startDate)->format('Y-m-d');
        $endDateFormatted   = Carbon::parse($this->endDate)->format('Y-m-d');
        $filename = "Laporan_Pembelian_{$startDateFormatted}_s_d_{$endDateFormatted}.xlsx";

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Headers
        $headers = [
            'No. PO',
            'Supplier',
            'Pembuat',
            'Nama Barang',
            'Qty',
            'Satuan',
            'Harga Satuan (Rp)',
            'Subtotal (Rp)',
            'Status PO',
            'Tanggal PO',
        ];

        foreach ($headers as $colIndex => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $headerText);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
        }

        $details = PurchaseOrderDetail::query()
            ->whereHas('purchaseOrder', function ($q) {
                $q->whereBetween('po_date', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay(),
                ]);
            })
            ->with(['purchaseOrder.supplier', 'purchaseOrder.user', 'item'])
            ->get();

        $row = 2;
        foreach ($details as $detail) {
            $sheet->setCellValue('A' . $row, $detail->purchaseOrder->po_number ?? '-');
            $sheet->setCellValue('B' . $row, $detail->purchaseOrder->supplier->name ?? '-');
            $sheet->setCellValue('C' . $row, $detail->purchaseOrder->user->name ?? '-');
            $sheet->setCellValue('D' . $row, $detail->item->name ?? '-');
            $sheet->setCellValue('E' . $row, $detail->qty);
            $sheet->setCellValue('F' . $row, $detail->item->unit ?? '-');
            $sheet->setCellValue('G' . $row, $detail->price_at_purchase);
            $sheet->setCellValue('H' . $row, $detail->subtotal);
            $sheet->setCellValue('I' . $row, $detail->purchaseOrder->status ?? '-');
            $sheet->setCellValue('J' . $row, $detail->purchaseOrder->po_date ? Carbon::parse($detail->purchaseOrder->po_date)->format('Y-m-d') : '-');
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
                $query = PurchaseOrderDetail::query();

                if (!$this->isProcessed || !$this->startDate || !$this->endDate) {
                    return $query->whereRaw('1 = 0');
                }

                $query->whereHas('purchaseOrder', function ($q) {
                    $q->whereBetween('po_date', [
                        Carbon::parse($this->startDate)->startOfDay(),
                        Carbon::parse($this->endDate)->endOfDay(),
                    ]);
                });

                return $query->with(['purchaseOrder.supplier', 'purchaseOrder.user', 'item']);
            })
            ->columns([
                TextColumn::make('purchaseOrder.po_number')
                    ->label('No. PO')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('purchaseOrder.supplier.name')
                    ->label('Supplier')
                    ->searchable(),
                TextColumn::make('purchaseOrder.user.name')
                    ->label('Pembuat'),
                TextColumn::make('item.name')
                    ->label('Nama Barang')
                    ->searchable(),
                TextColumn::make('qty')
                    ->label('Qty')
                    ->formatStateUsing(fn ($state, $record) => $state . ' ' . ($record->item->unit ?? '')),
                TextColumn::make('price_at_purchase')
                    ->label('Harga Satuan')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('purchaseOrder.status')
                    ->label('Status PO')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft'     => 'gray',
                        'approved'  => 'success',
                        'order'     => 'info',
                        'received'  => 'warning',
                        default     => 'gray',
                    }),
                TextColumn::make('purchaseOrder.po_date')
                    ->label('Tanggal PO')
                    ->date('d/m/Y')
                    ->sortable(),
            ]);
    }
}
