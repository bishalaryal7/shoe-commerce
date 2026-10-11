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
        ];
    }

    public function attributes(): array
    {
        return ['rating' => __('rating'), 'fit' => __('fit'), 'title' => __('title'), 'body' => __('review')];
    }
}
