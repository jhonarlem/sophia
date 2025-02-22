<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Credit;
use App\Models\Customer;
use App\Models\Sale;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerInfo extends BaseWidget
{
  protected static ?string $pollingInterval = null;

  public int $customerId = 0;

  // Al montar el widget, leemos el ID desde la sesión
  public function mount(): void
  {
    $this->customerId = session()->get('customer_id', 0);
  }

  protected function getStats(): array
  {
    $totalSales = Sale::where('customer_id', $this->customerId)->count();

    // Total gastado en compras, formateado a dos decimales
    $totalAmountSpent = number_format(
      Sale::where('customer_id', $this->customerId)->sum('total_amount'),
      0,
      ',',
      '.'
    );

    $saldoPendiente = number_format(
      Credit::whereHas('sale', function ($query) {
        $query->where('customer_id', $this->customerId);
      })->sum('total_pending'),
      0,
      ',',
      '.'
    );

    $totalCredits = Credit::whereHas('sale', function ($query) {
      $query->where('customer_id', $this->customerId)->where('status', 'Pendiente');
    })->count();

    // Obtener la fecha de registro del cliente
    $customer = Customer::find($this->customerId);
    $customerSince = $customer->created_at->format('d/m/Y');

    if ($totalCredits > 0) {
      $color = 'warning';
    } else {
      $color = 'success';
    };


    return [
      Stat::make('Valor acumulado en compras', '$' . $totalAmountSpent)
        ->description('Suma total de compras')
        ->descriptionIcon('heroicon-o-banknotes', IconPosition::Before)
        ->chart([4, 12, 4, 15, 3, 10, 2])
        ->color('success'),
      Stat::make('Compras realizadas', $totalSales)
        ->description('Cliente desde: ' . $customerSince)
        ->descriptionIcon('heroicon-o-calendar-days', IconPosition::Before)
        ->chart([7, 2, 10, 3, 15, 4, 17])
        ->color('primary'),
      Stat::make('Saldo pendiente por pagar', $saldoPendiente)
        ->description('Créditos pendientes: ' . $totalCredits)
        ->descriptionIcon('heroicon-o-building-library', IconPosition::Before)
        ->chart([2, 10, 3, 15, 4, 17, 7])
        ->color($color),
    ];
  }
}
