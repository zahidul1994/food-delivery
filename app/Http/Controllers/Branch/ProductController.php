<?php

namespace App\Http\Controllers\Branch;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Product;
use App\Model\ProductByBranch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $query = Product::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        } else {
            $query = new Product();
        }
        $products = $query->with('product_by_branch')->orderBy('id', 'DESC')->paginate(Helpers::getPagination())->appends($query_param);
       //return $products;
        return view('branch-views.product.list', compact('products', 'search'));
    }

    public function set_price_index($id)
    {
        $product = Product::with(['translations', 'product_by_branch'])->find($id);
        return view('branch-views.product.set-price', compact('product'));
    }

    public function set_price_update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'price' => 'required',
            'discount_type' => 'required|in:percent,amount',
            'discount' => 'required',
        ], [
            'price.required' => translate('Product price is required!'),
            'discount_type.required' => translate('please select discount type!'),
            'discount.required' => translate('discount is required!')
        ]);

        if ($request['discount_type'] == 'percent') {
            $dis = ($request['price'] / 100) * $request['discount'];
        } else {
            $dis = $request['discount'];
        }

        if ($request['price'] <= $dis) {
            $validator->getMessageBag()->add('unit_price', translate('Discount can not be more or equal to the price!'));
        }

        if ($request['price'] <= $dis || $validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $price = $request['price'];
        $variations = [];

        if(isset($request->options))
        {
            foreach(array_values($request->options) as $key=>$option)
            {
                $temp_variation['name']= $option['name'];
                $temp_variation['type']= $option['type'];
                $temp_variation['min']= $option['min'] ?? 0;
                $temp_variation['max']= $option['max'] ?? 0;
                $temp_variation['required']= $option['required']??'off';
                if ($option['min'] > 0 && $option['min'] >= $option['max']) {
                    $validator->getMessageBag()->add('name', translate('maximum_value_can_not_be_smaller_or_equal_then_minimum_value'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if(!isset($option['values'])){
                    $validator->getMessageBag()->add('name', translate('please_add_options_for'). ' ' .$option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if($option['max'] > count($option['values'])  ){
                    $validator->getMessageBag()->add('name', translate('please_add_more_options_or_change_the_max_value_for'). ' ' .$option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $temp_value = [];

                foreach($option['values'] as $value)
                {
                    if(isset($value['label'])){
                        $temp_option['label'] = $value['label'];
                    }
                    $temp_option['optionPrice'] = $value['optionPrice'];
                    $temp_value[] = $temp_option;
                }
                $temp_variation['values']= $temp_value;
                $variations[] = $temp_variation;
            }
        }

        $product_id = $id;
        $branch_product = [
            'product_id' => $product_id,
            'price' => $price,
            'discount_type' => $request['discount_type'],
            'discount' => $request['discount'],
            'branch_id' => auth('branch')->id(),
            'is_available' => 1,
            'variations' => $variations,
        ];
        //dd($branch_product);

        ProductByBranch::updateOrCreate([
            'product_id' => $branch_product['product_id'],
            'branch_id' => auth('branch')->id(),
            ], $branch_product
        );

        if (auth('branch')->id() == 1){
            $product = Product::find($branch_product['product_id']);
            if($product){
                $product->price = $request['price'];
                $product->discount_type = $request['discount_type'];
                $product->discount = $request['discount'];
                $product->variations = json_encode($variations);
                $product->update();
            }
        }

        return response()->json([], 200);
    }

    public function status(Request $request)
    {
        $product = Product::find($request->id);
        $branch_product = ProductByBranch::where(['product_id' => $product->id, 'branch_id' => auth('branch')->id()])->first();
        if (isset($branch_product )){
            $data = [
                'price' => $branch_product->price,
                'discount_type' => $branch_product->discount_type,
                'discount' => $branch_product->discount,
                'product_id' => $product->id,
                'is_available' => $request->status,
            ];

            ProductByBranch::updateOrCreate([
                'product_id' => $data['product_id'],
                'branch_id' => auth('branch')->id()
                ], $data
            );
        }
        else{
            $variations = json_decode($product->variations,true);

            $data = [];

            if (count($variations) > 0){
                foreach($variations as $variation){

                    if(isset($variation["price"])){
                        return response()->json(['variation_message' => 'Please update your variation first!']);
                    }

                    $var[] = $variation;
                    $data = [
                        'product_id' => $product->id,
                        'price' => $product->price,
                        'discount_type' => $product->discount_type,
                        'discount' => $product->discount,
                        'branch_id' => auth('branch')->id(),
                        'is_available' => $request->status,
                        'variations' => $var,
                    ];
                }
            }else{
                $data = [
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'discount_type' => $product->discount_type,
                    'discount' => $product->discount,
                    'branch_id' => auth('branch')->id(),
                    'is_available' => $request->status,
                    'variations' => [],
                ];

            }
            ProductByBranch::updateOrCreate([
                'product_id' => $product->id,
                'branch_id' => auth('branch')->id()
            ], $data
            );
        }
        return response()->json(['success_message' => 'Status updated!']);
    }
}
