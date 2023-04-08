<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CustomerLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\WalletTransaction;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerWalletController extends Controller
{
    public function transfer_loyalty_point_to_wallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'point' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        //user point check (if has sufficient amount)
        $user = User::find($request->user()->id);
        if($request['point'] > $user->point) {
            return response()->json(['errors' => [['code' => 'wallet', 'message' => translate('Your point in not sufficient!')]]], 401);
        }

        //minimum point check (for transferring)
        $min_point = BusinessSetting::where(['key' => 'loyalty_point_minimum_point'])->first()->value;
        if ($request['point'] < $min_point ) {
            return response()->json(['errors' => [['code' => 'wallet', 'message' => translate('Your point in not sufficient!')]]], 401);
        }

        $loyalty_point_exchange_rate = BusinessSetting::where(['key' => 'loyalty_point_exchange_rate'])->first()->value;
        $loyalty_amount = $request['point']/$loyalty_point_exchange_rate;
        //dd($loyalty_amount);

        //point transfer transaction
        CustomerLogic::loyalty_point_wallet_transfer_transaction($user->id, $request['point'], $loyalty_amount);

        return response()->json(['message' => translate('transfer success')], 200);
    }

    public function wallet_transactions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $paginator = WalletTransaction::where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->limit, ['*'], 'page', $request->offset);

        $data = [
            'total_size' => $paginator->total(),
            'limit' => $request->limit,
            'offset' => $request->offset,
            'data' => $paginator->items()
        ];
        return response()->json($data, 200);
    }
}
