<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pembelian (PO)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 20px;
            font-size: 11px;
            line-height: 1.5;
            background: #fff;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 3px double #f59e0b;
            padding-bottom: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 20px;
            font-weight: 700;
            color: #d97706;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            color: #4b5563;
            font-size: 12px;
        }
        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        .report-info span {
            color: #374151;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background-color: #fffbeb;
            color: #92400e;
            font-weight: 700;
            text-align: left;
            padding: 7px 8px;
            border: 1px solid #fde68a;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            color: #374151;
            vertical-align: top;
        }
        tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }

        .status-badge {
            display: inline-block;
            padding: 1px 6px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
        }
        .status-draft    { background-color: #e5e7eb; color: #4b5563; }
        .status-approved { background-color: #d1fae5; color: #065f46; }
        .status-order    { background-color: #dbeafe; color: #1e40af; }
        .status-received { background-color: #fef3c7; color: #d97706; }

        .summary-box {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        .summary-table {
            width: 300px;
            border: none;
            margin-bottom: 0;
        }
        .summary-table td {
            border: none;
            padding: 4px 0;
            font-size: 12px;
            background: transparent;
        }
        .summary-table tr.total-row td {
            font-weight: 700;
            color: #d97706;
            border-top: 2px solid #fde68a;
            padding-top: 8px;
        }



        @media print {
            @page { margin: 0 1.5cm; size: landscape; }
            body { margin: 0; padding: 0; }
            .container { max-width: 100%; padding: 1.5cm 0 36px 0; }
            .print-footer {
                display: flex !important;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 6px 1.5cm;
                background: #fff;
                font-size: 10px;
                color: #374151;
                justify-content: space-between;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>Gudang Umum Co.</h1>
            <p>Bagian Logistik &amp; Gudang Umum</p>
            <p><strong>Laporan Pembelian Barang (Purchase Order)</strong></p>
        </div>

        <!-- Report Meta Info -->
        <div class="report-info">
            <div>Periode: <span>{{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}</span></div>
            <div>Dicetak oleh: <span>{{ auth()->user()->name }}</span></div>
            <div>Tanggal Cetak: <span>{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</span></div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 35px;" class="text-center">No</th>
                    <th>No. PO</th>
                    <th>Supplier</th>
                    <th>Pembuat</th>
                    <th>Nama Barang</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Harga Satuan</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-center">Status PO</th>
                    <th class="text-center">Tgl PO</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-bold">{{ $detail->purchaseOrder->po_number ?? '-' }}</td>
                    <td>{{ $detail->purchaseOrder->supplier->name ?? '-' }}</td>
                    <td>{{ $detail->purchaseOrder->user->name ?? '-' }}</td>
                    <td>{{ $detail->item->name ?? '-' }}</td>
                    <td class="text-center">{{ $detail->qty }} {{ $detail->item->unit ?? '' }}</td>
                    <td class="text-right">Rp {{ number_format($detail->price_at_purchase ?? 0, 0, ',', '.') }}</td>
                    <td class="text-right font-bold">Rp {{ number_format($detail->subtotal ?? 0, 0, ',', '.') }}</td>
                    <td class="text-center">
                        <span class="status-badge status-{{ $detail->purchaseOrder->status ?? 'draft' }}">
                            {{ $detail->purchaseOrder->status ?? '-' }}
                        </span>
                    </td>
                    <td class="text-center">{{ \Carbon\Carbon::parse($detail->purchaseOrder->po_date)->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #9ca3af;">
                        Tidak ada data laporan pada periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        @if($data->count() > 0)
        <div class="summary-box">
            <table class="summary-table">
                <tr>
                    <td>Total Baris Item:</td>
                    <td class="text-right"><strong>{{ $data->count() }} item</strong></td>
                </tr>
                <tr>
                    <td>Total Dokumen PO:</td>
                    <td class="text-right"><strong>{{ $data->pluck('purchase_order_id')->unique()->count() }} dokumen</strong></td>
                </tr>
                <tr class="total-row">
                    <td>Total Nilai Pembelian (Subtotal):</td>
                    <td class="text-right">Rp {{ number_format($data->sum('subtotal'), 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>
        @endif


    </div>

    <!-- Custom Print Footer -->
    <div class="print-footer" style="display:none;">
        <div>Aplikasi GudangUmum-V.2.2</div>
        <div>Dicetak oleh: {{ auth()->user()->name }} {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
