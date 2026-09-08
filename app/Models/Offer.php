<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\MaterialType;
use App\Enums\OfferStatus;
use App\Enums\OrderStatus;
use App\Support\ApproximateLocation;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'material',
    'quantity_tons',
    'price_per_ton',
    'generation_year',
    'moisture_percentage',
    'impurities_percentage',
    'province',
    'municipality',
    'description',
    'exact_latitude',
    'exact_longitude',
])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'material' => MaterialType::class,
            'status' => OfferStatus::class,
            'quantity_tons' => 'decimal:2',
            'price_per_ton' => 'decimal:2',
            'moisture_percentage' => 'decimal:2',
            'impurities_percentage' => 'decimal:2',
            'exact_latitude' => 'decimal:7',
            'exact_longitude' => 'decimal:7',
            'public_latitude' => 'decimal:7',
            'public_longitude' => 'decimal:7',
            'published_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function hasLocation(): bool
    {
        return $this->public_latitude !== null && $this->public_longitude !== null;
    }

    /**
     * Recompute the public (approximate) coordinates from the exact ones.
     * The offset is deterministic, so it stays put between page loads and
     * only moves if the exact location itself changes.
     */
    public function refreshPublicLocation(): void
    {
        if ($this->exact_latitude === null || $this->exact_longitude === null) {
            $this->public_latitude = null;
            $this->public_longitude = null;

            return;
        }

        $seed = "company:{$this->company_id}:{$this->exact_latitude}:{$this->exact_longitude}";

        $approximate = ApproximateLocation::displace(
            (float) $this->exact_latitude,
            (float) $this->exact_longitude,
            $seed,
        );

        $this->public_latitude = $approximate['latitude'];
        $this->public_longitude = $approximate['longitude'];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->status === OfferStatus::Draft;
    }

    public function isPublished(): bool
    {
        return $this->status === OfferStatus::Published;
    }

    public function isPaused(): bool
    {
        return $this->status === OfferStatus::Paused;
    }

    public function isClosed(): bool
    {
        return $this->status === OfferStatus::Closed;
    }

    /**
     * Whether a buyer can currently contact the seller or place an order
     * about this offer: published, and the seller's company still in good
     * standing. Checked at the point of action (not just once when the
     * offer was published), so a company blocked after publishing offers
     * can't keep trading through them.
     */
    public function isTradeable(): bool
    {
        return $this->isPublished() && $this->company->isApproved();
    }

    /**
     * @param  Builder<Offer>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', OfferStatus::Published);
    }

    /**
     * Published offers whose seller company is still approved — what the
     * marketplace should actually list. Kept separate from `published()`
     * (which reflects the offer's own lifecycle only) so the two concerns
     * — "is this offer published" vs. "should it be visible to buyers" —
     * don't get conflated.
     *
     * @param  Builder<Offer>  $query
     */
    #[Scope]
    protected function visibleInMarket(Builder $query): void
    {
        $query->published()->whereHas('company', fn ($q) => $q->where('status', CompanyStatus::Approved));
    }

    /**
     * @return HasMany<Conversation, $this>
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Quantity still available to order: the listed quantity minus whatever
     * is already committed (accepted or completed orders). Pending orders
     * don't reserve quantity — availability is re-checked at accept time.
     */
    public function availableQuantity(): float
    {
        $committed = $this->orders()
            ->whereIn('status', [OrderStatus::Accepted, OrderStatus::Completed])
            ->sum('quantity_tons');

        return max(0.0, (float) $this->quantity_tons - (float) $committed);
    }
}
