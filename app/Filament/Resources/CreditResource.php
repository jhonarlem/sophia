<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreditResource\Pages;
use App\Filament\Resources\CreditResource\RelationManagers;
use App\Models\Credit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CreditResource extends Resource
{
  protected static ?string $model = Credit::class;
  protected static ?int $navigationSort = 3;
  protected static ?string $navigationIcon = 'heroicon-o-building-library';
  protected static $title = 'Créditos';
  protected static ?string $label = 'crédito';
  protected static ?string $pluralLabel = 'créditos';
  protected static ?string $slug = 'creditos';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        //
      ]);
  }

  public static function table(Table $table): Table
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
        Tables\Columns\TextColumn::make('credit_date')
          ->label('Fecha:')
          ->dateTime()
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
      ])->defaultSort('created_at', 'desc')
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

  public static function getRelations(): array
  {
    return [
      RelationManagers\PaymentsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListCredits::route('/'),
      'view' => Pages\ViewCredit::route('/{record}'),
    ];
  }
}
