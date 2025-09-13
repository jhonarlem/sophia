<?php

namespace App\Services\Box;

use Exception;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class BoxTableBuilder
{
  /**
   * Configure the box table.
   *
   * @param Table $table
   * @return Table
   */
  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('id')
          ->label('ID')
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('cashRegister.name')
          ->label('Nombre')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->label('Fecha')
          ->date()
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->searchable()
          ->color(fn(string $state): string => match ($state) {
            'Abierta' => 'success',
            'Cerrada' => 'warning',
            default => 'gray',
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('base_amount')
          ->label('Base')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('cash_payment')
          ->label('Ventas')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('cash_electronic_payment')
          ->label('Ventas electrónicas')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('transfer_payment')
          ->label('Transferencia')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('credit_sales')
          ->label('Crédito')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('credit_payment')
          ->label('Recaudo crédito')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('total_other_income')
          ->label('Ingresos')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('total_expenses')
          ->label('Gastos')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('total_sales')
          ->label('Total')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('real_amount')
          ->label('Contado')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('difference')
          ->label('Diferencia')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('openedBy.name')
          ->label('Abierta por')
          ->searchable()
          ->sortable()
          ->default('-')
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('opened_at')
          ->label('Fecha apertura')
          ->dateTime()
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('closedBy.name')
          ->label('Cerrada por')
          ->searchable()
          ->sortable()
          ->default('-')
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('closed_at')
          ->label('Fecha cierre')
          ->dateTime()
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('observations')
          ->label('Notas')
          ->searchable()
          ->sortable()
          ->default('-')
          ->toggleable(isToggledHiddenByDefault: true),
        /*  Tables\Columns\TextColumn::make('created_at')
          ->label('Creado')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true), */
        Tables\Columns\TextColumn::make('updated_at')
          ->label('Actualizado')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('status')
          ->label('Estado')
          ->options([
            'Abierta' => 'Abierta',
            'Cerrada' => 'Cerrada',
          ])
          ->native(false)
          ->placeholder('Selecciona un estado'),
        SelectFilter::make('cashRegister.name')
          ->label('Caja')
          ->relationship('cashRegister', 'name')
          ->native(false)
          ->placeholder('Selecciona una caja'),
      ])
      ->actions([
        ActionGroup::make([
          Tables\Actions\ViewAction::make()
            ->label('Ver')
            ->color('primary')
            ->modalHeading('Detalles del turno')
            ->extraModalFooterActions(
              [
                Action::make('Imprimir')
                  ->label('Imprimir')
                  ->url(fn(Tables\Actions\Action $action) => route('recibo-turno', $action->getRecord()->id))
                  ->openUrlInNewTab(),
              ]
            )
            ->slideOver(),
          Action::make('Imprimir')
            ->label('Imprimir')
            ->icon('heroicon-o-printer')
            ->color('info')
            ->url(fn(Tables\Actions\Action $action) => route('recibo-turno', $action->getRecord()->id))
            ->openUrlInNewTab(),
        ])
          ->icon('heroicon-o-ellipsis-horizontal-circle')
          ->tooltip('Acciones')
          ->size(ActionSize::Large),
      ])
      ->actionsPosition(ActionsPosition::BeforeColumns)
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          ExportBulkAction::make()
            ->label('Exportar a Excel')
            ->exports([
              ExcelExport::make()
                ->fromTable()
                ->except([])
                ->withFilename(fn($resource) => ucfirst($resource::getPluralLabel()) . ' ' . config('app.name')),
            ]),
        ]),
      ])
      ->paginated([5, 10, 25, 50, 100]);
  }
}
