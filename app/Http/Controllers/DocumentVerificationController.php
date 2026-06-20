<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Request as RequestsModel;
use App\Models\PurchaseOrder;

class DocumentVerificationController extends Controller
{
    public function verify(Request $request) 
    {
        $id = $request->query('id');
        $sigBase64 = $request->query('sig');
        $type = $request->query('type', 'creator'); 
        $docType = $request->query('doc', 'request'); 

        // 1. Ambil data dokumen dari Database (Sumber Kebenaran)
        $document = ($docType === 'po') 
            ? PurchaseOrder::with(['user', 'approvedBy'])->find($id) 
            : RequestsModel::with(['user', 'approvedBy'])->find($id);

        if (!$document) {
            return view('verification-result', [
                'status' => 'invalid', 
                'message' => 'Dokumen tidak ditemukan.',
                'docType' => $docType
            ]);
        }

        // 2. Tentukan variabel berdasarkan jenis dokumen
        // Gunakan data dari $document (DB), bukan dari $request (URL)
        $userName = trim(($type === 'creator' ? $document->user->name : ($document->approvedBy->name ?? 'N/A')));
        $reqNo = ($docType === 'po') ? $document->po_number : $document->request_number;
        $total = ($docType === 'po') ? $document->grand_total : $document->total_amount;

        $roleName = ($document->approvedBy && $document->approvedBy->roles->isNotEmpty()) 
            ? ucwords(str_replace('_', ' ', $document->approvedBy->roles->pluck('name')->first())) 
            : 'User';

        // 3. Rekonstruksi String menggunakan data dari DATABASE
        // Ini memastikan verifikasi membandingkan "Tanda Tangan Asli" vs "Data Asli di Server"
        $reqNo = ($docType === 'po') ? $document->po_number : $document->request_number;
        $formattedTotal = number_format((float)($docType === 'po' ? $document->grand_total : $document->total_amount), 2, '.', '');
       if ($docType === 'po') {
            $reqNo = $document->po_number;
            // Pakai rumus yang SAMA PERSIS dengan di EditPurchaseOrder
            $formattedTotal = number_format((float)$document->grand_total, 2, '.', '');
            
            if ($type === 'creator') {
                $dataToVerify = "DocType:PO|Identifier:{$document->po_number}|PoNo:{$reqNo}|Total:{$formattedTotal}|Status:pending|Creator:{$userName}|CreatorID:{$document->user_id}";
                $publicKey = $document->user->public_key ?? null;
            } else {
                $dataToVerify = "DocID:{$document->id}|PoNo:{$reqNo}|Total:{$formattedTotal}|Status:{$document->status}|Approver:{$userName}|ApproverID:{$document->approved_by_id}|doc:po";
                $publicKey = $document->approvedBy->public_key ?? null;
            }
        
        } else {
            $dataToVerify = ($type === 'creator')
                ? "DocID:{$document->id}|ReqNo:{$reqNo}|Total:{$total}|Status:pending|Creator:{$userName}|CreatorID:{$document->user_id}|Type:creator"
                : "DocID:{$document->id}|ReqNo:{$reqNo}|Total:{$total}|Status:approved|Approver:{$userName}|ApproverID:{$document->approved_by_id}";
            
            $publicKey = ($type === 'creator') ? $document->user->public_key : ($document->approvedBy->public_key ?? null);
        }

        // 4. Verifikasi Signature
        $isValid = 0;
        if ($publicKey) {
            $cleanKey = trim($publicKey);
            if (strpos($cleanKey, '-----BEGIN PUBLIC KEY-----') === false) {
                $cleanKey = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($cleanKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
            }
            $isValid = openssl_verify($dataToVerify, base64_decode($sigBase64), $cleanKey, OPENSSL_ALGO_SHA256);
        }
        $formattedTotal = number_format((float)($docType === 'po' ? $document->grand_total : $document->total_amount), 2, '.', '');
        return view('verification-result', [
            'status' => ($isValid === 1) ? 'valid' : 'invalid',
            'message' => ($isValid === 1) ? '✅ DOKUMEN ASLI & VALID' : '❌ DOKUMEN TIDAK VALID / MANIPULASI!',
            'document' => $document,
            'type' => $type,
            'docType' => $docType,
            'roleName' => $roleName,
            'total'    => $formattedTotal,
            'debug' => [
                'data_reconstructed' => $dataToVerify,
                'openssl_error' => ($isValid !== 1) ? openssl_error_string() : 'None'
            ]
        ]);
    }
}