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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Filament\Infolists\Components\Section;
use Filament\Support\RawJs;

class ViewCredit extends ViewRecord
{
  protected static string $resource = CreditResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Action::make('Pagar')
        ->label('Pagar (F2)')
        ->keyBindings(['f2'])
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
                ->native(false)
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
                ->mask(RawJs::make('$money($input, \',\', \'.\')'))
                ->stripCharacters('.')
                // ->default(0)
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
        ->action(function ($data) {
          try {
            // 1️⃣ Validar métodos de pago
            $validatedPayments = Validator::make($data, [
              'payments' => 'required|array|min:1',
              'payments.*.payment_id' => 'required',
              'payments.*.amount' => 'required|numeric|min:0',
              'payments.*.reference' => 'nullable',
            ])->validate();

            // 2️⃣ Obtener el crédito
            $credit = $this->record;

            // 3️⃣ Calcular total pagado
            $totalPagado = collect($validatedPayments['payments'])->sum('amount');

            // 4️⃣ Validar montos
            if ($totalPagado > $credit->total_pending) {
              throw ValidationException::withMessages([
                'message' => 'El valor pagado es mayor al total del crédito',
              ]);
            }

            // 5️⃣ Determinar estado del crédito
            $status = $totalPagado < $credit->total_pending ? 'Pendiente' : 'Pagada';

            if ($status === 'Pagada') {
              // Actualizar estado de la venta
              Sale::where('id', $credit->sale_id)->update(['status' => 'Pagada']);
            }

            // 6️⃣ Actualizar el crédito
            $credit->update([
              'total_paid'    => $credit->total_paid + $totalPagado,
              'total_pending' => $credit->total_pending - $totalPagado,
              'status'        => $status,
            ]);

            // 7️⃣ Crear los pagos y actualizar caja
            foreach ($validatedPayments['payments'] as $payment) {
              Payment::create([
                'sale_id'        => $credit->sale_id,
                'amount'         => $payment['amount'],
                'payment_method' => $payment['payment_id'],
                'reference'      => $payment['reference'] ?? null,
                'payment_date'   => now(),
              ]);

              $box = Box::where('status', 'Abierta')->first();

              if ($payment['payment_id'] === 'Efectivo') {
                $box->increment('credit_payment', $payment['amount']);
                $box->increment('total_sales', $payment['amount']);
              } elseif ($payment['payment_id'] === 'Transferencia') {
                $box->increment('transfer_payment', $payment['amount']);
              }
            }

            // 🔑 8️⃣ Recalcular el "received" real de la venta
            //     Solo suma pagos en Efectivo y Transferencia
            $realReceived = Payment::where('sale_id', $credit->sale_id)
              ->whereIn('payment_method', ['Efectivo', 'Transferencia'])
              ->sum('amount');

            Sale::where('id', $credit->sale_id)
              ->update(['received' => $realReceived]);

            // 9️⃣ Notificación
            Notification::make()
              ->title($status === 'Pagada' ? 'Crédito pagado' : 'Pago parcial')
              ->{$status === 'Pagada' ? 'success' : 'info'}()
              ->send();
          } catch (\Exception $e) {
            Notification::make()
              ->title($e->getMessage())
              ->danger()
              ->color('danger')
              ->send();
            throw $e;
          }
        })

        ->visible(fn() => $this->record->status == 'Pendiente' && Box::latest()->first()?->status == 'Abierta' && Gate::allows('pay', $this->record)),
      Action::make('Ver venta')
        ->label('Ver venta')
        ->color(Color::Purple)
        ->url(route('filament.dashboard.resources.ventas.view', $this->record->sale_id)),
      // ->openUrlInNewTab(),
      Action::make('Ver cliente')
        ->label('Ver cliente')
        ->color(Color::Emerald)
        ->url(route('filament.dashboard.resources.clientes.view', $this->record->sale->customer_id)),
      Action::make('Recibo')
        ->label('Recibo (F10)')
        ->keyBindings(['f10'])
        ->color(Color::Lime)
        ->url(route('recibo-credito', $this->record->id))
        ->openUrlInNewTab(),

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

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Section::make('Detalles del crédito')
          ->columns(4)
          ->schema([
            TextEntry::make('sale.full_invoice_number')
              ->label('Factura')
              ->icon('heroicon-o-document-text'),
            TextEntry::make('customer.name')
              ->label('Cliente')
              ->icon('heroicon-o-user'),
            TextEntry::make('status')
              ->label('Estado')
              ->badge()
              ->color(function ($record) {
                return $record->status == 'Pagada' ? 'success' : 'warning';
              }),
            TextEntry::make('credit_date')
              ->label('Fecha')
              ->icon('heroicon-o-calendar-days')
              ->date('M j, Y h:m a'),
            TextEntry::make('total_credit')
              ->label('Total crédito')
              ->numeric()
              ->icon('heroicon-o-currency-dollar'),
            TextEntry::make('total_paid')
              ->label('Total pagado')
              ->numeric()
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('total_pending')
              ->label('Total pendiente')
              ->numeric()
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('user.name')
              ->label('Cajero')
              ->icon('heroicon-o-user'),
          ])
      ]);
  }
}
