<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubCategory;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    use ResponseTrait;

    public function index(Request $request)
    {
        $query = SubCategory::with('category');
        
        $category = null;
        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('category_id', $request->category_id);
            $category = \App\Models\Category::find($request->category_id);
        }
        
        $subCategories = $query->get();
        $categories = \App\Models\Category::all();
        
        if ($request->ajax()) {
            return $this->successResponse($subCategories, 'Sub Categories retrieved successfully');
        }
        
        return view('admin.sub_categories.index', compact('subCategories', 'categories', 'category'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $subCategory = SubCategory::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return $this->successResponse($subCategory, 'Sub Category created successfully', 201);
    }

    public function show($id)
    {
        $subCategory = SubCategory::with(['category', 'questions'])->find($id);
        if (!$subCategory) {
            return redirect()->route('admin.sub-categories.index')->with('error', 'Sub Pelajaran tidak ditemukan');
        }
        
        return view('admin.sub_categories.show', compact('subCategory'));
    }

    public function update(Request $request, $id)
    {
        $subCategory = SubCategory::find($id);
        if (!$subCategory) return $this->errorResponse('Sub Category not found', 404);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $subCategory->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return $this->successResponse($subCategory, 'Sub Category updated successfully');
    }

    public function destroy($id)
    {
        $subCategory = SubCategory::find($id);
        if (!$subCategory) return $this->errorResponse('Sub Category not found', 404);

        $subCategory->delete();
        return $this->successResponse(null, 'Sub Category deleted successfully');
    }
}
