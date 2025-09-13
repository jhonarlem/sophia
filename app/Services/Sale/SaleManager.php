<?php

namespace App\Services\Sale;

use App\Models\Box;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Payment;
use App\Models\Credit;
use App\Services\Box\BoxManager;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaleManager
{
  private float $total = 0;
  private float $subtotal = 0;
  private float $discount = 0;
  private float $iva = 0;

  /**
   * Create a new sale.
   *
   * @param array<string, mixed> $data
   * @return Sale
   * @throws MassAssignmentException
   * @throws ValidationException
   * @throws Throwable
   */
  public function createSale(array $data): Sale
  {
    return DB::transaction(function () use ($data) {
      $items = $data['saleItems'];
      $paymentData = $data['paymentData'];
      $customer = $data['customer'];

      // Verificar que haya al menos un item en la venta
      if (empty($items) || count($items) === 0) {
        throw ValidationException::withMessages([
          'message' => 'La venta debe incluir al menos un producto',
        ]);
      }

      // Calcular el total, subtotal, descuento e IVA de los items
      $calculatedItems = $this->calculateItems($items);
      $userId = Auth::id();

      // Busca el turno abierto del usuario
      // $openBox = Box::where('opened_by', $userId)->where('status', 'Abierta')->first();
      $openBox = Box::where('status', 'Abierta')->first();
      if (!$openBox) {
        throw ValidationException::withMessages([
          'message' => 'No tienes un turno abierto para realizar la venta',
        ]);
      }
      // Obtiene los datos de factura
      // $invoiceData = $this->getInvoiceNumber();
      $invoiceData = Sale::getBoxAndInvoiceNumber();

      // Verifica si se incluyó un pago de Crédito
      $isCreditPaymentIncluded = collect($data['paymentData'])->contains('payment_id', 'Credito');

      // Validacion: No permitir credito a cliente con ID 1
      if ($isCreditPaymentIncluded && $customer === 1) {
        throw ValidationException::withMessages([
          'message' => 'No es posible realizar ventas a crédito a CONSUMIDOR FINAL, por favor selecciona o crea un cliente específico'
        ]);
      }

      // Calcular el total pagado
      $totalPayments = collect($paymentData)->sum('amount');

      // Verificar si el total pagado es menor al total de la venta
      if ($totalPayments < $this->total) {
        throw ValidationException::withMessages([
          'message' => 'El monto pagado es inferior al total de la venta, faltan $ ' . number_format($this->total - $totalPayments, 0, ',', '.') . ' para completar el pago'
        ]);
      }

      // Validar que si el metodo de pago es 'Transferencia' el monto no sea mayor o menor que el valor total de la venta
      $transferPayment = collect($data['paymentData'])->firstWhere('payment_id', 'Transferencia');
      if ($transferPayment && ($transferPayment['amount'] < $this->total || $transferPayment['amount'] > $this->total)) {
        throw ValidationException::withMessages([
          'message' => 'El valor de la transferencia debe ser igual al total de la venta',
        ]);
      }

      // Establecer el método de pago dinámicamente
      $salePaymentMethod = count($data['paymentData']) > 1
        ? 'Mixto'
        : ($data['paymentData'][0]['payment_id'] ?? null);


      // Total efectivamente recibido (excluyendo créditos)
      $totalReceivedNow = collect($paymentData)
        ->reject(fn($p) => $p['payment_id'] === 'Credito')
        ->sum(function ($p) {
          return (float) str_replace(['.', ','], '', $p['amount'] ?? 0);
        });

      // Calcular el cambio
      $saleExchangeAmount = $totalPayments - $this->total;



      // Crear la venta
      $sale = Sale::create([
        'box_id' => $invoiceData['box_id'],
        'customer_id' => $customer,
        'user_id' => $userId,
        // 'invoice' => $invoiceData['invoice_number'],
        'invoice_number' => $invoiceData['invoice_number'],
        'total_amount' => $this->total,
        'total_iva' => $this->iva,
        'total_discount' => $this->discount,
        'payment_method' => $salePaymentMethod,
        'received'      => $totalReceivedNow,   // 👈 solo lo realmente recibido
        'exchange'      => $totalReceivedNow - min($this->total, $totalReceivedNow),
        'status' => $isCreditPaymentIncluded ? 'Pendiente' : 'Pagada',
        'sale_date' => now(),
      ]);


      // Crear los items de la venta
      foreach ($calculatedItems as $item) {
        $item['sale_id'] = $sale->id;
        SaleItem::create($item);
        $product = Product::findOrFail($item['product_id']);
        $product->decrement('stock', $item['quantity']);

        if ($product->stock <= 0) {
          $product->update(['status' => 'Inactivo']);
        }
      }


      // Crear los pagos de la venta
      foreach ($data['paymentData'] as $payment) {
        $amount = (float) str_replace(['.', ','], '', $payment['amount'] ?? 0);
        $paymentId = $payment['payment_id'] ?? null;
        $reference = $payment['reference'] ?? null;

        Payment::create([
          'sale_id' => $sale->id,
          'box_id' => $openBox->id,
          'amount' => $amount,
          'payment_method' => $paymentId,
          'reference' => $reference,
          'payment_date' => now(),
        ]);

        if ($paymentId === 'Credito') {
          Credit::create([
            'sale_id' => $sale->id,
            'user_id' => $userId,
            'total_credit' => $amount,
            'total_paid' => 0,
            'total_pending' => $amount,
            'status' => 'Pendiente',
            'credit_date' => now(),
          ]);
        }
      }

      // Calcular el total de cada metodo de pago a partir de paymentData
      $totalCashForBox = collect($data['paymentData'])->filter(fn($payment) => ($payment['payment_id'] ?? null) === 'Efectivo')->sum(function ($payment) {
        return (float) str_replace(['.', ','], '', $payment['amount'] ?? 0);
      });

      $totalTransferForBox = collect($data['paymentData'])->filter(fn($payment) => ($payment['payment_id'] ?? null) === 'Transferencia')->sum(function ($payment) {
        return (float) str_replace(['.', ','], '', $payment['amount'] ?? 0);
      });

      $totalCreditForBox = collect($data['paymentData'])->filter(fn($payment) => ($payment['payment_id'] ?? null) === 'Credito')->sum(function ($payment) {
        return (float) str_replace(['.', ','], '', $payment['amount'] ?? 0);
      });

      // Actualizar contadores de la Box (turno)
      $openBox->cash_payment += ($totalCashForBox - $saleExchangeAmount);
      $openBox->transfer_payment += $totalTransferForBox;
      $openBox->credit_sales += $totalCreditForBox;
      $openBox->total_sales += $sale->total_amount;
      $openBox->save();

      return $sale;
    });
  }

  /**
   * Cancel a sale.
   *
   * @param int $saleId
   * @return bool
   * @throws \Exception
   */
  public function cancelSale(int $saleId): bool
  {
    return DB::transaction(function () use ($saleId) {

      $sale = Sale::findOrFail($saleId);
      $userId = Auth::id();
      // $openBox = Box::where('opened_by', $userId)->where('status', 'Abierta')->first();
      $openBox = Box::where('cash_register_id', $sale->box->cash_register_id)->where('status', 'Abierta')->first();

      if (!$openBox) {
        throw ValidationException::withMessages([
          'message' => 'No tienes un turno abierto para anular la venta',
        ]);
      }

      // Verificar si la venta ya fue cancelada
      if ($sale->status === 'Anulada') {
        throw ValidationException::withMessages([
          'message' => 'La venta ya fue anulada',
        ]);
      }

      // Verificar si la venta tiene un crédito asociado y anularlo
      $credit = Credit::where('sale_id', $sale->id)->first();

      if ($credit) {
        $credit->update(['status' => 'Anulado']);
      }

      $payments = Payment::where('sale_id', $sale->id)->get();

      // Actualizar contadores de la Box (turno)
      foreach ($payments as $payment) {
        if ($payment->payment_method === 'Efectivo') {
          $openBox->cash_payment -= ($payment->amount - $sale->exchange);
          $openBox->total_sales -= ($payment->amount - $sale->exchange);
        } elseif ($payment->payment_method === 'Transferencia') {
          $openBox->transfer_payment -= $payment->amount;
          $openBox->total_sales -= $payment->amount;
        } elseif ($payment->payment_method === 'Credito') {
          $openBox->credit_sales -= $payment->amount;
          $openBox->total_sales -= $payment->amount;
        }
      }
      // Guardar los cambios en la caja
      $openBox->save();

      // Actualizar el estado de la venta
      $sale->update(['status' => 'Anulada']);

      return true;
    });
  }


  /**
   * Calculate the total, subtotal, discount, and IVA for the sale items.
   *
   * @param array $items
   * @return array
   */
  private function calculateItems(array $items): array
  {
    $calculated = [];
    $this->total = 0;
    $this->subtotal = 0;
    $this->discount = 0;
    $this->iva = 0;

    foreach ($items as $item) {
      $product = Product::findOrFail($item['id']);
      $quantity = (float) ($item['quantity'] ?? 0);
      $discountPercent = (float) ($item['discount'] ?? 0);
      $ivaPercent = (float) ($product->iva ?? 0);
      $priceWithIva = (float) ($product->sale_price ?? 0);

      $totalWithIva = round($priceWithIva * $quantity, 2);
      $discountAmount = round($totalWithIva * ($discountPercent / 100), 2);
      $finalTotalWithIva = round($totalWithIva - $discountAmount, 2);

      $subtotal = round($finalTotalWithIva / (1 + ($ivaPercent / 100)), 2);
      $ivaAmount = round($finalTotalWithIva - $subtotal, 2);

      $this->subtotal += $subtotal;
      $this->discount += $discountAmount;
      $this->iva += $ivaAmount;
      $this->total += $finalTotalWithIva;

      $calculated[] = [
        'product_id' => $item['id'],
        'quantity' => $quantity,
        'unit_price' => $priceWithIva,
        'discount' => $discountAmount,
        'iva' => $ivaAmount,
        'subtotal' => $subtotal,
        'total' => $finalTotalWithIva,
      ];
    }

    return $calculated;
  }

  /**
   * Get the next invoice number.
   * @return array<string, int|string>
   */
  public function getInvoiceNumber(): array
  {
    $lastSale = Sale::orderBy('id', 'desc')->first();
    return [
      'invoice' => $lastSale ? $lastSale->id + 1 : 1,
      'invoice_number' =>  $lastSale ? $lastSale->id + 1 : 1,
    ];
  }


  public function returnProductSale(SaleItem $saleItem, $quantity): void
  {
    DB::transaction(function () use ($saleItem, $quantity) {
      // Verificar si solo es un item de venta
      if ($saleItem->quantity <= 1) {
        throw ValidationException::withMessages([
          'message' => 'No puedes devolver este producto porque solo se vendió una unidad. Si necesitas revertir la operación, anula la venta'
        ]);
      }
      $saleItemQuantity = (intval($saleItem->quantity) == floatval($saleItem->quantity)) ? intval($saleItem->quantity) : $saleItem->quantity;

      // Verificar si se esta devolviendo todo el item y es el único item de la venta
      if ($saleItemQuantity === $quantity && SaleItem::where('sale_id', $saleItem->sale_id)->count() === 1) {
        throw ValidationException::withMessages([
          'message' => 'No puedes devolver la cantidad total porque es el único producto vendido. Si necesitas revertir la operación, anula la venta'
        ]);
      }

      // Verificar si la cantidad a devolver es válida
      if ($quantity <= 0 || $quantity > $saleItem->quantity) {
        throw ValidationException::withMessages([
          'message' => 'Cantidad a devolver inválida',
        ]);
      }
      /*  // Actualizar el stock del producto
      $product = Product::findOrFail($saleItem->product_id);
      $product->increment('stock', $quantity);

      // Actualizar el inventario asociado al producto
      $inventory = Inventory::where('product_id', $product->id)->first();
      if ($inventory) {
        $inventory->increment('current_quantity', $quantity);
        if ($inventory->current_quantity > 0) {
          $inventory->update(['status' => 'Activo']);
        }
      } */

      $saleItemQuantity = $saleItem->quantity;
      $totalPriceItem = ($saleItem->total / $saleItemQuantity);
      $totalDiscountItem = ($saleItem->discount / $saleItemQuantity);
      $totalIvaItem = ($saleItem->iva / $saleItemQuantity);

      // Actualizar el total de la venta
      $sale = $saleItem->sale;
      $sale->total_amount -= ($totalPriceItem * $quantity);
      $sale->total_discount -= ($totalDiscountItem * $quantity);
      $sale->total_iva -= ($totalIvaItem * $quantity);
      $sale->exchange = $sale->received - $sale->total_amount;
      $sale->save();

      // Actualizar el saleItem
      $saleItem->decrement('quantity', $quantity);
      $saleItem->total -= ($totalPriceItem * $quantity);
      $saleItem->discount -= ($totalDiscountItem * $quantity);
      $saleItem->iva -= ($totalIvaItem * $quantity);
      $saleItem->subtotal = ($saleItem->total - $saleItem->iva);
      $saleItem->save();

      // Si la cantidad llega a cero, eliminar el item de venta
      if ($saleItem->quantity <= 0) {
        $saleItem->delete();
      }

      // Actualizar la caja (turno)
      $openBox = Box::where('cash_register_id', $sale->box->cash_register_id)->where('status', 'Abierta')->first();
      if (!$openBox) {
        throw ValidationException::withMessages([
          'message' => 'No tienes un turno abierto para procesar la devolución',
        ]);
      }
      $openBox->cash_payment -= ($totalPriceItem * $quantity);
      $openBox->total_sales -= ($totalPriceItem * $quantity);
      $openBox->save();

      return true;
    });
  }
}
