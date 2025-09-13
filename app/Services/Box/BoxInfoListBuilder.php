<?php

namespace App\Services\Box;

use Illuminate\Support\Carbon;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class BoxInfoListBuilder
{
  /**
   * Configure the inventory infolist.
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
            'lg' => 3,
          ])
          ->schema([

            TextEntry::make('name')
              ->label('Nombre')
              ->icon('heroicon-o-computer-desktop'),
            TextEntry::make('resolution.number')
              ->label('Resolución:')
              ->icon('heroicon-o-hashtag'),
            TextEntry::make('status')
              ->label('Estado')
              ->badge()
              ->color(fn(string $state): string => match ($state) {
                'Abierta' => 'success',
                'Cerrada' => 'warning',
              }),
            TextEntry::make('base_amount')
              ->label('Dinero base')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('cash_payment')
              ->label('Dinero de ventas')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            /* TextEntry::make('cash_electronic_payment')
              ->label('Dinero de ventas electrónicas')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'), */
            TextEntry::make('transfer_payment')
              ->label('Dinero de transferencias')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('credit_sales')
              ->label('Dinero ventas a crédito')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('credit_payment')
              ->label('Dinero recaudo de crédito')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            /*  TextEntry::make('total_other_income')
              ->label('Dinero otros ingresos')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'), */
            TextEntry::make('total_expenses')
              ->label('Dinero gastos')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('total_sales')
              ->label('Total')->badge()
              ->color('success')
              ->icon('heroicon-s-currency-dollar')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('real_amount')
              ->label('Dinero contado')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('difference')
              ->label('Diferencia de valores')
              ->badge()
              ->color(fn($state) => $state < 0 ? 'danger' : 'info')
              ->icon('heroicon-s-currency-dollar')
              ->formatStateUsing(function ($state) {
                return '$ ' . number_format($state, 2, ',', '.');
              })
              ->icon('heroicon-o-banknotes'),
            TextEntry::make('openedBy.name')
              ->label('Abierta por')
              ->default('-')
              ->icon('heroicon-o-user'),
            TextEntry::make('opened_at')
              ->label('Fecha apertura')
              ->dateTime()
              ->icon('heroicon-o-calendar-days'),
            TextEntry::make('closedBy.name')
              ->label('Cerrada por')
              ->default('-')
              ->icon('heroicon-o-user'),
            TextEntry::make('closed_at')
              ->label('Fecha cierre')
              ->dateTime()
              ->icon('heroicon-o-calendar-days'),
            TextEntry::make('observations')
              ->label('Observaciones')
              ->default('-')
              ->icon('heroicon-o-book-open'),
          ]),
      ]);
  }
}
