<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LossResource\Pages;
use App\Filament\Resources\LossResource\RelationManagers;
use App\Models\Loss;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class LossResource extends Resource
{
  protected static ?string $model = Loss::class;

  protected static ?int $navigationSort = 6;
  protected static ?string $navigationIcon = 'heroicon-o-archive-box-x-mark';
  protected static $title = 'Pérdidas';
  protected static ?string $label = 'pérdida';
  protected static ?string $pluralLabel = 'pérdidas';
  protected static ?string $slug = 'perdidas';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Section::make('Información de la pérdida')
          ->columns(2)
          ->schema([
            Select::make('product_id')
              ->label('Producto')
              ->relationship('product', 'name')
              ->required()
              ->searchable()
              ->preload()
              ->placeholder('Selecciona un producto')
              ->getOptionLabelFromRecordUsing(fn($record) => "{$record->sku} - {$record->name}"),
            Forms\Components\TextInput::make('quantity')
              ->label('Cantidad')
              ->required()
              ->autocomplete(false)
              ->numeric(),
            Forms\Components\Textarea::make('description')
              ->label('Descripción')
              ->required()
              ->autocomplete(false)
              ->columnSpanFull(),
          ])

      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('id')
          ->label('ID')
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('product.name')
          ->label('Producto')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('quantity')
          ->label('Cantidad')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('unit_price')
          ->label('Precio compra')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('total')
          ->label('Total')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('description')
          ->label('Descripción')
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('user.name')
          ->label('Usuario')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->label('Creado')
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('updated_at')
          ->label('Actualizado')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->recordUrl(null)
      ->defaultSort('created_at', 'desc')
      ->filters([
        //
      ])
      ->actions([
        ActionGroup::make([
          Tables\Actions\ViewAction::make()->label('Ver')->color('primary')
        ])
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          ExportBulkAction::make(),
        ]),
      ]);
  }

  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        TextEntry::make('id')
          ->label('ID:'),
        TextEntry::make('product.name')
          ->label('Producto:'),
        TextEntry::make('quantity')
          ->label('Cantidad:'),
        TextEntry::make('unit_price')
          ->label('Precio compra:')
          ->numeric(),
        TextEntry::make('total')
          ->label('Total:')
          ->numeric(),
        TextEntry::make('description')
          ->label('Descripción:'),
        TextEntry::make('user.name')
          ->label('Usuario:'),
        TextEntry::make('created_at')
          ->label('Creado:')
          ->dateTime(),
        TextEntry::make('updated_at')
          ->label('Actualizado:')
          ->dateTime(),

      ])
      ->columns(2)
      ->inlineLabel();
  }

  public static function getRelations(): array
  {
    return [
      //
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListLosses::route('/'),
      'create' => Pages\CreateLoss::route('/crear'),
    ];
  }
}
