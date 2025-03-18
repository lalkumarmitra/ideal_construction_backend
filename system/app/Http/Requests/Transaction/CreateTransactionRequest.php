<?php

namespace App\Http\Requests\Transaction;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role->priority <=2;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "product_id" => 'required|numeric|exists:products,id',
            "loading_point_id"=>'required|numeric|exists:clients,id',
            "loading_vehicle_id"=>'required|numeric|exists:vehicles,id',
            "loading_date"=>'nullable|date_format:Y-m-d',
            "loading_rate"=>'nullable|numeric',
            "loading_quantity"=>'nullable|numeric',
            "unloading_point_id"=>'nullable|numeric|exists:clients,id',
            "unloading_vehicle_id"=>'nullable|numeric|exists:vehicles,id',
            "unloading_date"=>'nullable|date_format:Y-m-d',
            "unloading_rate"=>'nullable|numeric',
            "unloading_quantity"=>'nullable|numeric',

            "do_number"=>'nullable|string',
            "challan_number"=>'nullable|string',
            "txn_type"=>'nullable|string',

            "transport_expense"=>'nullable|numeric',
            "loading_driver_id"=>'nullable|numeric|exists:users,id',
            "unloading_driver_id"=>'nullable|numeric|exists:users,id',

            "unit" => 'nullable|string|in:'.implode(',',Transaction::UNIT_LIST)
        ];
    }
}
