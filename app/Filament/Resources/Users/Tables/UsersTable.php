<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\UserStatusChangedNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('first_name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Apellidos')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RoleName::tryFrom($state)?->label() ?? $state),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label())
                    ->color(fn (UserStatus $state): string => match ($state) {
                        UserStatus::Approved => 'success',
                        UserStatus::Pending => 'warning',
                        UserStatus::Rejected, UserStatus::Blocked => 'danger',
                        UserStatus::Inactive => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(fn (): array => collect(UserStatus::cases())->mapWithKeys(fn (UserStatus $case) => [$case->value => $case->label()])->all()),
                SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => RoleName::tryFrom($record->name)?->label() ?? $record->name),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Pending)
                    ->authorize('approve')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->status = UserStatus::Approved;
                        $record->approved_at = now();
                        $record->approved_by = auth()->id();
                        $record->save();
                        $record->notify(new UserStatusChangedNotification($record->status));
                    })
                    ->successNotificationTitle('Usuario aprobado'),
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Pending)
                    ->authorize('approve')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->status = UserStatus::Rejected;
                        $record->save();
                        $record->notify(new UserStatusChangedNotification($record->status));
                    })
                    ->successNotificationTitle('Usuario rechazado'),
                Action::make('block')
                    ->label('Bloquear')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (User $record): bool => $record->status === UserStatus::Approved)
                    ->authorize('approve')
                    ->requiresConfirmation()
                    ->action(function (User $record): void {
                        $record->status = UserStatus::Blocked;
                        $record->save();
                        $record->notify(new UserStatusChangedNotification($record->status));
                    })
                    ->successNotificationTitle('Usuario bloqueado'),
                EditAction::make()
                    ->label('Editar'),
            ]);
    }
}
