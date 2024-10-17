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
    try {
      // Validar datos
      $validatorCustomer = Validator::make($data, [
        'customer_id' => 'required',
      ]);
      $validatedCustomer = $validatorCustomer->validate();

      $validatorProducts = Validator::make($data, [
        'products' => 'required|array|min:1',
        'products.*.product_id' => 'required',
        'products.*.quantity' => 'required',
        'products.*.sale_price' => 'required',
        'products.*.iva' => 'required',
        'products.*.discount' => 'required',
        'products.*.total' => 'required',
      ]);
      $validatedProducts = $validatorProducts->validate();


      // Validar si hay existencias suficientes
      foreach ($validatedProducts['products'] as $product) {
        $productModel = Product::find($product['product_id']);
        if ($productModel->stock < $product['quantity']) {
          return Notification::make()
            ->title('Existencias insuficientes')
            ->message("Sin stck suficiente para el producto {$productModel->name}.")
            ->error()
            ->icon('heroicon-o-exclamation')
            ->send();
        }
      }

      // Crear la venta
      $sale = Sale::create([
        'box_id' => Box::latest()->first()->id,
        'customer_id' => $validatedCustomer['customer_id'],
        'user_id' => Auth::id(),
        'invoice_number' => Sale::getNextInvoiceNumber(),
        'total_amount' => collect($validatedProducts['products'])->sum('total'),
        'total_iva' => collect($validatedProducts['products'])->sum('iva'),
        'total_discount' => collect($validatedProducts['products'])->sum('discount'),
        'payment_method' => $data['payment_method'],
        'status' => 'Pendiente',
        'sale_date' => now(),
      ]);

      // Agregar los sale_items


    } catch (\Exception $e) {
      Notification::make()
        ->title('Error al crear')
        ->danger()
        ->send();
      // Retirar la excepción para manejo superior si es necesario
      throw $e;
    }
  }
}
