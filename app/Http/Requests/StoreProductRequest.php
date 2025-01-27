<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // if the user is not authenticated
        if (!Auth::check()) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_name' => 'required|string|max:255',
            'product_description' => 'nullable|string',
            'product_price' => 'required|numeric|min:0',
            'product_rating' => 'nullable|integer|min:0|max:5',
            'slug' => 'required|string|unique:slug',
            'product_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
            'in_stock' => 'boolean',
            'category_id' => 'required|exists:categories,id',
        ];
    }
}
