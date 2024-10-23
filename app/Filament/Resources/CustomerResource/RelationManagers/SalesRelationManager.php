<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesRelationManager extends RelationManager
{
  protected static string $relationship = 'sales';
  protected static ?string $title = 'Historial de compras';


  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('Historial de compras')
      ->columns([
        Tables\Columns\TextColumn::make('id')
          ->label('ID')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('box.name')
          ->label('Caja')
          ->numeric()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('customer.name')
          ->label('Cliente')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('invoice_number')
          ->label('Factura')
          ->formatStateUsing(fn($record) => $record->box->resolution->prefix . '-' . $record->invoice_number)
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->color(fn($record) => match ($record->status) {
            'Pagada' => 'success',
            'Pendiente' => 'warning',
            'Anulada' => 'danger',
          })
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('total_amount')
          ->label('Total')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_iva')
          ->label('IVA')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_discount')
          ->label('Descuento')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('payment_method')
          ->label('Método de pago')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('received')
          ->label('Recibido')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('exchange')
          ->label('Cambio')
          ->searchable()
          ->numeric()
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
      // Redirigir a la vista de la venta
      ->recordUrl(fn($record) => route('filament.dashboard.resources.ventas.view', $record->id))
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
      ]);
  }
}
