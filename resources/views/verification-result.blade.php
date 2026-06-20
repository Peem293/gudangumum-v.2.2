<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dokumen Digital</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="bg-white p-6 rounded-2xl shadow-lg max-w-md w-full text-center">
        
        @if($status === 'valid')
            <div class="text-green-500 text-6xl mb-4">✓</div>
            <h1 class="text-2xl font-bold text-green-600 mb-2">{{ $message }}</h1>
            
            <div class="border-t border-gray-200 my-4 pt-4 text-left text-sm text-gray-700 space-y-3">
                <p><strong>Jenis Dokumen:</strong> 
                    <span class="font-bold text-gray-900">
                        {{ $docType === 'po' ? 'Purchase Order (PO)' : 'Permintaan Unit' }}
                    </span>
                </p>

                <p><strong>Nomor Dokumen:</strong> 
                    {{-- Menggunakan null coalescing untuk menghindari error jika kolom kosong --}}
                    {{ $docType === 'po' ? ($document->po_number ?? 'N/A') : ($document->request_number ?? 'N/A') }}
                </p>
                
                <p><strong>Status:</strong> 
                    @php
                        $statusColor = match($document->status ?? 'pending') {
                            'approved' => 'green',
                            'pending'  => 'yellow',
                            'rejected' => 'red',
                            default    => 'gray',
                        };
                    @endphp
                    <span class="bg-{{ $statusColor }}-100 text-{{ $statusColor }}-800 px-2 py-0.5 rounded text-xs font-semibold uppercase">
                        {{ $document->status ?? 'Unknown' }}
                    </span>
                </p>
                
                <p><strong>Ditandatangani oleh:</strong> 
                    <span class="font-semibold text-gray-900">
                        {{ $type === 'creator' ? ($document->user->name ?? 'N/A') : ($document->approvedBy->name ?? 'N/A') }}
                    </span>
                </p>
                <p><strong>Total Nominal:</strong> 
                    <span class="font-bold text-gray-900">
                        Rp {{ number_format((float)$total, 0, ',', '.') }}
                    </span>
                </p>
                <p><strong>Peran:</strong> 
                    <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded text-xs font-semibold uppercase">
                        {{ $roleName }}
                    </span>
                </p>

                <p><strong>Waktu Verifikasi:</strong> {{ now()->format('d M Y H:i') }} WIB</p>
            </div>
        @elseif($status === 'invalid')
            <div class="text-red-500 text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-red-600 mb-2">Verifikasi Gagal</h1>
            <p class="text-gray-600 my-4 font-medium">{{ $message }}</p>
            
            @if(isset($debug))
                <div class="bg-red-50 p-4 rounded-lg mt-4 text-left text-red-700 border border-red-200 text-xs overflow-hidden">
                    <h3 class="font-bold border-b border-red-200 pb-1 mb-2">Info Debug:</h3>
                    <p><strong>OpenSSL Error:</strong> {{ $debug['openssl_error'] ?: 'Data tanda tangan tidak cocok' }}</p>
                    <p class="mt-2 font-bold">String yang diverifikasi:</p>
                    <code class="block mt-1 break-all bg-red-100 p-2 rounded">{{ $debug['data_reconstructed'] }}</code>
                </div>
            @endif
        @else
            <div class="text-red-500 text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-red-600 mb-2">Sistem Error</h1>
            <p class="text-gray-600 my-4 font-medium">Terjadi kesalahan pada sistem verifikasi.</p>
        @endif
        
        <p class="text-xs text-gray-400 mt-6">Sistem Validasi QR Code Internal v2.2</p>
    </div>
</body>
</html>