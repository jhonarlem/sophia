<?php

namespace App\Services\Box;

use App\Models\Box;
use App\Models\CashRegister;
use App\Models\Resolution;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class BoxManager
{
  /**
   * Open a box session for a specific cash register.
   *
   * @param array<string, mixed> $data {'cash_register_id', 'base_amount'}
   * @return Box
   * @throws ValidationException
   */
  public function openBox(array $data): Box
  {
    return DB::transaction(function () use ($data) {
      // Obtener y validar datos minimos
      $resolutionId = $data['resolution_id'] ?? null;
      // $baseAmount = $data['base_amount'] ?? null;

      if ($resolutionId === null) {
        throw ValidationException::withMessages(['message' => 'La resolución es obligatoria para abrir la caja']);
      }

      // Calcular el efectivo base basado en conteo de billetes y monedas
      $baseAmount = $this->calculateCashCountTotal($data);

      // Crear el nuevo registro de Box (turno)
      $box = new Box();
      $box->resolution_id = $resolutionId;
      $box->base_amount = $baseAmount;
      $box->total_sales = $baseAmount;
      $box->opened_by = Auth::id();
      $box->status = 'Abierta';
      $box->opened_at = now();
      $box->save();
      return $box;
    });
  }


  /**
   * Close a box session.
   *
   * @param array<string, 
   * @return Box
   * @throws ValidationException
   */
  public function closeBox(array $data): Box
  {
    return DB::transaction(function () use ($data) {

      // Obtener datos de cierre y calcular diferencia
      $boxToClose = Box::where('status', 'Abierta')->first();
      $observations = $data['observations'] ?? null;

      // Calcular el efectivo real basado en conteo de billetes y monedas
      $realAmount = $this->calculateCashCountTotal($data);


      // Calcular efectivo esperado basado en movimientos/ventas registrados
      $expectedCash = $boxToClose->base_amount         // Efectivo inicial
        + $boxToClose->cash_payment         // Efectivo de ventas (factura estándar)
        // + $boxToClose->cash_electronic_payment  Efectivo de ventas electrónicas
        + $boxToClose->credit_payment;    // Efectivo recibido por créditos
      // + $boxToClose->total_other_income  Otros ingresos en efectivo
      // - $boxToClose->total_expenses;      Egresos pagados en efectivo

      $difference = $realAmount - $expectedCash; // Diferencia entre efectivo real y esperado

      // Actualizar campos de cierre de la Box (turno)
      $boxToClose->real_amount = $realAmount;
      $boxToClose->difference = $difference;
      $boxToClose->closed_by = Auth::id();
      $boxToClose->status = 'Cerrada';
      $boxToClose->closed_at = now();
      $boxToClose->observations = $observations;

      // Guardar la Box actualizada
      $boxToClose->save();
      return $boxToClose;
    });
  }


  /**
   * Calculate the total amount from bill and coin counts.
   *
   * @param array $arqueoData Array containing denomination counts (e.g., ['hundred_bills' => 5, 'thousand_coins' => 2]).
   * @return float The total calculated amount.
   */
  private function calculateCashCountTotal(array $arqueoData): float
  {
    // Define el valor de cada denominación
    $denominations = [
      'hundred_bills' => 100000,
      'fifty_bills' => 50000,
      'twenty_bills' => 20000,
      'ten_bills' => 10000,
      'five_bills' => 5000,
      'two_bills' => 2000,
      'thousand_coins' => 1000,
      'five_hundred_coins' => 500,
      'two_hundred_coins' => 200,
      'one_hundred_coins' => 100,
      'fifty_coins' => 50,
    ];

    $total = 0;

    // Iterar sobre las denominaciones y sumar el valor total
    foreach ($denominations as $key => $value) {
      // Obtener la cantidad contada para esta denominación.
      $count = (int) ($arqueoData[$key] ?? 0);
      $total += $count * $value;
    }

    return (float) $total;
  }
}
