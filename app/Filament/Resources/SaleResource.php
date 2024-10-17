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
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
    return $form
      ->columns(1)
      ->schema([
        Wizard::make([
          Wizard\Step::make('Detalles de la venta')
            ->columns(2)
            ->schema([
              Grid::make(12) // Usamos una cuadrícula de 12 columnas
                ->schema([
                  // Columna izquierda: Detalles del cliente (8 columnas de 12)
                  Section::make('Detalles del cliente')
                    ->icon('heroicon-o-user')
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
                    ->icon('heroicon-o-document-text')
                    ->schema([
                      Placeholder::make('invoice_number')
                        ->label('Factura:')
                        ->content(Sale::generateInvoiceNumber())
                        ->inlineLabel(),
                      Placeholder::make('subtotal')
                        ->label('Subtotal:')
                        ->content(function (Get $get) {
                          $products = $get('Producto') ?? [];
                          return '$ ' . number_format(collect($products)->sum(function ($product) {
                            $priceWithoutIva = $product['sale_price'] / (1 + ($product['iva'] / 100));
                            return $priceWithoutIva * $product['quantity'];
                          }), 2);
                        })
                        ->inlineLabel(),
                      Placeholder::make('total_iva')
                        ->label('IVA:')
                        ->content(function (Get $get) {
                          $products = $get('Producto') ?? [];
                          return '$ ' . number_format(collect($products)->sum(function ($product) {
                            $priceWithoutIva = $product['sale_price'] / (1 + ($product['iva'] / 100));
                            $ivaAmount = ($product['sale_price'] - $priceWithoutIva) * $product['quantity'];
                            return $ivaAmount;
                          }), 2);
                        })
                        ->inlineLabel(),
                      Placeholder::make('total_discount')
                        ->label('Descuento:')
                        ->content(function (Get $get) {
                          $products = $get('Producto') ?? [];
                          return '$ ' . number_format(collect($products)->sum('discount'), 2);
                        })
                        ->inlineLabel(),
                      Placeholder::make('total_amount')
                        ->label('TOTAL:')
                        ->content(function (Get $get) {
                          $products = $get('Producto') ?? [];
                          return '$ ' . number_format(collect($products)->sum(function ($product) {
                            return ($product['sale_price'] * $product['quantity']) - $product['discount'];
                          }), 2);
                        })
                        ->inlineLabel(),
                    ])
                    ->columnSpan(4),  // Ocupa un tercio (4 de 12 columnas)
                ]),
              Section::make('Detalles de los productos')
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
                          if (!$state) {
                            $set('is_product_selected', false);
                            $set('total', null);
                            $set('sale_price', null);
                            $set('iva', null);
                            $set('discount_placeholder', null);
                            $set('quantity', 1); // Reiniciamos la cantidad a 1
                            $set('discount', null); // Limpiamos el descuento
                            return;
                          }

                          $product = Product::find($state);
                          if (!$product) {
                            $set('is_product_selected', false);
                            $set('total', null);
                            $set('sale_price', null);
                            $set('iva', null);
                            $set('discount_placeholder', null);
                            $set('quantity', 1); // Reiniciamos la cantidad a 1
                            $set('discount', null); // Limpiamos el descuento
                            return;
                          }

                          $set('total', round($product->sale_price, 2));
                          $set('sale_price', round($product->sale_price, 2));
                          $set('iva', $product->iva);
                          $set('discount_placeholder', round($product->discount_amount, 2));
                          $set('is_product_selected', true);
                          // No reiniciamos la cantidad aquí para mantener el valor si ya se seleccionó un producto
                        })
                        ->columnSpan(3),
                      TextInput::make('quantity')
                        ->label('Cantidad')
                        ->numeric()
                        ->required()
                        ->default(1)
                        ->columnSpan(1)
                        ->reactive()
                        ->disabled(fn($get) => !$get('is_product_selected'))
                        ->live(debounce: 700)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                          $productPrice = $get('sale_price');
                          $discountPlaceholder = $get('discount_placeholder');

                          // Permitir números negativos y cero, pero mostrar una notificación
                          if ($state <= 0) {
                            $set('quantity', 1);
                            return Notification::make()
                              ->title('Cantidad inválida')
                              ->body('La cantidad no puede ser menor o igual a 0')
                              ->danger()
                              ->send();
                          }

                          // Reiniciamos el descuento a cero, manteniendo el placeholder
                          $set('discount', null);
                          // Recalculamos el total sin aplicar descuento
                          $total = $productPrice * $state;
                          $set('total', $total);
                        }),
                      TextInput::make('sale_price')
                        ->label('Precio')
                        ->numeric()
                        ->disabled()
                        ->default(0)
                        ->columnSpan(1),
                      TextInput::make('iva')
                        ->label('IVA (%)')
                        ->numeric()
                        ->disabled()
                        ->default(0)
                        ->visible(false)
                        ->columnSpan(1),
                      TextInput::make('discount')
                        ->label('Descuento')
                        ->numeric()
                        ->placeholder(function ($get) {
                          $discountPlaceholder = $get('discount_placeholder');
                          $quantity = $get('quantity');
                          return $discountPlaceholder > 0 ? $discountPlaceholder * $quantity : 0;
                        })
                        ->disabled(fn($get) => $get('discount_placeholder') <= 0)
                        ->live(debounce: 700)
                        ->afterStateUpdated(function ($state, callable $set, $get) {
                          $productPrice = $get('sale_price');
                          $quantity = $get('quantity');
                          $maxDiscount = $get('discount_placeholder') * $quantity;

                          // Si el descuento está vacío o es null, lo tratamos como 0
                          $state = $state === null || $state === '' ? 0 : floatval($state);

                          if ($state > $maxDiscount) {
                            $set('discount', $maxDiscount);
                            Notification::make()
                              ->title('Descuento excedido')
                              ->body('El descuento ingresado es mayor al descuento permitido')
                              ->danger()
                              ->send();
                            $state = $maxDiscount;
                          }

                          if ($state < 0) {
                            $set('discount', $maxDiscount);
                            Notification::make()
                              ->title('Descuento negativo')
                              ->body('El descuento ingresado es negativo')
                              ->danger()
                              ->send();
                            $state = null;
                          }

                          $total = ($productPrice * $quantity) - $state;
                          $set('total', $total);
                          $set('discount', $state); // Aseguramos que el valor del descuento se actualice
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
            ]),
          Wizard\Step::make('Detalles del método de pago')
            ->columns(2)
            ->schema([
              Section::make('Información del pago')
                ->columns(1)
                ->icon('heroicon-o-currency-dollar')
                ->schema([
                  Grid::make(2)
                    ->schema([
                      Placeholder::make('total_amount_payment')
                        ->label('Total a pagar:')
                        ->content(function (Get $get) {
                          $products = $get('Producto') ?? [];
                          return '$ ' . number_format(collect($products)->sum(function ($product) {
                            return ($product['sale_price'] * $product['quantity']) - $product['discount'];
                          }), 2);
                        })
                        ->inlineLabel(),
                      Placeholder::make('change')
                        ->label('Cambio:')
                        ->content(function (Get $get) {
                          $payments = $get('Pago') ?? [];
                          $totalAmount = collect($get('Producto') ?? [])->sum(function ($product) {
                            return ($product['sale_price'] * $product['quantity']) - $product['discount'];
                          });
                          $totalPaid = collect($payments)->sum(function ($payment) {
                            return is_numeric($payment['amount']) ? $payment['amount'] : 0;
                          });
                          return '$ ' . number_format($totalPaid - $totalAmount, 2);
                        })
                        ->inlineLabel(),
                    ]),
                  Repeater::make('Pago')
                    ->label('')
                    ->columns(4)
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
                        ->reactive() // Cambiamos a reactive como en la sección de productos
                        ->afterStateUpdated(function ($state, callable $set) {
                          if (!$state) {
                            $set('amount', 0);
                            $set('reference', '');
                            return;
                          }

                          if ($state === 'Transferencia') {
                            $set('reference', '');
                          }
                        })
                        ->columnSpan(2),
                      TextInput::make('amount')
                        ->label('Cantidad')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->disabled(fn($get) => empty($get('payment_id')))
                        ->live(debounce: 700) // Mismo debounce que usas en productos
                        ->columnSpan(fn($get) => $get('payment_id') === 'Transferencia' ? 1 : 2),
                      TextInput::make('reference')
                        ->label('Referencia')
                        ->visible(fn($get) => $get('payment_id') === 'Transferencia')
                        ->required(fn($get) => $get('payment_id') === 'Transferencia')
                        ->columnSpan(1), // Ajustamos el tamaño de la columna si es necesario
                    ])
                    ->addActionLabel('Agregar otro método de pago')
                    ->reorderable(false),
                ])
            ]),

        ]),
      ]);
    /* return $form
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
                    Placeholder::make('subtotal')
                      ->label('Subtotal:')
                      ->content(function (Get $get) {
                        $products = $get('Producto') ?? [];
                        return '$ ' . number_format(collect($products)->sum(function ($product) {
                          $priceWithoutIva = $product['sale_price'] / (1 + ($product['iva'] / 100));
                          return $priceWithoutIva * $product['quantity'];
                        }), 2);
                      })
                      ->inlineLabel(),
                    Placeholder::make('total_iva')
                      ->label('IVA:')
                      ->content(function (Get $get) {
                        $products = $get('Producto') ?? [];
                        return '$ ' . number_format(collect($products)->sum(function ($product) {
                          $priceWithoutIva = $product['sale_price'] / (1 + ($product['iva'] / 100));
                          $ivaAmount = ($product['sale_price'] - $priceWithoutIva) * $product['quantity'];
                          return $ivaAmount;
                        }), 2);
                      })
                      ->inlineLabel(),
                    Placeholder::make('total_discount')
                      ->label('Descuento:')
                      ->content(function (Get $get) {
                        $products = $get('Producto') ?? [];
                        return '$ ' . number_format(collect($products)->sum('discount'), 2);
                      })
                      ->inlineLabel(),
                    Placeholder::make('total_amount')
                      ->label('TOTAL:')
                      ->content(function (Get $get) {
                        $products = $get('Producto') ?? [];
                        return '$ ' . number_format(collect($products)->sum(function ($product) {
                          return ($product['sale_price'] * $product['quantity']) - $product['discount'];
                        }), 2);
                      })
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
                    if (!$state) {
                      $set('is_product_selected', false);
                      $set('total', null);
                      $set('sale_price', null);
                      $set('iva', null);
                      $set('discount_placeholder', null);
                      $set('quantity', 1); // Reiniciamos la cantidad a 1
                      $set('discount', null); // Limpiamos el descuento
                      return;
                    }

                    $product = Product::find($state);
                    if (!$product) {
                      $set('is_product_selected', false);
                      $set('total', null);
                      $set('sale_price', null);
                      $set('iva', null);
                      $set('discount_placeholder', null);
                      $set('quantity', 1); // Reiniciamos la cantidad a 1
                      $set('discount', null); // Limpiamos el descuento
                      return;
                    }

                    $set('total', round($product->sale_price, 2));
                    $set('sale_price', round($product->sale_price, 2));
                    $set('iva', $product->iva);
                    $set('discount_placeholder', round($product->discount_amount, 2));
                    $set('is_product_selected', true);
                    // No reiniciamos la cantidad aquí para mantener el valor si ya se seleccionó un producto
                  })
                  ->columnSpan(3),
                TextInput::make('quantity')
                  ->label('Cantidad')
                  ->numeric()
                  ->required()
                  ->default(1)
                  ->columnSpan(1)
                  ->reactive()
                  ->disabled(fn($get) => !$get('is_product_selected'))
                  ->live(debounce: 700)
                  ->afterStateUpdated(function ($state, callable $set, $get) {
                    $productPrice = $get('sale_price');
                    $discountPlaceholder = $get('discount_placeholder');

                    // Permitir números negativos y cero, pero mostrar una notificación
                    if ($state <= 0) {
                      $set('quantity', 1);
                      return Notification::make()
                        ->title('Cantidad inválida')
                        ->body('La cantidad no puede ser menor o igual a 0')
                        ->danger()
                        ->send();
                    }

                    // Reiniciamos el descuento a cero, manteniendo el placeholder
                    $set('discount', null);
                    // Recalculamos el total sin aplicar descuento
                    $total = $productPrice * $state;
                    $set('total', $total);
                  }),
                TextInput::make('sale_price')
                  ->label('Precio')
                  ->numeric()
                  ->disabled()
                  ->default(0)
                  ->columnSpan(1),
                TextInput::make('iva')
                  ->label('IVA (%)')
                  ->numeric()
                  ->disabled()
                  ->default(0)
                  ->visible(false)
                  ->columnSpan(1),
                TextInput::make('discount')
                  ->label('Descuento')
                  ->numeric()
                  ->placeholder(function ($get) {
                    $discountPlaceholder = $get('discount_placeholder');
                    $quantity = $get('quantity');
                    return $discountPlaceholder > 0 ? $discountPlaceholder * $quantity : 0;
                  })
                  ->disabled(fn($get) => $get('discount_placeholder') <= 0)
                  ->live(debounce: 700)
                  ->afterStateUpdated(function ($state, callable $set, $get) {
                    $productPrice = $get('sale_price');
                    $quantity = $get('quantity');
                    $maxDiscount = $get('discount_placeholder') * $quantity;

                    // Si el descuento está vacío o es null, lo tratamos como 0
                    $state = $state === null || $state === '' ? 0 : floatval($state);

                    if ($state > $maxDiscount) {
                      $set('discount', $maxDiscount);
                      Notification::make()
                        ->title('Descuento excedido')
                        ->body('El descuento ingresado es mayor al descuento permitido')
                        ->danger()
                        ->send();
                      $state = $maxDiscount;
                    }

                    if ($state < 0) {
                      $set('discount', $maxDiscount);
                      Notification::make()
                        ->title('Descuento negativo')
                        ->body('El descuento ingresado es negativo')
                        ->danger()
                        ->send();
                      $state = null;
                    }

                    $total = ($productPrice * $quantity) - $state;
                    $set('total', $total);
                    $set('discount', $state); // Aseguramos que el valor del descuento se actualice
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
        Section::make('Información del pago')
          ->columns(1)
          ->icon('heroicon-o-currency-dollar')
          ->schema([
            Grid::make(2)
              ->schema([
                Placeholder::make('total_amount_payment')
                  ->label('Total a pagar:')
                  ->content(function (Get $get) {
                    $products = $get('Producto') ?? [];
                    return '$ ' . number_format(collect($products)->sum(function ($product) {
                      return ($product['sale_price'] * $product['quantity']) - $product['discount'];
                    }), 2);
                  })
                  ->inlineLabel(),
                Placeholder::make('change')
                  ->label('Cambio:')
                  ->content(function (Get $get) {
                    $payments = $get('Pago') ?? [];
                    $totalAmount = collect($get('Producto') ?? [])->sum(function ($product) {
                      return ($product['sale_price'] * $product['quantity']) - $product['discount'];
                    });
                    $totalPaid = collect($payments)->sum(function ($payment) {
                      return is_numeric($payment['amount']) ? $payment['amount'] : 0;
                    });
                    return '$ ' . number_format($totalPaid - $totalAmount, 2);
                  })
                  ->inlineLabel(),
              ]),
            Repeater::make('Pago')
              ->label('')
              ->columns(4)
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
                  ->reactive() // Cambiamos a reactive como en la sección de productos
                  ->afterStateUpdated(function ($state, callable $set) {
                    if (!$state) {
                      $set('amount', 0);
                      $set('reference', '');
                      return;
                    }

                    if ($state === 'Transferencia') {
                      $set('reference', '');
                    }
                  })
                  ->columnSpan(2),
                TextInput::make('amount')
                  ->label('Cantidad')
                  ->numeric()
                  ->required()
                  ->default(0)
                  ->disabled(fn($get) => empty($get('payment_id')))
                  ->columnSpan(fn($get) => $get('payment_id') === 'Transferencia' ? 1 : 2),
                TextInput::make('reference')
                  ->label('Referencia')
                  ->visible(fn($get) => $get('payment_id') === 'Transferencia')
                  ->required(fn($get) => $get('payment_id') === 'Transferencia')
                  ->columnSpan(1), // Ajustamos el tamaño de la columna si es necesario
              ])
              ->addActionLabel('Agregar otro método de pago')
              ->reorderable(false),
          ])
          ->collapsible()
          ->persistCollapsed()
          ->id('payments'),

      ]);
   */
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
