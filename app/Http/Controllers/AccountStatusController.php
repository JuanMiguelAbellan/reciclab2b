<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountStatusController extends Controller
{
    /**
     * Show why the current user cannot access the application yet: either
     * their own account or their company is not approved.
     */
    public function __invoke(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user->status !== UserStatus::Approved) {
            return Inertia::render('AccountStatus', [
                'scope' => 'user',
                'status' => $user->status->value,
                'statusLabel' => $user->status->label(),
            ]);
        }

        $company = $user->companies()->first();

        // This route only requires `auth`, not `has-company` — reachable
        // directly by staff (no company by design) or by a freshly
        // approved user who hasn't created a company yet. /dashboard's own
        // middleware sends each of those exactly where they belong.
        if ($company === null) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('AccountStatus', [
            'scope' => 'company',
            'status' => $company->status->value,
            'statusLabel' => $company->status->label(),
            'companyName' => $company->trade_name,
        ]);
    }
}
