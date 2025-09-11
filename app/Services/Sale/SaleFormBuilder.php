<?php

namespace App\Services\Sale;

use App\Filament\Resources\CustomerResource;
use App\Models\Inventory;
use App\Models\Product;
use App\Services\Customer\CustomerFormBuilder;
use App\Services\Customer\CustomerManager;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Split;
use Filament\Support\RawJs;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;

class SaleFormBuilder
{
  public static function form(Form $form): Form
  {
    return $form->schema([
      static::getSaleFormSchema(),
    ]);
  }


  public static function getSaleFormSchema(): Section
  {
    return
      Section::make('Información de la venta')
      ->columns(['default' => 1, 'md' => 1])
      ->schema(
        [
          Split::make([
            Section::make([
              static::getCodeProductormField()->columnSpan('full'),
              static::getSelectProductFormField()->columnSpan('full'),
            ]),
            Section::make([
              Placeholder::make('subtotal')
                ->label('Subtotal:')
                ->content(fn(Get $get) => '$ ' . number_format(
                  array_sum(
                    array_map(
                      fn($item) => ((float)str_replace(',', '.', str_replace('.', '', $item['price'] ?? 0))) *
                        ((float)str_replace(',', '.', str_replace('.', '', $item['quantity'] ?? 1))),
                      $get('saleItems') ?? []
                    )
                  ),
                  2,
                  ',',
                  '.'
                ))
                ->inlineLabel(),

              Placeholder::make('discount')
                ->label('Descuento:')
                ->content(fn(Get $get) => '$ ' . number_format(
                  array_sum(
                    array_map(
                      fn($item) => ((float)str_replace(',', '.', str_replace('.', '', $item['price'] ?? 0))) *
                        ((float)str_replace(',', '.', str_replace('.', '', $item['quantity'] ?? 1))) *
                        ((float)str_replace(',', '.', str_replace('.', '', $item['discount'] ?? 0)) / 100),
                      $get('saleItems') ?? []
                    )
                  ),
                  2,
                  ',',
                  '.'
                ))
                ->inlineLabel(),

              Placeholder::make('total')
                ->label('Total:')
                ->content(fn(Get $get) => '$ ' . number_format(
                  array_sum(
                    array_map(
                      fn($item) => (float)str_replace(',', '.', str_replace('.', '', $item['subtotal'])),
                      $get('saleItems') ?? []
                    )
                  ), // Aquí solo sumamos los subtotales ya con descuento
                  2,
                  ',',
                  '.'
                ))
                ->inlineLabel(),

            ])->grow(false),
          ])->from('md'),
          TableRepeater::make('saleItems')
            ->label('Productos de la venta')
            ->colStyles([
              'product_name' => 'width: 450px;',
            ])
            ->schema([
              TextInput::make('product_name')
                ->label('Producto')
                ->readOnly(),
              TextInput::make('quantity')
                ->label('Cantidad')
                ->numeric()
                ->required()
                ->default(1)
                ->minValue(1)
                ->live(debounce: 1000)
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                  // Obtener el stock disponible para validación
                  $stock = $get('stock') ?? 0;

                  // Si la cantidad ingresada excede el stock, ajustarla al máximo disponible
                  if ($state > $stock) {
                    $set('quantity', $stock);
                    $unit = $stock === 1 ? 'unidad disponible' : 'unidades disponibles';
                    // Actualizar el estado de la cantidad con el valor del stock
                    $state = $stock;
                    // Enviar una notificación de advertencia
                    Notification::make()
                      ->title('Cantidad máxima excedida')
                      ->body("Actualmente solo hay {$stock} {$unit} para este producto")
                      ->warning()
                      ->send();
                  }

                  // Calcular el subtotal
                  $price = str_replace('.', '', $get('price')) ?? 0;
                  $discount = $get('discount') ?? 0;
                  $subtotal = ($price * $state) * (1 - ($discount / 100));

                  // Establecer el subtotal formateado
                  $set('subtotal', number_format(round($subtotal, 2), 0, ',', '.'));
                })
                ->validationMessages([
                  'required' => 'La cantidad es obligatoria',
                  'numeric' => 'La cantidad debe ser un número',
                  'min_value' => 'La cantidad debe ser al menos 1',
                ]),
              TextInput::make('discount')
                ->label('Dto (%)')
                ->numeric()
                // ->required()
                ->placeholder(function ($get) {
                  $discountAllowed = $get('discount_allowed') ?? 0;
                  $price = str_replace('.', '', $get('price')) ?? 0;
                  $discountPlaceholder = intval(($discountAllowed * 100) / $price);
                  return $discountPlaceholder ? "{$discountPlaceholder}%" : 0;
                })
                ->minValue(0)
                ->live(debounce: 1000)
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                  // Obtener las variables necesarias
                  $price = str_replace('.', '', $get('price')) ?? 0;
                  $quantity = $get('quantity') ?? 1;
                  $discountApplicable = $get('discount_applicable') ?? false;

                  // Si es 0 no hacer nada
                  if (intval($state) == 0) {
                    $subtotal = ($price * $quantity);
                    $set('subtotal', number_format(round($subtotal, 2), 0, ',', '.'));
                    return;
                  }

                  // Si el descuento no es aplicable, fijar el descuento a 0 y notificar.
                  if (!$discountApplicable) {
                    $set('discount', 0);
                    // Actualizar el estado con el valor ajustado.
                    $state = 0;
                    Notification::make()
                      ->title('Descuento no aplicable')
                      ->body('Este producto no permite descuentos')
                      ->warning()
                      ->send();
                  } else {
                    // Calcular el descuento máximo permitido en porcentaje
                    $discountAllowedAmount = $get('discount_allowed') ?? 0;
                    $maxDiscountPercentage = ($price > 0) ? intval(($discountAllowedAmount * 100) / $price) : 0;

                    // Si el descuento ingresado excede el máximo permitido, ajustarlo
                    if ((float)$state > $maxDiscountPercentage) {
                      $set('discount', $maxDiscountPercentage);
                      // Actualizar el estado con el valor ajustado.
                      $state = $maxDiscountPercentage;
                      Notification::make()
                        ->title('Descuento máximo excedido')
                        ->body("El descuento máximo permitido para este producto es de {$maxDiscountPercentage}%")
                        ->warning()
                        ->send();
                    }
                  }

                  // Calcular el subtotal
                  $subtotal = ($price * $quantity) * (1 - ($state / 100));

                  // Establecer el subtotal formateado
                  $set('subtotal', number_format(round($subtotal, 2), 0, ',', '.'));
                })
                ->validationMessages([
                  'required' => 'El descuento es obligatorio',
                  'numeric' => 'El descuento debe ser un número',
                  'min_value' => 'El descuento no puede ser negativo',
                ]),

              Hidden::make('price'),
              Hidden::make('discount_applicable'),
              Hidden::make('discount_allowed'),
              Hidden::make('stock'),
              TextInput::make('price')
                ->label('Precio')
                ->readOnly(),
              TextInput::make('subtotal')
                ->label('Subtotal')
                ->readOnly(),
            ])
            ->colStyles([
              'product_name' => 'width: 400px;',
            ])
            ->columns(12)
            ->columnSpan('full')
            ->defaultItems(0)
            ->reorderable(false)
            ->addable(false),
        ]
      );
  }


  public static function getSalePaymentForm(): array
  {
    return [
      static::salePaymentFormSchema()
    ];
  }


  public static function getQuickSaleFormSchema(): array
  {
    return [
      static::quickSaleFormSchema()
    ];
  }


  public static function quickSaleFormSchema(): Section
  {
    return
      Section::make('Información de la venta rápida')
      ->columns(['default' => 1, 'md' => 1])
      ->schema(
        [
          Split::make([
            Section::make([
              static::getQuickCodeProductormField()->columnSpan('full'),
              static::getSelectProductFormField()->columnSpan('full'),
            ]),
            Section::make([
              Placeholder::make('subtotal')
                ->label('Subtotal:')
                ->content(fn(Get $get) => '$ ' . number_format(
                  array_sum(
                    array_map(
                      fn($item) => ((float)str_replace(',', '.', str_replace('.', '', $item['price'] ?? 0))) *
                        ((float)str_replace(',', '.', str_replace('.', '', $item['quantity'] ?? 1))),
                      $get('saleItems') ?? []
                    )
                  ),
                  2,
                  ',',
                  '.'
                ))
                ->hidden()
                ->inlineLabel(),

              Placeholder::make('discount')
                ->label('Descuento:')
                ->content(fn(Get $get) => '$ ' . number_format(
                  array_sum(
                    array_map(
                      fn($item) => ((float)str_replace(',', '.', str_replace('.', '', $item['price'] ?? 0))) *
                        ((float)str_replace(',', '.', str_replace('.', '', $item['quantity'] ?? 1))) *
                        ((float)str_replace(',', '.', str_replace('.', '', $item['discount'] ?? 0)) / 100),
                      $get('saleItems') ?? []
                    )
                  ),
                  2,
                  ',',
                  '.'
                ))
                ->hidden()
                ->inlineLabel(),

              Placeholder::make('total')
                ->label('Total:')
                ->content(fn(Get $get) => '$ ' . number_format(
                  array_sum(
                    array_map(
                      fn($item) => (float)str_replace(',', '.', str_replace('.', '', $item['subtotal'])),
                      $get('saleItems') ?? []
                    )
                  ), // Aquí solo sumamos los subtotales ya con descuento
                  2,
                  ',',
                  '.'
                ))
                ->inlineLabel(),

              TextInput::make('amount')
                ->label('Recibido')
                ->required()
                ->numeric()
                ->placeholder('(F2)')
                ->minValue(0)
                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                ->stripCharacters('.')
                ->live()
                ->validationMessages([
                  'required' => 'La cantidad recibida es obligatoria',
                  'numeric' => 'La cantidad recibida debe ser un número',
                  'min_value' => 'La cantidad recibida no puede ser menor a $ 0 ',
                ])
                ->id('amountInput')
                ->extraAttributes([
                  // Keybinding: F2 o Ctrl/Cmd + K
                  'x-on:keydown.window.prevent.f2' => '$el.querySelector("#amountInput").focus()',
                  'x-on:keydown.window.ctrl.k.prevent' => '$el.querySelector("#amountInput").focus()',
                  'x-on:keydown.window.meta.k.prevent' => '$el.querySelector("#amountInput").focus()',
                ])
                ->inlineLabel(),

              Placeholder::make('change')
                ->label('Cambio:')
                ->content(function (Get $get): string {
                  // 1. Obtener total numérico de la venta
                  $totalSale = array_sum(
                    array_map(
                      fn($item) => (float)str_replace(',', '.', str_replace('.', '', $item['subtotal'])),
                      $get('saleItems') ?? []
                    )
                  );

                  // 2. Calcular el total pagado
                  $totalPaid = $get('amount') ?? 0;
                  // Parsear el monto
                  $totalPaid = str_replace(',', '.', str_replace('.', '', $totalPaid));
                  $totalPaid = (float)$totalPaid;

                  // 3. Calcular la diferencia (será negativa si falta pagar)
                  $change = $totalPaid - $totalSale;

                  // 4. Formatear y devolver SIEMPRE la diferencia (positiva o negativa)
                  return '$ ' . number_format($change, 2, ',', '.');
                })
                ->inlineLabel(),

            ])->grow(false),
          ])->from('md'),
          TableRepeater::make('saleItems')
            ->label('Productos de la venta rápida')
            ->colStyles([
              'product_name' => 'width: 450px;',
            ])
            ->schema([
              TextInput::make('product_name')
                ->label('Producto')
                ->readOnly(),
              TextInput::make('quantity')
                ->label('Cantidad')
                ->numeric()
                ->required()
                ->default(1)
                ->minValue(1)
                ->live(debounce: 1000)
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                  // Obtener el stock disponible para validación
                  $stock = $get('stock') ?? 0;

                  // Si la cantidad ingresada excede el stock, ajustarla al máximo disponible
                  if ($state > $stock) {
                    $set('quantity', $stock);
                    $unit = $stock === 1 ? 'unidad disponible' : 'unidades disponibles';
                    // Actualizar el estado de la cantidad con el valor del stock
                    $state = $stock;
                    // Enviar una notificación de advertencia
                    Notification::make()
                      ->title('Cantidad máxima excedida')
                      ->body("Actualmente solo hay {$stock} {$unit} para este producto")
                      ->warning()
                      ->send();
                  }

                  // Calcular el subtotal
                  $price = str_replace('.', '', $get('price')) ?? 0;
                  $discount = $get('discount') ?? 0;
                  $subtotal = ($price * $state) * (1 - ($discount / 100));

                  // Establecer el subtotal formateado
                  $set('subtotal', number_format(round($subtotal, 2), 0, ',', '.'));
                })
                ->validationMessages([
                  'required' => 'La cantidad es obligatoria',
                  'numeric' => 'La cantidad debe ser un número',
                  'min_value' => 'La cantidad debe ser al menos 1',
                ]),
              TextInput::make('discount')
                ->label('Dto (%)')
                ->numeric()
                // ->required()
                ->placeholder(function ($get) {
                  $discountAllowed = $get('discount_allowed') ?? 0;
                  $price = str_replace('.', '', $get('price')) ?? 0;
                  $discountPlaceholder = intval(($discountAllowed * 100) / $price);
                  return $discountPlaceholder ? "{$discountPlaceholder}%" : 0;
                })
                ->minValue(0)
                ->live(debounce: 1000)
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                  // Obtener las variables necesarias
                  $price = str_replace('.', '', $get('price')) ?? 0;
                  $quantity = $get('quantity') ?? 1;
                  $discountApplicable = $get('discount_applicable') ?? false;

                  // Si es 0 no hacer nada
                  if (intval($state) == 0) {
                    $subtotal = ($price * $quantity);
                    $set('subtotal', number_format(round($subtotal, 2), 0, ',', '.'));
                    return;
                  }

                  // Si el descuento no es aplicable, fijar el descuento a 0 y notificar.
                  if (!$discountApplicable) {
                    $set('discount', 0);
                    // Actualizar el estado con el valor ajustado.
                    $state = 0;
                    Notification::make()
                      ->title('Descuento no aplicable')
                      ->body('Este producto no permite descuentos')
                      ->warning()
                      ->send();
                  } else {
                    // Calcular el descuento máximo permitido en porcentaje
                    $discountAllowedAmount = $get('discount_allowed') ?? 0;
                    $maxDiscountPercentage = ($price > 0) ? intval(($discountAllowedAmount * 100) / $price) : 0;

                    // Si el descuento ingresado excede el máximo permitido, ajustarlo
                    if ((float)$state > $maxDiscountPercentage) {
                      $set('discount', $maxDiscountPercentage);
                      // Actualizar el estado con el valor ajustado.
                      $state = $maxDiscountPercentage;
                      Notification::make()
                        ->title('Descuento máximo excedido')
                        ->body("El descuento máximo permitido para este producto es de {$maxDiscountPercentage}%")
                        ->warning()
                        ->send();
                    }
                  }

                  // Calcular el subtotal
                  $subtotal = ($price * $quantity) * (1 - ($state / 100));

                  // Establecer el subtotal formateado
                  $set('subtotal', number_format(round($subtotal, 2), 0, ',', '.'));
                })
                ->validationMessages([
                  'required' => 'El descuento es obligatorio',
                  'numeric' => 'El descuento debe ser un número',
                  'min_value' => 'El descuento no puede ser negativo',
                ]),

              Hidden::make('price'),
              Hidden::make('discount_applicable'),
              Hidden::make('discount_allowed'),
              Hidden::make('stock'),
              TextInput::make('price')
                ->label('Precio')
                ->readOnly(),
              TextInput::make('subtotal')
                ->label('Subtotal')
                ->readOnly(),
            ])
            ->colStyles([
              'product_name' => 'width: 400px;',
            ])
            ->columns(12)
            ->columnSpan('full')
            ->defaultItems(0)
            ->reorderable(false)
            ->addable(false),
        ]
      );
  }

  /**
   * Return form schema for sale payment.
   *
   * @return Section
   */
  public static function salePaymentFormSchema(): Section
  {
    return Section::make('Información del pago')
      ->columns([
        'default' => 1,
      ])
      ->schema([
        Grid::make(2)
          ->schema([
            Grid::make([
              'default' => 2,
              'md' => 2,
            ])
              ->schema([
                Grid::make([
                  'default' => 1,
                  'md' => 2,
                ])
                  ->schema([
                    Select::make('customer_id')
                      ->label('Cliente')
                      ->relationship('customer', 'name')
                      ->getOptionLabelFromRecordUsing(fn($record) => "{$record->name} - {$record->document_number}")
                      ->placeholder('Selecciona un cliente')
                      ->createOptionForm(CustomerResource::customerFormSchema())
                      // ->createOptionUsing(fn(array $data) => resolve(CustomerManager::class)->createCustomer($data)->id)
                      ->createOptionAction(function (Action $action) {
                        return $action
                          ->modalHeading('Crear cliente')
                          ->modalSubmitActionLabel('Crear')
                          // ->slideOver()
                          ->closeModalByClickingAway(false);
                      })
                      ->validationMessages([
                        'required' => 'El cliente es obligatorio',
                      ])
                      ->searchable()
                      ->native(false)
                      ->required()
                      // ->preload()
                      ->default(1),
                    Select::make('invoice_type')
                      ->label('Tipo de factura')
                      ->options([
                        'FVD' => 'Factura de venta',
                        'FVE' => 'Factura de venta electrónica',
                      ])
                      ->placeholder('Selecciona un tipo de factura')
                      ->native(false)
                      ->required()
                      ->validationMessages([
                        'required' => 'El tipo de factura es obligatoria',
                      ]),
                  ]),
                Placeholder::make('total_amount_payment')
                  ->label('Total a pagar:')
                  ->content(fn(Get $get): string => $get('calculated_total_display') ?? '$ 0,00')
                  ->inlineLabel(),
                Placeholder::make('change')
                  ->label('Cambio:')
                  ->content(function (Get $get): string {
                    // 1. Obtener total numérico de la venta
                    $totalSale = (float)($get('calculated_total_numeric') ?? 0);

                    // 2. Obtener los items de pago ingresados
                    $paymentItems = $get('payment') ?? [];

                    // 3. Calcular el total pagado
                    $totalPaid = 0;
                    foreach ($paymentItems as $item) {
                      $amountStr = $item['amount'] ?? '0';
                      // Parsear el monto
                      $numericAmountStr = str_replace(',', '.', str_replace('.', '', $amountStr));
                      $totalPaid += (float)$numericAmountStr;
                    }

                    // 4. Calcular la diferencia (será negativa si falta pagar)
                    $change = $totalPaid - $totalSale;

                    // 5. Formatear y devolver SIEMPRE la diferencia (positiva o negativa)
                    return '$ ' . number_format($change, 2, ',', '.');
                  })
                  ->inlineLabel(),
              ])
          ]),
        Repeater::make('payment')
          ->label('')
          ->columns(['default' => 1, 'md' => 4])
          ->schema([
            Grid::make(['default' => 1, 'md' => 4])
              ->schema([
                Select::make('payment_id')
                  ->label('Método de pago')
                  ->options([
                    'Efectivo' => 'Efectivo',
                    'Transferencia' => 'Transferencia',
                    'Credito' => 'Crédito',
                  ])
                  ->placeholder('Selecciona un método de pago')
                  ->native(false)
                  ->required()
                  ->reactive()
                  ->columnSpan(['default' => 1, 'md' => 2]),
                TextInput::make('amount')
                  ->label('Cantidad')
                  ->required()
                  ->autofocus()
                  ->autocomplete(false)
                  ->numeric()
                  ->minValue(0)
                  ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                  ->stripCharacters('.')
                  ->live()
                  ->validationMessages([
                    'required' => 'La cantidad es obligatoria',
                    'numeric' => 'La cantidad debe ser un número',
                    'min_value' => 'La cantidad no puede ser menor a $ 0 ',
                  ])
                  ->columnSpan(['default' => 1, 'md' => fn(Get $get) => $get('payment_id') === 'Transferencia' ? 1 : 2]),

                TextInput::make('reference')
                  ->label('Referencia')
                  ->autocomplete(false)
                  ->required(fn(Get $get) => $get('payment_id') === 'Transferencia')
                  ->visible(fn(Get $get) => $get('payment_id') === 'Transferencia') // Visibilidad
                  ->validationMessages([
                    'required' => 'La referencia es obligatoria',
                  ])
                  ->columnSpan(['default' => 1, 'md' => 1]),
              ]),
          ])
          ->addActionLabel('Agregar otro método de pago')
          ->defaultItems(1)
          ->reorderable(false),
      ]);
  }

  /**
   * Get code product form field.
   *
   * @return TextInput
   */
  public static function getCodeProductormField(): TextInput
  {
    return TextInput::make('product_code')
      ->label('Buscar producto por código')
      ->autocomplete(false)
      ->autofocus()
      ->placeholder('Escanea el código del producto (F3)')
      ->id('productCodeInput')
      ->extraAttributes([
        'x-on:keydown.enter.prevent' => 'window.Livewire.dispatch("scanBarcode")',
        // Keybinding: F2 o Ctrl/Cmd + K
        'x-on:keydown.window.prevent.f3' => '$el.querySelector("#productCodeInput").focus()',
        'x-on:keydown.window.ctrl.k.prevent' => '$el.querySelector("#productCodeInput").focus()',
        'x-on:keydown.window.meta.k.prevent' => '$el.querySelector("#productCodeInput").focus()',
      ]);
  }

  /**
   * Get code product form field.
   *
   * @return TextInput
   */
  public static function getQuickCodeProductormField(): TextInput
  {
    return TextInput::make('product_code')
      ->label('Buscar producto por código')
      ->autocomplete(false)
      ->autofocus()
      ->placeholder('Escanea el código del producto (F3)')
      ->id('productQuickCodeInput')
      ->reactive()
      ->live()
      ->extraAttributes([
        // Keybinding: F2 o Ctrl/Cmd + K
        'x-on:keydown.window.prevent.f3' => '$el.querySelector("#productQuickCodeInput").focus()',
        'x-on:keydown.window.ctrl.k.prevent' => '$el.querySelector("#productQuickCodeInput").focus()',
        'x-on:keydown.window.meta.k.prevent' => '$el.querySelector("#productQuickCodeInput").focus()',
      ])
      ->afterStateUpdated(function (Set $set, Get $get, $state) {
        if (!$state) return;
        // Usar la misma lógica de escaneo pero con el ID del producto
        self::handleQuickBarcodeScan($set, $get,  $state);
      });
  }


  /**
   * Get select product form field.
   *
   * @return Select
   */
  public static function getSelectProductFormField(): Select
  {
    return Select::make('id')
      ->label('Buscar producto por nombre')
      ->relationship('products', 'name', fn($query) => $query->where('status', 'Activo'))
      ->getOptionLabelFromRecordUsing(function ($record) {
        return "{$record->name} - $ " . number_format($record->sale_price, 0, ',', '.');
      })
      ->searchable()
      // ->preload()
      ->native(false)
      ->placeholder('Escribe el nombre del producto')
      ->reactive()
      ->afterStateUpdated(function (Set $set, Get $get, $state) {
        if (!$state) return;

        $product = Product::where('id', $state)->first();
        if (!$product) {
          Notification::make()
            ->title('No hay inventario para este producto')
            ->danger()
            ->send();
          return;
        }

        // Usar la misma lógica de escaneo pero con el ID del producto
        self::handleProductSelection($set, $get, $product);
      });
  }


  /**
   * Handle barcode scan.
   *
   * @param Set $set
   * @param Get $get
   * @param string|null $state
   * @return void
   */
  public static function handleBarcodeScan(callable $set, callable $get, ?string $state): void
  {
    if (!$state) return;

    // Buscar el inventario por código de barras
    $product = Product::where('sku', $state)->where('status', 'Activo')
      ->first();

    if (!$product) {
      // Limpiar el campo del código escaneado
      $set('product_code', '');
      Notification::make()
        ->title('Producto no encontrado')
        ->danger()
        ->send();
      return;
    }

    // Verificar si hay stock suficiente
    if ($product->stock <= 0) {
      // Limpiar el campo del código escaneado
      $set('product_code', '');
      Notification::make()
        ->title('Producto sin stock')
        ->body("El producto {$product->name} no tiene stock disponible")
        ->warning()
        ->send();
      return;
    }

    // Obtener los items actuales del Repeater
    $saleItems = $get('saleItems') ?? [];

    $found = false;
    foreach ($saleItems as $index => $item) {
      if (isset($item['id']) && $item['id'] == $product->id) {
        // Si ya existe, aumentar la cantidad
        $currentQuantity = $item['quantity'] ?? 0;
        if ($currentQuantity + 1 > $product->stock) {
          // Limpiar el campo del código escaneado
          $set('product_code', '');
          $set('quantity', $product->stock);
          $unit = $product->stock === 1 ? 'unidad disponible' : 'unidades disponibles';
          Notification::make()
            ->title('Cantidad máxima excedida')
            ->body("Actualmente solo hay {$product->stock} {$unit} para este producto")
            ->warning()
            ->send();
          return;
        }
        $currentQuantity = $item['quantity'] ?? 0;
        if ($currentQuantity + 1 > $product->stock) {
          // Limpiar el campo del código escaneado
          $set('product_code', '');
          $set('quantity', $product->stock);
          $unit = $product->stock === 1 ? 'unidad disponible' : 'unidades disponibles';
          Notification::make()
            ->title('Cantidad máxima excedida')
            ->body("Actualmente solo hay {$product->stock} {$unit} para este producto")
            ->warning()
            ->send();
          return;
        }
        $quantity = ($item['quantity'] ?? 0) + 1;
        $price = $item['price'] ?? $product->sale_price;
        $price = str_replace('.', '', $price);
        $price = $price ?: $product->sale_price;
        $discount = $item['discount'] ?? 0;
        $subtotal = ($price * $quantity) * (1 - ($discount / 100));
        $saleItems[$index]['quantity'] = $quantity;
        $saleItems[$index]['subtotal'] = number_format($subtotal, 0, ',', '.');

        // Mover el item actualizado al principio
        $updatedItem = $saleItems[$index];
        unset($saleItems[$index]);
        array_unshift($saleItems, $updatedItem);

        $found = true;
        break;
      }
    }

    if (!$found) {
      // Agregar el nuevo producto al PRINCIPIO del array
      array_unshift($saleItems, [
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => number_format(round($product->sale_price, 2), 0, ',', '.'),
        // 'price' => $product->sale_price ?? 0,
        // 'discount' => 0,
        'discount_allowed' => $product->discount_amount ?? 0,
        'discount_applicable' => $product->discount_applicable === 'Si',
        // 'subtotal' => (string)intval($product->sale_price ?? 0),
        'subtotal' => number_format(round($product->sale_price, 2), 0, ',', '.'),
        'stock' => $product->stock ?? 0,
        'id' => $product->id,
      ]);
    }

    // Actualizar el Repeater
    $set('saleItems', $saleItems);

    Notification::make()
      ->title($found ? 'Cantidad aumentada' : 'Producto agregado')
      ->body($product->name)
      ->success()
      ->send();

    // Limpiar el campo del código escaneado
    $set('product_code', '');
  }

  public static function handleQuickBarcodeScan(Set $set, Get $get, ?string $state)
  {
    if (!$state) return;

    // Buscar el inventario por código de barras
    $product = Product::where('sku', $state)->where('status', 'Activo')
      ->first();

    if (!$product) {
      // Limpiar el campo del código escaneado
      $set('product_code', '');
      Notification::make()
        ->title('Producto no encontrado')
        ->danger()
        ->send();
      return;
    }

    // Verificar si hay stock suficiente
    if ($product->stock <= 0) {
      // Limpiar el campo del código escaneado
      $set('product_code', '');
      Notification::make()
        ->title('Producto sin stock')
        ->body("El producto {$product->name} no tiene stock disponible")
        ->warning()
        ->send();
      return;
    }

    // Obtener los items actuales del Repeater
    $saleItems = $get('saleItems') ?? [];

    $found = false;
    foreach ($saleItems as $index => $item) {
      if (isset($item['id']) && $item['id'] == $product->id) {
        // Si ya existe, aumentar la cantidad
        $currentQuantity = $item['quantity'] ?? 0;
        if ($currentQuantity + 1 > $product->stock) {
          // Limpiar el campo del código escaneado
          $set('product_code', '');
          $set('quantity', $product->stock);
          $unit = $product->stock === 1 ? 'unidad disponible' : 'unidades disponibles';
          Notification::make()
            ->title('Cantidad máxima excedida')
            ->body("Actualmente solo hay {$product->stock} {$unit} para este producto")
            ->warning()
            ->send();
          return;
        }
        $quantity = ($item['quantity'] ?? 0) + 1;
        $price = $item['price'] ?? $product->sale_price;
        $price = str_replace('.', '', $price);
        $price = $price ?: $product->sale_price;
        $discount = $item['discount'] ?? 0;
        $subtotal = ($price * $quantity) * (1 - ($discount / 100));
        $saleItems[$index]['quantity'] = $quantity;
        $saleItems[$index]['subtotal'] = number_format($subtotal, 0, ',', '.');

        // Mover el item actualizado al principio
        $updatedItem = $saleItems[$index];
        unset($saleItems[$index]);
        array_unshift($saleItems, $updatedItem);

        $found = true;
        break;
      }
    }

    if (!$found) {
      // Agregar el nuevo producto al PRINCIPIO del array
      array_unshift($saleItems, [
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => number_format(round($product->sale_price, 2), 0, ',', '.'),
        // 'price' => $product->sale_price ?? 0,
        // 'discount' => 0,
        'discount_allowed' => $product->discount_amount ?? 0,
        'discount_applicable' => $product->discount_applicable === 'Si',
        // 'subtotal' => (string)intval($product->sale_price ?? 0),
        'subtotal' => number_format(round($product->sale_price, 2), 0, ',', '.'),
        'stock' => $product->stock ?? 0,
        'id' => $product->id,
      ]);
    }

    // Actualizar el Repeater
    $set('saleItems', $saleItems);

    Notification::make()
      ->title($found ? 'Cantidad aumentada' : 'Producto agregado')
      ->body($product->name)
      ->success()
      ->send();

    // Limpiar el campo del código escaneado
    $set('product_code', '');
  }

  /**
   * Handle product selection.
   *
   * @param Set $set
   * @param Get $get
   * @param Product $product
   * @return void
   */
  public static function handleProductSelection(Set $set, Get $get, Product $product)
  {
    $saleItems = $get('saleItems') ?? [];
    $found = false;

    foreach ($saleItems as $index => $item) {
      if (isset($item['id']) && $item['id'] == $product->id) {
        // Si ya existe, aumentar la cantidad
        $currentQuantity = $item['quantity'] ?? 0;
        if ($currentQuantity + 1 > $product->stock) {
          // Limpiar el campo del código escaneado
          $set('product_code', '');
          $set('quantity', $product->stock);
          $unit = $product->stock === 1 ? 'unidad disponible' : 'unidades disponibles';
          Notification::make()
            ->title('Cantidad máxima excedida')
            ->body("Actualmente solo hay {$product->stock} {$unit} para este producto")
            ->warning()
            ->send();
          return;
        }
        $quantity = ($item['quantity'] ?? 0) + 1;
        $price = $item['price'] ?? $product->sale_price;
        $price = str_replace('.', '', $price);
        $price = $price ?: $product->sale_price;
        $discount = $item['discount'] ?? 0;
        $subtotal = ($price * $quantity) * (1 - ($discount / 100));

        $saleItems[$index]['quantity'] = $quantity;
        $saleItems[$index]['subtotal'] = number_format($subtotal, 0, ',', '.');

        // Mover el item actualizado al principio
        $updatedItem = $saleItems[$index];
        unset($saleItems[$index]);
        array_unshift($saleItems, $updatedItem);

        $found = true;
        break;
      }
    }

    if (!$found) {
      // Agregar el nuevo producto al PRINCIPIO del array
      array_unshift($saleItems, [
        'product_name' => $product->name,
        'quantity' => 1,
        'price' => $product->sale_price ?? 0,
        // 'discount' => 0,
        'discount_allowed' => $product->discount_amount ?? 0,
        'discount_applicable' => $product->discount_applicable === 'Si',
        'subtotal' => (string)intval($product->sale_price ?? 0),
        'stock' => $product->stock ?? 0,
        'id' => $product->id,
      ]);
    }

    // Actualizar el Repeater
    $set('saleItems', $saleItems);

    Notification::make()
      ->title($found ? 'Cantidad aumentada' : 'Producto agregado')
      ->body($product->name)
      ->success()
      ->send();

    // Limpiar la selección
    $set('id', null);
  }
}
