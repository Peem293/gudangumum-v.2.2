<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderPrintController extends Controller
{
    public function print(PurchaseOrder $purchaseOrder)
    {
        // Load relations
        $purchaseOrder->load(['supplier', 'user', 'approvedBy', 'details.item']);

        // Calculate subtotal sum
        $totalSubtotal = $purchaseOrder->details->sum('subtotal');

        // Calculate tax amount
        $taxPercentage = (float) $purchaseOrder->ppn_percentage;
        $taxAmount = $totalSubtotal * ($taxPercentage / 100);

        return view('purchase-orders.print', compact('purchaseOrder', 'totalSubtotal', 'taxAmount'));
    }
}
