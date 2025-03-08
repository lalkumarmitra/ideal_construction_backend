<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateNewUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role->priority <= 2;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'=>'required|string',
            'email'=>'nullable|email',
            'gender'=>'nullable|in:male,female,other',
            'dob'=>'nullable|date_format:Y-m-d',
            'phone'=>'required|numeric|unique:users,phone',
            'password'=>'nullable|string|min:6|max:64',
            'avatar'=>'nullable|image|mimes:png,jpg',
            'role_id'=>'required|numeric|exists:roles,id'
        ];
    }
}
