<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    use ResponseTrait;

    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request)
    {
        $categories = $this->categoryService->getAll();
        
        if ($request->ajax()) {
            return $this->successResponse($categories, 'Categories retrieved successfully');
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $category = $this->categoryService->store($request->all());
        return $this->successResponse($category, 'Category created successfully', 201);
    }

    public function show(Request $request, $id)
    {
        $category = Category::with('questions')->find($id);
        
        if (!$category) {
            if ($request->ajax()) return $this->errorResponse('Category not found', 404);
            return redirect()->route('admin.categories.index')->with('error', 'Kategori tidak ditemukan');
        }

        if ($request->ajax()) {
            return $this->successResponse($category);
        }

        return view('admin.categories.show', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:categories,name,' . $id,
        ]);

        if ($validator->fails()) {
            return $this->validationResponse($validator->errors());
        }

        $updated = $this->categoryService->update($id, $request->all());
        if (!$updated) return $this->errorResponse('Category update failed');
        
        return $this->successResponse(null, 'Category updated successfully');
    }

    public function destroy($id)
    {
        $deleted = $this->categoryService->destroy($id);
        if (!$deleted) return $this->errorResponse('Category deletion failed');
        
        return $this->successResponse(null, 'Category deleted successfully');
    }
}
