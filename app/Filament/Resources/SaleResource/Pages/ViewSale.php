<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewSale extends ViewRecord
{
  protected static string $resource = SaleResource::class;

  /* protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
      // Otros botones de acción que desees
    ];
  } */

  public function getViewData(): array
  {
    return [
      // Personaliza los campos que quieres mostrar
    ];
  }
}
