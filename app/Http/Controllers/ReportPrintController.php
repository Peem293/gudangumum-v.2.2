<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Request as UnitRequest;
use App\Models\PurchaseOrder;
use App\Models\AdjustmentRequest;

class ReportPrintController extends Controller
{
    public function printRequestReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $user = auth()->user();
        $query = \App\Models\RequestDetail::query()
            ->whereHas('request', function ($q) use ($startDate, $endDate, $user) {
                $q->whereBetween('created_at', [$startDate, $endDate]);

                if ($user->hasRole('staf_unit')) {
                    $q->where('unit_id', $user->unit_id);
                } elseif ($user->hasRole('manager')) {
                    if (!$user->isPurchasingManager() && !$user->isFinanceManager()) {
                        $q->where('department_id', $user->department_id);
                    }
                }
            });

        $data = $query->with(['request.user', 'request.department', 'request.unit', 'item'])->get();

        return view('reports.request-print', [
            'data' => $data,
            'startDate' => Carbon::parse($request->start_date),
            'endDate' => Carbon::parse($request->end_date),
        ]);
    }

    public function printPurchaseOrderReport(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole(['administrator', 'direktur', 'admin_gudang', 'manager_keuangan']) && !$user->isPurchasingManager()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $data = \App\Models\PurchaseOrderDetail::query()
            ->whereHas('purchaseOrder', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('po_date', [$startDate, $endDate]);
            })
            ->with(['purchaseOrder.supplier', 'purchaseOrder.user', 'item'])
            ->get();

        return view('reports.purchase-order-print', [
            'data' => $data,
            'startDate' => Carbon::parse($request->start_date),
            'endDate' => Carbon::parse($request->end_date),
        ]);
    }

    public function printAdjustmentReport(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole(['administrator', 'direktur', 'admin_gudang', 'manager_keuangan'])) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $data = AdjustmentRequest::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['item', 'user', 'approvedBy', 'executedBy'])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('reports.adjustment-print', [
            'data' => $data,
            'startDate' => Carbon::parse($request->start_date),
            'endDate' => Carbon::parse($request->end_date),
        ]);
    }
}
