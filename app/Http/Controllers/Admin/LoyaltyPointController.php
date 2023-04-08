<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\PointTransitions;
use App\Models\LoyaltyPointTransaction;
use Illuminate\Http\Request;

class LoyaltyPointController extends Controller
{
    public function report(Request $request)
    {
        $data = PointTransitions::selectRaw('sum(credit) as total_credit, sum(debit) as total_debit')
            ->when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when($request->transaction_type, function($query)use($request){
                $query->where('type',$request->transaction_type);
            })
            ->when($request->customer_id, function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
            ->get();

        $transactions = PointTransitions::with(['customer'])
            ->when(($request->from && $request->to),function($query)use($request){
                $query->whereBetween('created_at', [$request->from.' 00:00:00', $request->to.' 23:59:59']);
            })
            ->when($request->transaction_type, function($query)use($request){
                $query->where('type',$request->transaction_type);
            })
            ->when($request->customer_id, function($query)use($request){
                $query->where('user_id',$request->customer_id);
            })
            ->latest()
            ->paginate(Helpers::getPagination());

        return view('admin-views.customer.loyalty-point.report', compact('data','transactions'));
    }
}
