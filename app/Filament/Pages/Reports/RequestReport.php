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
use App\Models\Request as UnitRequest;
use App\Models\RequestDetail;
use BackedEnum;
use UnitEnum;

class RequestReport extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string|UnitEnum|null $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Permintaan Unit';
    protected static ?string $title = 'Laporan Permintaan Unit';
    protected string $view = 'filament.pages.reports.request-report';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];
    public bool $isProcessed = false;
    public ?string $startDate = null;
    public ?string $endDate = null;

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
        return route('reports.requests.print', [
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
        $filename = "Laporan_Permintaan_Unit_{$startDateFormatted}_s_d_{$endDateFormatted}.xlsx";

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set Headers
        $headers = [
            'No. Request', 
            'Staf Peminta', 
            'Departemen', 
            'Unit Kerja', 
            'Nama Barang',
            'Jumlah',
            'Harga Satuan (Rp)',
            'Subtotal (Rp)',
            'Status', 
            'Tanggal Pengajuan'
        ];

        foreach ($headers as $colIndex => $headerText) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex + 1);
            $sheet->setCellValue($colLetter . '1', $headerText);
            $sheet->getStyle($colLetter . '1')->getFont()->setBold(true);
        }

        $query = RequestDetail::query()
            ->whereHas('request', function ($q) {
                $q->whereBetween('created_at', [
                    Carbon::parse($this->startDate)->startOfDay(),
                    Carbon::parse($this->endDate)->endOfDay()
                ]);

                $user = Auth::user();
                if ($user->hasRole('staf_unit')) {
                    $q->where('unit_id', $user->unit_id);
                } elseif ($user->hasRole('manager')) {
                    if (!$user->isPurchasingManager() && !$user->isFinanceManager()) {
                        $q->where('department_id', $user->department_id);
                    }
                }
            });

        $details = $query->with(['request.user', 'request.department', 'request.unit', 'item'])->get();

        $row = 2;
        foreach ($details as $detail) {
            $sheet->setCellValue('A' . $row, $detail->request->request_number ?? '-');
            $sheet->setCellValue('B' . $row, $detail->request->user->name ?? '-');
            $sheet->setCellValue('C' . $row, $detail->request->department->name ?? '-');
            $sheet->setCellValue('D' . $row, $detail->request->unit->name ?? '-');
            $sheet->setCellValue('E' . $row, $detail->item->name ?? '-');
            $sheet->setCellValue('F' . $row, $detail->qty_requested . ' ' . ($detail->item->unit ?? ''));
            $sheet->setCellValue('G' . $row, $detail->price_at_transaction);
            $sheet->setCellValue('H' . $row, $detail->subtotal);
            $sheet->setCellValue('I' . $row, $detail->request->status ?? '-');
            $sheet->setCellValue('J' . $row, $detail->request->created_at ? $detail->request->created_at->format('Y-m-d H:i:s') : '-');
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
                $query = RequestDetail::query();

                if (!$this->isProcessed || !$this->startDate || !$this->endDate) {
                    return $query->whereRaw('1 = 0');
                }

                $query->whereHas('request', function ($q) {
                    $q->whereBetween('created_at', [
                        Carbon::parse($this->startDate)->startOfDay(),
                        Carbon::parse($this->endDate)->endOfDay()
                    ]);

                    $user = Auth::user();
                    if ($user->hasRole('staf_unit')) {
                        $q->where('unit_id', $user->unit_id);
                    } elseif ($user->hasRole('manager')) {
                        if (!$user->isPurchasingManager() && !$user->isFinanceManager()) {
                            $q->where('department_id', $user->department_id);
                        }
                    }
                });

                return $query->with(['request.user', 'request.department', 'request.unit', 'item']);
            })
            ->columns([
                TextColumn::make('request.request_number')
                    ->label('No. Request')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('request.user.name')
                    ->label('Staf Peminta'),
                TextColumn::make('request.department.name')
                    ->label('Departemen'),
                TextColumn::make('request.unit.name')
                    ->label('Unit Kerja'),
                TextColumn::make('item.name')
                    ->label('Nama Barang')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('qty_requested')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state, $record) => $state . ' ' . $record->item->unit),
                TextColumn::make('price_at_transaction')
                    ->label('Harga Satuan')
                    ->money('IDR', locale: 'id'),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR', locale: 'id')
                    ->sortable(),
                TextColumn::make('request.status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'pending' => 'gray',
                        'approved' => 'warning',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('request.created_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ]);
    }
}
