<?php

namespace App\Http\Requests\client;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
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
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'middle_name' => 'nullable|string',
            // 'phone' => 'required|string|unique:users',
            // 'email' => 'required|string|email|unique:users',
            'password' => 'nullable|string',
            // 'kra_pin' => 'required|string|unique:clients',
            // 'id_no' => 'required|string|unique:clients',
            'post_address' => 'nullable|string',
            'post_code' => 'nullable|string',
            // 'city' => 'required|string',
            // 'county' => 'required|string',
            'country' => 'nullable|',
            'notes' => 'nullable',
            'allow_login' => 'boolean'
        ];
    }
}
