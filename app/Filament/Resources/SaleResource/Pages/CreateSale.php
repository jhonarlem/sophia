<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Box;
use App\Models\Product;
use App\Models\Sale;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CreateSale extends CreateRecord
{
  protected static string $resource = SaleResource::class;

  protected function getRedirectUrl(): string
  {
    return route('filament.dashboard.resources.ventas.index');
  }

  protected function getCreateFormAction(): Action
  {
    return parent::getCreateFormAction()->extraAttributes(['type' => 'button', 'wire:click' => 'create']);
  }

  public $defaultAction = 'statusBox';

  public function statusBoxAction(): Action
  {
    $statuxBox = Box::latest()->first()?->status;
    return Action::make('checkStatusBox')
      ->visible(fn() => $statuxBox != 'Abierta')
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

  protected function handleRecordCreation(array $data): Model
  {
    // dd($data);
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
          // throw new \Exception("Sin stock suficiente para el producto {$productModel->name}.");
          return Notification::make()
            ->title('Sin stock suficiente')
            ->body("No hay stock suficiente para el producto {$productModel->name}.")
            ->error()
            ->icon('heroicon-o-exclamation')
            ->send();
        }
      }
      $boxAndInvoice = Sale::getBoxAndInvoiceNumber();

      if (!$boxAndInvoice['box_id'] || !$boxAndInvoice['invoice_number']) {
        return
          Notification::make()
          ->title('No es posible generar la factura')
          ->error()
          ->icon('heroicon-o-exclamation')
          ->send();
      }

      // Calcular los totales
      $totales = calcularTotales($validatedProducts['products']);

      // Calcular el total pagado
      $totalPagado = collect($validatedPayments['payments'])->sum('amount');

      // Verificar si el total pagado es menor al total de la factura
      if ($totalPagado < $totales['total_amount']) {
        return Notification::make()
          ->title('El valor pagado es menor al total de la factura')
          ->error()
          ->icon('heroicon-o-exclamation')
          ->send();
      }

      // Establecer el método de pago dinámicamente
      $paymentMethod = count($validatedPayments['payments']) > 1 ? 'Mixto' : $validatedPayments['payments'][0]['payment_id'];

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
        'status' => 'Pagada',
        'sale_date' => now(),
      ]);
      /*  $sale = Sale::create([
        'box_id' => $boxAndInvoice['box_id'],
        'invoice_number' => $boxAndInvoice['invoice_number'],
        'customer_id' => $validatedCustomer['customer_id'],
        'user_id' => Auth::id(),
        'total_amount' => collect($validatedProducts['products'])->sum('total_price_product'),
        'total_iva' => collect($validatedProducts['products'])->sum(function ($product) {
          return ($product['sale_price_product'] * $product['quantity'] * $product['iva']) / 100; // Cambiado a sale_price_product
        }),
        'total_discount' => collect($validatedProducts['products'])->sum('discount'),
        'payment_method' => 'Efectivo', // Considera hacer esto dinámico en el futuro
        'status' => 'Pagada',
        'sale_date' => now(),
      ]); */

      // Crear los detalles de la venta y actualizar el stock
      /*  foreach ($validatedProducts['products'] as $product) {
        $sale->saleDetails()->create([
          'product_id' => $product['product_id'],
          'quantity' => $product['quantity'],
          'sale_price' => $product['sale_price'],
          'iva' => $product['iva'],
          'discount' => $product['discount'],
          'total' => $product['total'],
        ]);

        // Actualizar el stock del producto
        $productModel = Product::find($product['product_id']);
        $productModel->decrement('stock', $product['quantity']);
      } */

      return $sale;
    } catch (\Exception $e) {
      Notification::make()
        ->title('Error al crear la venta')
        ->body($e->getMessage())
        ->danger()
        ->send();
      throw $e;
    }
  }
}
