<?php

namespace App\Filament\Resources\SaleResource\Widgets;

use App\Models\Sale;
use Filament\Actions\ActionGroup;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SalesWidget extends BaseWidget
{
  protected static ?string $pollingInterval = null;
  protected int | string | array $columnSpan = 'full';
  protected static ?string $heading = 'Historial de ventas';

  public function table(Table $table): Table
  {
    return $table
      ->query(Sale::query()->orderBy('created_at', 'desc')->limit(5))
      ->columns([
        Tables\Columns\TextColumn::make('id')
          ->label('ID')
          ->searchable(),
        Tables\Columns\TextColumn::make('box.name')
          ->label('Caja')
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('customer.name')
          ->label('Cliente')
          ->searchable(),
        Tables\Columns\TextColumn::make('invoice_number')
          ->label('Factura')
          ->formatStateUsing(fn($record) => $record->box->resolution->prefix . '-' . $record->invoice_number)

          ->searchable(),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->color(fn($record) => match ($record->status) {
            'Pagada' => 'success',
            'Pendiente' => 'warning',
            'Anulada' => 'danger',
          })

          ->searchable(),
        Tables\Columns\TextColumn::make('total_amount')
          ->label('Total')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          }),
        Tables\Columns\TextColumn::make('total_iva')
          ->label('IVA')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          }),
        Tables\Columns\TextColumn::make('total_discount')
          ->label('Descuento')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          }),
        Tables\Columns\TextColumn::make('payment_method')
          ->label('Método de pago')

          ->searchable(),
        Tables\Columns\TextColumn::make('received')
          ->label('Recibido')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          }),
        Tables\Columns\TextColumn::make('exchange')
          ->label('Cambio')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          }),
        Tables\Columns\TextColumn::make('sale_date')
          ->label('Fecha')
          ->searchable()
          ->dateTime(),
        Tables\Columns\TextColumn::make('user.name')
          ->label('Cajero')
          ->searchable(),
        Tables\Columns\TextColumn::make('created_at')
          ->label('Creado')
          ->dateTime()

          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('updated_at')
          ->label('Actualizado')
          ->dateTime()

          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->recordUrl(fn($record) => route('filament.dashboard.resources.ventas.view', $record))
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('status')
          ->label('Estado')
          ->options([
            'Pagada' => 'Pagada',
            'Pendiente' => 'Pendiente',
            'Anulada' => 'Anulada',
          ])
          ->searchable()
          ->preload()
          ->placeholder('Selecciona un estado'),
      ])
      ->paginated(false);
  }
}
