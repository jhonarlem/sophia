<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Appliance;
use App\Models\Houseware;
use App\Models\Product;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CreateProduct extends CreateRecord
{
  protected static string $resource = ProductResource::class;

  protected function getRedirectUrl(): string
  {
    return route('filament.dashboard.resources.productos.index');
  }

  protected function handleRecordCreation(array $data): Model
  {
    try {

      // Validar datos
      $validatorProduct = Validator::make($data, [
        'category' => 'required',
        'sku' => 'required|unique:products,sku',
        'name' => 'required',
        'purchase_price' => 'required',
        'sale_price' => 'required',
        'iva' => 'required',
        'stock' => 'required',
        'min_stock' => 'required',
        'status' => 'required',
      ]);

      $validatedProduct = $validatorProduct->validate();

      // Determinar si es un electrodoméstico o no
      $isAppliance = $validatedProduct['category'] != 'Hogar';

      // Validar los datos dependiendo de la categoría
      if ($isAppliance) {

        // Validar si discount_applicable es No y poner discount_amount en 0
        if ($data['discount_applicable'] == 'No') {
          $data['discount_amount'] = 0;
        }
        // Validar los campos de electrodoméstico
        $validatorAppliance = Validator::make($data, [
          'serial_number' => 'required',
          'brand' => 'required',
          'model' => 'required',
          'color' => 'required',
          'warranty' => 'required',
          'purchase_date' => 'required',
          'discount_applicable' => 'required',
          'discount_amount' => 'required',
        ]);

        $validatedAppliance = $validatorAppliance->validate();
      } else {

        // Validar si discount_applicable es No y poner discount_amount en 0
        if ($data['discount_applicable'] == 'No') {
          $data['discount_amount'] = 0;
        }
        // Validar los campos de artículo para el hogar
        $validatorHouseware = Validator::make($data, [
          'brand' => 'required',
          'color' => 'required',
          'discount_applicable' => 'required',
          'discount_amount' => 'required',
        ]);

        $validatedHouseware = $validatorHouseware->validate();
      }

      // Crear producto
      $productData = [
        'category' => $validatedProduct['category'],
        'sku' => $validatedProduct['sku'],
        'name' => $validatedProduct['name'],
        'purchase_price' => $validatedProduct['purchase_price'],
        'sale_price' => $validatedProduct['sale_price'],
        'iva' => $validatedProduct['iva'],
        'stock' => $validatedProduct['stock'],
        'min_stock' => $validatedProduct['min_stock'],
        'status' => $validatedProduct['status'],
      ];

      // Añadir los campos correspondientes dependiendo de si es un electrodoméstico o no
      if ($isAppliance) {
        $productData = array_merge($productData, [
          'serial_number' => $validatedAppliance['serial_number'],
          'brand' => $validatedAppliance['brand'],
          'model' => $validatedAppliance['model'],
          'color' => $validatedAppliance['color'],
          'warranty' => $validatedAppliance['warranty'],
          'purchase_date' => $validatedAppliance['purchase_date'],
          'discount_applicable' => $validatedAppliance['discount_applicable'],
          'discount_amount' => $validatedAppliance['discount_amount'],
        ]);
      } else {
        $productData = array_merge($productData, [
          'brand' => $validatedHouseware['brand'],
          'color' => $validatedHouseware['color'],
          'discount_applicable' => $validatedHouseware['discount_applicable'],
          'discount_amount' => $validatedHouseware['discount_amount'],
        ]);
      }

      // Finalmente, crear el producto con todos los datos
      $product = Product::create($productData);

      return $product;
    } catch (\Exception $e) {
      Notification::make()
        ->title('Error al crear')
        //->body($e->getMessage())
        ->danger()
        ->send();
      // Retirar la excepción para manejo superior si es necesario
      throw $e;
    }
  }
}
