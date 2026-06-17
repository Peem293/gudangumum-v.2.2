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
            <div class="border-t border-gray-200 my-4 pt-4 text-left text-sm text-gray-700 space-y-2">
                <p><strong>ID Dokumen:</strong> {{ $document->id }}</p>
                <p><strong>Status:</strong> <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-xs font-semibold">APPROVED</span></p>
                <p><strong>Disetujui Oleh:</strong> {{ $document->approver->name }}</p>
                <p><strong>Waktu Approve:</strong> {{ $document->updated_at->format('d M Y H:i') }} WIB</p>
            </div>
        @else
            <div class="text-red-500 text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-red-600 mb-2">Verifikasi Gagal</h1>
            <p class="text-gray-600 my-4 font-medium">{{ $message }}</p>
        @endif
        
        <p class="text-xs text-gray-400 mt-6">Sistem Validasi QR Code Internal v2.2</p>
    </div>
</body>
</html>