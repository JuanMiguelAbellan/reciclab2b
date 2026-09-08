<?php

namespace App\Http\Controllers;

use App\Actions\Offers\CloseOfferAction;
use App\Actions\Offers\CreateOfferAction;
use App\Actions\Offers\PauseOfferAction;
use App\Actions\Offers\PublishOfferAction;
use App\Actions\Offers\UpdateOfferAction;
use App\Enums\MaterialType;
use App\Http\Requests\StoreOfferRequest;
use App\Http\Requests\UpdateOfferRequest;
use App\Models\Offer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OfferController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Offer::class);

        $company = $request->user()->company();

        $offers = $company
            ? $company->offers()->latest()->get()
            : collect();

        return Inertia::render('offers/Index', [
            'offers' => $offers->map($this->present(...)),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Offer::class);

        return Inertia::render('offers/Create', [
            'materialTypes' => $this->materialTypeOptions(),
        ]);
    }

    public function store(StoreOfferRequest $request, CreateOfferAction $action): RedirectResponse
    {
        $company = $request->user()->company();

        abort_if($company === null, 403);

        $action->handle($request->user(), $company, $request->validated());

        return redirect()->route('offers.index')->with('success', 'Oferta guardada como borrador.');
    }

    public function edit(Offer $offer): Response
    {
        Gate::authorize('update', $offer);

        return Inertia::render('offers/Edit', [
            'offer' => $this->present($offer),
            'materialTypes' => $this->materialTypeOptions(),
        ]);
    }

    public function update(UpdateOfferRequest $request, Offer $offer, UpdateOfferAction $action): RedirectResponse
    {
        $action->handle($offer, $request->validated());

        return redirect()->route('offers.index')->with('success', 'Oferta actualizada.');
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        Gate::authorize('delete', $offer);

        $offer->delete();

        return redirect()->route('offers.index')->with('success', 'Borrador eliminado.');
    }

    public function publish(Offer $offer, PublishOfferAction $action): RedirectResponse
    {
        Gate::authorize('manage', $offer);

        $action->handle($offer);

        return redirect()->route('offers.index')->with('success', 'Oferta publicada.');
    }

    public function pause(Offer $offer, PauseOfferAction $action): RedirectResponse
    {
        Gate::authorize('manage', $offer);

        $action->handle($offer);

        return redirect()->route('offers.index')->with('success', 'Oferta pausada.');
    }

    public function close(Request $request, Offer $offer, CloseOfferAction $action): RedirectResponse
    {
        Gate::authorize('manage', $offer);

        $action->handle($request->user(), $offer);

        return redirect()->route('offers.index')->with('success', 'Oferta cerrada.');
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
            'description' => $offer->description,
            'status' => ['value' => $offer->status->value, 'label' => $offer->status->label()],
            'exact_latitude' => $offer->exact_latitude !== null ? (float) $offer->exact_latitude : null,
            'exact_longitude' => $offer->exact_longitude !== null ? (float) $offer->exact_longitude : null,
        ];
    }
}
