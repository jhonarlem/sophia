<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleItemsRelationManager extends RelationManager
{
  protected static string $relationship = 'saleItems';
  protected static ?string $title = 'Productos';


  public function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('product.name')
          ->label('Producto')
          ->searchable(),
        Tables\Columns\TextColumn::make('quantity')
          ->label('Cantidad')
          ->numeric()
          ->searchable(),
        Tables\Columns\TextColumn::make('unit_price')
          ->label('Precio unitario')
          ->numeric()
          ->searchable(),
        Tables\Columns\TextColumn::make('iva')
          ->label('IVA')
          ->numeric()
          ->searchable(),
        Tables\Columns\TextColumn::make('discount')
          ->label('Descuento')
          ->numeric()
          ->searchable(),
        Tables\Columns\TextColumn::make('subtotal')
          ->label('Subtotal')
          ->numeric()
          ->searchable(),
        Tables\Columns\TextColumn::make('total')
          ->label('Total')
          ->numeric()
          ->searchable(),
      ]);
  }
}
