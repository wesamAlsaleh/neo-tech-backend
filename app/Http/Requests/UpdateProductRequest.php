<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // If the user is not an admin, they are not authorized to create a product
        if (Auth::user() == null || Auth::user()->role != 'admin') {
            return true;
        }

        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_name' => 'nullable|string|max:255',
            'product_description' => 'nullable|string',
            'product_price' => 'nullable|numeric|min:0',
            'product_rating' => 'nullable|integer|min:0|max:5',
            'product_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'in_stock' => 'boolean',
            'slug' => 'required|string|unique:products,slug',
            'category_id' => 'required|exists:categories,id',
        ];
    }
}
