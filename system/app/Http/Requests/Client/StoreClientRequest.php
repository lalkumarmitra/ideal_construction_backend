<?php

namespace App\Http\Requests\Client;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role->priority<=2;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name"=>'required|string',
            "address"=>'nullable|string',
            "state"=>'nullable|string',
            "pin"=>'nullable|string',
            "type"=>'required|string|in:'.implode(',',Client::TYPE_LIST),
            "image"=>'nullable|image|mimes:png,jpg|max:'.(1024*5),
            "client_size"=>'required|string|in:'.implode(',',Client::SIZE_LIST),
        ];
    }
}
