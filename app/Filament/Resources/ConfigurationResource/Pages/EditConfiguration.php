<?php

namespace App\Filament\Resources\ConfigurationResource\Pages;

use App\Filament\Resources\ConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Configuration;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

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


  /**
   * Get the title of the notification to be displayed when the record is updated.
   * 
   * @return string|null
   */
  protected function getSavedNotificationTitle(): ?string
  {
    return 'Configuración editada';
  }

  /**
   * Handle the validation error.
   * 
   * @param ValidationException $exception
   * @return void
   */
  protected function onValidationError(ValidationException $exception): void
  {
    Notification::make()
      ->title('Error de validación')
      ->body($exception->getMessage())
      ->danger()
      ->send();
  }
}
