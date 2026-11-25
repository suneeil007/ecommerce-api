<?php

namespace App\Modules\User\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UpdateUserRequest extends FormRequest
{
    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public function authorize(): bool
    {
        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    */

    public function rules(): array
    {
        // Find user using users.uuid
        $userId = DB::table('users')
            ->where('uuid', $this->route('uuid'))
            ->value('id');

        return [

            /*
            |--------------------------------------------------------------------------
            | User Account
            |--------------------------------------------------------------------------
            */

            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            /*
            |--------------------------------------------------------------------------
            | Customer Profile
            |--------------------------------------------------------------------------
            */

            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],

            'date_of_birth' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'gender' => [
                'sometimes',
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }
}