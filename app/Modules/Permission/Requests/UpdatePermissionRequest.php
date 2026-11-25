<?php

namespace App\Modules\Permission\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission');

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('permissions', 'name')
                    ->ignore($permissionId),
            ],

            'display_name' => [
                'required',
                'string',
                'max:191',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ];
    }
}
