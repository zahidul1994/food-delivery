<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        return view('admin-views.branch.index');
    }

    public function store(Request $request)
    {
        //dd($request->all());
        $request->validate([
            'name' => 'required|max:255|unique:branches',
            'email' => 'required|max:255|unique:branches',
            'password' => 'required|min:8|max:255',
            'image' => 'required|max:255',
        ], [
            'name.required' => translate('Name is required!'),
        ]);

        //image upload
        if (!empty($request->file('image'))) {
            $image_name = Helpers::upload('branch/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        if (!empty($request->file('cover_image'))) {
            $cover_image_name = Helpers::upload('branch/', 'png', $request->file('cover_image'));
        } else {
            $cover_image_name = 'def.png';
        }

        $branch = new Branch();
        $branch->name = $request->name;
        $branch->email = $request->email;
        $branch->longitude = $request->longitude;
        $branch->latitude = $request->latitude;
        $branch->coverage = $request->coverage ? $request->coverage : 0;
        $branch->address = $request->address;
        $branch->phone = $request->phone?? null;
        $branch->password = bcrypt($request->password);
        $branch->image = $image_name;
        $branch->cover_image = $cover_image_name;
        $branch->save();
        Toastr::success(translate('Branch added successfully!'));
        return back();
    }

    public function edit($id)
    {
        $branch = Branch::find($id);
        return view('admin-views.branch.edit', compact('branch'));
    }

    public function update(Request $request, $id)
    {
        //dd($request->all());
        $request->validate([
            'name' => 'required|max:255',
            'email' => ['required', 'unique:branches,email,'.$id.',id']
        ], [
            'name.required' => translate('Name is required!'),
        ]);

        $request->validate([
            'name' => 'required',
            'email' => 'required'
        ], [
            'name.required' => translate('Name is required!'),
        ]);

        $branch = Branch::find($id);
        $branch->name = $request->name;
        $branch->email = $request->email;
        $branch->longitude = $request->longitude;
        $branch->latitude = $request->latitude;
        $branch->coverage = $request->coverage ? $request->coverage : 0;
        $branch->address = $request->address;
        $branch->image = $request->has('image') ? Helpers::update('branch/', $branch->image, 'png', $request->file('image')) : $branch->image;
        $branch->cover_image = $request->has('cover_image') ? Helpers::update('branch/', $branch->cover_image, 'png', $request->file('cover_image')) : $branch->cover_image;
        if ($request['password'] != null) {
            $branch->password = bcrypt($request->password);
        }
        $branch->phone = $request->phone?? '';

        $branch->save();
        Toastr::success(translate('Branch updated successfully!'));
        return back();
    }

    public function delete(Request $request)
    {
        $branch = Branch::find($request->id);
        $branch->delete();
        Toastr::success(translate('Branch removed!'));
        return back();
    }

    public function status(Request $request)
    {
        $branch = Branch::find($request->id);
        $branch->status = $request->status;
        $branch->save();

        Toastr::success(translate('Branch status updated!'));
        return back();
    }

    public function list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        $query = Branch::when($search, function ($q) use ($search) {
            $key = explode(' ', $search);
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('name', 'like', "%{$value}%");
            }
        });
        $query_param = ['search' => $request['search']];
        $branches = $query->orderBy('id', 'DESC')->paginate(Helpers::getPagination())->appends($query_param);

        return view('admin-views.branch.list', compact('branches', 'search'));
    }
}
