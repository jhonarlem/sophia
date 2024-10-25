<?php

namespace App\Filament\Resources\CreditResource\Pages;

use App\Filament\Resources\CreditResource;
use App\Models\Box;
use App\Models\Credit;
use App\Models\Payment;
use App\Models\Sale;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ViewCredit extends ViewRecord
{
  protected static string $resource = CreditResource::class;

  protected function getHeaderActions(): array
  {

    return [
      Action::make('Pagar')
        ->label('Pagar')
        ->modalHeading('Pagar crédito')
        ->modalDescription('Ingresa los métodos de pago que utilizarás para pagar el crédito.')
        ->modalSubmitActionLabel('Pagar')
        ->form([
          Repeater::make('payments')
            ->label('')
            ->columns(4)
            ->schema([
              Select::make('payment_id')
                ->label('Método de pago')
                ->options([
                  'Efectivo' => 'Efectivo',
                  'Transferencia' => 'Transferencia',
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
        ->action(
          function ($data) {
            try {
              // Validar los metodos de pago
              $validatorPayments = Validator::make($data, [
                'payments' => 'required|array|min:1',
                'payments.*.payment_id' => 'required',
                'payments.*.amount' => 'required|numeric|min:0',
                'payments.*.reference' => 'nullable',
              ]);

              $validatedPayments = $validatorPayments->validate();

              // obtener el credito
              $credit = $this->record;

              // Calcular el total pagado
              $totalPagado = collect($validatedPayments['payments'])->sum('amount');

              // Verificar si el total pagado es menor al total del credito
              if ($totalPagado > $credit->total_pending) {
                throw ValidationException::withMessages([
                  'message' => 'El valor pagado es mayor al total del crédito',
                ]);
              }

              // Verificar si el total pagado es menor que el total del credito
              if ($totalPagado < $credit->total_pending) {
                $status = 'Pendiente';
              } else {
                $status = 'Pagada';
                // Actualizar el estado de la venta
                Sale::where('id', $credit->sale_id)
                  ->update(['status' => 'Pagada']);
              }

              // Actualizar el credito
              $credit->update([
                'total_paid' => $credit->total_paid + $totalPagado,
                'total_pending' => $credit->total_pending - $totalPagado,
                'status' => $status,
              ]);

              // Crear los pagos
              foreach ($validatedPayments['payments'] as $payment) {
                Payment::create([
                  'sale_id' => $credit->sale_id,
                  'amount' => $payment['amount'],
                  'payment_method' => $payment['payment_id'],
                  'reference' => $payment['reference'],
                  'payment_date' => now(),
                ]);

                // Actualizar la caja
                $box = Box::where('status', 'Abierta')->first();
                if ($payment['payment_id'] == 'Efectivo') {
                  $cashEntry = $payment['amount'];
                  $box->increment('cash_payment', $cashEntry);
                  $box->increment('total_sales', $cashEntry);
                } elseif ($payment['payment_id'] == 'Transferencia') {
                  $box->increment('transfer_payment', $payment['amount']);
                }
              }

              // Enviar notificación dependiendo del estado del credito
              if ($status === 'Pagada') {
                Notification::make()
                  ->title('Crédito pagado')
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
                ->color('danger')
                ->send();
              throw $e;
            }
          }
        )
        ->visible(fn() => $this->record->status == 'Pendiente'),
    ];
  }

  public function getViewData(): array
  {
    return [
      'sales' => $this->record->sales,
      'customer' => $this->record->customer,
      'user' => $this->record->user,
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [];
  }

  public function form(Form $form): Form
  {
    return $form->schema([
      Grid::make(12)->schema([
        Section::make('Detalles del crédito')
          ->schema([
            Grid::make(2)
              ->schema([
                Placeholder::make('sale.invoice_number')
                  ->label('Factura:')
                  ->content(fn($record) => $record->sale->invoice_number ?? '')
                  ->inlineLabel(),
                Placeholder::make('name')
                  ->label('Cliente:')
                  ->content(fn($record) => $record->customer->name ?? '')
                  ->inlineLabel(),
                Placeholder::make('status')
                  ->label('Estado:')
                  ->content(fn($record) => $record->status ?? '')
                  ->inlineLabel(),
                Placeholder::make('total_credit')
                  ->label('Total crédito:')
                  ->content(fn($record) => $record->total_credit !== null
                    ? number_format($record->total_credit, 2, ',', '.')
                    : '')
                  ->inlineLabel(),
                Placeholder::make('total_paid')
                  ->label('Total pagado:')
                  ->content(fn($record) => $record->total_paid !== null
                    ? number_format($record->total_paid, 2, ',', '.')
                    : '')
                  ->inlineLabel(),
                Placeholder::make('total_pending')
                  ->label('Total pendiente:')
                  ->content(fn($record) => $record->total_pending !== null
                    ? number_format($record->total_pending, 2, ',', '.')
                    : '')
                  ->inlineLabel(),
                Placeholder::make('credit_date')
                  ->label('Fecha:')
                  ->content(fn($record) => $record->credit_date !== null
                    ? Carbon::parse($record->credit_date)->locale('es')->isoFormat('MMM D YYYY HH:mm') : '')
                  ->inlineLabel(),
                Placeholder::make('user.name')
                  ->label('Cajero:')
                  ->content(fn($record) => $record->user->name ?? '')
                  ->inlineLabel(),
              ]),
          ])
          ->columnSpan(12),
      ])
    ]);
  }
}
