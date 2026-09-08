<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'trade_name',
    'legal_name',
    'tax_id',
    'company_type',
    'address',
    'postal_code',
    'municipality',
    'province',
    'autonomous_community',
    'country',
    'phone',
    'email',
    'website',
    'contact_person',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'company_type' => CompanyType::class,
            'status' => CompanyStatus::class,
            'approved_at' => 'datetime',
            'is_current_client' => 'boolean',
            'is_current_supplier' => 'boolean',
            'is_managed_by_admin' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_primary')->withTimestamps();
    }

    public function isApproved(): bool
    {
        return $this->status === CompanyStatus::Approved;
    }

    public function primaryMember(): ?User
    {
        return $this->users()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * @return HasMany<Offer, $this>
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * @return HasMany<CompanyInvitation, $this>
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(CompanyInvitation::class);
    }
}
