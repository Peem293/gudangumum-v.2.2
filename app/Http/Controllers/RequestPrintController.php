<?php

namespace App\Http\Controllers;

use App\Models\Request as UnitRequest;
use Illuminate\Http\Request;

class RequestPrintController extends Controller
{
    public function print(UnitRequest $request)
    {
        // Load relations
        $request->load(['user', 'department', 'unit', 'details.item']);

        // Calculate total subtotal
        $totalSubtotal = $request->details->sum('subtotal');

        return view('requests.print', compact('request', 'totalSubtotal'));
    }
}
