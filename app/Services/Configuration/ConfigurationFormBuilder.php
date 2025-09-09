<?php

namespace App\Services\Configuration;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;

class ConfigurationFormBuilder
{
  /**
   * Return form schema for supplier.
   *
   * @param Form $form
   * @return Form
   */
  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        static::getConfigurationFormSchema(),
      ]);
  }

  /**
   * Return form schema for configuration.
   *
   * @return array
   */
  public static function configurationFormSchema(): array
  {
    return [
      static::getConfigurationFormSchema()
    ];
  }

  /**
   * Return configuration form schema.
   *
   * @return Section
   */
  public static function getConfigurationFormSchema(): Section
  {
    return Section::make('Información de la empresa o negocio')
      ->columns([
        'default' => 1,
        'md' => 2,
      ])
      ->schema([
        Grid::make([
          'default' => 1,
          'sm' => 1,
          'md' => 12,
        ])
          ->columnSpan([
            'default' => 1,
            'md' => 2,
          ])
          ->schema([
            static::getDocumentTypeFormField()->columnSpan([
              'default' => 1,
              'md' => 6,
            ]),
            static::getDocumentFormField()->columnSpan([
              'default' => 1,
              'md' => 4,
            ]),
            static::getDVFormField()->columnSpan([
              'default' => 1,
              'md' => 2,
            ]),
          ]),
        static::getCompanyFormField(),
        static::getLegalRepresentativeFormField(),
        static::getEmailFormField(),
        static::getCellphoneFormField(),
        static::getStreetFormField(),
        static::getCityFormField(),
        static::getDepartmentFormField(),
        static::getRegimeFormField(),
        static::getImageFormField(),
      ]);
  }

  /**
   * Return document type form field.
   *
   * @return Select
   */
  public static function getDocumentTypeFormField(): Select
  {
    return Select::make('document_type')
      ->label('Tipo de documento')
      ->options([
        'NIT' => 'NIT',
        'CC' => 'Cédula de ciudadanía',
      ])
      ->placeholder('Selecciona un tipo de documento')
      ->native(false)
      ->required()
      ->validationMessages([
        'required' => 'El tipo de documento es obligatorio',
      ]);
  }

  /**
   * Return document form field.
   *
   * @return TextInput
   */
  public static function getDocumentFormField(): TextInput
  {
    return TextInput::make('document_number')
      ->label('Número de documento')
      ->required()
      ->unique(ignoreRecord: true)
      ->autocomplete(false)
      ->autofocus()
      ->numeric()
      ->maxLength(30)
      ->minLength(5)
      ->validationMessages([
        'required' => 'El número de documento es obligatorio',
        'unique' => 'El número de documento ya está registrado',
        'numeric' => 'El número de documento debe ser numérico',
        'min_length' => 'El número de documento debe tener al menos 5 dígitos',
        'max_length' => 'El número de documento no puede tener más de 30 dígitos',
      ]);
  }

  /**
   * Return DV form field.
   *
   * @return TextInput
   */
  public static function getDVFormField(): TextInput
  {
    return TextInput::make('dv')
      ->label('Dv')
      ->numeric()
      ->maxLength(1)
      ->autocomplete(false)
      ->default(null)
      ->required()
      ->validationMessages([
        'required' => 'El dígito de verificación es obligatorio',
        'numeric' => 'El dígito de verificación debe ser numérico',
        'max_length' => 'El dígito de verificación no puede tener más de 1 dígito',
      ]);
  }

  /**
   * Return name form field.
   *
   * @return TextInput
   */
  public static function getLegalRepresentativeFormField(): TextInput
  {
    return TextInput::make('legal_representative')
      ->label('Representante legal')
      ->required()
      ->string()
      ->minLength(5)
      ->maxLength(100)
      ->autocomplete(false)
      ->validationMessages([
        'required' => 'El representante legal es obligatorio',
        'min_length' => 'El representante legal debe tener al menos 5 caracteres',
        'max_length' => 'El representante legal no debe exceder los 100 caracteres',
      ]);
  }

  /**
   * Return company form field.
   *
   * @return TextInput
   */
  public static function getCompanyFormField(): TextInput
  {
    return TextInput::make('company')
      ->label('Razón social')
      ->required()
      ->string()
      ->minLength(4)
      ->maxLength(100)
      ->required()
      ->autocomplete(false)
      ->validationMessages([
        'required' => 'La razón social es obligatoria',
        'min_length' => 'El nombre debe tener al menos 4 caracteres',
        'max_length' => 'El nombre no debe exceder los 100 caracteres',
      ]);
  }

  /**
   * Return email form field.
   *
   * @return TextInput
   */
  public static function getEmailFormField(): TextInput
  {
    return TextInput::make('email')
      ->label('Correo electrónico')
      ->email()
      ->required()
      ->maxLength(50)
      ->autocomplete(false)
      ->validationMessages([
        'required' => 'El correo electrónico es obligatorio',
        'email' => 'El correo electrónico no es válido',
        'max_length' => 'El correo electrónico no debe exceder los 50 caracteres',
      ]);
  }

  /**
   * Return cellphone form field.
   *
   * @return TextInput
   */
  public static function getCellphoneFormField(): TextInput
  {
    return TextInput::make('cellphone')
      ->label('Celular')
      ->tel()
      ->required()
      ->maxLength(10)
      ->minLength(10)
      ->autocomplete(false)
      ->validationMessages([
        'required' => 'El celular es obligatorio',
        'max_length' => 'El celular debe tener 10 dígitos',
        'min_length' => 'El celular debe tener 10 dígitos',
      ]);
  }

  /**
   * Return street form field.
   *
   * @return TextInput
   */
  public static function getStreetFormField(): TextInput
  {
    return TextInput::make('street')
      ->label('Dirección')
      ->required()
      ->maxLength(255)
      ->autocomplete(false)
      ->validationMessages([
        'max_length' => 'La dirección no debe exceder los 255 caracteres',
        'required' => 'La dirección es obligatoria',
      ]);
  }

  /**
   * Return city form field.
   *
   * @return TextInput
   */
  public static function getCityFormField(): TextInput
  {
    return TextInput::make('city')
      ->label('Ciudad o municipio')
      ->required()
      ->maxLength(255)
      ->autocomplete(false)
      ->validationMessages([
        'max_length' => 'La ciudad o municipio no debe exceder los 255 caracteres',
        'required' => 'La ciudad o municipio es obligatoria',
      ]);
  }


  /**
   * Return department form field.
   *
   * @return TextInput
   */
  public static function getDepartmentFormField(): TextInput
  {
    return TextInput::make('department')
      ->label('Departamento')
      ->required()
      ->maxLength(255)
      ->autocomplete(false)
      ->validationMessages([
        'max_length' => 'El departamento no debe exceder los 255 caracteres',
        'required' => 'El departamento es obligatorio',
      ]);
  }


  /**
   * Return regime form field.
   *
   * @return TextInput
   */
  public static function getRegimeFormField(): TextInput
  {
    return TextInput::make('regime')
      ->label('Regimen')
      ->required()
      ->maxLength(255)
      ->autocomplete(false)
      ->validationMessages([
        'max_length' => 'El regimen no debe exceder los 255 caracteres',
        'required' => 'El regimen es obligatorio',
      ]);
  }

  /**
   * Return image form field.
   *
   * @return FileUpload
   */
  public static function getImageFormField(): FileUpload
  {
    return FileUpload::make('image')
      ->label('Logo')
      ->image()
      ->disk('public')
      ->visibility('private')
      ->directory('images')
      ->columnSpanFull()
      ->maxSize(250)
      ->imageEditor()
      ->validationMessages([
        'max_length' => 'El regimen no debe exceder los 255 caracteres',
        'required' => 'El regimen es obligatorio',
      ]);
  }
}
