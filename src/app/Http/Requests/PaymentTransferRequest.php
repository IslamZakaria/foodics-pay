<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentTransferRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'reference' => 'required|string|max:255',
            'date' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'sender_account' => 'required|string',
            'receiver_bank_code' => 'required|string',
            'receiver_account' => 'required|string',
            'beneficiary_name' => 'required|string|max:255',
            'notes' => 'nullable|array',
            'notes.*' => 'string',
            'payment_type' => 'nullable|integer',
            'charge_details' => 'nullable|string',
        ];
    }
}