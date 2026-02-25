<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PaymentMethodType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Services\Shared\ResponseService;

class CreatePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Methods that require a specific channel/bank/provider
        $channelRequiredMethods = ['virtual_account', 'e_wallet', 'paylater'];

        return [
            // merchant_ref: order ID from client system. Must be unique per client
            // for non-terminal transactions (idempotency enforced in controller).
            'merchant_ref'    => ['required', 'string', 'max:255', 'regex:/^[\w\-\.]+$/'],
            // merchant_code: optional outlet/agent/location code of the client.
            // If not provided, defaults to the client's first active outlet.
            'merchant_code'   => ['nullable', 'string', 'max:50'],
            'payment_method'  => ['required', 'string', 'in:' . implode(',', PaymentMethodType::values())],
            // payment_channel is required for VA (which bank), e-wallet (which wallet), paylater (which provider).
            // QRIS has only one channel so it's not required.
            'payment_channel' => [
                Rule::requiredIf(fn() => in_array($this->input('payment_method'), $channelRequiredMethods)),
                'nullable', 'string', 'max:50',
            ],
            'amount'          => ['required', 'integer', 'min:1'],
            'currency'        => ['nullable', 'string', 'in:IDR'],
            'expired_at'      => ['nullable', 'date', 'after:now', 'before:' . now()->addDays(30)->toDateTimeString()],
            'customer'        => ['required', 'array'],
            'customer.name'   => ['required', 'string', 'max:255'],
            'customer.email'  => ['required', 'email', 'max:255'],
            'customer.phone'  => ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9]{8,15}$/'],
            'items'           => ['nullable', 'array'],
            'items.*.name'    => ['required_with:items', 'string', 'max:255'],
            'items.*.qty'     => ['required_with:items', 'integer', 'min:1'],
            'items.*.price'   => ['required_with:items', 'integer', 'min:0'],
            'metadata'        => ['nullable', 'array'],
            'callback_url'    => ['nullable', 'url', 'max:1000'],
            'redirect_url'    => ['nullable', 'url', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'merchant_ref.regex'    => 'merchant_ref may only contain letters, numbers, dashes, underscores, and dots.',
            'customer.phone.regex'  => 'customer.phone must be a valid phone number (8–15 digits, optional leading +).',
            'payment_channel.required_if' => 'payment_channel is required for ' . implode(', ', ['virtual_account', 'e_wallet', 'paylater']) . '.',
            'expired_at.before'     => 'expired_at cannot exceed 30 days from now.',
            'amount.min'            => 'amount must be at least 1.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ResponseService::validationError($validator->errors()->toArray())
        );
    }
}
