<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit Request - {{ $request->request_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.5;
            background: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #ef4444; /* Red theme for internal requests */
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .company-details h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 700;
            color: #dc2626;
        }
        .company-details p {
            margin: 2px 0;
            color: #4b5563;
        }
        .document-title {
            text-align: right;
        }
        .document-title h2 {
            margin: 0 0 5px 0;
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .document-title p {
            margin: 2px 0;
            font-weight: 500;
            color: #4b5563;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .info-section h3 {
            margin: 0 0 8px 0;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #9ca3af;
            letter-spacing: 0.5px;
        }
        .info-card {
            background-color: transparent;
            border: none;
            padding: 0;
            border-radius: 0;
            min-height: auto;
        }
        .info-card p {
            margin: 4px 0;
            color: #374151;
        }
        .info-card .name {
            font-weight: 600;
            color: #111827;
            font-size: 14px;
            margin-bottom: 6px;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 9999px;
            background-color: #f3f4f6;
            color: #374151;
        }
        .status-approved {
            background-color: #fef3c7;
            color: #d97706;
        }
        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
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
            padding: 10px 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            color: #4b5563;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        .totals-table {
            width: 320px;
            margin-bottom: 0;
        }
        .totals-table td {
            padding: 6px 12px;
            border-bottom: none;
        }
        .totals-table tr.border-t td {
            border-top: 1px solid #e5e7eb;
        }
        .totals-table tr.grand-total-row td {
            font-size: 14px;
            font-weight: 600;
            color: #dc2626;
            padding-top: 10px;
        }
        .signatures-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 100px;
            margin-top: 50px;
            text-align: center;
        }
        .signature-box {
            padding-top: 60px;
            border-top: 1px solid #d1d5db;
            color: #4b5563;
        }
        .signature-box .role {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 4px;
        }
        
        .print-only {
            display: none !important;
        }
        
        /* Print styles optimization */
        @media print {
            @page {
                margin: 0; /* Menghilangkan URL, Tanggal, dan Page Number bawaan browser */
            }
            body {
                margin: 1.6cm; /* Menggantikan margin kertas agar konten tidak terpotong */
                padding: 0;
                background: none;
            }
            .container {
                border: none;
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .print-only {
                display: flex !important;
                position: fixed;
                bottom: 1.6cm;
                left: 1.6cm;
                right: 1.6cm;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-details">
                <h1>Gudang Umum Co.</h1>
                <p>Bagian Logistik & Gudang Umum</p>
                <p>Internal Unit Request Document</p>
            </div>
            <div class="document-title">
                <h2>Permintaan Barang</h2>
                <p>No: {{ $request->request_number }}</p>
                <p>Tanggal: {{ \Carbon\Carbon::parse($request->created_at)->format('d F Y H:i') }}</p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-section">
                <h3>Asal Permintaan</h3>
                <div class="info-card">
                    <p class="name">{{ $request->user->name }}</p>
                    <p>Departemen: {{ $request->department->name }}</p>
                    <p>Unit Kerja: {{ $request->unit->name }}</p>
                </div>
            </div>
            <div class="info-section">
                <h3>Status & Catatan</h3>
                <div class="info-card">
                    <p>Status: 
                        <span class="status-badge {{ $request->status === 'approved' ? 'status-approved' : ($request->status === 'completed' ? 'status-completed' : '') }}">
                            {{ $request->status }}
                        </span>
                    </p>
                    <p style="margin-top: 8px;"><strong>Keterangan:</strong></p>
                    <p style="font-style: italic; color: #6b7280;">{{ $request->notes ?: '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th style="width: 150px;">Nama Barang</th>
                    <th style="width: 80px;" class="text-center">Jumlah</th>
                    <th style="width: 40px;" class="text-center">Satuan</th>
                    <th style="width: 130px;" class="text-right">Harga Satuan</th>
                    <th style="width: 130px;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($request->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-weight: 500; color: #111827;">{{ $detail->item->name }}</td>
                    <td class="text-center">{{ $detail->qty_requested }}</td>
                    <td class="text-center">{{ $detail->item->unit }}</td>
                    <td class="text-right">Rp {{ number_format($detail->price_at_transaction, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <table class="totals-table">
                <tr class="grand-total-row">
                    <td>Total Pengeluaran:</td>
                    <td class="text-right">Rp {{ number_format($request->total_amount, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <!-- Signatures Section -->
        <div class="signatures-section">
            <div class="signature-box">
                <div style="margin: 10px 0;">
                    @if($request->creator_signature)
                        <div style="display: inline-block; padding: 5px; border: 1px solid #ccc; background-color: white;">
                            {!! QrCode::size(100)->margin(1)->generate(route('document.verify', ['id' => $request->id, 'sig' => $request->creator_signature, 'type' => 'creator'])) !!}
                        </div>
                    @else
                        <div style="margin-bottom: 65px; color: #666; font-size: 13px; padding-top: 15px;">
                            ( Belum ada TTD )
                        </div>
                    @endif
                </div>
                <strong>{{ $request->user->name }}</strong>
                <div class="role">Staf Peminta (Yang Mengajukan)</div>
            </div>
            <div class="signature-box">
                <div style="margin: 10px 0;">
                @if($request->signature)
                    <div style="display: inline-block; padding: 5px; border: 1px solid #ccc; background-color: white;">
                        {!! QrCode::size(100)->margin(1)->generate(route('document.verify', ['id' => $request->id, 'sig' => $request->signature])) !!}
                    </div>
                @else
                    <div style="margin-bottom: 60px; color: #dc2626; font-weight: bold; font-size: 14px;">
                        BELUM DI-APPROVE
                    </div>
                @endif
                </div>

                <strong>{{ $request->approvedBy->name ?? '( .................................................... )' }}</strong>
                <div class="role" style="font-size: 12px; color: gray;">Manager {{ $request->department->name }}</div>
            </div>
        </div>

        <!-- Footer Info -->
        <div style="margin-top: 50px; border-top: 1px dashed #d1d5db; padding-top: 10px; font-size: 11px; color: #9ca3af; display: flex; justify-content: space-between;" class="print-only">
            <div>Aplikasi Gudang Umum</div>
            <div>Dicetak oleh: {{ auth()->user()->name }} pada {{ \Carbon\Carbon::now()->format('d F Y H:i') }}</div>
        </div>
    </div>

    <!-- Automatically open print window when loading -->
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>
