<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requests;

class DocumentVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $id = $request->query('id');
        $incomingSignature = base64_decode($request->query('sig'));

        // 1. Cari dokumen di database berdasarkan ID dari QR Code
        // Asumsi relasi ke user peng-approve bernama 'approver' (sesuai approved_by_id)
        $document = Requests::with('approver')->find($id);

        if (!$document || !$document->signature) {
            return view('verification-result', [
                'status' => 'invalid',
                'message' => 'Dokumen tidak ditemukan atau belum disetujui secara digital.'
            ]);
        }

        // 2. REKONSTRUKSI DATA: Susun kembali string data dengan format yang SAMA PERSIS seperti di Tahap 2 tadi
        $dataToVerify = "DocID:" . $document->id . 
                        "|Status:approved" .
                        "|Approver:" . $document->approver->name .
                        "|ApproverID:" . $document->approved_by_id;

        // 3. AMBIL PUBLIC KEY: Mengambil kunci publik milik atasan yang meng-approve
        $publicKey = $document->approver->public_key;

        if (!$publicKey) {
            return view('verification-result', [
                'status' => 'invalid',
                'message' => 'Kunci publik verifikator tidak ditemukan.'
            ]);
        }

        // 4. PROSES VERIFIKASI: COCOKKAN DATA + SIGNATURE + PUBLIC KEY menggunakan OpenSSL
        $isValid = openssl_verify($dataToVerify, $incomingSignature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($isValid === 1) {
            return view('verification-result', [
                'status' => 'valid',
                'message' => '✅ DOKUMEN ASLI & VALID',
                'document' => $document
            ]);
        } else {
            return view('verification-result', [
                'status' => 'invalid',
                'message' => '❌ PERINGATAN: DOKUMEN TIDAK VALID / SUDAH DIMANIPULASI!'
            ]);
        }
    }
}
