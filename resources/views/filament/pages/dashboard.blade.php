<!-- resources/views/filament/pages/dashboard.blade.php -->
<x-filament::page>
    @php
        $summary = $this->getSummaryData();
        $chartData = $this->getChartData();
        $distData = $this->getDistributionData();
        $lowStockItems = $this->getLowStockItems();
        $canAccessLowStock = $this->canAccessLowStock();
        
        $requestCompleted = $summary['requestCompleted'] ?? 0;
        $requestOnProcess = $summary['requestOnProcess'] ?? 0;
        $requestRejected = $summary['requestRejected'] ?? 0;
        $hasPurchasing = $summary['hasPurchasing'] ?? false;
        
        $purchaseCompleted = $summary['purchaseCompleted'] ?? 0;
        $purchaseOnProcess = $summary['purchaseOnProcess'] ?? 0;
        $purchaseDraft = $summary['purchaseDraft'] ?? 0;
    @endphp

    <style>
        /* Custom CSS Dashboard WMS Gudang Umum */
        .wms-dashboard {
            font-family: 'Inter', system-ui, sans-serif;
            color: #1f2937;
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .dark .wms-dashboard {
            color: #f3f4f6;
        }

        /* Header Welcome Card */
        .welcome-banner {
            position: relative;
            background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.3);
            overflow: hidden;
        }
        .welcome-banner h2 {
            font-size: 24px;
            font-weight: 800;
            margin: 0 0 8px 0;
            line-height: 1.2;
        }
        .welcome-banner p {
            font-size: 14px;
            margin: 0;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.5;
        }
        .welcome-date {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 12px 16px;
            margin-top: 16px;
        }
        .welcome-date-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.9);
        }
        .welcome-date-text span {
            display: block;
        }
        .welcome-date-title {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.75);
            text-transform: uppercase;
            font-weight: 700;
        }
        .welcome-date-value {
            font-size: 13px;
            font-weight: 700;
        }
        @media (min-width: 768px) {
            .welcome-banner {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .welcome-banner h2 {
                font-size: 28px;
            }
            .welcome-date {
                margin-top: 0;
            }
        }

        /* Card Grids */
        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 16px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #374151;
        }
        .dark .section-title {
            color: #e5e7eb;
        }
        .section-title-dot {
            width: 6px;
            height: 20px;
            border-radius: 99px;
        }
        .section-title-dot.request { background-color: #f59e0b; }
        .section-title-dot.purchasing { background-color: #3b82f6; }

        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            margin-bottom: 8px;
        }
        @media (min-width: 640px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Individual Stat Cards */
        .stat-card {
            position: relative;
            border-radius: 16px;
            padding: 20px;
            background: white;
            border: 1px solid #f3f4f6;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .dark .stat-card {
            background: #1e293b;
            border-color: #334155;
            box-shadow: none;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .stat-label {
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
        }
        .dark .stat-label {
            color: #9ca3af;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 850;
            margin-top: 6px;
            line-height: 1;
        }
        .stat-icon-wrapper {
            border-radius: 12px;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-desc {
            font-size: 11px;
            color: #9ca3af;
            margin: 10px 0 0 0;
            line-height: 1.4;
        }

        /* Card Variations */
        .req-onprocess {
            border-left: 5px solid #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #ffffff 100%);
        }
        .dark .req-onprocess {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.08) 0%, #1e293b 100%);
        }
        .req-onprocess .stat-icon-wrapper { background: rgba(245, 158, 11, 0.15); color: #d97706; }
        .req-onprocess .stat-value { color: #b45309; }
        .dark .req-onprocess .stat-value { color: #f59e0b; }

        .req-completed {
            border-left: 5px solid #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
        }
        .dark .req-completed {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, #1e293b 100%);
        }
        .req-completed .stat-icon-wrapper { background: rgba(16, 185, 129, 0.15); color: #059669; }
        .req-completed .stat-value { color: #047857; }
        .dark .req-completed .stat-value { color: #10b981; }

        .req-rejected {
            border-left: 5px solid #f43f5e;
            background: linear-gradient(135deg, #fff1f2 0%, #ffffff 100%);
        }
        .dark .req-rejected {
            background: linear-gradient(135deg, rgba(244, 63, 94, 0.08) 0%, #1e293b 100%);
        }
        .req-rejected .stat-icon-wrapper { background: rgba(244, 63, 94, 0.15); color: #e11d48; }
        .req-rejected .stat-value { color: #be123c; }
        .dark .req-rejected .stat-value { color: #f43f5e; }

        .pur-draft {
            border-left: 5px solid #64748b;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }
        .dark .pur-draft {
            background: linear-gradient(135deg, rgba(100, 116, 139, 0.08) 0%, #1e293b 100%);
        }
        .pur-draft .stat-icon-wrapper { background: rgba(100, 116, 139, 0.15); color: #475569; }
        .pur-draft .stat-value { color: #334155; }
        .dark .pur-draft .stat-value { color: #cbd5e1; }

        .pur-onprocess {
            border-left: 5px solid #a855f7;
            background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%);
        }
        .dark .pur-onprocess {
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.08) 0%, #1e293b 100%);
        }
        .pur-onprocess .stat-icon-wrapper { background: rgba(168, 85, 247, 0.15); color: #7c3aed; }
        .pur-onprocess .stat-value { color: #6d28d9; }
        .dark .pur-onprocess .stat-value { color: #c084fc; }

        .pur-completed {
            border-left: 5px solid #3b82f6;
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
        }
        .dark .pur-completed {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.08) 0%, #1e293b 100%);
        }
        .pur-completed .stat-icon-wrapper { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
        .pur-completed .stat-value { color: #1d4ed8; }
        .dark .pur-completed .stat-value { color: #60a5fa; }

        /* Charts Grid Layout */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }
        @media (min-width: 1024px) {
            .charts-grid {
                grid-template-columns: 2fr 1fr;
            }
        }
        .panel-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #f3f4f6;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
        }
        .dark .panel-card {
            background: #1e293b;
            border-color: #334155;
            box-shadow: none;
        }
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .panel-title {
            font-size: 16px;
            font-weight: 750;
            margin: 0;
            color: #111827;
        }
        .dark .panel-title {
            color: #f9fafb;
        }
        .panel-subtitle {
            font-size: 11px;
            color: #9ca3af;
            margin: 4px 0 0 0;
        }
        .chart-legends {
            display: flex;
            gap: 16px;
            font-size: 11px;
            font-weight: 600;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 3px;
        }
        .legend-color.req { background-color: #10b981; }
        .legend-color.pur { background-color: #3b82f6; }

        .chart-wrapper {
            position: relative;
            height: 280px;
            width: 100%;
        }
        .doughnut-wrapper {
            position: relative;
            height: 240px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Low Stock Notification Section */
        .stock-warning-header {
            display: flex;
            flex-direction: column;
            gap: 12px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }
        .dark .stock-warning-header {
            border-bottom-color: #334155;
        }
        @media (min-width: 640px) {
            .stock-warning-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
        }
        .warning-badge {
            background-color: #fef2f2;
            color: #ef4444;
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 700;
            border: 1px solid #fee2e2;
        }
        .dark .warning-badge {
            background-color: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.2);
        }
        .pulse-dot-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ef4444;
            position: relative;
        }
        .pulse-dot::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background-color: #ef4444;
            animation: pulse-ring 1.8s infinite ease-in-out;
            top: 0;
            left: 0;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.5; }
            50% { transform: scale(1.8); opacity: 0; }
            100% { transform: scale(0.95); opacity: 0; }
        }

        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
            text-align: left;
        }
        .custom-table th {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            color: #9ca3af;
            padding: 12px 16px;
            border-bottom: 1px solid #f3f4f6;
        }
        .dark .custom-table th {
            border-bottom-color: #334155;
        }
        .custom-table td {
            padding: 12px 16px;
            border-bottom: 1px solid #f9fafb;
            vertical-align: middle;
        }
        .dark .custom-table td {
            border-bottom-color: #1e293b;
        }
        .custom-table tr:hover td {
            background-color: #f9fafb;
        }
        .dark .custom-table tr:hover td {
            background-color: #1e293b;
        }
        .item-sku {
            font-family: monospace;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }
        .item-name {
            font-weight: 700;
            color: #1f2937;
        }
        .dark .item-name {
            color: white;
        }

        /* Stock progress indicators */
        .progress-container {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
        }
        .progress-bar-bg {
            flex-grow: 1;
            background-color: #e2e8f0;
            height: 6px;
            border-radius: 99px;
            overflow: hidden;
            max-width: 160px;
        }
        .dark .progress-bar-bg {
            background-color: #334155;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 99px;
        }
        .progress-bar-fill.critical { background-color: #ef4444; }
        .progress-bar-fill.warning { background-color: #f59e0b; }

        .status-badge {
            padding: 2px 8px;
            border-radius: 99px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
        }
        .status-badge.critical {
            background-color: #fef2f2;
            color: #dc2626;
        }
        .dark .status-badge.critical {
            background-color: rgba(220, 38, 38, 0.15);
            color: #f87171;
        }
        .status-badge.warning {
            background-color: #fffbeb;
            color: #d97706;
        }
        .dark .status-badge.warning {
            background-color: rgba(217, 119, 6, 0.15);
            color: #fbbf24;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background-color: #f59e0b;
            color: white;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 0.2s;
            border: none;
            cursor: pointer;
        }
        .action-btn:hover {
            background-color: #d97706;
            color: white;
        }
    </style>

    <div class="wms-dashboard">
        <!-- HEADER WELCOME CARD -->
        <div class="welcome-banner">
            <div>
                <h2>Selamat Datang, {{ auth()->user()->name }}!</h2>
                <p>
                    @if(auth()->user()->hasRole('administrator'))
                        Akses Administrator - Kelola penuh seluruh aktivitas pergudangan dan keuangan.
                    @elseif(auth()->user()->hasRole('admin_gudang'))
                        Akses Admin Gudang - Monitor stok, proses PO, dan selesaikan request unit.
                    @elseif(auth()->user()->hasRole('staf_unit'))
                        Akses Staf Unit - Ajukan permintaan barang dan monitor status pengiriman unit Anda.
                    @elseif(auth()->user()->hasRole('manager') && auth()->user()->isPurchasingManager())
                        Akses Manager Penunjang Umum - Setujui request unit dan kelola pengadaan barang (Purchasing).
                    @elseif(auth()->user()->hasRole('manager'))
                        Akses Manager - Setujui request dari unit di bawah departemen Anda.
                    @elseif(auth()->user()->hasRole('manager_keuangan'))
                        Akses Manager Keuangan - Evaluasi pengajuan adjustment dan monitor finansial pergudangan.
                    @elseif(auth()->user()->hasRole('direktur'))
                        Akses Direktur - Laporan performa logistik dan grafik pergudangan global.
                    @else
                        Akses Pengguna - Gudang Umum & Logistik.
                    @endif
                </p>
            </div>
            
            <div class="welcome-date">
                <div class="welcome-date-icon">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="welcome-date-text">
                    <span class="welcome-date-title">Tanggal Hari Ini</span>
                    <span class="welcome-date-value">{{ now()->translatedFormat('d F Y') }}</span>
                </div>
            </div>
        </div>

        <!-- TRANSACTION SUMMARY SECTION -->
        <div class="summaries-wrapper">
            <!-- 1. REQUEST TRANS -->
            <div style="margin-bottom: 24px;">
                <h3 class="section-title">
                    <span class="section-title-dot request"></span>
                    <span>Ringkasan Transaksi Permintaan (Request)</span>
                </h3>
                <div class="stats-grid">
                    <!-- Request On Process -->
                    <div class="stat-card req-onprocess">
                        <div class="stat-header">
                            <div>
                                <span class="stat-label">Masih Proses</span>
                                <h4 class="stat-value">{{ $requestOnProcess }}</h4>
                            </div>
                            <div class="stat-icon-wrapper">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="stat-desc">Permintaan pending/approved yang sedang diproses oleh gudang</p>
                    </div>

                    <!-- Request Completed -->
                    <div class="stat-card req-completed">
                        <div class="stat-header">
                            <div>
                                <span class="stat-label">Selesai (Completed)</span>
                                <h4 class="stat-value">{{ $requestCompleted }}</h4>
                            </div>
                            <div class="stat-icon-wrapper">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="stat-desc">Barang telah diserahterimakan ke unit dan nilai finansial dikunci</p>
                    </div>

                    <!-- Request Rejected -->
                    <div class="stat-card req-rejected">
                        <div class="stat-header">
                            <div>
                                <span class="stat-label">Ditolak (Rejected)</span>
                                <h4 class="stat-value">{{ $requestRejected }}</h4>
                            </div>
                            <div class="stat-icon-wrapper">
                                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="stat-desc">Permintaan barang yang ditolak oleh Pihak Penyunting/Manager</p>
                    </div>
                </div>
            </div>

            <!-- 2. PURCHASING TRANS -->
            @if($hasPurchasing)
                <div style="margin-bottom: 8px;">
                    <h3 class="section-title">
                        <span class="section-title-dot purchasing"></span>
                        <span>Ringkasan Transaksi Pembelian (Purchasing / PO)</span>
                    </h3>
                    <div class="stats-grid">
                        <!-- PO Draft -->
                        <div class="stat-card pur-draft">
                            <div class="stat-header">
                                <div>
                                    <span class="stat-label">Draf Pengajuan</span>
                                    <h4 class="stat-value">{{ $purchaseDraft }}</h4>
                                </div>
                                <div class="stat-icon-wrapper">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-desc">Purchase Order baru yang disimpan dan belum dikonfirmasi</p>
                        </div>

                        <!-- PO Process -->
                        <div class="stat-card pur-onprocess">
                            <div class="stat-header">
                                <div>
                                    <span class="stat-label">Sedang Dipesan</span>
                                    <h4 class="stat-value">{{ $purchaseOnProcess }}</h4>
                                </div>
                                <div class="stat-icon-wrapper">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-desc">PO yang sudah diajukan ke supplier, menunggu kedatangan barang</p>
                        </div>

                        <!-- PO Completed -->
                        <div class="stat-card pur-completed">
                            <div class="stat-header">
                                <div>
                                    <span class="stat-label">Barang Diterima</span>
                                    <h4 class="stat-value">{{ $purchaseCompleted }}</h4>
                                </div>
                                <div class="stat-icon-wrapper">
                                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                            </div>
                            <p class="stat-desc">Transaksi selesai, stok fisik digudang bertambah secara otomatis</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- CHARTS SECTION -->
        <div class="charts-grid">
            <!-- 1. Line Chart -->
            <div class="panel-card">
                <div class="panel-header">
                    <div>
                        <h3 class="panel-title">Tren Transaksi Selesai</h3>
                        <p class="panel-subtitle">Analisis perbandingan request keluar vs purchasing masuk 6 bulan terakhir</p>
                    </div>
                    <div class="chart-legends">
                        <div class="legend-item">
                            <span class="legend-color req"></span>
                            <span>Request</span>
                        </div>
                        @if($hasPurchasing)
                            <div class="legend-item">
                                <span class="legend-color pur"></span>
                                <span>Purchasing</span>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>

            <!-- 2. Doughnut Chart -->
            <div class="panel-card">
                <div>
                    <h3 class="panel-title">{{ $distData['title'] }}</h3>
                    <p class="panel-subtitle">Visualisasi porsi sebaran logistik saat ini</p>
                </div>
                <div class="doughnut-wrapper">
                    @if(empty($distData['data']))
                        <div style="text-align: center; color: #9ca3af; padding: 24px 0;">
                            <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 12px auto; opacity: 0.6;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2"></path>
                            </svg>
                            <span style="font-size: 13px; font-weight: 500;">Belum ada data terkunci historis</span>
                        </div>
                    @else
                        <canvas id="distChart"></canvas>
                    @endif
                </div>
            </div>
        </div>

        <!-- LOW STOCK SECTION -->
        @if($canAccessLowStock)
            <div class="panel-card">
                <div class="stock-warning-header">
                    <div class="pulse-dot-container">
                        <span class="pulse-dot"></span>
                        <h3 class="panel-title" style="margin: 0;">Laporan Stok Barang Kritis & Hampir Habis</h3>
                    </div>
                    <span class="warning-badge">Ambang Batas Minimum: &le; 10 Item</span>
                </div>

                @if($lowStockItems->isEmpty())
                    <div style="text-align: center; padding: 32px 0;">
                        <svg width="48" height="48" fill="none" stroke="#10b981" viewBox="0 0 24 24" style="margin: 0 auto 12px auto;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h4 style="font-size: 14px; font-weight: 700; margin: 0 0 4px 0; color: #111827;">Seluruh Stok Aman</h4>
                        <p style="font-size: 12px; color: #9ca3af; margin: 0;">Tidak ada item master barang yang menyentuh batas kritis.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Kode SKU</th>
                                    <th>Nama Barang</th>
                                    <th>Stok</th>
                                    <th style="width: 200px;">Status Tingkat Kritis</th>
                                    <th>Satuan</th>
                                    <th style="text-align: right; width: 120px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockItems as $index => $item)
                                    <tr>
                                        <td style="font-weight: 600;">{{ $index + 1 }}</td>
                                        <td class="item-sku">{{ $item->code }}</td>
                                        <td class="item-name">{{ $item->name }}</td>
                                        <td>
                                            <span class="status-badge {{ $item->stock <= 3 ? 'critical' : 'warning' }}">
                                                {{ $item->stock }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress-container">
                                                <div class="progress-bar-bg">
                                                    <div class="progress-bar-fill {{ $item->stock <= 3 ? 'critical' : 'warning' }}" style="width: {{ max(10, min(100, $item->stock * 10)) }}%"></div>
                                                </div>
                                                <span style="font-size: 11px; font-weight: 700; color: {{ $item->stock <= 3 ? '#dc2626' : '#d97706' }}">
                                                    {{ $item->stock <= 3 ? 'Kritis' : 'Menipis' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td>{{ $item->unit }}</td>
                                        <td style="text-align: right;">
                                            <a href="{{ url('admin/purchase-orders-draft/create?prefill_item='.$item->id) }}" class="action-btn">
                                                <span>Restok PO</span>
                                                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke-width: 3px;">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- SCRIPT CHART INITIALIZATION -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chartData = @json($chartData);
            const distData = @json($distData);
            
            // 1. Line Chart - Tren Transaksi Bulanan
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            const datasets = [];
            
            datasets.push({
                label: 'Permintaan Selesai (Request)',
                data: chartData.requestTrend,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.04)',
                borderWidth: 3,
                tension: 0.35,
                fill: true,
                pointBackgroundColor: '#10b981',
                pointHoverRadius: 6,
                pointRadius: 4
            });
            
            if (chartData.hasPurchasing) {
                datasets.push({
                    label: 'Pembelian Selesai (Purchasing)',
                    data: chartData.purchaseTrend,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.04)',
                    borderWidth: 3,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointHoverRadius: 6,
                    pointRadius: 4
                });
            }
            
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            padding: 10,
                            cornerRadius: 8,
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            titleFont: { size: 12, weight: 'bold' },
                            bodyFont: { size: 11 }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                color: '#9ca3af',
                                font: { family: 'Inter, sans-serif', size: 11 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(156, 163, 175, 0.08)' },
                            ticks: {
                                color: '#9ca3af',
                                font: { family: 'Inter, sans-serif', size: 11 },
                                stepSize: 1
                            }
                        }
                    }
                }
            });

            // 2. Doughnut Chart - Distribusi
            if (distData && distData.data && distData.data.length > 0) {
                const distCtx = document.getElementById('distChart').getContext('2d');
                const premiumColors = [
                    '#f59e0b', // Amber-500
                    '#3b82f6', // Blue-500
                    '#10b981', // Emerald-500
                    '#a855f7', // Purple-500
                    '#f43f5e', // Rose-500
                    '#06b6d4'  // Cyan-500
                ];
                
                new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: distData.labels,
                        datasets: [{
                            data: distData.data,
                            backgroundColor: premiumColors.slice(0, distData.data.length),
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    boxWidth: 10,
                                    padding: 12,
                                    color: '#6b7280',
                                    font: { family: 'Inter, sans-serif', size: 10.5 }
                                }
                            },
                            tooltip: { padding: 8, cornerRadius: 6 }
                        }
                    }
                });
            }
        });
    </script>
</x-filament::page>
