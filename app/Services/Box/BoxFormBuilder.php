<?php

namespace App\Services\Box;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;

use Illuminate\Database\Eloquent\Builder;
use Filament\Support\RawJs;
use Illuminate\Support\Carbon;

class BoxFormBuilder
{
  public static function closedBoxFormSchema(): array
  {
    return [
      static::getClosedBoxFormSchema()
    ];
  }

  public static function openBoxFormSchema(): array
  {
    return [
      static::getOpenBoxFormSchema()
    ];
  }

  /**
   * Return form schema for open box.
   *
   * @return Section
   */
  public static function getOpenBoxFormSchema(): Section
  {
    return Section::make('Información de la apertura de la caja')
      ->description('Selecciona la resolución e ingresa el dinero inicial para abrir la caja')
      ->columns([
        'default' => 1,
      ])
      ->schema([
        static::getResolutionFormField(),
        Fieldset::make('Arqueo de caja')
          ->schema([
            Grid::make([
              'default' => 1,
              'sm' => 3,
              'md' => 4,
            ])
              ->schema([
                static::getHundredBillsFormField(),
                static::getFiftyBillsFormField(),
                static::getTwentyBillsFormField(),
                static::getTenBillsFormField(),
                static::getFiveBillsFormField(),
                static::getTwoBillsFormField(),
                static::getThousandCoinFormField(),
                static::getFiveHundredCoinFormField(),
                static::getTwoHundredCoinFormField(),
                static::getOneHundredCoinFormField(),
                static::getFiftyCoinFormField(),
                static::getTotalFormField(),
              ]),
          ]),
      ]);
  }

  /**
   * Return form schema for closed box.
   *
   * @return Section
   */
  public static function getClosedBoxFormSchema(): Section
  {
    return Section::make('Información del cierre de la caja')
      ->description('Ingresa el dinero total, incluyendo la base para completar el cierre de caja. Ten en cuenta que esta acción no se puede deshacer')
      ->columns([
        'default' => 1,
      ])
      ->schema([
        // static::getCashRegisterFormField(),
        Fieldset::make('Arqueo de caja')
          ->schema([
            Grid::make([
              'default' => 1,
              'sm' => 3,
              'md' => 4,
            ])
              ->schema([
                static::getHundredBillsFormField(),
                static::getFiftyBillsFormField(),
                static::getTwentyBillsFormField(),
                static::getTenBillsFormField(),
                static::getFiveBillsFormField(),
                static::getTwoBillsFormField(),
                static::getThousandCoinFormField(),
                static::getFiveHundredCoinFormField(),
                static::getTwoHundredCoinFormField(),
                static::getOneHundredCoinFormField(),
                static::getFiftyCoinFormField(),
                static::getTotalFormField(),
              ]),
          ]),
        static::getNoteFormField(),
      ]);
  }


  /**
   * Get base amount form field.
   *
   * @return TextInput
   */
  public static function getBaseAmountFormField(): TextInput
  {
    return TextInput::make('base_amount')
      ->label('Dinero base')
      ->required()
      ->autocomplete(false)
      ->autofocus()
      ->numeric()
      ->minValue(0)
      ->mask(RawJs::make('$money($input, \',\', \'.\')'))
      ->stripCharacters('.')
      ->validationMessages([
        'required' => 'El dinero base es obligatorio',
        'numeric' => 'El dinero base debe ser un número',
        'min_value' => 'El dinero base no puede ser menor a $ 0 ',
      ]);
  }

  /**
   * Get product form field.
   *
   * @return Select
   */
  public static function getCashRegisterFormField(): Select
  {
    return Select::make('cash_register_id')
      ->label('Caja')
      ->relationship('cashRegister', 'name', fn(Builder $query) => $query->where('status', 'Activa'))
      ->placeholder('Selecciona una caja')
      ->native(false)
      ->preload()
      ->required()
      ->validationMessages([
        'required' => 'La caja es obligatoria',
      ]);
  }

