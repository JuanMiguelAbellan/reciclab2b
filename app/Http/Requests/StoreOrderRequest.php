<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use App\Models\Offer;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('placeFor', [Order::class, $this->offer()]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'quantity_tons' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $offer = $this->offer();
            $quantity = (float) $this->input('quantity_tons');

            if ($quantity > $offer->availableQuantity()) {
                $validator->errors()->add(
                    'quantity_tons',
                    "Solo quedan {$offer->availableQuantity()} t disponibles.",
                );
            }
        });
    }

    public function offer(): Offer
    {
        $offer = $this->route('offer');

        if ($offer instanceof Offer) {
            return $offer;
        }

        /** @var Conversation $conversation */
        $conversation = $this->route('conversation');

        return $conversation->offer;
    }
}
