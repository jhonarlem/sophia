<?php

namespace App\Filament\Resources\ConfigurationResource\Pages;

use App\Filament\Resources\ConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Configuration;

class EditConfiguration extends EditRecord
{
  protected static string $resource = ConfigurationResource::class;

  /* protected function getHeaderActions(): array
  {
    return [
      Actions\DeleteAction::make(),
    ];
  } */

  public function mount($record = null): void
  {
    $this->record = Configuration::firstOrFail();
    parent::mount($this->record->getKey());
  }
}
