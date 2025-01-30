<?php

namespace App\Http\Requests\client;

use App\Models\Client;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
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
        $clientId = $this->route('client');

        \Log::info('Client ID in request:', ['clientId' => $clientId]);
        $client = Client::find($clientId);

        // If the client is not found, throw a validation exception
        if (!$client) {
            \Log::error('Client not found in validation rules:', ['clientId' => $clientId]);
            abort(404, 'Client not found');
        }

        $userId = $client->user_id;
        return [
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'middle_name' => 'nullable|string',
//             'phone' => [
//                 'required',
//                 'string',
// //                Rule::unique('users')->ignore($userId),
//             ],
            // 'email' => [
            //     'required',
            //     'string',
            //     'email',
            //     Rule::unique('users')->ignore($userId),
            // ],
            'password' => 'nullable|string',
            // 'kra_pin' => [
            //     'required',
            //     'string',
            //     Rule::unique('clients')->ignore($clientId),
            // ],
            // 'id_no' => [
            //     'required',
            //     'string',
            //     Rule::unique('clients')->ignore($clientId),
            // ],
            'post_address' => 'nullable|string',
            'post_code' => 'nullable|string',
            // 'city' => 'required|string',
            // 'county' => 'required|string',
            'country' => 'nullable|string',
            'notes' => 'nullable|string',
            'allow_login' => 'boolean',
        ];
    }
}
