<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Widgets\CustomerInfo;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;

class ViewCustomer extends ViewRecord
{
  protected static string $resource = CustomerResource::class;

  /**
   * Mount the component with the given record.
   *
   * This method is called when the component is initialized. It calls the parent
   * mount method with the provided record and stores the record ID in the session
   * under the key 'customer_id'.
   *
   * @param mixed $record The record to mount the component with.
   *
   * @return void
   */
  public function mount($record): void
  {
    parent::mount($record);
    session()->put('customer_id', $record);
  }

  protected function getHeaderActions(): array
  {

    return [
      Actions\EditAction::make(),
    ];
  }

  public function getViewData(): array
  {
    return [
      'document_type' => $this->record->document_type,
      'document_number' => $this->record->document_number,
      'name' => $this->record->name,
      'cellphone' => $this->record->cellphone,
      'email' => $this->record->email,
      'street' => $this->record->street,
      'sales' => $this->record->sales,
    ];
  }

  protected function getHeaderWidgets(): array
  {
    return [
      CustomerInfo::make(),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Section::make('Detalles del cliente')
          ->columns(3)
          ->schema([
            TextEntry::make('document_type')
              ->label('Documento')
              ->icon('heroicon-o-identification'),
            TextEntry::make('document_number')
              ->label('Número')
              ->numeric()
              ->icon('heroicon-o-identification'),
            TextEntry::make('name')
              ->label('Cliente')
              ->icon('heroicon-o-user'),
            TextEntry::make('email')
              ->label('Correo')
              ->icon('heroicon-o-envelope'),
            TextEntry::make('cellphone')
              ->label('Celular')
              ->icon('heroicon-o-device-phone-mobile'),
            TextEntry::make('street')
              ->label('Dirección')
              ->numeric()
              ->icon('heroicon-o-map-pin'),
          ])
      ]);
  }
}
