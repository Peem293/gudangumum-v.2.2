# Dokumen Spesifikasi Teknis & Desain Arsitektur WMS

**Proyek:** Warehouse Management System (WMS) / Aplikasi Gudang Berbasis Perusahaan  
**Teknologi:** Laravel 11, Filament v3, Spatie Permission (Filament Shield)  
**Tahun Pengembangan:** 2026  

---

## 1. Rangkuman Eksekutif & Tujuan Sistem
Sistem ini dirancang untuk mengotomatisasi manajemen inventaris gudang dengan kontrol hak akses yang ketat (*Data Governance*), pembatasan visibilitas data antar departemen/unit (*Data Segregation*), pencegahan kebocoran stok (*Race Condition Handling*), serta pencatatan nilai finansial yang akurat menggunakan metode *Snapshotting* (Denormalisasi Harga Historis).

---

## 2. Struktur Hak Akses & Matriks Multi-Role (Filament Shield)

Aplikasi menggunakan paket **Filament Shield** (berbasis Spatie Permission). Setiap pengguna (*User*) wajib terikat pada `department_id` dan `unit_id` tertentu untuk mengunci ruang lingkup data yang dapat mereka lihat.

| Nama Role | Ruang Lingkup Akses (Scope) | Deskripsi Hak Akses & Fitur Utama |
| :--- | :--- | :--- |
| **Administrator** | Global (Seluruh Sistem) | - Akses penuh ke sistem untuk kebutuhan dev & uji coba.<br>- Manajemen Konfigurasi Hak Akses (Role & Permission).<br>- Eksekutor akhir dari dokumen *Stock Adjustment*. |
| **Admin Gudang** | Operasional Gudang | - Manajemen Master Barang, Vendor/Supplier, dan Pajak.<br>- Membuat dokumen *Purchase Order* (PO) ke Supplier.<br>- Mengubah status PO menjadi `received` (menambah stok).<br>- Memproses pengeluaran barang ke Unit (*Request Completed*).<br>- Membuat draf pengajuan *Stock Adjustment Request*. |
| **Staf Unit** | Unit Kerja Sendiri | - Membuat dokumen *Request* permintaan barang ke gudang.<br>- Hanya bisa melihat riwayat transaksi dari unitnya sendiri. |
| **Manager** | Departemen Sendiri | - Melakukan Approval/Rejection atas permintaan barang dari Staf yang berada dalam satu Departemen yang sama.<br>- Melihat statistik & report akumulasi dari seluruh unit di bawah departemennya. |
| **Manager Keuangan**| Keuangan & Akuntansi | - Melakukan Approval/Rejection atas draf pengajuan *Stock Adjustment* yang diajukan oleh Admin Gudang sebelum diteruskan ke Administrator. |
| **Direktur** | Global (Read-Only) | - Melihat dashboard monitoring, grafik tren, statistik pemakaian barang, dan laporan performa logistik di seluruh Departemen & Unit. |

---

## 3. Entity Relationship Diagram (ERD) & Skema Database

