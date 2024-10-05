<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;

class ProductResource extends Resource
{
  protected static ?string $model = Product::class;

  protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
  protected static $title = 'Productos';
  protected static ?string $label = 'producto';
  protected static ?string $pluralLabel = 'productos';
  protected static ?string $slug = 'productos';

  public static function form(Form $form): Form
  {
    return $form
      ->columns(1)
      ->schema([
        Wizard::make([
          Wizard\Step::make('Información del producto')
            ->columns(2)
            ->schema([
              Select::make('category')
                ->label('Categoria')
                ->options([
                  'Hogar' => 'Hogar',
                  'Electrodoméstico' => 'Electrodoméstico',
                ])
                ->required()
                ->searchable()
                ->preload()
                ->reactive()
                ->live()
                ->placeholder('Selecciona una categoria'),
              Forms\Components\TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->autocomplete(false)
                ->maxLength(255),
              Forms\Components\TextInput::make('name')
                ->label('Nombre')
                ->autocomplete(false)
                ->required()
                ->maxLength(255),
              Forms\Components\TextInput::make('purchase_price')
                ->label('Precio de compra')
                ->required()
                ->autocomplete(false)
                ->numeric(),
              Forms\Components\TextInput::make('sale_price')
                ->label('Precio de venta')
                ->required()
                ->autocomplete(false)
                ->numeric(),
              Forms\Components\TextInput::make('iva')
                ->label('IVA en %')
                ->required()
                ->autocomplete(false)
                ->numeric(),
              Forms\Components\TextInput::make('stock')
                ->label('Existencias')
                ->required()
                ->autocomplete(false)
                ->numeric(),
              Forms\Components\TextInput::make('min_stock')
                ->label('Stock mínimo')
                ->required()
                ->autocomplete(false)
                ->numeric(),
              Forms\Components\Select::make('status')
                ->label('Estado')
                ->options([
                  'Activo' => 'Activo',
                  'Inactivo' => 'Inactivo',
                ])
                ->placeholder('Selecciona el estado')
                ->preload()
                ->searchable()
                ->required(),
            ]),
          Wizard\Step::make('Detalles del producto')
            ->columns(2)
            ->schema(
              fn(callable $get) =>
              $get('category') != 'Hogar' ? [
                /* Appliance form */
                Forms\Components\TextInput::make('serial_number')
                  ->label('Serial')
                  ->required()
                  ->autocomplete(false)
                  ->maxLength(255),
                Forms\Components\TextInput::make('brand')
                  ->label('Marca')
                  ->autocomplete(false)
                  ->required()
                  ->maxLength(255),
                Forms\Components\TextInput::make('model')
                  ->label('Modelo')
                  ->autocomplete(false)
                  ->required()
                  ->maxLength(255),
                Forms\Components\TextInput::make('color')
                  ->label('Color')
                  ->required()
                  ->autocomplete(false)
                  ->maxLength(255),
                Forms\Components\TextInput::make('warranty')
                  ->label('Garantía en meses')
                  ->required()
                  ->autocomplete(false)
                  ->numeric(),
                Forms\Components\DatePicker::make('purchase_date')
                  ->label('Fecha de compra'),
                Forms\Components\Select::make('discount_applicable')
                  ->label('Descuento aplicable')
                  ->options([
                    'No' => 'No',
                    'Si' => 'Si',
                  ])
                  ->searchable()
                  ->afterStateUpdated(function ($state, callable $set) {
                    if ($state === 'No') {
                      $set('discount_amount', 0);
                    }
                  })
                  ->required()
                  ->preload()
                  ->reactive()
                  ->live(),
                Forms\Components\TextInput::make('discount_amount')
                  ->label('Monto de descuento')
                  ->disabled(fn(callable $get) => $get('discount_applicable') === 'No')
                  ->required()
                  ->autocomplete(false)
                  ->numeric(),
              ] : [
                /* Houseware form */
                Forms\Components\TextInput::make('brand')
                  ->label('Marca')
                  ->autocomplete(false)
                  ->required()
                  ->maxLength(255),
                Forms\Components\TextInput::make('color')
                  ->label('Color')
                  ->required()
                  ->autocomplete(false)
                  ->maxLength(255),
                Forms\Components\Select::make('discount_applicable')
                  ->label('Descuento aplicable')
                  ->options([
                    'No' => 'No',
                    'Si' => 'Si',
                  ])
                  ->searchable()
                  ->afterStateUpdated(function ($state, callable $set) {
                    if ($state === 'No') {
                      $set('discount_amount', 0);
                    }
                  })
                  ->required()
                  ->preload()
                  ->reactive()
                  ->live(),
                Forms\Components\TextInput::make('discount_amount')
                  ->label('Monto de descuento')
                  ->disabled(fn(callable $get) => $get('discount_applicable') === 'No')
                  ->required()
                  ->autocomplete(false)
                  ->numeric(),
              ]
            ),
        ]),
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
        Tables\Columns\TextColumn::make('sku')
          ->label('SKU')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('name')
          ->label('Nombre')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('category')
          ->label('Categoria')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'Hogar' => 'info',
            'Electrodoméstico' => 'primary',
          })
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('sale_price')
          ->label('Precio de venta')
          ->numeric()
          ->badge()
          ->color('success')
          ->icon('heroicon-s-currency-dollar')
          ->sortable(),
        Tables\Columns\TextColumn::make('purchase_price')
          ->label('Precio de compra')
          ->searchable()
          ->numeric()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('discount_applicable')
          ->label('Descuento')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('discount_amount')
          ->label('$ Descuento')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('iva')
          ->label('IVA en %')
          ->searchable()
          ->numeric()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('stock')
          ->label('Existencias')
          ->numeric()
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('min_stock')
          ->label('Stock mínimo')
          ->searchable()
          ->numeric()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('serial_number')
          ->label('Serial')
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('brand')
          ->label('Marca')
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('model')
          ->label('Modelo')
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('color')
          ->label('Color')
          ->searchable()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('warranty')
          ->label('Garantía en meses')
          ->searchable()
          ->numeric()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'Activo' => 'success',
            'Inactivo' => 'warning',
          })
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
      ->recordUrl(null)
      ->defaultSort('created_at', 'desc')
      ->filters([
        SelectFilter::make('status')
          ->label('Estado')
          ->options([
            'Activo' => 'Activo',
            'Inactivo' => 'Inactivo',
          ])
          ->searchable()
          ->preload()
          ->placeholder('Selecciona un estado'),
        SelectFilter::make('category')
          ->label('Categoria')
          ->options([
            'Hogar' => 'Hogar',
            'Electrodoméstico' => 'Electrodoméstico',
          ])
          ->searchable()
          ->preload()
          ->placeholder('Selecciona una categoria'),
        Filter::make('min_stock')
          ->label('Productos con stock mínimo')
          ->toggle()
          ->query(function (Builder $query) {
            $query->whereColumn('stock', '<=', 'min_stock');
          }),
        Filter::make('discount_applicable')
          ->label('Productos con descuento')
          ->toggle()
          ->query(function (Builder $query) {
            $query->where('discount_applicable', 'Si');
          }),
        Filter::make('iva')
          ->label('Productos con IVA')
          ->toggle()
          ->query(function (Builder $query) {
            $query->where('iva', '>', 0);
          }),
      ])
      ->actions([
        ActionGroup::make([
          Tables\Actions\ViewAction::make()->label('Ver'),
          Tables\Actions\EditAction::make()->color('warning'),
          DeleteAction::make()
            ->action(function ($record) {
              try {
                $record->delete();
                Notification::make()
                  ->title('Borrado')
                  ->success()
                  ->send();
              } catch (\Exception $e) {
                Notification::make()
                  ->title('Error al borrar')
                  ->danger()
                  ->send();
              }
            }),
        ])
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          ExportBulkAction::make(),
          //Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
  }


  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        TextEntry::make('id')
          ->label('ID:'),
        TextEntry::make('sku')
          ->label('SKU:'),
        TextEntry::make('name')
          ->label('Nombre:'),
        TextEntry::make('category')
          ->label('Categoria:')
          ->badge()
          ->color(function ($record) {
            return match ($record->category) {
              'Hogar' => 'info',
              'Electrodoméstico' => 'primary',
              default => 'gray',
            };
          }),
        TextEntry::make('sale_price')
          ->label('Precio de venta:')
          ->badge()
          ->color('success')
          ->icon('heroicon-s-currency-dollar')
          ->numeric(),
        TextEntry::make('purchase_price')
          ->label('Precio de compra:')
          ->numeric(),
        TextEntry::make('discount_applicable')
          ->label('Descuento:')
          ->badge()
          ->color(function ($record) {
            return $record->discount_applicable == 'Si' ? 'success' : 'warning';
          }),
        TextEntry::make('discount_amount')
          ->label('Monto de descuento:')
          ->numeric(),
        TextEntry::make('iva')
          ->label('IVA en %:')
          ->numeric(),
        TextEntry::make('stock')
          ->label('Existencias:')
          ->numeric(),
        TextEntry::make('min_stock')
          ->label('Stock mínimo:')
          ->numeric(),
        TextEntry::make('serial_number')
          ->label('Serial:')
          ->visible(fn($record) => $record->category == 'Electrodoméstico'),
        TextEntry::make('brand')
          ->label('Marca:'),
        TextEntry::make('model')
          ->label('Modelo:')
          ->visible(fn($record) => $record->category == 'Electrodoméstico'),
        TextEntry::make('color')
          ->label('Color:'),
        TextEntry::make('warranty')
          ->label('Garantía en meses:')
          ->numeric()
          ->visible(fn($record) => $record->category == 'Electrodoméstico'),
        TextEntry::make('purchase_date')
          ->label('Fecha de compra:')
          ->date()
          ->visible(fn($record) => $record->category == 'Electrodoméstico'),
        TextEntry::make('status')
          ->label('Estado:')
          ->badge()
          ->color(function ($record) {
            return $record->status == 'Activo' ? 'success' : 'warning';
          }),
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
      'index' => Pages\ListProducts::route('/'),
      'create' => Pages\CreateProduct::route('/crear'),
      'edit' => Pages\EditProduct::route('/editar/{record}'),
    ];
  }
}
