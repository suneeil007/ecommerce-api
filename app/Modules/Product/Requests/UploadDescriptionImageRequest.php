<?php

namespace App\Modules\Product\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadDescriptionImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'upload' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'upload.required' =>
                'Please select an image.',

            'upload.image' =>
                'The uploaded file must be an image.',

            'upload.mimes' =>
                'The image must be JPG, JPEG, PNG, WEBP, or GIF.',

            'upload.max' =>
                'The image may not be larger than 5MB.',
        ];
    }
}