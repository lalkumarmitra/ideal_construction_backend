<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            "name"=>'nullable|string',
            "gender"=>'nullable|string',
            "dob"=>'nullable|string',
            "avatar"=>'nullable|image|mimes:png,jpg|max:'.(1024*5),
            "role_id"=>'nullable|numeric|exists:roles,id',
            "password"=>'nullable|string'
        ];
    }
}
