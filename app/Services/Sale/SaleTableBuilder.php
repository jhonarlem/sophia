<?php

namespace App\Services\Sale;

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

class SaleTableBuilder
{
  /**
   * Configure the sale table.
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
        Tables\Columns\TextColumn::make('customer.name')
          ->label('Cliente')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('box.cashRegister.name')
          ->label('Caja')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('invoice')
          ->label('Factura')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->color(fn($record) => match ($record->status) {
            'Pagada' => 'success',
            'Pendiente' => 'warning',
            'Anulada' => 'danger',
            default => 'gray',
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('total_amount')
          ->label('Total')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('total_iva')
          ->label('IVA')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('total_discount')
          ->label('Descuento')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('payment_method')
          ->label('Método de pago')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('received')
          ->label('Recibido')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('exchange')
          ->label('Cambio')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('sale_date')
          ->label('Fecha')
          ->searchable()
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('user.name')
          ->label('Cajero')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->label('Creado')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
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
            'Pagada' => 'Pagada',
            'Pendiente' => 'Pendiente',
            'Anulada' => 'Anulada',
          ])
          ->native(false)
          ->placeholder('Selecciona un estado'),
        SelectFilter::make('box.cashRegister.name')
          ->label('Caja')
          ->relationship('box.cashRegister', 'name')
          ->native(false)
          ->placeholder('Selecciona una caja'),
      ])
      ->actions([
        ActionGroup::make([
          Tables\Actions\ViewAction::make()
            ->label('Ver')
            ->color('primary'),
          /* ->modalHeading('Detalles del proveedor')
            ->slideOver(), */
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
