<?php

namespace App\Services\Sale;

use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class SaleInfoListBuilder
{
  /**
   * Configure the supplier sale infolist.
   *
   * @param Infolist $infolist
   * @return Infolist
   */
  public static function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Section::make('')
          ->columns([
            'default' => 1,
            'md' => 3,
            'lg' => 4,
          ])
          ->schema([
            TextEntry::make('customer.name')
              ->label('Cliente')
              ->icon('heroicon-o-user'),
            TextEntry::make('customer.document_type')
              ->label('Documento')
              ->icon('heroicon-o-identification'),
            TextEntry::make('customer.document_number')
              ->label('Número')
              ->icon('heroicon-o-hashtag'),
            TextEntry::make('customer.cellphone')
              ->label('Celular')
              ->icon('heroicon-o-device-phone-mobile'),
            TextEntry::make('customer.email')
              ->label('Correo')
              ->limit(20)
              ->tooltip(fn($record): string => $record->customer->email)
              ->icon('heroicon-o-envelope'),
            TextEntry::make('full_invoice_number')
              ->label('Factura')
              ->icon('heroicon-o-document-text'),
            TextEntry::make('status')
              ->label('Estado')
              ->badge()
              ->color(fn(string $state): string => match ($state) {
                'Pagada' => 'success',
                'Pendiente' => 'warning',
                'Anulada' => 'danger',
                default => 'gray',
              }),
            TextEntry::make('payment_method')
              ->label('Método de pago')
              ->icon('heroicon-o-credit-card'),
            TextEntry::make('total_amount')
              ->label('Subtotal')
              ->formatStateUsing(function ($record) {
                $subtotal = $record->total_amount + $record->total_discount - $record->total_iva;
                return '$ ' . number_format($subtotal, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('total_iva')
              ->label('IVA')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('total_discount')
              ->label('Descuento')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('total_amount')
              ->label('Total')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('received')
              ->label('Recibido')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('exchange')
              ->label('Cambio')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('sale_date')
              ->label('Fecha')
              ->dateTime()
              ->icon('heroicon-o-calendar-days'),
            TextEntry::make('user.name')
              ->label('Cajero')
              ->icon('heroicon-o-user'),
          ]),
      ]);
  }
}
