<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $rules = ['name' => 'required|string|min:2|max:150', 'category' => 'required|in:Everyday,Running,Outdoor', 'description' => 'required|string|min:10|max:5000', 'retail_price' => 'required|integer|min:1|max:100000000', 'wholesale_price' => 'required|integer|min:1|max:100000000|lte:retail_price', 'name_ne' => 'nullable|string|min:2|max:150', 'description_ne' => 'nullable|string|min:10|max:5000'];
        if ($this->isMethod('POST')) {
            return [...$rules, 'color' => 'required|in:cream,orange,green,white,blue,black', 'sizes' => 'required|array|min:1|max:10', 'sizes.*' => 'required|distinct|in:36,37,38,39,40,41,42,43,44,45', 'stock' => 'required|integer|min:0|max:1000000'];
        }
        $rules['category'] = 'sometimes|'.$rules['category'];
        $rules['description'] = 'sometimes|'.$rules['description'];

        return [...$rules, 'active' => 'required|boolean'];
    }
}
