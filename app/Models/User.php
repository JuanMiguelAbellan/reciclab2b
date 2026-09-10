<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['first_name', 'last_name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
            'notification_preferences' => 'array',
        ];
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => trim("{$this->first_name} {$this->last_name}"),
        );
    }

    public function isApproved(): bool
    {
        return $this->status === UserStatus::Approved;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isApproved() && $this->isStaff();
    }

    /**
     * Staff roles (superadmin/admin) manage the platform from the Filament
     * panel instead of belonging to a company — used to gate access to
     * /admin (canAccessPanel) and to steer the main app's UI away from
     * company-only features (nav links, dashboard) for these users.
     */
    public function isStaff(): bool
    {
        return $this->hasAnyRole([RoleName::SuperAdmin->value, RoleName::Admin->value]);
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    /**
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withPivot('is_primary')->withTimestamps();
    }

    public function hasApprovedCompany(): bool
    {
        return $this->companies()
            ->where('status', CompanyStatus::Approved->value)
            ->exists();
    }

    /**
     * The company this user belongs to, regardless of whether they're the
     * primary/responsible member or a regular one — a user only ever
     * belongs to one company at a time. Use this for "does this user have
     * a company, and which one" (creating offers/orders/conversations,
     * dashboard). For "is this user allowed to manage the company itself"
     * (edit profile, add/remove members), use primaryCompany() instead.
     */
    public function company(): ?Company
    {
        return $this->companies()->first();
    }

    /**
     * The company this user is the primary/responsible member of — null if
     * they're a regular (non-primary) member, even though they still belong
     * to a company. Only the primary member can manage the company itself
     * (see CompanyPolicy::update). Don't use this to check "does the user
     * have a company" — use company() for that.
     */
    public function primaryCompany(): ?Company
    {
        return $this->companies()
            ->wherePivot('is_primary', true)
            ->first();
    }

    /**
     * IDs of conversations that have a message from the other party newer
     * than this user's last visit (or never visited).
     *
     * @return Collection<int, int>
     */
    public function unreadConversationIds(): Collection
    {
        $companyIds = $this->companies()->pluck('companies.id');

        return Conversation::query()
            ->where(function ($query) use ($companyIds) {
                $query->whereIn('buyer_company_id', $companyIds)
                    ->orWhereHas('offer', fn ($q) => $q->whereIn('company_id', $companyIds));
            })
            ->with(['messages' => fn ($q) => $q->where('sender_id', '!=', $this->id)->orderByDesc('id')->limit(1)])
            ->with(['reads' => fn ($q) => $q->where('user_id', $this->id)])
            ->get()
            ->filter(function (Conversation $conversation) {
                $lastMessage = $conversation->messages->first();

                if ($lastMessage === null) {
                    return false;
                }

                $read = $conversation->reads->first();

                return $read === null || $read->last_read_message_id === null || $read->last_read_message_id < $lastMessage->id;
            })
            ->pluck('id');
    }

    public function unreadConversationsCount(): int
    {
        return $this->unreadConversationIds()->count();
    }
}
