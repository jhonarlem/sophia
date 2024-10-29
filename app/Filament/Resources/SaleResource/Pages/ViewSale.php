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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ViewSale extends ViewRecord
{
  protected static string $resource = SaleResource::class;

  protected function getHeaderActions(): array
  {

    return [
      Action::make('Crear')
        ->label('Crear venta')
        ->action(
          fn() => redirect()->route('filament.dashboard.resources.ventas.create')
        ),
      Action::make('Anular')
        ->requiresConfirmation()
        ->label('Anular venta')
        ->color('danger')
        ->modalHeading('Anular venta ' . $this->record->full_invoice_number)
        ->modalDescription('¿Estás seguro de que deseas anular esta venta? Ten en cuenta que el dinero de la venta no será descontado de la caja y deberás considerarlo al momento de realizar el cierre de la caja. Esta acción no se puede deshacer.')
        ->modalSubmitActionLabel('Anular')
        ->modalCancelActionLabel('Cancelar')
        ->action(
          function () {
            try {
              DB::transaction(function () {
                // Obtener la venta
                $sale = $this->record;

                // Obtener los productos asociados a la venta
                $saleItems = DB::table('sale_items')
                  ->where('sale_id', $sale->id)
                  ->get();

                // Actualizar el stock de los productos
                foreach ($saleItems as $item) {
                  DB::table('products')
                    ->where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
                }

                // Verificar si la venta tiene un crédito asociado y anularlo
                $creditSale = DB::table('credits')
                  ->where('sale_id', $sale->id)
                  ->where('status', 'Pendiente')
                  ->first();

                if ($creditSale) { // Verifica si existe el registro
                  DB::table('credits')
                    ->where('id', $creditSale->id)
                    ->update(['status' => 'Anulada']);
                }

                // Actualizar el estado de la venta
                $sale->update(['status' => 'Anulada']);
              });
              Notification::make()
                ->title('Venta anulada')
                ->body('La venta ' . $this->record->full_invoice_number . ' ha sido anulada y los productos han sido devueltos al inventario.')
                ->success()
                ->send();
            } catch (\Exception $e) {
              Notification::make()
                ->title('Error al anular venta')
                ->body('Ocurrió un error al anular la venta ' . $this->record->full_invoice_number . '. Por favor intenta de nuevo.')
                ->danger()
                ->send();
              throw $e;
            }
          }
        )
        ->visible(fn() => $this->record->status !== 'Anulada'),

      Action::make('Factura')
        ->label('Factura')
        ->color(Color::Purple)
        ->url(route('factura', $this->record->id))
        ->openUrlInNewTab()
        ->visible(fn() => $this->record->status !== 'Anulada'),
      Action::make('Recibo')
        ->label('Recibo')
        ->color(Color::Lime)
        ->url(route('recibo', $this->record->id))
        ->openUrlInNewTab(),
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
                  ->content(fn($record) => $record->total_amount && $record->total_discount !== null
                    ? number_format($record->total_amount - $record->total_discount, 2, ',', '.')
                    : '')
                  ->inlineLabel(),
                Placeholder::make('total_iva')
                  ->label('IVA:')
                  ->content(fn($record) => $record->total_iva !== null
                    ? number_format($record->total_iva, 2, ',', '.')
                    : '')
                  ->inlineLabel(),
                Placeholder::make('total_discount')
                  ->label('Descuento:')
                  ->content(fn($record) => $record->total_discount !== null
                    ? number_format($record->total_discount, 2, ',', '.')
                    : '')
                  ->inlineLabel(),
                Placeholder::make('total_amount')
                  ->label('TOTAL:')
                  ->content(fn($record) => $record->total_amount !== null
                    ? number_format($record->total_amount, 2, ',', '.')
                    : '')
                  ->inlineLabel(),
                Placeholder::make('sale_date')
                  ->label('Fecha:')
                  ->content(fn($record) => Carbon::parse($record->sale_date)->locale('es')->isoFormat('MMM D YYYY HH:mm') ?? '')
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
