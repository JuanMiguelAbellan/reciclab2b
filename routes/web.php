<?php

use App\Http\Controllers\AccountStatusController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyInvitationAcceptanceController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/acerca-de', function () {
    return Inertia::render('About');
});

Route::middleware('signed')->group(function () {
    Route::get('/invitaciones/{invitation}/aceptar', [CompanyInvitationAcceptanceController::class, 'show'])->name('invitations.accept');
    Route::post('/invitaciones/{invitation}/aceptar', [CompanyInvitationAcceptanceController::class, 'store'])->name('invitations.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/cuenta/estado', AccountStatusController::class)->name('account.status');

    Route::middleware('approved')->group(function () {
        Route::get('/empresa/crear', [CompanyController::class, 'create'])->name('companies.create');
        Route::post('/empresa', [CompanyController::class, 'store'])->name('companies.store');
    });
});

Route::middleware(['auth', 'verified', 'approved', 'has-company'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('empresa')->name('companies.')->group(function () {
        Route::get('/', [CompanyController::class, 'edit'])->name('edit');
        Route::put('/{company}', [CompanyController::class, 'update'])->name('update');
        Route::post('/{company}/miembros', [CompanyController::class, 'addMember'])->name('members.store');
        Route::delete('/{company}/miembros/{member}', [CompanyController::class, 'removeMember'])->name('members.destroy');
        Route::post('/{company}/invitaciones', [CompanyController::class, 'inviteMember'])->name('invitations.store');
        Route::delete('/{company}/invitaciones/{invitation}', [CompanyController::class, 'revokeInvitation'])->name('invitations.destroy');
    });

    Route::prefix('ofertas')->name('offers.')->group(function () {
        Route::get('/', [OfferController::class, 'index'])->name('index');
        Route::get('/crear', [OfferController::class, 'create'])->name('create');
        Route::post('/', [OfferController::class, 'store'])->name('store');
        Route::get('/{offer}/editar', [OfferController::class, 'edit'])->name('edit');
        Route::put('/{offer}', [OfferController::class, 'update'])->name('update');
        Route::delete('/{offer}', [OfferController::class, 'destroy'])->name('destroy');
        Route::post('/{offer}/publicar', [OfferController::class, 'publish'])->name('publish');
        Route::post('/{offer}/pausar', [OfferController::class, 'pause'])->name('pause');
        Route::post('/{offer}/cerrar', [OfferController::class, 'close'])->name('close');
    });

    Route::prefix('mercado')->name('market.')->group(function () {
        Route::get('/', [MarketplaceController::class, 'index'])->name('index');
        Route::get('/{offer}', [MarketplaceController::class, 'show'])->name('show');
        Route::post('/{offer}/contactar', [ConversationController::class, 'store'])->name('contact');
        Route::post('/{offer}/pedidos', [OrderController::class, 'storeForOffer'])->name('orders.store');
    });

    Route::prefix('mensajes')->name('conversations.')->group(function () {
        Route::get('/', [ConversationController::class, 'index'])->name('index');
        Route::get('/{conversation}', [ConversationController::class, 'show'])->name('show');
        Route::post('/{conversation}/mensajes', [ConversationController::class, 'storeMessage'])->name('messages.store');
        Route::post('/{conversation}/pedidos', [OrderController::class, 'storeForConversation'])->name('orders.store');
    });

    Route::prefix('pedidos')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/aceptar', [OrderController::class, 'accept'])->name('accept');
        Route::post('/{order}/rechazar', [OrderController::class, 'reject'])->name('reject');
        Route::post('/{order}/completar', [OrderController::class, 'complete'])->name('complete');
        Route::post('/{order}/cancelar', [OrderController::class, 'cancel'])->name('cancel');
    });
});
