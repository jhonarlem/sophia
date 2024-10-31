<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Expense;
use App\Models\Product;
use Carbon\Carbon;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Support\Colors\Color;

class ProductsWidget extends BaseWidget
{
  protected static ?string $pollingInterval = null;

  protected function getStats(): array
  {

    $productsQuantity = Product::sum('stock');
    $totalProducts = Product::where('status', 'Activo')->count();
    $expenses = Expense::where('status', 'Pendiente')->get();
    $totalExpensePending = $expenses->count();

    $todayCount = 0;
    foreach ($expenses as $expense) {
      $dueDate = Carbon::parse($expense->due_date);
      if ($dueDate->isToday()) {
        $todayCount++;
      }
    }
    $totalExpensePendingToday = 'Gatos a pagar hoy: ' . $todayCount;


    $productsUnderStock = Product::whereColumn('stock', '<', 'min_stock')->count();
    if ($productsUnderStock > 0) {
      $icon = 'heroicon-o-exclamation-circle';
      $description = 'Verifica el inventario';
      $color = 'danger';
    } else {
      $icon = 'heroicon-o-check-circle';
      $description = 'Inventario en orden';
      $color = Color::Lime;
    }


    return [
      Stat::make('Total de unidades disponibles', $productsQuantity)
        ->description(sprintf('Productos registrados: %d', $totalProducts))
        ->descriptionIcon('heroicon-o-shopping-bag', IconPosition::Before)
        ->chart([17, 5, 10, 5, 11, 3, 14])
        ->color(Color::Indigo),
      Stat::make('Productos en stock minimo', $productsUnderStock)
        ->description($description)
        ->descriptionIcon($icon, IconPosition::Before)
        ->chart([14, 4, 13, 6, 11, 5, 10])
        ->color($color),
      Stat::make('Total gastos pendientes', $totalExpensePending)
        ->description($totalExpensePendingToday)
        ->descriptionIcon('heroicon-o-calendar-days', IconPosition::Before)
        ->chart([10, 3, 12, 6, 15, 7, 20])
        ->color(Color::Purple),
    ];
  }
}
