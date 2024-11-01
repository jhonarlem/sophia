<?php

namespace App\Filament\Resources\BoxResource\Widgets;

use App\Models\Box;
use App\Models\Credit;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

use function Filament\Support\format_number;

class BoxWidget extends BaseWidget
{
  protected static ?string $pollingInterval = null;

  protected function getStats(): array
  {
    $box = Box::latest()->first();
    $status = $box?->status ?? 'Desconocido';
    $user = match ($status) {
      'Abierta' => $box?->openedBy?->name ?? 'Usuario desconocido',
      'Cerrada' => $box?->closedBy?->name ?? 'Usuario desconocido',
      default => 'Usuario desconocido',
    };

    $color = match ($status) {
      'Abierta' => 'success',
      'Cerrada' => 'warning',
      default => 'secondary',
    };
    $totalSales = $box
      ? '$ ' . number_format($box->total_sales - $box->base_amount, 0, ',', '.')
      : '$ 0';

    $totalSalesCount = $box && $box->sales
      ? 'Ventas realizadas: ' . number_format($box->sales->count())
      : 'Ventas realizadas: 0';

    $credits = Credit::where('status', 'Pendiente')->count();

    $totalCredit = '$ ' . number_format(
      Credit::where('status', 'Pendiente')->sum('total_pending') ?? 0,
      0,
      ',',
      '.'
    );

    $totalCreditPending = 'Créditos pendientes: ' . $credits;

    return [
      Stat::make('Estado de la caja', $status)
        ->description($user)
        ->descriptionIcon('heroicon-o-user', IconPosition::Before)
        ->chart([7, 2, 10, 3, 15, 4, 17])
        ->color($color),
      Stat::make('Total ventas de la caja', $totalSales)
        ->description($totalSalesCount)
        ->descriptionIcon('heroicon-o-shopping-cart', IconPosition::Before)
        ->chart([17, 5, 10, 5, 11, 3, 14])
        ->color('primary'),
      Stat::make('Total ventas a crédito', $totalCredit)
        ->description($totalCreditPending)
        ->descriptionIcon('heroicon-o-building-library', IconPosition::Before)
        ->chart([14, 4, 13, 6, 11, 5, 10])
        ->color('warning'),
    ];
  }
}
