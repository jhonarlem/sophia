<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BoxResource\Pages;
use App\Filament\Resources\BoxResource\RelationManagers;
use App\Models\Box;
use Faker\Provider\ar_EG\Text;

use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class BoxResource extends Resource
{
  protected static ?string $model = Box::class;

  protected static ?string $navigationIcon = 'heroicon-o-calculator';
  protected static ?int $navigationSort = 5;
  protected static $title = 'Caja';
  protected static ?string $label = 'caja';
  protected static ?string $pluralLabel = 'caja';
  protected static ?string $slug = 'caja';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('name')
          ->required()
          ->maxLength(255)
          ->default('CAJA PRINCIPAL'),
        Forms\Components\TextInput::make('resolution_id')
          ->required()
          ->numeric(),
        Forms\Components\TextInput::make('base_amount')
          ->required()
          ->numeric(),
        Forms\Components\TextInput::make('cash_payment')
          ->required()
          ->numeric()
          ->default(0),
        Forms\Components\TextInput::make('transfer_payment')
          ->required()
          ->numeric()
          ->default(0),
        Forms\Components\TextInput::make('credit_sales')
          ->required()
          ->numeric()
          ->default(0),
        Forms\Components\TextInput::make('total_sales')
          ->required()
          ->numeric()
          ->default(0),
        Forms\Components\TextInput::make('real_amount')
          ->numeric(),
        Forms\Components\TextInput::make('difference')
          ->numeric(),
        Forms\Components\TextInput::make('status')
          ->required()
          ->maxLength(255)
          ->default('Abierta'),
        Forms\Components\Textarea::make('observations')
          ->columnSpanFull(),
        Forms\Components\TextInput::make('opened_by')
          ->required()
          ->numeric(),
        Forms\Components\TextInput::make('closed_by')
          ->required()
          ->numeric(),
        Forms\Components\DateTimePicker::make('opened_at')
          ->required(),
        Forms\Components\DateTimePicker::make('closed_at'),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('id')
          ->label('ID')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('name')
          ->label('Nombre')
          ->searchable(),
        Tables\Columns\TextColumn::make('resolution.number')
          ->label('Resolución')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->searchable()
          ->color(fn(string $state): string => match ($state) {
            'Abierta' => 'success',
            'Cerrada' => 'warning',
          })
          ->sortable(),
        Tables\Columns\TextColumn::make('base_amount')
          ->label('$ Base')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('cash_payment')
          ->label('$ Efectivo')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('transfer_payment')
          ->label('$ Transferencia')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('credit_sales')
          ->label('$ Crédito')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_sales')
          ->label('$ Total')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('real_amount')
          ->label('$ Real')
          ->searchable()
          ->numeric()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('difference')
          ->label('$ Diferencia')
          ->searchable()
          ->numeric()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('openedBy.name')
          ->label('Abierta por')
          ->searchable()
          ->sortable()
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
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('closed_at')
          ->label('Fecha cierre')
          ->dateTime()
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('observations')
          ->label('Observaciones')
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
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
      ->recordUrl(null)
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('status')
          ->label('Estado')
          ->options([
            'Abierta' => 'Abierta',
            'Cerrada' => 'Cerrada',
          ])
          ->searchable()
          ->preload()
          ->placeholder('Selecciona un estado'),
      ])
      ->actions([
        ActionGroup::make([
          Tables\Actions\ViewAction::make()->label('Ver')->color('primary'),
          Action::make('Imprimir')
            ->label('Imprimir')
            ->icon('heroicon-o-printer')
            ->color('success')
            ->url(fn(Tables\Actions\Action $action) => route('recibo-caja', $action->getRecord()->id))
            ->openUrlInNewTab(),
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

  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        TextEntry::make('id')
          ->label('ID:'),
        TextEntry::make('name')
          ->label('Nombre:'),
        TextEntry::make('resolution.number')
          ->label('Resolución:'),
        TextEntry::make('status')
          ->label('Estado:')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'Abierta' => 'success',
            'Cerrada' => 'warning',
          }),
        TextEntry::make('base_amount')
          ->label('$ Base:')
          ->numeric(),
        TextEntry::make('cash_payment')
          ->label('$ Efectivo:')
          ->numeric(),
        TextEntry::make('transfer_payment')
          ->label('$ Transferencia:')
          ->numeric(),
        TextEntry::make('credit_sales')
          ->label('$ Crédito:')
          ->numeric(),
        TextEntry::make('total_sales')
          ->label('$ Total:')->badge()
          ->color('success')
          ->icon('heroicon-s-currency-dollar')
          ->numeric(),
        TextEntry::make('real_amount')
          ->label('$ Real:')
          ->numeric(),
        TextEntry::make('difference')
          ->label('$ Diferencia:')
          ->badge()
          ->color('warning')
          ->icon('heroicon-s-currency-dollar')
          ->numeric(),
        TextEntry::make('openedBy.name')
          ->label('Abierta por:'),
        TextEntry::make('opened_at')
          ->label('Fecha apertura:')
          ->dateTime(),
        TextEntry::make('closedBy.name')
          ->label('Cerrada por:'),
        TextEntry::make('closed_at')
          ->label('Fecha cierre:')
          ->dateTime(),
        TextEntry::make('observations')
          ->label('Observaciones:'),
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
      'index' => Pages\ListBoxes::route('/'),
    ];
  }
}
