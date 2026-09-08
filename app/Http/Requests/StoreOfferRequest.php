<?php

namespace App\Http\Requests;

use App\Enums\MaterialType;
use App\Models\Offer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Offer::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $currentYear = (int) date('Y');

        return [
            'material' => ['required', new Enum(MaterialType::class)],
            'quantity_tons' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'price_per_ton' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'generation_year' => ['required', 'integer', 'min:2000', 'max:'.($currentYear + 1)],
            'moisture_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'impurities_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'province' => ['required', 'string', 'max:255'],
            'municipality' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'exact_latitude' => ['nullable', 'required_with:exact_longitude', 'numeric', 'between:-90,90'],
            'exact_longitude' => ['nullable', 'required_with:exact_latitude', 'numeric', 'between:-180,180'],
        ];
    }
}
