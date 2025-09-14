<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Box;
use App\Models\Sale;
use App\Services\ElectronicSale\ElectronicSaleManager;
use App\Services\Sale\SaleFormBuilder;
use App\Services\Sale\SaleManager;
use Exception;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Resources\Pages\CreateRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;

class CreateSale extends CreateRecord
{
  protected static string $resource = SaleResource::class;

  // Quitar los botones de create y cancel
  protected function getFormActions(): array
  {
    return [];
  }


  public $defaultAction = 'statusBox';

  public function statusBoxAction(): Action
  {
    $statusBox = Box::latest()->first()?->status;
    $invoiceNumber = Sale::generateInvoiceNumber();

    // Si la caja está cerrada
    if ($statusBox != 'Abierta') {
      return Action::make('Estado de la caja')
        ->requiresConfirmation()
        ->closeModalByClickingAway(false)
        ->closeModalByEscaping(false)
        ->modalCloseButton(false)
        ->color('warning')
        ->modalIcon('heroicon-o-lock-closed')
        ->modalHeading('Caja cerrada')
        ->modalDescription('Actualmente, no es posible registrar una venta ya que la caja se encuentra cerrada. Por favor, abre la caja para poder continuar con el proceso de venta.')
        ->modalSubmitActionLabel('Abrir caja')
        ->modalCancelAction(false)
        ->action(function () {
          return redirect()->route('filament.dashboard.resources.caja.index');
        });
    }

    // Si no hay números de factura disponibles
    if ($invoiceNumber === null) {
      return Action::make('Estado de la resolución')
        ->requiresConfirmation()
        ->closeModalByClickingAway(false)
        ->closeModalByEscaping(false)
        ->modalCloseButton(false)
        ->color('danger')
        ->modalIcon('heroicon-o-exclamation-triangle')
        ->modalHeading('Resolución agotada')
        ->modalDescription('Se han agotado los números de factura en la resolución actual asociada a la caja. Para continuar con el proceso de venta, debes: 1) Configurar una nueva resolución en el sistema, y 2) Cerrar la caja actual y abrir una nueva caja asociándola a la nueva resolución configurada.')
        ->modalSubmitActionLabel('Ir a resoluciones')
        ->modalCancelAction(false)
        ->action(function () {
          return redirect()->route('filament.dashboard.resources.resoluciones.index');
        });
    }

    return Action::make('Estado de la caja')
      ->visible(false);
  }

  /**
   * Define los listeners para eventos específicos.
   * En este caso, estamos escuchando el evento 'scanBarcode' para manejar el escaneo de códigos de barras.
   * Esto permite que el componente reaccione a eventos emitidos desde el frontend, como el escaneo de un código de barras.
   */
  protected function getListeners(): array
  {
    return [
      'scanBarcode' => 'handleBarcodeScan',
    ];
  }

  /**
   * Maneja el escaneo de código de barras.
   * Este método se invoca cuando se recibe el evento 'scanBarcode'.
   */
  public function handleBarcodeScan(): void
  {
    $state = $this->data['product_code'] ?? '';
    if (empty($state)) return;

    // Crear funciones compatibles con callable
    $set = function ($key, $value) {
      data_set($this->data, $key, $value);
    };

    $get = function ($key) {
      return data_get($this->data, $key);
    };

    SaleFormBuilder::handleBarcodeScan($set, $get, $state);

    // Actualizar el formulario con los nuevos datos
    $this->form->fill($this->data);
  }


  protected function handleRecordCreation(array $data): Model
  {
    /* if ($data['invoiceType'] === 'FVE') {
      $sale = resolve(ElectronicSaleManager::class)->createElectronicSale($data);
      $this->redirect(route('filament.admin.resources.ventas-electronicas.view', [
        'record' => $sale->id,
      ]));
      Notification::make()
        ->title('Venta electrónica creada')
        ->success()
        ->send();
    } else {
      $sale = resolve(SaleManager::class)->createSale($data);
      $this->redirect(route('filament.admin.resources.ventas.view', [
        'record' => $sale->id,
      ]));
      Notification::make()
        ->title('Venta creada')
        ->success()
        ->send();
    } */
    if ($data['invoiceType'] === 'FVE') {
      Notification::make()
        ->title('Funcionalidad no disponible')
        ->body('La creación de facturas electrónicas no está disponible en esta versión del sistema. Para más información, contacta al desarrollador.')
        ->info()
        ->color('info')
        ->send();
      // Return a dummy Sale model to satisfy the return type
      return new Sale();
    }
    $sale = resolve(SaleManager::class)->createSale($data);
    $this->redirect(route('filament.dashboard.resources.ventas.view', [
      'record' => $sale->id,
    ]));
    Notification::make()
      ->title('Venta creada')
      ->success()
      ->send();
    return $sale;
  }


