<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyStatus;
use App\Enums\CompanyType;
use App\Models\Company;
use App\Notifications\CompanyStatusChangedNotification;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('trade_name')
                    ->label('Nombre comercial')
                    ->searchable(),
                TextColumn::make('tax_id')
                    ->label('CIF/NIF')
                    ->searchable(),
                TextColumn::make('company_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (CompanyType $state): string => $state->label()),
                TextColumn::make('province')
                    ->label('Provincia')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (CompanyStatus $state): string => $state->label())
                    ->color(fn (CompanyStatus $state): string => match ($state) {
                        CompanyStatus::Approved => 'success',
                        CompanyStatus::Pending => 'warning',
                        CompanyStatus::Rejected, CompanyStatus::Blocked => 'danger',
                        CompanyStatus::Inactive => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('Registrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(fn (): array => collect(CompanyStatus::cases())->mapWithKeys(fn (CompanyStatus $case) => [$case->value => $case->label()])->all()),
                SelectFilter::make('company_type')
                    ->label('Tipo')
                    ->options(fn (): array => collect(CompanyType::cases())->mapWithKeys(fn (CompanyType $case) => [$case->value => $case->label()])->all()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Company $record): bool => $record->status === CompanyStatus::Pending)
                    ->authorize('approve')
                    ->requiresConfirmation()
                    ->action(function (Company $record): void {
                        $record->status = CompanyStatus::Approved;
                        $record->approved_at = now();
                        $record->approved_by = auth()->id();
                        $record->save();
                        self::notifyPrimaryMember($record);
                    })
                    ->successNotificationTitle('Empresa aprobada'),
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Company $record): bool => $record->status === CompanyStatus::Pending)
                    ->authorize('approve')
                    ->requiresConfirmation()
                    ->action(function (Company $record): void {
                        $record->status = CompanyStatus::Rejected;
                        $record->save();
                        self::notifyPrimaryMember($record);
                    })
                    ->successNotificationTitle('Empresa rechazada'),
                Action::make('block')
                    ->label('Bloquear')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (Company $record): bool => $record->status === CompanyStatus::Approved)
                    ->authorize('approve')
                    ->requiresConfirmation()
                    ->action(function (Company $record): void {
                        $record->status = CompanyStatus::Blocked;
                        $record->save();
                        self::notifyPrimaryMember($record);
                    })
                    ->successNotificationTitle('Empresa bloqueada'),
                EditAction::make()
                    ->label('Editar'),
            ]);
    }

    private static function notifyPrimaryMember(Company $company): void
    {
        $company->primaryMember()?->notify(new CompanyStatusChangedNotification($company, $company->status));
    }
}
