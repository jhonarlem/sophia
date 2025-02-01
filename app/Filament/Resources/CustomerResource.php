<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class CustomerResource extends Resource
{
  protected static ?string $model = Customer::class;
  protected static ?int $navigationSort = 4;
  protected static ?string $navigationIcon = 'heroicon-o-users';
  protected static $title = 'Clientes';
  protected static ?string $label = 'cliente';
  protected static ?string $pluralLabel = 'clientes';
  protected static ?string $slug = 'clientes';

  public static function customerFormSchema(): array
  {
    return [
      Section::make('Información del cliente')
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
            ->unique(ignoreRecord: true)
            ->autocomplete(false)
            ->numeric()
            ->maxLength(20)->reactive()
            ->afterStateUpdated(function ($state, $set, $get) {
              $customer = Customer::where('document_number', $state)->first();
              if ($customer && $get('id') !== $customer->id) {
                Notification::make()
                  ->title('Documento ya registrado')
                  ->warning()
                  ->send();
              }
            }),
          Forms\Components\TextInput::make('name')
            ->label('Nombres')
            ->autocomplete(false)
            ->required()
            ->maxLength(255),
          Forms\Components\TextInput::make('email')
            ->label('Correo electrónico')
            ->email()
            ->required()
            ->default("notiene@notiene.com")
            ->maxLength(255)
            ->autocomplete(false),
          Forms\Components\TextInput::make('cellphone')
            ->label('Celular')
            ->tel()
            ->maxLength(10)
            ->minLength(10)
            ->required()
            ->autocomplete(false),
          Forms\Components\TextInput::make('street')
            ->label('Dirección')
            ->autocomplete(false)
            ->maxLength(255),
        ])
    ];
  }

  public static function form(Form $form): Form
  {
    return $form
      ->schema(self::customerFormSchema());
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
        Tables\Columns\TextColumn::make('document_type')
          ->label('Tipo')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('document_number')
          ->label('Documento')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('name')
          ->label('Nombres')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('email')
          ->label('Correo')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('cellphone')
          ->label('Celular')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('street')
          ->label('Dirección')
          ->sortable()
          ->searchable()
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
      // ->recordUrl(null)
      ->defaultSort('created_at', 'desc')
      ->filters([
        //
      ])
      ->actions([
        ActionGroup::make([
          Tables\Actions\ViewAction::make()->label('Ver')->color('primary'),
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
          ->icon('heroicon-o-ellipsis-horizontal-circle'),
      ])
      ->actionsPosition(ActionsPosition::BeforeColumns)
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          ExportBulkAction::make(),
          //Tables\Actions\DeleteBulkAction::make(),
        ]),
      ]);
  }

  public static function getRelations(): array
  {
    return [
      RelationManagers\SalesRelationManager::class,
      RelationManagers\CreditsRelationManager::class,
    ];
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListCustomers::route('/'),
      'create' => Pages\CreateCustomer::route('/crear'),
      'edit' => Pages\EditCustomer::route('/editar/{record}'),
      'view' => Pages\ViewCustomer::route('/ver/{record}'),
    ];
  }
}
