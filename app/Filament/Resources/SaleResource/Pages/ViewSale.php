<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;

class ViewSale extends ViewRecord
{
  protected static string $resource = SaleResource::class;

  protected function getHeaderActions(): array
  {

    return [
      Action::make('new_sale')
        ->label('Crear venta')
        ->action(
          fn() => redirect()->route('filament.dashboard.resources.ventas.create')
        ),
      Action::make('null_sale')
        ->requiresConfirmation()
        ->label('Anular venta')
        ->color('danger')
        ->modalHeading('Anular venta ' . $this->record->full_invoice_number)
        ->modalDescription('¿Estás seguro que deseas anular esta venta? esta acción no se puede deshacer.')
        ->modalSubmitActionLabel('Anular')
        ->modalCancelActionLabel('Cancelar')
        ->action(
          fn() =>
          Notification::make()
            ->title('Venta anulada')
            ->body('La venta ' . $this->record->full_invoice_number . ' ha sido anulada')
            ->success()
            ->send()
        ),
      Action::make('generate_pdf')
        ->label('Generar garantia')
        ->color(Color::Purple)
        ->action(
          fn() =>
          Notification::make()
            ->title('Generando garantia')
            ->body('Generando garantia ' . $this->record->full_invoice_number . ' por favor espera...')
            ->success()
            ->icon('heroicon-o-document-text')
            ->send()
        ),
      Action::make('print_sale')
        ->label('Imprimir')
        ->color(Color::Lime)
        ->action(
          fn() =>
          Notification::make()
            ->title('Imprimiendo factura')
            ->body('Imprimiendo factura ' . $this->record->full_invoice_number . ' por favor espera...')
            ->success()
            ->icon('heroicon-o-printer')
            ->send()
        ),
    ];
  }

  public function getViewData(): array
  {
    return [
      'sale' => $this->record,
      'saleItems' => $this->record->saleItems,
      'payments' => $this->record->payments,
      'customer' => $this->record->customer,
      'users' => $this->record->users,
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
          ->icon('heroicon-o-user')
          ->schema([
            Placeholder::make('document_type')
              ->label('Documento:')
              ->content(fn($record) => $record->customer->document_type ?? '')
              ->inlineLabel(),
            Placeholder::make('document_number')
              ->label('Número:')
              ->content(fn($record) => $record->customer->document_number ?? '')
              ->inlineLabel(),
            Placeholder::make('name')
              ->label('Nombres:')
              ->content(fn($record) => $record->customer->name ?? '')
              ->inlineLabel(),
            Placeholder::make('cellphone')
              ->label('Celular:')
              ->content(fn($record) => $record->customer->cellphone ?? '')
              ->inlineLabel(),
          ])
          ->columnSpan(5),
        Section::make('Detalles de la factura')
          ->icon('heroicon-o-document-text')
          ->schema([
            Grid::make(2)
              ->schema([
                Placeholder::make('status')
                  ->label('Estado:')
                  ->content(fn($record) => $record->status ?? '')
                  ->inlineLabel(),
                Placeholder::make('invoice_number')
                  ->label('Factura:')
                  ->content(fn($record) => $record->full_invoice_number)
                  ->inlineLabel(),
                Placeholder::make('subtotal')
                  ->label('Subtotal:')
                  ->content(fn($record) => number_format(($record->total_amount - $record->total_discount) ?? 0, 2, '.', ''))
                  ->inlineLabel(),
                Placeholder::make('total_iva')
                  ->label('IVA:')
                  ->content(fn($record) => $record->total_iva ?? '')
                  ->inlineLabel(),
                Placeholder::make('total_discount')
                  ->label('Descuento:')
                  ->content(fn($record) => $record->total_discount ?? '')
                  ->inlineLabel(),
                Placeholder::make('total_amount')
                  ->label('TOTAL:')
                  ->content(fn($record) => $record->total_amount ?? '')
                  ->inlineLabel(),
                Placeholder::make('sale_date')
                  ->label('Fecha:')
                  ->content(fn($record) => $record->sale_date ?? '')
                  ->inlineLabel(),
                Placeholder::make('user.name')
                  ->label('Cajero:')
                  ->content(fn($record) => $record->user->name ?? '')
                  ->inlineLabel(),
              ]),
          ])
          ->columnSpan(7),
      ])
    ]);
  }
}
