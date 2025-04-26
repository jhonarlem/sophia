<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResolutionResource\Pages;
use App\Filament\Resources\ResolutionResource\RelationManagers;
use App\Models\Resolution;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ResolutionResource extends Resource
{
  protected static ?string $model = Resolution::class;

  protected static ?string $navigationIcon = 'heroicon-o-document-text';
  protected static ?string $navigationGroup = 'Configuración';
  protected static ?int $navigationSort = 2;
  protected static $title = 'Resoluciones';
  protected static ?string $label = 'resolución';
  protected static ?string $pluralLabel = 'resoluciones';
  protected static ?string $slug = 'resoluciones';

  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Section::make('Información de la resolución')
          ->columns(2)
          ->schema([
            Forms\Components\TextInput::make('prefix')
              ->label('Prefijo')
              ->autofocus()
              ->autocomplete(false)
              ->required()
              ->maxLength(20),
            Forms\Components\TextInput::make('number')
              ->label('Número de resolución')
              ->autocomplete(false)
              ->required()
              ->numeric(),
            Forms\Components\TextInput::make('start_invoice_number')
              ->label('Número de inicio de factura')
              ->autocomplete(false)
              ->required()
              ->numeric(),
            Forms\Components\TextInput::make('end_invoice_number')
              ->label('Número de fin de factura')
              ->autocomplete(false)
              ->required()
              ->numeric(),
            Forms\Components\DatePicker::make('start_date')
              ->label('Fecha de inicio de vigencia')
              ->required()
              ->closeOnDateSelection()
              ->displayFormat('d/m/Y')
              ->firstDayOfWeek(7)
              ->native(false),
            Forms\Components\DatePicker::make('end_date')
              ->label('Fecha de fin de vigencia')
              ->required()
              ->closeOnDateSelection()
              ->displayFormat('d/m/Y')
              ->firstDayOfWeek(7)
              ->native(false),
            Forms\Components\TextInput::make('valid_months')
              ->label('Meses de validez')
              ->autocomplete(false)
              ->required()
              ->numeric(),
            Forms\Components\Textarea::make('description')
              ->label('Descripción')
              ->autocomplete(false)
              ->maxLength(255),
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
        Tables\Columns\TextColumn::make('prefix')
          ->label('Prefijo')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('number')
          ->label('Resolución')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('start_invoice_number')
          ->label('Número inicio')
          ->numeric()
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('end_invoice_number')
          ->label('Número fin')
          ->numeric()
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->badge()
          ->color(fn($record) => Carbon::parse($record->end_date)->endOfDay()->isPast() ? 'danger' : 'success')
          ->formatStateUsing(fn($record) => Carbon::parse($record->end_date)->endOfDay()->isPast() ? 'No Vigente' : 'Vigente')
          ->getStateUsing(fn($record) => Carbon::parse($record->end_date)->endOfDay()->isPast() ? 'No Vigente' : 'Vigente')
          ->icon(fn($record) => Carbon::parse($record->end_date)->endOfDay()->isPast() ? 'heroicon-s-x-circle' : 'heroicon-s-check-badge')
          ->searchable()
          ->sortable(),
        Tables\Columns\TextColumn::make('start_date')
          ->label('Fecha inicio')
          ->searchable()
          ->date()
          ->sortable(),
        Tables\Columns\TextColumn::make('end_date')
          ->label('Fecha fin')
          ->searchable()
          ->date()
          ->sortable(),
        Tables\Columns\TextColumn::make('valid_months')
          ->label('Meses de validez')
          ->searchable()
          ->numeric()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('description')
          ->label('Descripción')
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

  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Fieldset::make('')
          ->schema([
            TextEntry::make('id')
              ->label('ID:'),
            TextEntry::make('prefix')
              ->label('Prefijo'),
            TextEntry::make('number')
              ->label('Número de resolución:'),
            TextEntry::make('start_invoice_number')
              ->label('Número de inicio de factura:')
              ->numeric(),
            TextEntry::make('end_invoice_number')
              ->label('Número de fin de factura:')
              ->numeric(),
            TextEntry::make('status')
              ->label('Estado:')
              ->badge()
              ->color(fn($record) => Carbon::parse($record->end_date)->endOfDay()->isPast() ? 'danger' : 'success')
              ->formatStateUsing(fn($record) => Carbon::parse($record->end_date)->endOfDay()->isPast() ? 'No Vigente' : 'Vigente')
              ->getStateUsing(fn($record) => Carbon::parse($record->end_date)->endOfDay()->isPast() ? 'No Vigente' : 'Vigente')
              ->icon(fn($record) => Carbon::parse($record->end_date)->endOfDay()->isPast() ? 'heroicon-s-x-circle' : 'heroicon-s-check-badge'),
            TextEntry::make('start_date')
              ->label('Fecha de inicio de vigencia:')
              ->date(),
            TextEntry::make('end_date')
              ->label('Fecha de fin de vigencia:')
              ->date(),
            TextEntry::make('valid_months')
              ->label('Meses de validez:')
              ->formatStateUsing(fn($record) => $record->valid_months . ' meses'),
            TextEntry::make('description')
              ->label('Descripción:'),
            TextEntry::make('created_at')
              ->label('Creado:')
              ->dateTime(),
            TextEntry::make('updated_at')
              ->label('Actualizado:')
              ->dateTime(),
          ]),
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
      'index' => Pages\ListResolutions::route('/'),
      'create' => Pages\CreateResolution::route('/crear'),
      'edit' => Pages\EditResolution::route('/editar/{record}'),
    ];
  }
}
