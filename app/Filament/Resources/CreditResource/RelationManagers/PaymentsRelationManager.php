<?php

namespace App\Filament\Resources\CreditResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
  protected static string $relationship = 'payments';
  protected static ?string $title = 'Historial de pagos';

  // Mostrar los campos de la relación en el formulario
  public function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('sale_id')
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('payment_method')
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('reference')
          ->required()
          ->maxLength(255),
        Forms\Components\TextInput::make('amount')
          ->required()
          ->maxLength(255),
        Forms\Components\DateTimePicker::make('payment_date')
          ->required(),
      ]);
  }

  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('sale.full_invoice_number')
          ->label('Factura')
          ->searchable(),
        Tables\Columns\TextColumn::make('payment_method')
          ->label('Método de pago')
          ->searchable(),
        Tables\Columns\TextColumn::make('reference')
          ->label('Referencia')
          ->searchable(),
        Tables\Columns\TextColumn::make('amount')
          ->label('Monto')
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->searchable(),
        Tables\Columns\TextColumn::make('payment_date')
          ->label('Fecha de pago')
          ->dateTime()
          ->searchable(),
      ])
      ->defaultSort('created_at', 'desc');
  }
}
