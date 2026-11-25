<?php

namespace App\Modules\Role\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Modules\Role\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        $role = $this->route('role');

        $roleId = $role instanceof Role
            ? $role->id
            : $role;

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('roles', 'name')->ignore($roleId),
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

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'A role with this name already exists.',
            'display_name.required' => 'Display name is required.',
            'description.string' => 'Description must be a valid string.',
        ];
    }
}