  /**
   * Get product form field.
   *
   * @return Select
   */
  public static function getResolutionFormField(): Select
  {
    return Select::make('resolution_id')
      ->label('Resolución')
      ->relationship('resolution', 'number', fn(Builder $query) => $query->whereDate('end_date', '>=', now()))
      ->placeholder('Selecciona una resolución')
      ->getOptionLabelFromRecordUsing(fn($record) => "{$record->number} - Prefijo: {$record->prefix} - Vence: " . Carbon::parse($record->end_date)->locale('es')->isoFormat('D MMM YYYY'))
      ->native(false)
      ->preload()
      ->required()
      ->validationMessages([
        'required' => 'La caja es obligatoria',
      ]);
  }

  /**
   * Get base amount form field.
   *
   * @return TextInput
   */
  public static function getRealAmountFormField(): TextInput
  {
    return TextInput::make('real_amount')
      ->label('Dinero de cierre')
      ->required()
      ->autocomplete(false)
      ->autofocus()
      ->numeric()
      ->minValue(0)
      ->mask(RawJs::make('$money($input, \',\', \'.\')'))
      ->stripCharacters('.')
      ->validationMessages([
        'required' => 'El dinero de cierre es obligatorio',
        'numeric' => 'El dinero de cierre debe ser un número',
        'min_value' => 'El dinero de cierre no puede ser menor a $ 0 ',
      ]);
  }


  /**
   * Get observations form field.
   *
   * @return Textarea
   */
  public static function getNoteFormField(): Textarea
  {
    return Textarea::make('observations')
      ->label('Notas')
      ->autocomplete(false)
      ->string()
      ->maxLength(255)
      ->columnSpanFull()
      ->validationMessages([
        'max_length' => 'La nota no debe exceder los 255 caracteres',
      ]);
  }


