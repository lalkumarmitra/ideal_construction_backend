<?php

namespace App\Http\Requests\Transaction;

use App\Models\Client;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TransactionExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return Auth::user()->role->priority <= 2;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array {
        return [
            // Date range filters
            'loading_date_from' => 'nullable|date',
            'loading_date_to' => 'nullable|date',
            'unloading_date_from' => 'nullable|date',
            'unloading_date_to' => 'nullable|date',
            
            // ID filters
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'loading_point_ids' => 'nullable|array',
            'loading_point_ids.*' => 'exists:clients,id',
            'unloading_point_ids' => 'nullable|array',
            'unloading_point_ids.*' => 'exists:clients,id',
            'loading_vehicle_ids' => 'nullable|array',
            'loading_vehicle_ids.*' => 'exists:vehicles,id',
            'loading_vehicle_ids' => 'nullable|array',
            'loading_vehicle_ids.*' => 'exists:vehicles,id',
            'unloading_vehicle_ids' => 'nullable|array',
            'unloading_vehicle_ids.*' => 'exists:vehicles,id',
            
            // Quantity filters
            'max_loading_quantity' => 'nullable|numeric',
            'min_loading_quantity' => 'nullable|numeric',
            'max_unloading_quantity' => 'nullable|numeric',
            'min_unloading_quantity' => 'nullable|numeric',
            
            // Rate filters
            'max_loading_rate' => 'nullable|numeric',
            'min_loading_rate' => 'nullable|numeric',
            'max_unloading_rate' => 'nullable|numeric',
            'min_unloading_rate' => 'nullable|numeric',
            
            // Keyword searches
            'search' => 'nullable|string',
            'vehicle' => 'nullable|string',
            
            // Client size filters
            'loading_client_size' => ['nullable', Rule::in(Client::SIZE_LIST)],
            'unloading_client_size' => ['nullable', Rule::in(Client::SIZE_LIST)],
            'loading_client_sizes' => 'nullable|array',
            'loading_client_sizes.*' => [Rule::in(Client::SIZE_LIST)],
            'unloading_client_sizes' => 'nullable|array',
            'unloading_client_sizes.*' => [Rule::in(Client::SIZE_LIST)],
            
            // Transaction type filter
            'txn_type' => ['nullable', Rule::in(Transaction::TYPE_LIST)],
            
            // Sold status filter
            'is_sold' => 'nullable|boolean',
            
            // Excel columns selection
            'columns' => 'nullable|array',
            'columns.*' => [
                'string',
                Rule::in([
                    'loading_point', 'unloading_point', 'loading_vehicle', 'unloading_vehicle','vehicle',
                    'product', 'loading_rate', 'unloading_rate', 'loading_quantity', 'unloading_quantity',
                    'loading_price', 'unloading_price', 'loading_vehicle_number', 'unloading_vehicle_number',
                    'loading_date', 'unloading_date', 'transaction_id', 'do_number', 'challan_number', 'status',
                    'transport_expense','loading_driver','unloading_driver'
                ])
            ],
            // File format and name options
            'file_name' => 'nullable|string|max:100',
            'file_format' => ['nullable', Rule::in(['xlsx', 'csv'])],
            
            //shorting configuration
            'sort_field' => 'required_with:sort_order|in:id,loading_date,unloading_date,loading_quantity,unloading_quantity,transport_expense',
            'sort_order' => 'required_with:sort_field|in:asc,desc',
            
        ];
    }
}