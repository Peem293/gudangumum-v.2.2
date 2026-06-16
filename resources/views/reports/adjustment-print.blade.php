<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Koreksi Stok (Adjustment)</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.5;
            background: #fff;
        }
        .container {
            max-width: 950px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 3px double #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 5px 0;
            font-size: 22px;
            font-weight: 700;
            color: #2563eb;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            color: #4b5563;
            font-size: 13px;
        }
        .report-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 12px;
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
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 600;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            font-size: 11px;
            text-transform: uppercase;
        }
        td {
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            color: #4b5563;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 1px 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 4px;
            background-color: #f3f4f6;
            color: #374151;
        }
        .status-pending { background-color: #e5e7eb; color: #4b5563; }
        .status-approved_by_manager { background-color: #dbeafe; color: #1e40af; }
        .status-executed_by_admin { background-color: #d1fae5; color: #065f46; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }

        .type-badge {
            display: inline-block;
            padding: 1px 5px;
            font-size: 10px;
            font-weight: 600;
            border-radius: 3px;
        }
        .type-in { background-color: #d1fae5; color: #065f46; }
        .type-out { background-color: #fee2e2; color: #dc2626; }

        .summary-box {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        .summary-table {
            width: 250px;
            border: none;
            margin-bottom: 0;
        }
        .summary-table td {
            border: none;
            padding: 4px 0;
            font-size: 13px;
        }
        .summary-table tr.total-row td {
            font-weight: 700;
            color: #2563eb;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
        }



        @media print {
            @page {
                margin: 0 1.5cm;
                size: auto landscape;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 100%;
                padding: 1.5cm 0 36px 0;
            }
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
            <p>Bagian Logistik & Gudang Umum</p>
            <p><strong>Laporan Koreksi & Penyesuaian Stok (Stock Adjustment)</strong></p>
        </div>

        <!-- Report Meta Info -->
        <div class="report-info">
            <div>Periode: <span>{{ $startDate->format('d/m/Y') }} s/d {{ $endDate->format('d/m/Y') }}</span></div>
            <div>Dicetak oleh: <span>{{ auth()->user()->name }}</span></div>
        </div>

        <!-- Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>No. Koreksi</th>
                    <th>Nama Barang</th>
                    <th class="text-center">Jenis</th>
                    <th class="text-center">Qty Koreksi</th>
                    <th>Pembuat</th>
                    <th class="text-center">Status</th>
                    <th>Alasan</th>
                    <th class="text-center">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $index => $adj)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-weight: 600;">{{ $adj->adjustment_number }}</td>
                    <td style="font-weight: 500; color: #111827;">{{ $adj->item->name }}</td>
                    <td class="text-center">
                        <span class="type-badge {{ $adj->type === 'in' ? 'type-in' : 'type-out' }}">
                            {{ $adj->type === 'in' ? 'Masuk (In)' : 'Keluar (Out)' }}
                        </span>
                    </td>
                    <td class="text-center" style="font-weight: 600;">{{ $adj->qty }} {{ $adj->item->unit }}</td>
                    <td>{{ $adj->user->name }}</td>
                    <td class="text-center">
                        <span class="status-badge status-{{ $adj->status }}">
                            {{ str_replace('_', ' ', $adj->status) }}
                        </span>
                    </td>
                    <td>{{ $adj->reason }}</td>
                    <td class="text-center">{{ $adj->created_at->format('d/m/Y H:i') }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center" style="padding: 20px; color: #9ca3af;">Tidak ada data laporan pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Summary -->
        @if($data->count() > 0)
        <div class="summary-box">
            <table class="summary-table">
                <tr class="total-row">
                    <td>Total Transaksi Koreksi:</td>
                    <td class="text-right">{{ $data->count() }} Kali</td>
                </tr>
                <tr>
                    <td>Total Barang Bertambah (In):</td>
                    <td class="text-right">{{ $data->where('type', 'in')->sum('qty') }} Pcs</td>
                </tr>
                <tr>
                    <td>Total Barang Berkurang (Out):</td>
                    <td class="text-right">{{ $data->where('type', 'out')->sum('qty') }} Pcs</td>
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