### A. Kelompok Master Data & Autentikasi
```sql
-- 1. Tabel Master Departemen
Schema::create('departments', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique(); // Contoh: 'MED', 'FIN', 'HRD'
    $table->string('name');
    $table->timestamps();
});

-- 2. Tabel Master Unit (Anak dari Departemen)
Schema::create('units', function (Blueprint $table) {
    $table->id();
    $table->foreignId('department_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->timestamps();
});

-- 3. Tabel Pengguna (Users)
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('department_id')->nullable()->constrained();
    $table->foreignId('unit_id')->nullable()->constrained();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->rememberToken();
    $table->timestamps();
});

-- 4. Tabel Roles (Spatie / Filament Shield)
Schema::create('roles', function (Blueprint $table) {
    $table->id();
    $table->string('name');       // Contoh: 'Administrator', 'Admin Gudang', 'Staf Unit'
    $table->string('guard_name'); // Default: 'web'
    $table->timestamps();
});

-- 5. Tabel Permissions (Spatie / Filament Shield)
Schema::create('permissions', function (Blueprint $table) {
    $table->id();
    $table->string('name');       // Contoh: 'create_Item', 'view_any_Request'
    $table->string('guard_name'); // Default: 'web'
    $table->timestamps();
});

-- 6. Tabel Jembatan: Hubungan Antara User dan Role (Model Has Roles)
Schema::create('model_has_roles', function (Blueprint $table) {
    $table->unsignedBigInteger('role_id');
    $table->string('model_type'); // Menunjuk ke 'App\Models\User'
    $table->unsignedBigInteger('model_id'); // Menunjuk ke user_id

    $table->primary(['role_id', 'model_id', 'model_type']);
    $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
});

-- 7. Tabel Jembatan: Hubungan Antara Role dan Permission (Role Has Permissions)
Schema::create('role_has_permissions', function (Blueprint $table) {
    $table->unsignedBigInteger('permission_id');
    $table->unsignedBigInteger('role_id');

    $table->primary(['permission_id', 'role_id']);
    $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
    $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
});

-- 8. Tabel Master Pajak
Schema::create('pajaks', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->decimal('ppn', 5, 2); // Contoh: 11.00 atau 12.00
    $table->timestamps();
});

-- 9. Tabel Master Barang (Items)
Schema::create('items', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique(); // Kode Barcode / SKU
    $table->string('name');
    $table->decimal('price', 12, 2); // Harga master saat ini
    $table->integer('stock')->default(0);
    $table->string('unit'); // Contoh: 'Pcs', 'Box', 'Rim'
    $table->timestamps();
});

-- 10. Tabel Master Supplier
Schema::create('suppliers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('phone');
    $table->text('address');
    $table->timestamps();
});

-- 11. Tabel Header Purchase Order
Schema::create('purchase_orders', function (Blueprint $table) {
    $table->id();
    $table->string('po_number')->unique(); // Contoh: PO-2026-0001
    $table->foreignId('supplier_id')->constrained();
    $table->foreignId('user_id')->constrained(); // Admin Gudang pembuat
    $table->foreignId('pajak_id')->constrained();
    $table->decimal('shipping_cost', 12, 2)->default(0.00); // Input manual oleh Admin Gudang
    $table->date('po_date');
    $table->enum('status', ['draft', 'ordered', 'received'])->default('draft');
    $table->decimal('grand_total', 12, 2)->default(0.00);
    $table->timestamps();
});

-- 12. Tabel Detail Purchase Order (Snapshot Harga Beli)
Schema::create('purchase_order_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
    $table->foreignId('item_id')->constrained();
    $table->integer('qty');
    $table->decimal('price_at_purchase', 12, 2); // Harga beli riil dikunci di sini
    $table->decimal('subtotal', 12, 2); // qty * price_at_purchase
    $table->timestamps();
});

-- 13. Tabel Header Request
Schema::create('requests', function (Blueprint $table) {
    $table->id();
    $table->string('request_number')->unique(); // Contoh: REQ-2026-0001
    $table->foreignId('user_id')->constrained(); // Staf peminta
    $table->foreignId('department_id')->constrained();
    $table->foreignId('unit_id')->constrained();
    $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
    $table->decimal('total_amount', 12, 2)->default(0.00); // Diisi saat transaksi 'completed'
    $table->text('notes')->nullable();
    $table->timestamps();
});

-- 14. Tabel Detail Request (Snapshot Harga Pengeluaran)
Schema::create('request_details', function (Blueprint $table) {
    $table->id();
    $table->foreignId('request_id')->constrained()->onDelete('cascade');
    $table->foreignId('item_id')->constrained();
    $table->integer('qty_requested');
    $table->decimal('price_at_transaction', 12, 2)->default(0.00); // Dikunci saat status 'completed'
    $table->decimal('subtotal', 12, 2)->default(0.00); // qty_requested * price_at_transaction
    $table->timestamps();
});

-- 15. Tabel Log Mutasi Stok (Kartu Stok Otomatis)
Schema::create('stock_mutations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('item_id')->constrained()->onDelete('cascade');
    $table->enum('type', ['in', 'out']);
    $table->integer('qty');
    $table->integer('beginning_stock');
    $table->integer('ending_stock');
    $table->string('reference'); // 'Stok Awal', No PO, atau No REQ
    $table->text('notes')->nullable();
    $table->timestamps();
});

-- 16. Tabel Pengajuan Koreksi Stok (Stock Adjustment)
Schema::create('adjustment_requests', function (Blueprint $table) {
    $table->id();
    $table->string('adjustment_number')->unique(); // Contoh: ADJ-2026-0001
    $table->foreignId('item_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained(); // Admin Gudang peminta
    $table->enum('type', ['in', 'out']);
    $table->integer('qty');
    $table->integer('current_stock_at_request'); // Stok sistem saat komplain diajukan
    $table->enum('status', ['pending', 'approved_by_manager', 'rejected', 'executed_by_admin'])->default('pending');
    $table->foreignId('approved_by_id')->nullable()->constrained('users'); // Manager Keuangan
    $table->foreignId('executed_by_id')->nullable()->constrained('users'); // Administrator
    $table->text('reason'); // Alasan (e.g., 'Barang rusak terendam air')
    $table->timestamps();
});

--**Logika Bisnis & Fitur Inti Proteksi Data**--
A. Pencegahan Stok Minus & Race Condition
Sistem menerapkan Pessimistic Locking (lockForUpdate()) dan Database Transaction pada basis data saat transaksi dibuat guna menangani interferensi banyak pengguna yang meminta barang yang sama secara simultan:

Saat Staf mengetik angka kuantitas barang pada form (menggunakan reactive() Filament), sistem langsung mengunci baris data barang di database dan mevalidasi ketersediaannya. Jika stok tidak mencukupi, input langsung di-reset ke angka 0 dan menampilkan notifikasi peringatan.

Saat tombol Submit ditekan, database transaction kembali mengunci data item tersebut sebelum memotong stok gudang untuk mengantisipasi selisih waktu milidetik antar user.

B. Prinsip Penguncian Laporan Historis (Data Snapshotting)
Untuk mencegah rusaknya pembukuan keuangan/logistik akibat perubahan harga master barang di masa mendatang, sistem wajib menyalin harga berjalan ke tabel detail transaksi saat berstatus final:

Pada Purchase Order: Dikunci ke kolom price_at_purchase saat status berubah menjadi received.

Pada Request Unit: Dikunci ke kolom price_at_transaction saat status berubah menjadi completed.

Laporan bulanan dan statistik Direktur akan selalu membaca data snapshot ini, bukan menghitung ulang dari tabel master barang.

C. Alur Terpusat Kartu Stok (Stock Card Automation)
Perubahan kolom stock pada tabel items tidak boleh dilakukan secara bebas di sembarang file. Model Item memiliki fungsi internal tunggal updateStockWithMutation() yang membungkus fungsi increment() / decrement() sekaligus mengisi baris riwayat baru pada tabel stock_mutations.

--**5. Alur Kerja (Workflow) Proses Bisnis Terintegrasi**--
Alur 1: Inisialisasi Sistem (Stok Awal)
Database Seeder / Form Awal ➔ Simpan Master Barang ➔ Picu updateStockWithMutation() ➔ Jenis 'in' ➔ Referensi 'Stok Awal' ➔ Saldo Stok Terisi Bersejarah.

Alur 2: Pengadaan Barang (Purchasing)
Admin Gudang input PO & Ongkir Manual ➔ Status 'ordered' ➔ Barang Datang ➔ Status Diubah 'received' ➔ Sistem Snapshot Harga ke PO Details ➔ Picu updateStockWithMutation() Jenis 'in' dengan referensi No PO ➔ Stok Gudang Bertambah.

Alur 3: Permintaan Barang (Unit Request)
Staf Unit Input Request (Validasi Real-time Anti-Minus) ➔ Status 'pending' ➔ Manager Departemen klik 'Approve' ➔ Status 'approved' ➔ Admin Gudang Menyerahkan Barang ➔ Klik 'Selesaikan & Kirim' ➔ Status 'completed' ➔ Sistem Snapshot Harga ke Request Details ➔ Hitung total_amount ➔ Picu updateStockWithMutation() Jenis 'out' dengan referensi No REQ ➔ Stok Gudang Berkurang.

Alur 4: Penyesuaian Koreksi (Stock Adjustment)
Admin Gudang Temukan Selisih Fisik ➔ Input Adjustment Request ➔ Status 'pending' ➔ Manager Keuangan Klik 'Setujui Pengajuan' ➔ Status 'approved_by_manager' ➔ Administrator Klik 'Eksekusi Penyesuaian' ➔ Status 'executed_by_admin' ➔ Picu updateStockWithMutation() Sesuai Type ('in'/'out') dengan referensi No ADJ ➔ Nilai Stok Tersinkronisasi Fisik & Sistem.

--**6. Urutan Langkah Pembangunan (Roadmap Tambahan)**--
Langkah 1: Jalankan migration database untuk seluruh file skema di atas (php artisan migrate).

Langkah 2: Instalasi Filament Shield (php artisan shield:install) dan konfigurasikan 6 Role utama di database melalui panel admin Filament.

Langkah 3: Gunakan perintah php artisan make:filament-resource NamaModel --simple untuk model master data pendek (Department, Unit, Pajak, Supplier).

Langkah 4: Buat Resource standar untuk Item (Barang), PurchaseOrder, Request, dan AdjustmentRequest.

Langkah 5: Pasang query scoping pada fungsi getEloquentQuery() di masing-masing resource agar filter visibilitas data Staf Unit, Manager, dan Direktur aktif secara otomatis.

Langkah 6: Pasang fungsi bantuan mutasi stok di model Item.php dan buat tombol aksi kustom (Custom Table Actions) untuk mengunci logika approval dan snapshot harga.