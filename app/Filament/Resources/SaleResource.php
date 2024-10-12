<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Box;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
  protected static ?string $model = Sale::class;

  protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
  protected static $title = 'Ventas';
  protected static ?string $label = 'venta';
  protected static ?string $pluralLabel = 'ventas';
  protected static ?string $slug = 'ventas';

  public static function form(Form $form): Form
  {
    $subTotal = 0;
    $totalAmount = 0;
    $totalIva = 0;
    $totalDiscount = 0;

    return $form
      ->schema([
        Section::make('Información del cliente y factura')
          ->icon('heroicon-o-user')
          ->schema([
            Grid::make(12) // Usamos una cuadrícula de 12 columnas
              ->schema([
                // Columna izquierda: Detalles del cliente (8 columnas de 12)
                Section::make('Detalles del cliente')
                  ->schema([
                    Select::make('customer_id')
                      ->label('Cliente')
                      ->relationship('customer', 'name')
                      ->required()
                      ->searchable()
                      ->preload()
                      ->placeholder('Selecciona un cliente')
                      ->createOptionForm(CustomerResource::customerFormSchema())
                      ->createOptionAction(function (Action $action) {
                        return $action
                          ->modalHeading('Crear cliente')
                          ->modalSubmitActionLabel('Crear');
                      })
                      ->reactive()
                      ->afterStateUpdated(function ($state, callable $set) {
                        if ($state) {
                          $customer = Customer::getInfoCustomer($state);
                          if ($customer) {
                            $set('document_type', $customer['document_type']);
                            $set('document_number', $customer['document_number']);
                            $set('name', $customer['name']);
                            $set('cellphone', $customer['cellphone']);
                          }
                        } else {
                          $set('document_type', '');
                          $set('document_number', '');
                          $set('name', '');
                          $set('cellphone', '');
                        }
                      }),
                    Grid::make(2)
                      ->schema([
                        Placeholder::make('document_type')
                          ->label('Documento:')
                          ->content(fn($get) => $get('document_type') ?? '')
                          ->inlineLabel(),
                        Placeholder::make('document_number')
                          ->label('Número:')
                          ->content(fn($get) => $get('document_number') ?? '')
                          ->inlineLabel(),
                        Placeholder::make('name')
                          ->label('Nombres:')
                          ->content(fn($get) => $get('name') ?? '')
                          ->inlineLabel(),
                        Placeholder::make('cellphone')
                          ->label('Celular:')
                          ->content(fn($get) => $get('cellphone') ?? '')
                          ->inlineLabel(),
                      ])
                  ])
                  ->columnSpan(8),  // Ocupa dos tercios (8 de 12 columnas)
                // Columna derecha: Detalles de la factura (4 columnas de 12)
                Section::make('Detalles de la factura')
                  ->schema([
                    Placeholder::make('invoice_number')
                      ->label('Factura:')
                      ->content(Sale::generateInvoiceNumber())
                      ->inlineLabel(),
                    Placeholder::make('total_amount')
                      ->label('Subtotal')
                      ->content('$ ' . $subTotal)
                      ->inlineLabel(),
                    Placeholder::make('total_iva')
                      ->label('IVA')
                      ->content('$ ' . $totalIva)
                      ->inlineLabel(),
                    Placeholder::make('total_discount')
                      ->label('Descuento')
                      ->content('$ ' . $totalDiscount)
                      ->inlineLabel(),
                    Placeholder::make('total_amount')
                      ->label('TOTAL')
                      ->content('$ ' . $totalAmount)
                      ->inlineLabel(),
                  ])
                  ->columnSpan(4),  // Ocupa un tercio (4 de 12 columnas)
              ]),
          ])
          ->collapsible()
          ->persistCollapsed()
          ->id('customer'),
        Section::make('Información de los productos')
          ->columns(1)
          ->icon('heroicon-o-shopping-bag')
          ->schema([
            Repeater::make('Producto')
              ->label('')
              ->columns(7)
              ->schema([
                Select::make('product_id')
                  ->label('Producto')
                  ->relationship('products', 'name')
                  ->required()
                  ->searchable()
                  ->preload()
                  ->placeholder('Selecciona un producto')
                  ->getOptionLabelFromRecordUsing(fn($record) => "{$record->sku} - {$record->name}")
                  ->reactive()
                  ->afterStateUpdated(function ($state, callable $set) {
                    $product = Product::find($state);
                    if (!$product) {
                      $set('is_product_selected', false);
                      $set('total', null);
                      $set('sale_price', null);
                      $set('discount_placeholder', null);
                      return;
                    }
                    $set('total', round($product->sale_price));
                    $set('sale_price', round($product->sale_price));
                    $set('discount_placeholder', round($product->discount_amount));
                    $set('is_product_selected', true);
                  })
                  ->columnSpan(3),
                TextInput::make('cuantity')
                  ->label('Cantidad')
                  ->numeric()
                  ->required()
                  ->default(1)
                  ->columnSpan(1)
                  ->reactive()
                  ->disabled(fn($get) => !$get('is_product_selected'))
                  ->afterStateUpdated(function ($state, callable $set, $get) {
                    $productPrice = $get('sale_price');
                    $discount = $get('descount');
                    $set('total', $productPrice * $state - $discount);
                  }),
                TextInput::make('sale_price')
                  ->label('Precio')
                  ->numeric()
                  ->disabled()
                  ->default(0)
                  ->columnSpan(1),
                TextInput::make('discount')
                  ->label('Descuento')
                  ->numeric()
                  ->placeholder(fn($get) => $get('discount_placeholder') > 0 ? $get('discount_placeholder') : 0)
                  ->disabled(fn($get) => $get('discount_placeholder') <= 0)
                  ->live(onBlur: true) // Este método asegura que la operación se realice al perder el foco
                  ->afterStateUpdated(function ($state, callable $set, $get) {
                    $productPrice = $get('sale_price');
                    $quantity = $get('cuantity');
                    $maxDiscount = $get('discount_placeholder') * $quantity;
                    if ($state > $maxDiscount) {
                      $set('discount', $maxDiscount);
                      return Notification::make()
                        ->title('Descuento excedido')
                        ->body('El descuento ingresado es mayor al descuento permitido')
                        ->danger()
                        ->send();
                    }
                    $total = $productPrice * $quantity - $state;
                    $set('total', $total);
                  })
                  ->columnSpan(1),
                TextInput::make('total')
                  ->label('Total')
                  ->default(0)
                  ->numeric()
                  ->disabled()
                  ->columnSpan(1),
              ])
              ->addActionLabel('Agregar otro producto')
              ->reorderable(false),
          ])
          ->collapsible()
          ->persistCollapsed()
          ->id('products'),
        /* Section::make('Información del pago')
          ->columns(1)
          ->icon('heroicon-o-currency-dollar')
          ->schema([
            Repeater::make('Pago')
              ->label('')
              ->columns(3)
              ->schema([
                Select::make('payment_id')
                  ->label('Método de pago')
                  ->options([
                    'Efectivo' => 'Efectivo',
                    'Transferencia' => 'Transferencia',
                    'Crédito' => 'Crédito',
                  ])
                  ->placeholder('Selecciona un método de pago')
                  ->searchable()
                  ->preload()
                  ->required()
                  ->columnSpan(2),
                TextInput::make('cuantity')
                  ->label('Cantidad')
                  ->numeric()
                  ->required()
                  ->default(0)
                  ->columnSpan(1),
              ])
              ->addActionLabel('Agregar otro método de pago')
              ->reorderable(false),
          ])
          ->collapsible()
          ->persistCollapsed()
          ->id('payments'), */
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('box_id')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('customer_id')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('user_id')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('invoice_number')
          ->searchable(),
        Tables\Columns\TextColumn::make('total_amount')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_iva')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('total_discount')
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('payment_method')
          ->searchable(),
        Tables\Columns\TextColumn::make('status')
          ->searchable(),
        Tables\Columns\TextColumn::make('sale_date')
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('created_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('updated_at')
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
      ])
      ->filters([
        //
      ])
      ->actions([
        Tables\Actions\EditAction::make(),
      ])
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
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
      'index' => Pages\ListSales::route('/'),
      'create' => Pages\CreateSale::route('/crear'),
      'edit' => Pages\EditSale::route('/editar/{record}'),
    ];
  }
}
