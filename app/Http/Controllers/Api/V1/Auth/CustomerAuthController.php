<?php

namespace App\Http\Controllers\Api\V1\Auth;
ini_set('memory_limit', '-1');
use App\CentralLogics\Helpers;
use App\CentralLogics\SMS_module;
use App\Http\Controllers\Controller;
use App\Mail\EmailVerification;
use App\Model\BusinessSetting;
use App\Model\EmailVerifications;
use App\Model\PhoneVerification;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use GuzzleHttp\Client;

class CustomerAuthController extends Controller
{
    public function check_phone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|min:11|max:14|unique:users'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (BusinessSetting::where(['key' => 'phone_verification'])->first()->value) {
            $token = rand(1000, 9999);
            DB::table('phone_verifications')->insert([
                'phone' => $request['phone'],
                'token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $response = SMS_module::send($request['phone'], $token);
            return response()->json([
                'message' => $response,
                'token' => 'active'
            ], 200);
        } else {
            return response()->json([
                'message' => translate('Number is ready to register'),
                'token' => 'inactive'
            ], 200);
        }
    }

    public function check_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|unique:users'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (BusinessSetting::where(['key' => 'email_verification'])->first()->value) {
            $token = rand(1000, 9999);
            DB::table('email_verifications')->insert([
                'email' => $request['email'],
                'token' => $token,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            try {
                $emailServices = Helpers::get_business_settings('mail_config');
                if (isset($emailServices['status']) && $emailServices['status'] == 1) {
                    Mail::to($request['email'])->send(new EmailVerification($token));
                }

            } catch (\Exception $exception) {
                return response()->json([
                    'message' => translate('Token sent failed')
                ], 403);
            }

            return response()->json([
                'message' => translate('Email is ready to register'),
                'token' => 'active'
            ], 200);
        } else {
            return response()->json([
                'message' => translate('Email is ready to register'),
                'token' => 'inactive'
            ], 200);
        }
    }

    public function verify_email(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $verify = EmailVerifications::where(['email' => $request['email'], 'token' => $request['token']])->first();

        if (isset($verify)) {
            $verify->delete();
            return response()->json([
                'message' => translate('OTP verified!'),
            ], 200);
        }

        return response()->json(['errors' => [
            ['code' => 'otp', 'message' => translate('OTP is not found!')]
        ]], 404);
    }

    public function verify_phone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $verify = PhoneVerification::where(['phone' => $request['phone'], 'token' => $request['token']])->first();

        if (isset($verify)) {
            $verify->delete();
            return response()->json([
                'message' => translate('OTP verified!'),
            ], 200);
        }

