<?php

namespace App\Http\Controllers;

use App\Enums\MaterialType;
use App\Models\Conversation;
use App\Models\Offer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Offer::class);

        $filters = $request->validate([
            'material' => ['nullable', new Enum(MaterialType::class)],
            'province' => ['nullable', 'string', 'max:255'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $offers = Offer::visibleInMarket()
            ->with('company:id,trade_name,province')
            ->when($filters['material'] ?? null, fn ($query, $material) => $query->where('material', $material))
            ->when($filters['province'] ?? null, fn ($query, $province) => $query->where('province', $province))
            ->when($filters['min_price'] ?? null, fn ($query, $value) => $query->where('price_per_ton', '>=', $value))
            ->when($filters['max_price'] ?? null, fn ($query, $value) => $query->where('price_per_ton', '<=', $value))
            ->when($filters['min_quantity'] ?? null, fn ($query, $value) => $query->where('quantity_tons', '>=', $value))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('market/Index', [
            'offers' => $offers->through($this->present(...)),
            'filters' => [
                'material' => $filters['material'] ?? '',
                'province' => $filters['province'] ?? '',
                'min_price' => $filters['min_price'] ?? '',
                'max_price' => $filters['max_price'] ?? '',
                'min_quantity' => $filters['min_quantity'] ?? '',
            ],
            'materialTypes' => $this->materialTypeOptions(),
            'provinces' => Offer::visibleInMarket()->distinct()->orderBy('province')->pluck('province'),
        ]);
    }

    public function show(Request $request, Offer $offer): Response
    {
        Gate::authorize('viewAny', Offer::class);

        abort_unless($offer->isTradeable(), 404);

        $offer->load('company');

        return Inertia::render('market/Show', [
            'offer' => [
                ...$this->present($offer),
                'description' => $offer->description,
                'company' => [
                    'trade_name' => $offer->company->trade_name,
                    'municipality' => $offer->company->municipality,
                    'province' => $offer->company->province,
                    'phone' => $offer->company->phone,
                    'email' => $offer->company->email,
                    'contact_person' => $offer->company->contact_person,
                ],
            ],
            'canContact' => $request->user()->can('startFor', [Conversation::class, $offer]),
            'canOrder' => $request->user()->can('placeFor', [Order::class, $offer]),
            'availableQuantity' => $offer->availableQuantity(),
        ]);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function materialTypeOptions(): array
    {
        return collect(MaterialType::cases())
            ->map(fn (MaterialType $material) => ['value' => $material->value, 'label' => $material->label()])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Offer $offer): array
    {
        return [
            'id' => $offer->id,
            'material' => ['value' => $offer->material->value, 'label' => $offer->material->label()],
            'quantity_tons' => (float) $offer->quantity_tons,
            'price_per_ton' => (float) $offer->price_per_ton,
            'generation_year' => $offer->generation_year,
            'moisture_percentage' => $offer->moisture_percentage !== null ? (float) $offer->moisture_percentage : null,
            'impurities_percentage' => $offer->impurities_percentage !== null ? (float) $offer->impurities_percentage : null,
            'province' => $offer->province,
            'municipality' => $offer->municipality,
            'company_name' => $offer->company->trade_name,
            'public_latitude' => $offer->public_latitude !== null ? (float) $offer->public_latitude : null,
            'public_longitude' => $offer->public_longitude !== null ? (float) $offer->public_longitude : null,
        ];
    }
}
