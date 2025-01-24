<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpenseResource\Pages;
use App\Filament\Resources\ExpenseResource\RelationManagers;
use App\Models\Expense;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class ExpenseResource extends Resource
{
  protected static ?string $model = Expense::class;

  protected static ?int $navigationSort = 7;
  protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
  protected static $title = 'Gastos';
  protected static ?string $label = 'gasto';
  protected static ?string $pluralLabel = 'gastos';
  protected static ?string $slug = 'gastos';


  public static function form(Form $form): Form
  {
    return $form
      ->schema([
        Section::make('Información del gasto')
          ->columns(2)
          ->schema([
            Select::make('service_type')
              ->label('Tipo de servicio')
              ->options([
                'Recibo público' => 'Recibo público',
                'Turno' => 'Turno',
                'Servicio' => 'Servicio',
                'Compra' => 'Compra',
                'Crédito' => 'Crédito',
                'Proveedor' => 'Proveedor',
                'Otro' => 'Otro',

              ])
              ->placeholder('Selecciona un tipo de servicio')
              ->searchable()
              ->preload()
              ->required(),
            TextInput::make('payee_name')
              ->label('Nombre del beneficiario')
              ->autocomplete(false)
              ->required()
              ->maxLength(255),
            TextInput::make('amount_total')
              ->label('Monto total')
              ->required()
              ->autocomplete(false)
              ->numeric(),
            TextInput::make('amount_paid')
              ->label('Monto pagado')
              ->required()
              ->autocomplete(false)
              ->numeric(),
            TextInput::make('amount_pending')
              ->label('Monto pendiente')
              ->required()
              ->autocomplete(false)
              ->numeric(),
            DatePicker::make('due_date')
              ->label('Fecha de vencimiento')
              ->required(),
            Select::make('status')
              ->label('Estado')
              ->options([
                'Pendiente' => 'Pendiente',
                'Pagada' => 'Pagada',
              ])
              ->placeholder('Selecciona un estado')
              ->searchable()
              ->preload()
              ->required(),
            Textarea::make('description')
              ->label('Descripción')
              ->autocomplete(false)
              ->columnSpanFull(),
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
        Tables\Columns\TextColumn::make('service_type')
          ->label('Tipo de servicio')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('payee_name')
          ->label('Beneficiario')
          ->sortable()
          ->searchable(),
        Tables\Columns\TextColumn::make('amount_total')
          ->label('Total')
          ->searchable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('amount_paid')
          ->label('Pagado')
          ->sortable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('amount_pending')
          ->label('Pendiente')
          ->sortable()
          ->numeric()
          ->sortable(),
        Tables\Columns\TextColumn::make('status')
          ->label('Estado')
          ->sortable()
          ->badge()
          ->color(fn(string $state): string => match ($state) {
            'Pagada' => 'success',
            'Pendiente' => 'warning',
            'Anulada' => 'danger',
          })
          ->searchable(),
        Tables\Columns\TextColumn::make('due_date')
          ->label('Fecha de vencimiento')
          ->searchable()
          ->dateTime()
          ->sortable(),
        Tables\Columns\TextColumn::make('paid_at')
          ->label('Fecha de pago')
          ->searchable()
          ->dateTime()
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('payment_method')
          ->label('Método de pago')
          ->sortable()
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        Tables\Columns\TextColumn::make('user.name')
          ->label('Usuario')
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
        SelectFilter::make('status')
          ->label('Estado')
          ->options([
            'Pagada' => 'Pagada',
            'Pendiente' => 'Pendiente',
            'Anulada' => 'Anulada',
          ])
          ->searchable()
          ->preload()
          ->placeholder('Selecciona un estado'),
      ])
      ->actions([
        ActionGroup::make([
          Tables\Actions\ViewAction::make()->label('Ver')->color('primary'),
          Action::make('Anular')
            ->label('Anular')
            ->requiresConfirmation()
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->modalHeading('Anular gasto')
            ->modalDescription('¿Estás seguro de que deseas anular este gasto? Esta acción es irreversible y no podrá deshacerse.')
            ->modalSubmitActionLabel('Anular')
            ->modalCancelActionLabel('Cancelar')
            ->action(function ($record) {
              try {
                $record->update(['status' => 'Anulada']);
                Notification::make()
                  ->title('Anulado')
                  ->success()
                  ->send();
              } catch (\Exception $e) {
                Notification::make()
                  ->title('Error al anular')
                  ->danger()
                  ->send();
                throw $e;
              }
            })
            ->visible(fn($record) => $record->status !== 'Anulada'),
          Action::make('Pagar')
            ->label('Pagar')
            ->icon('heroicon-o-currency-dollar')
            ->color('success')
            ->modalHeading('Pagar gasto')
            ->modalDescription('Ingresa el monto a pagar y el método de pago.')
            ->modalSubmitActionLabel('Pagar')
            ->form([
              Select::make('payment_method')
                ->label('Método de pago')
                ->options([
                  'Efectivo' => 'Efectivo',
                  'Transferencia' => 'Transferencia',
                  'Otro' => 'Otro',
                ])
                ->placeholder('Selecciona un método de pago')
                ->searchable()
                ->preload()
                ->required(),
              TextInput::make('amount_paid')
                ->label('Monto a pagar')
                ->required()
                ->numeric()
                ->autocomplete(false),
            ])
            ->modalWidth(MaxWidth::Medium)
            ->action(
              function ($record, $data) {
                try {
                  // Validar los datos
                  $validatorExpense = Validator::make($data, [
                    'payment_method' => 'required',
                    'amount_paid' => 'required|numeric|min:0',
                  ]);

                  $validatedExpense = $validatorExpense->validate();

                  // Validar que el monto a pagar no sea mayor al monto pendiente
                  if ($record->amount_pending < $validatedExpense['amount_paid']) {
                    throw ValidationException::withMessages([
                      'message' => 'El valor pagado es mayor al saldo pendiente.',
                    ]);
                  }

                  // Verificar si el total pagado es menor que el total del credito
                  $status = $record->amount_pending > $validatedExpense['amount_paid'] ? 'Pendiente' : 'Pagada';

                  // Actualizar el gasto
                  $record->update([
                    'amount_paid' => $record->amount_paid + $validatedExpense['amount_paid'],
                    'amount_pending' => $record->amount_pending - $validatedExpense['amount_paid'],
                    'status' => $status,
                    'paid_at' => now(),
                    'payment_method' => $validatedExpense['payment_method'],
                  ]);

                  if ($status === 'Pagada') {
                    Notification::make()
                      ->title('Pagado')
                      ->success()
                      ->send();
                  } else {
                    Notification::make()
                      ->title('Pago parcial')
                      ->info()
                      ->send();
                  }
                } catch (\Exception $e) {
                  Notification::make()
                    ->title($e->getMessage())
                    ->danger()
                    ->send();
                  throw $e;
                }
              }
            )
            ->visible(fn($record) => $record->status ===  'Pendiente'),
        ])
          ->icon('heroicon-o-ellipsis-horizontal-circle'),
      ])
      ->actionsPosition(ActionsPosition::BeforeColumns)
      ->bulkActions([
        Tables\Actions\BulkActionGroup::make([
          ExportBulkAction::make(),
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
            TextEntry::make('service_type')
              ->label('Tipo de servicio:'),
            TextEntry::make('payee_name')
              ->label('Beneficiario:'),
            TextEntry::make('amount_total')
              ->label('Total:')
              ->numeric(),
            TextEntry::make('amount_paid')
              ->label('Pagado:')
              ->numeric(),
            TextEntry::make('amount_pending')
              ->label('Pendiente:')
              ->numeric(),
            TextEntry::make('status')
              ->label('Estado:')
              ->badge()
              ->color(fn(string $state): string => match ($state) {
                'Pagada' => 'success',
                'Pendiente' => 'warning',
                'Anulada' => 'danger',
              }),
            TextEntry::make('due_date')
              ->label('Fecha de vencimiento:')
              ->dateTime(),
            TextEntry::make('paid_at')
              ->label('Fecha de pago:')
              ->dateTime(),
            TextEntry::make('payment_method')
              ->label('Método de pago:'),
            TextEntry::make('user.name')
              ->label('Usuario:'),
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
      'index' => Pages\ListExpenses::route('/'),
      'create' => Pages\CreateExpense::route('/crear'),

    ];
  }
}
