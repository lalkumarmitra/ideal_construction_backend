<?php

namespace App\Http\Requests\Transaction;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class TransactionSearchFilterRequest extends FormRequest
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
            'max_loading_quantity'      =>'nullable|numeric',
            'min_loading_quantity'      =>'nullable|numeric',
            'max_unloading_quantity'    =>'nullable|numeric',
            'min_unloading_quantity'    =>'nullable|numeric',
            'max_loading_rate'          =>'nullable|numeric',
            'min_loading_rate'          =>'nullable|numeric',
            'max_unloading_rate'        =>'nullable|numeric',
            'min_unloading_rate'        =>'nullable|numeric',
            'search'                    =>'nullable|string',
            'vehicle'                   =>'nullable|string',
            'txn_type'                  =>'nullable|string|in:'. implode(',',\App\Models\Transaction::TYPE_LIST),
            'loading_date_from'         =>'nullable|date',
            'loading_date_to'           =>'nullable|date',
            'unloading_date_from'       =>'nullable|date',
            'unloading_date_to'         =>'nullable|date',
            'is_sold'                   =>'nullable|boolean',
            'loading_client_size'       =>'nullable|string|in:'. implode(',',Client::SIZE_LIST),
            'unloading_client_size'     =>'nullable|string|in:'. implode(',',Client::SIZE_LIST),
            'loading_client_sizes'      =>'nullable|array',
            'loading_client_sizes.*'    =>'string|in:'. implode(',',Client::SIZE_LIST),
            'unloading_client_sizes'    =>'nullable|array',
            'unloading_client_sizes.*'  =>'string|in:'. implode(',',Client::SIZE_LIST),
            'product_ids'               =>'nullable|array',
            'product_ids.*'             =>'integer|exists:products,id',
            'loading_point_ids'         =>'nullable|array',
            'loading_point_ids.*'       =>'integer|exists:clients,id',
            'unloading_point_ids'       =>'nullable|array',
            'unloading_point_ids.*'     =>'integer|exists:clients,id',
            'loading_vehicle_ids'       =>'nullable|array',
            'loading_vehicle_ids.*'     =>'integer|exists:vehicles,id',
            'unloading_vehicle_ids'     =>'nullable|array',
            'unloading_vehicle_ids.*'   =>'integer|exists:vehicles,id',
        ];
    }
}

  
