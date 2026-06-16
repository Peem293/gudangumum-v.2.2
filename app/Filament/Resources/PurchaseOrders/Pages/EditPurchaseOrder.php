<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TOMBOL APPROVE KHUSUS ADMIN & MANAGER PENUNJANG UMUM
            Action::make('approve')
                ->label('Approve PO')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(function () {
                    // 1. Tombol hanya muncul jika status PO di database saat ini adalah 'draft'
                    if ($this->record->status !== 'draft') {
                        return false;
                    }

                    // 2. Ambil data user yang sedang login
                    $user = Auth::user();
                    if (!$user) {
                        return false;
                    }

                    /**
                     * KONDISI HAK AKSES (Spatie / Filament Shield):
                     * - Punya role 'super_admin' ATAU 'administrator'
                     * - ATAU punya role 'manager' DAN berada di departemen 'Penunjang Umum'
                     */
                    $isAdmin = $user->hasRole(['super_admin', 'administrator']);

                    // Pastikan Model User Anda memiliki relasi 'department' ke tabel departments
                    $isManagerPenunjangUmum = $user->hasRole('manager') &&
                        ($user->department?->name === 'Penunjang Umum');

                    // Tombol HANYA MUNCUL jika salah satu kondisi di atas terpenuhi (Admin Gudang otomatis false)
                    return $isAdmin || $isManagerPenunjangUmum;
                })
                ->action(function () {
                    // Simpan status approved dan user yang melakukan approval
                    $this->record->update([
                        'status' => 'approved',
                        'approved_by_id' => Auth::id(),
                    ]);

                    Notification::make()
                        ->title('Purchase Order Berhasil Di-approve!')
                        ->success()
                        ->send();

                    // Redirect kembali ke halaman index list PO Draft
                    $this->redirect($this->getResource()::getUrl('index'));
                }),

            DeleteAction::make(),
        ];
    }

    // Redirect bawaan jika menekan tombol 'Save' biasa di bawah form
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
