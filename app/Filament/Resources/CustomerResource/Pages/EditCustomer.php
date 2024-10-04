<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
  protected static string $resource = CustomerResource::class;

  protected function getRedirectUrl(): string
  {
    return route('filament.dashboard.resources.clientes.index');
  }

  /*  protected function getHeaderActions(): array
  {
    return [
      Actions\DeleteAction::make(),
    ];
  } */
}
