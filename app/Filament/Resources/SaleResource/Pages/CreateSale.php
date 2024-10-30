<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Box;
use App\Models\Credit;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateSale extends CreateRecord
{
  protected static string $resource = SaleResource::class;

  protected function getCreateFormAction(): Action
  {
    return parent::getCreateFormAction()->extraAttributes(['type' => 'button', 'wire:click' => 'create']);
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

  protected function handleRecordCreation(array $data): Model
  {
    try {
      // Validar datos del cliente
      $validatorCustomer = Validator::make($data, [
        'customer_id' => 'required',
      ]);
      $validatedCustomer = $validatorCustomer->validate();

      // Validar datos de los productos
      $validatorProducts = Validator::make($data, [
        'products' => 'required|array|min:1',
        'products.*.product_id' => 'required',
        'products.*.quantity' => 'required|numeric|min:1',
        'products.*.sale_price_product' => 'required|numeric|min:0',
        'products.*.iva' => 'required|numeric|min:0',
        'products.*.discount' => 'nullable|numeric|min:0',
      ]);
      $validatedProducts = $validatorProducts->validate();

      // Si discount es null, se asigna 0
      foreach ($validatedProducts['products'] as $key => $product) {
        if ($product['discount'] === null) {
          $validatedProducts['products'][$key]['discount'] = 0;
        }
      }

      $validatorPayments = Validator::make($data, [
        'payments' => 'required|array|min:1',
        'payments.*.payment_id' => 'required',
        'payments.*.amount' => 'required|numeric|min:0',
        'payments.*.reference' => 'nullable',
      ]);

      $validatedPayments = $validatorPayments->validate();

      // Calcular los totales de la venta
      function calcularTotales($products)
      {
        $totalAmount = 0;
        $totalIVA = 0;
        $totalDiscount = 0;

        foreach ($products as $product) {

          $discountProduct = $product['discount'];
          $totalProduct = $product['sale_price_product'] * $product['quantity'] - $discountProduct;
          $ivaProduct = ($product['sale_price_product'] * $product['quantity'] * $product['iva'] / (100 + $product['iva']));

          // Acumular los totales
          $totalDiscount  += $discountProduct;
          $totalAmount += $totalProduct;
          $totalIVA +=  $ivaProduct;
        }

        return [
          'total_amount' => round($totalAmount, 2),
          'total_iva' => round($totalIVA, 2),
          'total_discount' => round($totalDiscount, 2),
        ];
      }

      // Validar si hay existencias suficientes
      foreach ($validatedProducts['products'] as $product) {
        $productModel = Product::findOrFail($product['product_id']);
        if ($productModel->stock < $product['quantity']) {
          // Lanzar una excepción para detener la creación
          throw ValidationException::withMessages([
            'message' => 'Sin stock suficiente para el producto ' . $productModel->name,
          ]);
        }
      }
      $boxAndInvoice = Sale::getBoxAndInvoiceNumber();

      if (!$boxAndInvoice['box_id'] || !$boxAndInvoice['invoice_number']) {
        // Lanzar una excepción para detener la creación
        throw ValidationException::withMessages([
          'message' => 'No es posible generar el número de factura',
        ]);
      }

      // Calcular los totales
      $totales = calcularTotales($validatedProducts['products']);

      // Calcular el total pagado
      $totalPagado = collect($validatedPayments['payments'])->sum('amount');

      // Verificar si el total pagado es menor al total de la factura
      if ($totalPagado < $totales['total_amount']) {
        throw ValidationException::withMessages([
          'message' => 'El valor pagado es menor al total de la factura',
        ]);
      }

      // Establecer el método de pago dinámicamente
      $paymentMethod = count($validatedPayments['payments']) > 1 ? 'Mixto' : $validatedPayments['payments'][0]['payment_id'];

      // Verificar si en $validatedPayments['payments'] hay un pago con el método de pago 'Crédito'
      $statusSale = collect($validatedPayments['payments'])->contains('payment_id', 'Crédito') ? 'Pendiente' : 'Pagada';

      // Calcular el cambio
      $cambio = $totalPagado - $totales['total_amount'];

      // Crear la venta
      $sale = Sale::create([
        'box_id' => $boxAndInvoice['box_id'],
        'invoice_number' => $boxAndInvoice['invoice_number'],
        'customer_id' => $validatedCustomer['customer_id'],
        'user_id' => Auth::id(),
        'total_amount' => $totales['total_amount'],
        'total_iva' => $totales['total_iva'],
        'total_discount' => $totales['total_discount'],
        'payment_method' => $paymentMethod,
        'received' => $totalPagado,
        'exchange' => $cambio,
        'status' => $statusSale,
        'sale_date' => now(),
      ]);

      // Crear los detalles de la venta y actualizar el stock
      foreach ($validatedProducts['products'] as $product) {
        $discountProduct = $product['discount'];
        $ivaProduct = ($product['sale_price_product'] * $product['quantity'] * $product['iva'] / (100 + $product['iva']));
        $subtotalProduct = $product['sale_price_product'] * $product['quantity'] - $ivaProduct;
        $totalProduct = $product['sale_price_product'] * $product['quantity'] - $discountProduct;

        SaleItem::create([
          'sale_id' => $sale->id,
          'product_id' => $product['product_id'],
          'quantity' => $product['quantity'],
          'unit_price' => $product['sale_price_product'],
          'discount' => $discountProduct,
          'iva' => $ivaProduct,
          'subtotal' => $subtotalProduct,
          'total' => $totalProduct,
        ]);

        // Actualizar el stock del producto
        $productModel = Product::find($product['product_id']);
        $productModel->decrement('stock', $product['quantity']);
      }

      // Crear los pagos
      foreach ($validatedPayments['payments'] as $payment) {
        Payment::create([
          'sale_id' => $sale->id,
          'amount' => $payment['amount'],
          'payment_method' => $payment['payment_id'],
          'reference' => $payment['reference'],
          'payment_date' => now(),
        ]);

        // Crear el crédito si el método de pago es crédito
        if ($payment['payment_id'] == 'Crédito') {
          Credit::create([
            'sale_id' => $sale->id,
            'user_id' => Auth::id(),
            'total_credit' => $payment['amount'],
            'total_pending' => $payment['amount'],
            'credit_date' => now(),
          ]);
        }

        // Actualizar la caja
        $box = Box::where('status', 'Abierta')->first();
        if ($payment['payment_id'] == 'Efectivo') {
          $cashEntry = $payment['amount'] - $sale->exchange;
          $box->increment('cash_payment', $cashEntry);
          $box->increment('total_sales', $cashEntry);
        } elseif ($payment['payment_id'] == 'Transferencia') {
          $box->increment('transfer_payment', $payment['amount']);
          $box->increment('total_sales', $payment['amount']);
        } elseif ($payment['payment_id'] == 'Crédito') {
          $box->increment('credit_sales', $payment['amount']);
          $box->increment('total_sales', $payment['amount']);
        }
      }

      return $sale;
    } catch (\Exception $e) {
      Notification::make()
        ->title($e->getMessage())
        ->danger()
        ->color('danger')
        ->send();
      throw $e;
    }
  }
}
