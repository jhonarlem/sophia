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

  protected function getHeader(): ?string
  {
    return 'Información del cliente';
  }

  protected static ?string $pollingInterval = null;

  public int $customerId = 0;

  // Al montar el widget, leemos el ID desde la sesión
  public function mount(): void
  {
    $this->customerId = session()->get('customer_id', 0);
  }

  protected function getStats(): array
  {
    $customerId = $this->customerId;

    // Obtener las ventas normales no anuladas
    $sales = Sale::where('customer_id', $customerId)
      ->where('status', '!=', 'Anulada');

    // Obtener las ventas electrónicas (todas y solo validadas)
    $electronicSales = Sale::where('customer_id', $customerId);
    $validatedElectronicSales = (clone $electronicSales)->where('status', 'Validada');

    // Contar ventas
    $salesCount = $sales->count();
    $electronicSalesCount = $electronicSales->count();
    $totalSales = $salesCount + $electronicSalesCount;

    // Sumar totales
    $accumulatedSales = $sales->sum('total_amount');
    $accumulatedElectronicSales = $validatedElectronicSales->sum('total_amount');
    $totalAmountSpent = number_format($accumulatedSales + $accumulatedElectronicSales, 0, ',', '.');

    // Créditos del cliente (pendientes)
    $creditQuery = Credit::whereHas('sale', function ($query) use ($customerId) {
      $query->where('customer_id', $customerId)->where('status', 'Pendiente');
    });

    $saldoPendiente = number_format($creditQuery->sum('total_pending'), 0, ',', '.');

    $totalCredits = $creditQuery->whereHas('sale', function ($query) {
      $query->where('status', 'Pendiente');
    })->count();

    // Fecha de creación del cliente
    $customerSince = Customer::find($customerId)?->created_at?->format('d/m/Y') ?? 'N/A';

    $color = $totalCredits > 0 ? 'warning' : 'success';

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

      Stat::make('Saldo pendiente por pagar', '$' . $saldoPendiente)
        ->description('Créditos pendientes: ' . $totalCredits)
        ->descriptionIcon('heroicon-o-building-library', IconPosition::Before)
        ->chart([2, 10, 3, 15, 4, 17, 7])
        ->color($color),
    ];
  }
}
