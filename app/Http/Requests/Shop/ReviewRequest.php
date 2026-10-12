<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|between:1,5',
            'fit' => 'nullable|in:small,true,large',
            'title' => 'required|string|min:3|max:100',
            'body' => 'required|string|min:20|max:2000',
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4000,max_height=4000'],
        ];
    }

    public function attributes(): array
    {
        return ['rating' => __('rating'), 'fit' => __('fit'), 'title' => __('title'), 'body' => __('review'), 'photo' => __('review photo')];
    }
}
