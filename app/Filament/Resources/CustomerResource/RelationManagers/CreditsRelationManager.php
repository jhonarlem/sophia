<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CreditsRelationManager extends RelationManager
{
  protected static string $relationship = 'credits';
  protected static ?string $title = 'Historial de créditos';

  public function form(Form $form): Form
  {
    return $form
      ->schema([]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('sale.invoice_number')
          ->label('Factura')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('customer.name')
          ->label('Cliente')
          ->searchable()
          ->sortable(),
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
        Tables\Columns\TextColumn::make('total_credit')
          ->label('Total crédito')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_paid')
          ->label('Pagado')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_pending')
          ->label('Pendiente')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('user.name')
          ->label('Cajero')
          ->sortable()
          ->searchable(),
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
      ->recordUrl(fn($record) => route('filament.dashboard.resources.creditos.view', $record->id))
      ->filters([
        SelectFilter::make('credits.status')
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
