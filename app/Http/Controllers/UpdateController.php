<?php

namespace App\Http\Controllers;

use App\CentralLogics\Helpers;
use App\Model\Admin;
use App\Model\AdminRole;
use App\Model\BusinessSetting;
use App\Model\Order;
use App\Model\OrderDetail;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class UpdateController extends Controller
{
    public function update_software_index()
    {
        return view('update.update-software');
    }

    public function update_software(Request $request)
    {
        Helpers::setEnvironmentValue('SOFTWARE_ID', 'MzAzMjAzMzg=');
        Helpers::setEnvironmentValue('BUYER_USERNAME', $request['username']);
        Helpers::setEnvironmentValue('PURCHASE_CODE', $request['purchase_key']);
        Helpers::setEnvironmentValue('APP_MODE', 'live');
        Helpers::setEnvironmentValue('SOFTWARE_VERSION', '9.2');
        Helpers::setEnvironmentValue('APP_NAME', 'efood');

        $data = Helpers::requestSender($request);
        if (!$data['active']) {
            session()->flash('error', 'Invalid credentials');
            return back();
        }

        Artisan::call('migrate', ['--force' => true]);

        $previousRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.php');
        $newRouteServiceProvier = base_path('app/Providers/RouteServiceProvider.txt');
        copy($newRouteServiceProvier, $previousRouteServiceProvier);

        Artisan::call('optimize:clear');


        DB::table('business_settings')->updateOrInsert(['key' => 'self_pickup'], [
            'value' => 1
        ]);

        DB::table('business_settings')->updateOrInsert(['key' => 'delivery'], [
            'value' => 1
        ]);

        if (BusinessSetting::where(['key' => 'paystack'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'paystack',
                'value' => '{"status":"1","publicKey":"","razor_secret":"","secretKey":"","paymentUrl":"","merchantEmail":""}'
            ]);
        }
        if (BusinessSetting::where(['key' => 'senang_pay'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'senang_pay',
                'value' => '{"status":"1","secret_key":"","merchant_id":""}'
            ]);
        }
        if (BusinessSetting::where(['key' => 'bkash'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'bkash',
                'value' => '{"status":"1","api_key":"","api_secret":"","username":"","password":""}'
            ]);
        }
        if (BusinessSetting::where(['key' => 'paymob'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'paymob',
                'value' => '{"status":"1","api_key":"","iframe_id":"","integration_id":"","hmac":""}'
            ]);
        }
        if (BusinessSetting::where(['key' => 'flutterwave'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'flutterwave',
                'value' => '{"status":"1","public_key":"","secret_key":"","hash":""}'
            ]);
        }
        if (BusinessSetting::where(['key' => 'mercadopago'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'mercadopago',
                'value' => '{"status":"1","public_key":"","access_token":""}'
            ]);
        }
        if (BusinessSetting::where(['key' => 'paypal'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'paypal',
                'value' => '{"status":"1","paypal_client_id":"","paypal_secret":""}'
            ]);
        }
        if (BusinessSetting::where(['key' => 'internal_point'])->first() == false) {
            BusinessSetting::insert([
                'key' => 'internal_point',
                'value' => '{"status":"1"}'
            ]);
        }
        Order::where('delivery_date', null)->update([
            'delivery_date' => date('y-m-d', strtotime("-1 days")),
            'delivery_time' => '12:00',
            'updated_at' => now()
        ]);

        if (BusinessSetting::where(['key' => 'language'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'language'], [
                'value' => json_encode(["en"])
            ]);
        }
        if (BusinessSetting::where(['key' => 'time_zone'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'time_zone'], [
                'value' => 'Pacific/Midway'
            ]);
        }

        DB::table('business_settings')->updateOrInsert(['key' => 'phone_verification'], [
            'value' => 0
        ]);
        DB::table('business_settings')->updateOrInsert(['key' => 'msg91_sms'], [
            'key' => 'msg91_sms',
            'value' => '{"status":0,"template_id":null,"authkey":null}'
        ]);
        DB::table('business_settings')->updateOrInsert(['key' => '2factor_sms'], [
            'key' => '2factor_sms',
            'value' => '{"status":"0","api_key":null}'
        ]);
        DB::table('business_settings')->updateOrInsert(['key' => 'nexmo_sms'], [
            'key' => 'nexmo_sms',
            'value' => '{"status":0,"api_key":null,"api_secret":null,"signature_secret":"","private_key":"","application_id":"","from":null,"otp_template":null}'
        ]);
        DB::table('business_settings')->updateOrInsert(['key' => 'twilio_sms'], [
            'key' => 'twilio_sms',
            'value' => '{"status":0,"sid":null,"token":null,"from":null,"otp_template":null}'
        ]);
        if (BusinessSetting::where(['key' => 'pagination_limit'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'pagination_limit'], [
                'value' => 10
            ]);
        }
        if (BusinessSetting::where(['key' => 'default_preparation_time'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'default_preparation_time'], [
                'value' => 30
            ]);
        }
        if(BusinessSetting::where(['key' => 'decimal_point_settings'])->first() == false)
        {
            DB::table('business_settings')->updateOrInsert(['key' => 'decimal_point_settings'], [
                'value' => 2
            ]);
        }
        if (BusinessSetting::where(['key' => 'map_api_key'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'map_api_key'], [
                'value' => ''
            ]);
        }

        if (BusinessSetting::where(['key' => 'play_store_config'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'play_store_config'], [
                'value' => '{"status":"","link":"","min_version":""}'
            ]);
        } else {
            $play_store_config = Helpers::get_business_settings('play_store_config');
            DB::table('business_settings')->updateOrInsert(['key' => 'play_store_config'], [
                'value' => json_encode([
                    'status' => $play_store_config['status'],
                    'link' => $play_store_config['link'],
                    'min_version' => "1",
                ])
            ]);
        }

        if (BusinessSetting::where(['key' => 'app_store_config'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'app_store_config'], [
                'value' => '{"status":"","link":"","min_version":""}'
            ]);
        } else {
            $app_store_config = Helpers::get_business_settings('app_store_config');
            DB::table('business_settings')->updateOrInsert(['key' => 'app_store_config'], [
                'value' => json_encode([
                    'status' => $app_store_config['status'],
                    'link' => $app_store_config['link'],
                    'min_version' => "1",
                ])
            ]);
        }

        if (BusinessSetting::where(['key' => 'delivery_management'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'delivery_management'], [
                'value' => json_encode([
                    'status' => 0,
                    'min_shipping_charge' => 0,
                    'shipping_per_km' => 0,
                ]),
            ]);
        }
        if (BusinessSetting::where(['key' => 'recaptcha'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'recaptcha'], [
                'value' => '{"status":"0","site_key":"","secret_key":""}'
            ]);
        }


        //for modified language [new multi lang in admin]
        $languages = Helpers::get_business_settings('language');
        $lang_array = [];
        $lang_flag = false;

        foreach ($languages as $key => $language) {
            if(gettype($language) != 'array') {
                $lang = [
                    'id' => $key+1,
                    'name' => $language,
                    'direction' => 'ltr',
                    'code' => $language,
                    'status' => 1,
                    'default' => $language == 'en' ? true : false,
                ];

                array_push($lang_array, $lang);
                $lang_flag = true;
            }
        }
        if ($lang_flag == true) {
            BusinessSetting::where('key', 'language')->update([
                'value' => $lang_array
            ]);
        }
        //lang end

        if (BusinessSetting::where(['key' => 'schedule_order_slot_duration'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'schedule_order_slot_duration'], [
                'value' => '1'
            ]);
        }

        if (BusinessSetting::where(['key' => 'time_format'])->first() == false) {
            DB::table('business_settings')->updateOrInsert(['key' => 'time_format'], [
                'value' => '24'
            ]);
        }

        //for role management
        $admin_role = AdminRole::get()->first();
        if (!$admin_role) {
            DB::table('admin_roles')->insertOrIgnore([
                'id' => 1,
                'name' => 'Master Admin',
                'module_access' => null,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        $admin = Admin::get()->first();
        if($admin) {
            $admin->admin_role_id = 1;
            $admin->save();
        }

        $mail_config = \App\CentralLogics\Helpers::get_business_settings('mail_config');
        BusinessSetting::where(['key' => 'mail_config'])->update([
            'value' => json_encode([
                "status" => 0,
                "name" => $mail_config['name'],
                "host" => $mail_config['host'],
                "driver" => $mail_config['driver'],
                "port" => $mail_config['port'],
                "username" => $mail_config['username'],
                "email_id" => $mail_config['email_id'],
                "encryption" => $mail_config['encryption'],
                "password" => $mail_config['password']
            ]),
        ]);

        //*** auto run script ***
        try {
            $order_details = OrderDetail::get();
            foreach($order_details as $order_detail) {

                //*** addon quantity integer casting script ***
                $qtys = json_decode($order_detail['add_on_qtys'], true);
                array_walk($qtys, function (&$add_on_qtys) {
                    $add_on_qtys = (int) $add_on_qtys;
                });
                $order_detail['add_on_qtys'] = json_encode($qtys);
                //*** end ***


                //*** variation(POS) structure change script ***
                $variation = json_decode($order_detail['variation'], true);
                $product = json_decode($order_detail['product_details'], true);

                if(count($variation) > 0) {
                    $result = [];
                    if(!array_key_exists('price', $variation[0])) {
                        $result[] = [
                            'type' => $variation[0]['Size'],
                            'price' => Helpers::set_price($product['price'])
                        ];
                    }
                    if(count($result) > 0) {
                        $order_detail['variation'] = json_encode($result);
                    }

                }
                //*** end ***

                $order_detail->save();


            }
        } catch (\Exception $exception) {
            //
        }
        //*** end ***

        DB::table('branches')->insertOrIgnore([
            'id' => 1,
            'name' => 'Main Branch',
            'email' => 'main@gmail.com',
            'password' => '',
            'coverage' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        if (!BusinessSetting::where(['key' => 'wallet_status'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'wallet_status'], [
                'value' => '0'
            ]);
        }

        if (!BusinessSetting::where(['key' => 'loyalty_point_status'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'loyalty_point_status'], [
                'value' => '0'
            ]);
        }

        if (!BusinessSetting::where(['key' => 'ref_earning_status'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'ref_earning_status'], [
                'value' => '0'
            ]);
        }

        if (!BusinessSetting::where(['key' => 'loyalty_point_exchange_rate'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'loyalty_point_exchange_rate'], [
                'value' => '0'
            ]);
        }

        if (!BusinessSetting::where(['key' => 'ref_earning_exchange_rate'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'ref_earning_exchange_rate'], [
                'value' => '0'
            ]);
        }

        if (!BusinessSetting::where(['key' => 'loyalty_point_item_purchase_point'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'loyalty_point_item_purchase_point'], [
                'value' => '0'
            ]);
        }

        if (!BusinessSetting::where(['key' => 'loyalty_point_minimum_point'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'loyalty_point_minimum_point'], [
                'value' => '0'
            ]);
        }

        if (!BusinessSetting::where(['key' => 'whatsapp'])->first()) {
            BusinessSetting::insert([
                'key' => 'whatsapp',
                'value' => '{"status":0,"number":""}'
            ]);
        }

        if (!BusinessSetting::where(['key' => 'fav_icon'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'fav_icon'], [
                'value' => ''
            ]);
        }

        //user referral code
        $users = User::whereNull('refer_code')->get();
        foreach ($users as $user) {
            $user->refer_code = Helpers::generate_referer_code();
            $user->save();
        }

        if (!BusinessSetting::where(['key' => 'cookies'])->first()) {
            DB::table('business_settings')->updateOrInsert(['key' => 'cookies'], [
                'value' => '{"status":"1","text":"Allow Cookies for this site"}'
            ]);
        }

        return redirect('/admin/auth/login');
    }
}
