<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $purchaseOrder->po_number }}</title>
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
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .company-details h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 700;
            color: #2563eb;
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
            width: 300px;
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
            font-size: 15px;
            font-weight: 700;
            color: #2563eb;
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
        
        /* Print styles optimization */
        @media print {
            body {
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
        }
        
        /* Print Button floating styles */
        .print-btn-container {
            max-width: 800px;
            margin: 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            background-color: #2563eb;
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.15s ease;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }
        .btn:hover {
            background-color: #1d4ed8;
        }
        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
        }
        .btn-secondary:hover {
            background-color: #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="company-details">
                <h1>RS. HERMINA SOLO</h1>
                <p>Jl. Kol. Sutarto No. 16, Jebres, Surakarta</p>
                <p>Telp: (0271) 638989 | Email: logistik@gudang.com</p>
            </div>
            <div class="document-title">
                <h2>Purchase Order</h2>
                <p>No: {{ $purchaseOrder->po_number }}</p>
                <p>Tanggal: {{ \Carbon\Carbon::parse($purchaseOrder->po_date)->format('d F Y') }}</p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="info-grid">
            <div class="info-section">
                <h3>Supplier / Vendor</h3>
                <div class="info-card">
                    <p class="name">{{ $purchaseOrder->supplier->name }}</p>
                    <p>{{ $purchaseOrder->supplier->address }}</p>
                    <p>Telp: {{ $purchaseOrder->supplier->phone }}</p>
                </div>
            </div>
            <div class="info-section">
                <h3>Tujuan Pengiriman</h3>
                <div class="info-card">
                    <p class="name">RS. Hermina Solo</p>
                    <p>Jl. Kol. Sutarto No. 16, Jebres, Surakarta</p>
                    <p>Penerima: Admin Gudang</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Nama Barang</th>
                    <th style="width: 80px;" class="text-center">Satuan</th>
                    <th style="width: 60px;" class="text-center">Qty</th>
                    <th style="width: 130px;" class="text-right">Harga Bersih</th>
                    <th style="width: 130px;" class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchaseOrder->details as $index => $detail)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-weight: 500; color: #111827;">{{ $detail->item->name }}</td>
                    <td class="text-center">{{ $detail->item->unit }}</td>
                    <td class="text-center">{{ $detail->qty }}</td>
                    <td class="text-right">Rp {{ number_format($detail->price_at_purchase, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Total Subtotal:</td>
                    <td class="text-right">Rp {{ number_format($totalSubtotal, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>PPN ({{ number_format($purchaseOrder->ppn_percentage, 0) }}%):</td>
                    <td class="text-right">Rp {{ number_format($taxAmount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Ongkos Kirim:</td>
                    <td class="text-right">Rp {{ number_format($purchaseOrder->shipping_cost, 0, ',', '.') }}</td>
                </tr>
                <tr class="border-t grand-total-row">
                    <td>Grand Total:</td>
                    <td class="text-right">Rp {{ number_format($purchaseOrder->grand_total, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <!-- Signatures Section -->
        <div class="signatures-section">
            <div class="signature-box">
                <strong>{{ $purchaseOrder->user->name }}</strong>
                <div class="role">Admin Gudang (Pembuat)</div>
            </div>
            <div class="signature-box">
                <strong>{{ $purchaseOrder->approvedBy->name ?? '( .................................................... )' }}</strong>
                <div class="role">Pihak Yang Menyetujui / Approval</div>
            </div>
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
