<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConfigurationResource\Pages;
use App\Filament\Resources\ConfigurationResource\RelationManagers;
use App\Models\Configuration;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class ConfigurationResource extends Resource
{
  protected static ?string $model = Configuration::class;

  protected static ?string $navigationIcon = 'heroicon-o-cog';
  protected static ?string $navigationGroup = 'Configuración';
  protected static ?int $navigationSort = 3;
  protected static $title = 'Empresa';
  protected static ?string $label = 'empresa';
  protected static ?string $pluralLabel = 'empresa';
  protected static ?string $slug = 'ajustes';


  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Section::make('Información de la empresa')
          ->columns(2)
          ->schema([
            Forms\Components\Select::make('document_type')
              ->label('Tipo de documento')
              ->options([
                'CC' => 'Cédula',
                'NIT' => 'NIT',
                'CE' => 'Cédula de extranjería',
                'PA' => 'Pasaporte',
                'PEP' => 'Permiso especial de permanencia',
                'DIE' => 'Documento de identificación extranjero',
              ])
              ->placeholder('Selecciona un tipo de documento')
              ->searchable()
              ->preload()
              ->required(),
            Forms\Components\TextInput::make('document_number')
              ->label('Número de documento')
              ->required()
              ->autocomplete(false)
              ->maxLength(20),
            Forms\Components\TextInput::make('legal_representative')
              ->label('Representante legal')
              ->required()
              ->autocomplete(false)
              ->maxLength(255),
            Forms\Components\TextInput::make('name')
              ->label('Nombre de la empresa')
              ->required()
              ->autocomplete(false)
              ->maxLength(255),
            Forms\Components\TextInput::make('email')
              ->label('Correo electrónico')
              ->email()
              ->autocomplete(false)
              ->required()
              ->maxLength(255),
            Forms\Components\TextInput::make('cellphone')
              ->label('Celular')
              ->tel()
              ->required()
              ->autocomplete(false)
              ->maxLength(10),
            Forms\Components\TextInput::make('address')
              ->label('Dirección')
              ->autocomplete(false)
              ->required()
              ->maxLength(255),
            Forms\Components\TextInput::make('slogan')
              ->label('Eslogan')
              ->autocomplete(false)
              ->required(),
            Forms\Components\FileUpload::make('image')
              ->label('Logo')
              ->image()
              ->disk('public')
              ->visibility('private')
              ->directory('images')
              ->maxSize(250)
              ->imageEditor(),
            /* Forms\Components\FileUpload::make('image')
              ->label('Logo')
              ->disk('public')
              ->directory('images')
              ->visibility('public')
              ->moveFiles()
              ->maxSize(512)
              ->image()
              ->avatar()
              ->imageEditor()
              ->circleCropper()
              ->previewable() (bloqueado:mixed-content)
              ->default(fn($record) => $record->image ? Storage::url($record->image) : null), */

          ])
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('document_type')
          ->searchable(),
        Tables\Columns\TextColumn::make('document_number')
          ->searchable(),
        Tables\Columns\TextColumn::make('legal_representative')
          ->searchable(),
        Tables\Columns\TextColumn::make('name')
          ->searchable(),
        Tables\Columns\TextColumn::make('email')
          ->searchable(),
        Tables\Columns\TextColumn::make('cellphone')
          ->searchable(),
        Tables\Columns\TextColumn::make('address')
          ->searchable(),
        Tables\Columns\ImageColumn::make('image'),
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
      'index' => Pages\EditConfiguration::route('/'),
      /* 'create' => Pages\CreateConfiguration::route('/crear'),
      'edit' => Pages\EditConfiguration::route('/editar/{record}'), */
    ];
  }
}
