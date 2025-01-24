<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Faker\Provider\ar_EG\Text;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Tables\Enums\ActionsPosition;

class UserResource extends Resource
{
  protected static ?string $model = User::class;
  protected static ?string $navigationGroup = 'Seguridad';
  protected static ?int $navigationSort = 1;
  protected static ?string $navigationIcon = 'heroicon-o-user';
  protected static $title = 'Usuarios';
  protected static ?string $label = 'usuario';
  protected static ?string $pluralLabel = 'usuarios';
  protected static ?string $slug = 'usuarios';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Section::make('Información del usuario')
          ->columns(2)
          ->schema([
            Forms\Components\TextInput::make('name')
              ->label('Nombre')
              ->required()
              ->maxLength(255)
              ->autofocus()
              ->autocomplete(false),
            Forms\Components\TextInput::make('email')
              ->label('Correo electrónico')
              ->email()
              ->required()
              ->maxLength(255)
              ->autocomplete(false)
              ->unique(ignoreRecord: true)
              ->reactive()
              ->afterStateUpdated(function ($state, $set, $get) {
                if (User::where('email', $state)->exists()) {
                  Notification::make()
                    ->title('Correo en uso')
                    ->warning()
                    ->send();
                }
              }),
            Forms\Components\TextInput::make('cellphone')
              ->label('Celular')
              ->tel()
              ->maxLength(10)
              ->minLength(10)
              ->required()
              ->autocomplete(false),
            Forms\Components\Select::make('roles')
              ->relationship('roles', 'name')
              ->label('Rol')
              ->searchable()
              ->required()
              ->preload()
              ->placeholder('Selecciona un rol'),
            Forms\Components\Select::make('status')
              ->label('Estado')
              ->options([
                'Activo' => 'Activo',
                'Inactivo' => 'Inactivo',
              ])
              ->default('Activo')
              ->searchable()
              ->preload()
              ->required(),
            Forms\Components\TextInput::make('password')
              ->password()
              ->required()
              ->maxLength(255)
              ->revealable()
              ->hidden(fn($context) => $context === 'edit' || $context === 'view'),
          ])
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
        Tables\Columns\TextColumn::make('name')
          ->label('Nombre')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('email')
          ->label('Correo electrónico')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('cellphone')
          ->label('Celular')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('roles.name')
          ->label('Rol')
          ->sortable()
          ->badge()
          ->color('primary')
          ->searchable(),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'Activo' => 'success',
            'Inactivo' => 'warning',
          })
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('email_verified_at')
          ->label('Correo verificado')
          ->dateTime()
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
            'Activo' => 'Activo',
            'Inactivo' => 'Inactivo',
          ])
          ->searchable()
          ->preload()
          ->placeholder('Selecciona un estado'),
      ])
      ->actions([
        ActionGroup::make([
          Tables\Actions\ViewAction::make()->label('Ver')->color('primary'),
          Tables\Actions\EditAction::make()->color('warning'),
          Action::make('Cambiar contraseña')
            ->label('Actualizar')
            ->color('primary')
            ->icon('heroicon-o-finger-print')
            ->modalHeading('Actualizar contraseña')
            ->modalWidth(MaxWidth::Medium)
            ->modalSubmitActionLabel('Actualizar')
            ->form([
              TextInput::make('password')
                ->password()
                ->revealable()
                ->required()
                ->label('Nueva contraseña'),
            ])
            ->action(function ($record, $data) {
              try {
                // Verificar si es mi usuario para no actualizar la contraseña
                if ($record->id === Auth::id()) {
                  Notification::make()
                    ->title('Cambia tu contraseña desde tu perfil')
                    ->info()
                    ->icon('heroicon-o-information-circle')
                    ->persistent()
                    ->actions([
                      NotificationAction::make('Ir a mi perfil')
                        ->button()
                        ->url(route('filament.dashboard.pages.perfil')),

                    ])
                    ->send();
                  return;
                }

                $record->password = bcrypt($data['password']);
                $record->save();

                Notification::make()
                  ->title('Actualizada')
                  ->success()
                  ->send();
              } catch (\Exception $e) {
                Notification::make()
                  ->title('Error al actualizar')
                  ->danger()
                  ->send();
              }
            }),
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

  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        TextEntry::make('id')
          ->label('ID:'),
        TextEntry::make('name')
          ->label('Nombre:'),
        TextEntry::make('email')
          ->label('Correo electrónico:'),
        TextEntry::make('cellphone')
          ->label('Celular:'),
        TextEntry::make('roles.name')
          ->label('Rol:')
          ->badge()
          ->color('primary'),
        TextEntry::make('status')
          ->label('Estado:')
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'Activo' => 'success',
            'Inactivo' => 'warning',
          }),
        TextEntry::make('email_verified_at')
          ->label('Correo verificado:')
          ->dateTime(),
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
      'index' => Pages\ListUsers::route('/'),
      'create' => Pages\CreateUser::route('/crear'),
      'edit' => Pages\EditUser::route('/editar/{record}'),
    ];
  }
}
