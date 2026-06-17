<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentVerificationController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/purchase-orders/{purchaseOrder}/print', [App\Http\Controllers\PurchaseOrderPrintController::class, 'print'])
    ->name('purchase-orders.print')
    ->middleware('auth');

Route::get('/admin/requests/{request}/print', [App\Http\Controllers\RequestPrintController::class, 'print'])
    ->name('requests.print')
    ->middleware('auth');

Route::get('/admin/reports/requests/print', [App\Http\Controllers\ReportPrintController::class, 'printRequestReport'])
    ->name('reports.requests.print')
    ->middleware('auth');

Route::get('/admin/reports/purchase-orders/print', [App\Http\Controllers\ReportPrintController::class, 'printPurchaseOrderReport'])
    ->name('reports.purchase-orders.print')
    ->middleware('auth');

Route::get('/admin/reports/adjustments/print', [App\Http\Controllers\ReportPrintController::class, 'printAdjustmentReport'])
    ->name('reports.adjustments.print')
    ->middleware('auth');

Route::get('/verify-document', [DocumentVerificationController::class, 'verify'])->name('document.verify');

