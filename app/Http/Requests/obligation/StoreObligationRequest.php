<?php

namespace App\Http\Requests\obligation;

use Illuminate\Foundation\Http\FormRequest;

class StoreObligationRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'fee' => 'required|numeric|min:0',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|integer|in:0,1', // Assuming type is a string enum
            'privacy' => 'required|boolean',
            'start_date' => 'required|date',
            'frequency' => 'required|integer|in:0,1,2,3,4,5',
            'status' => 'required|boolean',
            'is_recurring' => 'required|boolean',
            'client_id' => 'nullable|exists:clients,id', // Assuming client_id should exist in clients table
            'company_id' => 'nullable|exists:companies,id', // Assuming company_id should exist in companies table
            'service_ids' => 'nullable|array', // Assuming service_ids is an array
            'service_ids.*' => 'exists:services,id',
//            'name' => 'required|string|max:255',
//            'description' => 'nullable|string',
//            'fee' => 'required|numeric|min:0',
//            'amount' => 'required|numeric|min:0',
//            'type' => 'required|integer|in:0,1',
//            'privacy' => 'required|boolean',
//            'start_date' => 'required|date',
//            'frequency' => 'required|integer|in:0,1,2,3,4,5',
//            'next_run' => 'nullable|date',
//            'status' => 'required|integer|in:0,1',
//            'is_recurring' => 'required|boolean',
        ];
    }
}
