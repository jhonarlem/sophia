<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;

class ViewCustomer extends ViewRecord
{
  protected static string $resource = CustomerResource::class;

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
    return [];
  }

  public function form(Form $form): Form
  {
    return $form->schema([
      Grid::make(12)->schema([
        Section::make('Detalles del cliente')
          ->schema([
            Grid::make(2)
              ->schema([
                Placeholder::make('document_type')
                  ->label('Documento:')
                  ->content(fn($record) => $record->document_type ?? '')
                  ->inlineLabel(),
                Placeholder::make('document_number')
                  ->label('Número:')
                  ->content(fn($record) => $record->document_number ?? '')
                  ->inlineLabel(),
                Placeholder::make('name')
                  ->label('Nombres:')
                  ->content(fn($record) => $record->name ?? '')
                  ->inlineLabel(),
                Placeholder::make('email')
                  ->label('Correo:')
                  ->content(fn($record) => $record->email ?? '')
                  ->inlineLabel(),
                Placeholder::make('cellphone')
                  ->label('Celular:')
                  ->content(fn($record) => $record->cellphone ?? '')
                  ->inlineLabel(),
                Placeholder::make('street')
                  ->label('Dirección:')
                  ->content(fn($record) => $record->street ?? '')
                  ->inlineLabel(),
              ]),
          ])
          ->columnSpan(12),
      ])
    ]);
  }
}
