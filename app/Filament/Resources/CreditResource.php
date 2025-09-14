<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreditResource\Pages;
use App\Filament\Resources\CreditResource\RelationManagers;
use App\Models\Credit;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CreditResource extends Resource implements HasShieldPermissions
{
  protected static ?string $model = Credit::class;
  protected static ?int $navigationSort = 3;
  protected static ?string $navigationIcon = 'heroicon-o-building-library';
  protected static $title = 'Créditos';
  protected static ?string $label = 'crédito';
  protected static ?string $pluralLabel = 'créditos';
  protected static ?string $slug = 'creditos';

  public static function getPermissionPrefixes(): array
  {
    return [
      'view',           // Ver un registro en particular
      'view_any',       // Ver el listado de registros
      'create',         // Crear
      'update',         // Actualizar
      'pay',         // Nuestro nuevo permiso de Anular
      'restore',        // Restaurar un registro en particular
      'restore_any',    // Restaurar varios registros
      'replicate',      // Replicar
      'reorder',        // Reordenar
      'delete',         // Eliminar un registro en particular
      'delete_any',     // Eliminar varios registros a la vez
      'force_delete',   // Forzar eliminación de un registro en particular
      'force_delete_any', // Forzar eliminación de varios registros
    ];
  }

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
        Tables\Columns\TextColumn::make('sale.full_invoice_number')
          ->label('Factura')
          // ->searchable()
          // ->numeric()
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
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('total_paid')
          ->label('Pagado')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('total_pending')
          ->label('Pendiente')
          ->searchable()
          ->formatStateUsing(function ($state) {
            return '$ ' . number_format($state, 2, ',', '.');
          })
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
          ->icon('heroicon-o-ellipsis-horizontal-circle'),
      ])
      ->actionsPosition(ActionsPosition::BeforeColumns)
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