        return response()->json(['errors' => [
            ['code' => 'token', 'message' => translate('OTP is not found!')]
        ]], 404);
    }

    public function registration(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|unique:users',
            'phone' => 'required|unique:users|min:5|max:20',
            'password' => 'required|min:6',
        ], [
            'f_name.required' => translate('The first name field is required.'),
            'l_name.required' => translate('The last name field is required.'),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        //dd($request->referral_code);

        if ($request->referral_code){
           $refer_user = User::where(['refer_code' => $request->referral_code])->first();
        }

        $temporary_token = Str::random(40);

        $user = User::create([
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => bcrypt($request->password),
            'temporary_token' => $temporary_token,
            'refer_code' => Helpers::generate_referer_code(),
            'refer_by' => $refer_user->id ?? null,
        ]);

        $phone_verification = Helpers::get_business_settings('phone_verification');
        $email_verification = Helpers::get_business_settings('email_verification');
        if ($phone_verification && !$user->is_phone_verified) {
            return response()->json(['temporary_token' => $temporary_token], 200);
        }
        if ($email_verification && !$user->is_email_verified) {
            return response()->json(['temporary_token' => $temporary_token], 200);
        }

        $token = $user->createToken('RestaurantCustomerAuth')->accessToken;
        return response()->json(['token' => $token], 200);
    }

    public function login(Request $request)
    {
        if($request->has('email_or_phone')) {
            $user_id = $request['email_or_phone'];
            $validator = Validator::make($request->all(), [
                'email_or_phone' => 'required',
                'password' => 'required|min:6'
            ]);
        }else{
            $user_id = $request['email'];
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required|min:6'
            ]);
        }

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $user = User::where('is_active', 1)
            ->where(function ($query) use($user_id) {
                $query->where(['email' => $user_id])->orWhere('phone', $user_id);
            })
            ->first();

        if (isset($user)) {
            $user->temporary_token = Str::random(40);
            $user->save();
            $data = [
                'email' => $user->email,
                'password' => $request->password,
                'user_type' => null,
            ];

            if (auth()->attempt($data)) {
                $token = auth()->user()->createToken('RestaurantCustomerAuth')->accessToken;
                return response()->json(['token' => $token], 200);
            }
        }

        $errors = [];
        $errors[] = ['code' => 'auth-001', 'message' => 'Invalid credential.'];
        return response()->json([
            'errors' => $errors
        ], 401);

    }

    public function remove_account(Request $request)
    {
        $customer = User::find($request->user()->id);

        if(isset($customer)) {
            Helpers::file_remover('customer/', $customer->image);
            $customer->delete();
        } else {
            return response()->json(['status_code' => 404, 'message' => translate('Not found')], 200);
        }
        return response()->json(['status_code' => 200, 'message' => translate('Successfully deleted')], 200);
    }

    public function social_customer_login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'unique_id' => 'required',
            'email' => 'required',
            'medium' => 'required|in:google,facebook',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $client = new Client();
        $token = $request['token'];
        $email = $request['email'];
        $unique_id = $request['unique_id'];

        try {
            if ($request['medium'] == 'google') {
                $res = $client->request('GET', 'https://www.googleapis.com/oauth2/v3/tokeninfo?id_token=' . $token);
                $data = json_decode($res->getBody()->getContents(), true);
            } elseif ($request['medium'] == 'facebook') {
                $res = $client->request('GET', 'https://graph.facebook.com/' . $unique_id . '?access_token=' . $token . '&&fields=name,email');
                $data = json_decode($res->getBody()->getContents(), true);
            }
        } catch (\Exception $exception) {
            $errors = [];
            $errors[] = ['code' => 'auth-001', 'message' => 'Invalid Token'];
            return response()->json([
                'errors' => $errors
            ], 401);
        }

        if (strcmp($email, $data['email']) === 0) {
            $user = User::where('email', $request['email'])->first();

            if (!isset($user)) {
                $name = explode(' ', $data['name']);
                if (count($name) > 1) {
                    $fast_name = implode(" ", array_slice($name, 0, -1));
                    $last_name = end($name);
                } else {
                    $fast_name = implode(" ", $name);
                    $last_name = '';
                }

                $user = new User();
                $user->f_name = $fast_name;
                $user->l_name = $last_name;
                $user->email = $data['email'];
                $user->phone = null;
                $user->image = 'def.png';
                $user->password = bcrypt(rand(100000, 999999));
                $user->login_medium = $request['medium'];
                $user->refer_code = Helpers::generate_referer_code();

                $user->save();
            }

            if (isset($user)){
                if ($user->is_active == 1){
                    $token = $user->createToken('AuthToken')->accessToken;
                    return response()->json([
                        'errors' => null,
                        'token' => $token,
                    ], 200);
                }else{
                    $errors = [];
                    $errors[] = ['code' => 'auth-001', 'message' => 'Unauthenticated.'];
                    return response()->json([
                        'errors' => $errors
                    ], 401);
                }
            }
        }

        $errors = [];
        $errors[] = ['code' => 'auth-001', 'message' => 'Invalid Token'];
        return response()->json([
            'errors' => $errors
        ], 401);
    }

}