  /**
   * Get hundred bills form field.
   *
   * @return TextInput
   */
  public static function getHundredBillsFormField(): TextInput
  {
    return TextInput::make('hundred_bills')
      ->label('Billetes de 100.000')
      ->required()
      ->autocomplete(false)
      ->autofocus()
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de billetes de 100 es obligatoria',
        'numeric' => 'La cantidad de billetes de 100 debe ser un número',
        'min_value' => 'La cantidad de billetes de 100 no puede ser menor a 0',
      ]);
  }


  /**
   * Get fifty bills form field.
   *
   * @return TextInput
   */
  public static function getFiftyBillsFormField(): TextInput
  {
    return TextInput::make('fifty_bills')
      ->label('Billetes de 50.000')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de billetes de 50 es obligatoria',
        'numeric' => 'La cantidad de billetes de 50 debe ser un número',
        'min_value' => 'La cantidad de billetes de 50 no puede ser menor a 0',
      ]);
  }


  /**
   * Get twenty bills form field.
   *
   * @return TextInput
   */
  public static function getTwentyBillsFormField(): TextInput
  {
    return TextInput::make('twenty_bills')
      ->label('Billetes de 20.000')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de billetes de 20 es obligatoria',
        'numeric' => 'La cantidad de billetes de 20 debe ser un número',
        'min_value' => 'La cantidad de billetes de 20 no puede ser menor a 0',
      ]);
  }


  /**
   * Get ten bills form field.
   *
   * @return TextInput
   */
  public static function getTenBillsFormField(): TextInput
  {
    return TextInput::make('ten_bills')
      ->label('Billetes de 10.000')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de billetes de 10 es obligatoria',
        'numeric' => 'La cantidad de billetes de 10 debe ser un número',
        'min_value' => 'La cantidad de billetes de 10 no puede ser menor a 0',
      ]);
  }


  /**
   * Get five bills form field.
   *
   * @return TextInput
   */
  public static function getFiveBillsFormField(): TextInput
  {
    return TextInput::make('five_bills')
      ->label('Billetes de 5.000')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de billetes de 5 es obligatoria',
        'numeric' => 'La cantidad de billetes de 5 debe ser un número',
        'min_value' => 'La cantidad de billetes de 5 no puede ser menor a 0',
      ]);
  }


  /**
   * Get two bills form field.
   *
   * @return TextInput
   */
  public static function getTwoBillsFormField(): TextInput
  {
    return TextInput::make('two_bills')
      ->label('Billetes de 2.000')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de billetes de 2 es obligatoria',
        'numeric' => 'La cantidad de billetes de 2 debe ser un número',
        'min_value' => 'La cantidad de billetes de 2 no puede ser menor a 0',
      ]);
  }


  /**
   * Get thousand coin form field.
   *
   * @return TextInput
   */
  public static function getThousandCoinFormField(): TextInput
  {
    return TextInput::make('thousand_coins')
      ->label('Monedas de 1.000')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de monedas de 1.000 es obligatoria',
        'numeric' => 'La cantidad de monedas de 1.000 debe ser un número',
        'min_value' => 'La cantidad de monedas de 1.000 no puede ser menor a 0',
      ]);
  }


  /**
   * Get five hundred coin form field.
   *
   * @return TextInput
   */
  public static function getFiveHundredCoinFormField(): TextInput
  {
    return TextInput::make('five_hundred_coins')
      ->label('Monedas de 500')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de monedas de 500 es obligatoria',
        'numeric' => 'La cantidad de monedas de 500 debe ser un número',
        'min_value' => 'La cantidad de monedas de 500 no puede ser menor a 0',
      ]);
  }


  /**
   * Get two hundred coin form field.
   *
   * @return TextInput
   */
  public static function getTwoHundredCoinFormField(): TextInput
  {
    return TextInput::make('two_hundred_coins')
      ->label('Monedas de 200')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de monedas de 200 es obligatoria',
        'numeric' => 'La cantidad de monedas de 200 debe ser un número',
        'min_value' => 'La cantidad de monedas de 200 no puede ser menor a 0',
      ]);
  }


  /**
   * Get one hundred coin form field.
   *
   * @return TextInput
   */
  public static function getOneHundredCoinFormField(): TextInput
  {
    return TextInput::make('one_hundred_coins')
      ->label('Monedas de 100')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de monedas de 100 es obligatoria',
        'numeric' => 'La cantidad de monedas de 100 debe ser un número',
        'min_value' => 'La cantidad de monedas de 100 no puede ser menor a 0',
      ]);
  }


  /**
   * Get fifty coin form field.
   *
   * @return TextInput
   */
  public static function getFiftyCoinFormField(): TextInput
  {
    return TextInput::make('fifty_coins')
      ->label('Monedas de 50')
      ->required()
      ->autocomplete(false)
      ->numeric()
      ->minValue(0)
      ->reactive()
      ->live(debounce: 500)
      ->afterStateUpdated(function ($state, callable $set, callable $get) {
        self::calculateTotal($get, $set);
      })
      ->validationMessages([
        'required' => 'La cantidad de monedas de 50 es obligatoria',
        'numeric' => 'La cantidad de monedas de 50 debe ser un número',
        'min_value' => 'La cantidad de monedas de 50 no puede ser menor a 0',
      ]);
  }


  /**
   * Get Total form field.
   *
   * @return TextInput
   */
  public static function getTotalFormField(): TextInput
  {
    return TextInput::make('total')
      ->label('TOTAL')
      ->default(0)
      ->readOnly()
      ->minValue(0)
      ->mask(RawJs::make('$money($input, \',\', \'.\')'))
      ->suffixIcon('heroicon-o-banknotes')
      ->suffixIconColor('success')
    ;
  }


  protected static function calculateTotal(callable $get, callable $set)
  {
    $denominations = [
      'hundred_bills' => 100000,
      'fifty_bills' => 50000,
      'twenty_bills' => 20000,
      'ten_bills' => 10000,
      'five_bills' => 5000,
      'two_bills' => 2000,
      'thousand_coins' => 1000,
      'five_hundred_coins' => 500,
      'two_hundred_coins' => 200,
      'one_hundred_coins' => 100,
      'fifty_coins' => 50,
    ];

    $total = 0;

    foreach ($denominations as $field => $value) {
      $amount = (int) ($get($field) ?? 0);
      $total += $amount * $value;
    }

    // cambiar el formato del total a miles
    $total = number_format($total, 0, ',', '.');

    $set('total', $total);
  }
}
