<?php

namespace App\Filament\Resources\LossResource\Pages;

use App\Filament\Resources\LossResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateLoss extends CreateRecord
{
  protected static string $resource = LossResource::class;

  protected function getRedirectUrl(): string
  {
    return route('filament.dashboard.resources.perdidas.index');
  }

  protected function handleRecordCreation(array $data): Model
  {
    try {
      // Validar los datos
      $validatorLoss = Validator::make($data, [
        'product_id' => 'required',
        'quantity' => 'required|numeric|min:1',
        'description' => 'string',
      ]);

      $validatedLoss = $validatorLoss->validate();

      // Verificar si hay stock suficiente
      $productModel = Product::findOrFail($validatedLoss['product_id']);
      if ($productModel->stock < $validatedLoss['quantity']) {
        // Lanzar una excepción para detener la creación
        throw ValidationException::withMessages([
          'message' => 'Sin stock suficiente para el producto ' . $productModel->name,
        ]);
      }

      // Calcular el total de la pérdida
      $unitPrice = $productModel->purchase_price;
      $totalLoss = $unitPrice * $validatedLoss['quantity'];

      // Crear la pérdida
      $loss = LossResource::getModel()::create([
        'product_id' => $validatedLoss['product_id'],
        'quantity' => $validatedLoss['quantity'],
        'unit_price' => $unitPrice,
        'total' => $totalLoss,
        'description' => $validatedLoss['description'],
        'user_id' => Auth::id(),
      ]);

      // Actualizar el stock del producto
      $productModel->decrement('stock', $validatedLoss['quantity']);


      return $loss;
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