  protected function getHeaderActions(): array
  {
    return [
      Action::make('VentaRapida')
        ->label('Venta rápida (F4)')
        ->icon('heroicon-o-bolt')
        ->color(Color::Sky)
        ->keyBindings(['f4'])
        ->tooltip('Venta rápida')
        ->modalHeading('Crear venta rápida')
        ->form(SaleFormBuilder::getQuickSaleFormSchema())
        ->modalSubmitActionLabel('Pagar')
        ->modalWidth(MaxWidth::ScreenLarge)
        // ->slideOver()
        ->closeModalByClickingAway(false)
        ->action(
          function ($data, $livewire) {
            $saleItems = $data['saleItems'] ?? [];
            $paymentData = [[
              'payment_id' => 'Efectivo',
              'amount' => $data['amount'] ?? 0,
            ]];
            $customer = $data['customer_id'] ?? 1;
            $invoiceType = $data['invoice_type'] ?? 'FVD';
            try {
              $data = [
                'saleItems' => $saleItems,
                'paymentData' => $paymentData,
                'customer' => $customer,
                'invoiceType' => $invoiceType,
              ];
              $sale = resolve(SaleManager::class)->createSale($data);
              Notification::make()
                ->title('Venta rápida creada')
                ->success()
                //  ->persistent()
                ->actions([
                  NotificationAction::make('Imprimir recibo')
                    ->button()
                    ->url(route('recibo', $sale->id))
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-printer')
                    ->color('primary'),
                ])
                ->send();
            } catch (Exception $e) {
              Notification::make()
                ->title('Error al crear venta rápida')
                ->body($e->getMessage())
                ->danger()
                ->send();
              $livewire->halt();
            }
          }
        )
      /*  ->mountUsing(function (Form $form, $livewire) {
          // Obtener los items de la venta del estado del formulario principal
          $saleItems = $this->data['saleItems'];
          // Calcular el total usando la misma lógica que en el Placeholder 'total' del form principal
          $total = array_sum(
            array_map(
              // Asegúrate que la lógica de parseo sea idéntica a la del placeholder 'total'
              fn($item) => (float) str_replace(',', '.', str_replace('.', '', $item['subtotal'] ?? '0')),
              $saleItems
            )
          );

          // Formatear el total
          $formattedTotal = '$ ' . number_format($total, 2, ',', '.');

          // Llenar el estado del formulario del modal con el total calculado
          $form->fill([
            'calculated_total_display_quick_sale' => $formattedTotal,
            'calculated_total_numeric_quick_sale' => $total,
            'customer_id' => 1,
            'invoice_type' => 'FVD',
            'payment' => [
              [
                'payment_id' => 'Efectivo',
                'amount' => 0
              ]
            ]
          ]);
          $livewire->dispatch('scanBarcode');
        }) */,
      Action::make('Pagar')
        ->label('Pagar (F2)')
        ->keyBindings(['f2'])
        ->tooltip('Completar venta')
        ->disabled(fn(): bool => empty($this->data['saleItems']))
        ->modalHeading('Pagar venta')
        ->form(SaleFormBuilder::getSalePaymentForm())
        ->modalSubmitActionLabel('Pagar')
        ->modalWidth(MaxWidth::ScreenLarge)
        // ->slideOver()
        ->closeModalByClickingAway(false)
        ->mountUsing(function (Form $form) {
          // Obtener los items de la venta del estado del formulario principal
          $saleItems = $this->data['saleItems'];
          // Calcular el total usando la misma lógica que en el Placeholder 'total' del form principal
          $total = array_sum(
            array_map(
              // Asegúrate que la lógica de parseo sea idéntica a la del placeholder 'total'
              fn($item) => (float)str_replace(',', '.', str_replace('.', '', $item['subtotal'] ?? '0')),
              $saleItems
            )
          );

          // Formatear el total
          $formattedTotal = '$ ' . number_format($total, 2, ',', '.');

          // Llenar el estado del formulario del modal con el total calculado
          $form->fill([
            'calculated_total_display' => $formattedTotal,
            'calculated_total_numeric' => $total,
            'customer_id' => 1,
            'invoice_type' => 'FVD',
            'payment' => [
              [
                'payment_id' => 'Efectivo',
                'amount' => null
              ]
            ]
          ]);
        })
        ->action(
          function ($data, $livewire) {
            $saleItems = $this->data['saleItems'] ?? [];
            $paymentData = $data['payment'] ?? [];
            $customer = $data['customer_id'] ?? 1;
            $invoiceType = $data['invoice_type'] ?? 'FVD';
            try {
              // Ejemplo: Validar que el pago cubra el total (necesitarás recalcular el total aquí también)
              $saleItems = $this->data['saleItems'] ?? [];
              /*  $totalSale = array_sum(
                array_map(
                  fn($item) => (float) str_replace(',', '.', str_replace('.', '', $item['subtotal'] ?? '0')),
                  $saleItems
                )
              ); */

              /*  $totalPaid = 0;
              foreach ($data['payment'] ?? [] as $paymentItem) {
                // Asegúrate de parsear correctamente el monto del pago según tu máscara
                $amount = (float) str_replace(',', '.', str_replace('.', '', $paymentItem['amount'] ?? '0'));
                $totalPaid += $amount;
              } */

              /* if ($totalPaid < $totalSale) {
                throw ValidationException::withMessages([
                  'message' => 'El monto pagado es menor al total de la venta',
                ]);
              } */

              $this->handleRecordCreation([
                'saleItems' => $saleItems,
                'paymentData' => $paymentData,
                'customer' => $customer,
                'invoiceType' => $invoiceType,
              ]);
            } catch (Exception $e) {
              Notification::make()
                ->title('Error al crear venta')
                ->body($e->getMessage())
                ->danger()
                ->send();
              $livewire->halt();
            }
          }
        ),
    ];
  }

  // Es buena idea tener el método form definido aunque uses el builder,
  // para que la página sepa qué formulario manejar.
  public function form(Form $form): Form
  {
    // Llama a tu builder para construir el formulario principal
    return SaleFormBuilder::form($form);
  }
}
