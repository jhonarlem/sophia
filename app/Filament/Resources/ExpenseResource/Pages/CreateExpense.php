<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use App\Models\Box;
use App\Models\Expense;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateExpense extends CreateRecord
{
  protected static string $resource = ExpenseResource::class;

  protected function getRedirectUrl(): string
  {
    return $this->getResource()::getUrl('index');
  }

  protected function handleRecordCreation(array $data): Model
  {
    try {
      // Validar datos
      $validatorExpense = Validator::make($data, [
        'payee_name' => 'required',
        'service_type' => 'required',
        'amount_total' => 'required|numeric|min:0',
        'amount_paid' => 'required|numeric|min:0',
        'amount_pending' => 'required|numeric|min:0',
        'due_date' => 'required|date',
        'status' => 'required',
        'description' => 'nullable|string|max:255',
        'box_withdrawal' => 'boolean',
      ]);

      $validatedExpense = $validatorExpense->validate();

      // Verificar que el monto total sea igual a la suma de los montos pagado y pendiente
      /* if ($validatedExpense['amount_total'] != $validatedExpense['amount_paid'] + $validatedExpense['amount_pending']) {
        // Lanzar una excepción para detener la creación
        throw ValidationException::withMessages([
          'message' => 'El monto total no coincide con la suma de los montos pagado y pendiente',
        ]);
      } */

      // Verificar si box_withdrawal es true
      if ($validatedExpense['box_withdrawal'] ?? false) {
        // Verificar si hay una caja abierta
        $openBox = Box::where('status', 'Abierta')->first();
        if (!$openBox) {
          throw ValidationException::withMessages([
            'message' => 'No hay una caja abierta para realizar el retiro',
          ]);
        }
        // Verificar si hay saldo suficiente en la caja
        // dd(intval($openBox->total_sales), intval($validatedExpense['amount_paid']));
        if (intval($openBox->total_sales) < intval($validatedExpense['amount_paid'])) {
          throw ValidationException::withMessages([
            'message' => 'No hay saldo suficiente en la caja para realizar el retiro',
          ]);
        }

        // Registrar el retiro en la caja
        $openBox->total_expenses += $validatedExpense['amount_paid'];
        $openBox->total_sales -= $validatedExpense['amount_paid'];
        $openBox->save();
      }

      // Crear el gasto
      $expense = Expense::create([
        'user_id' => Auth::id(),
        'payee_name' => $validatedExpense['payee_name'],
        'service_type' => $validatedExpense['service_type'],
        'amount_total' => $validatedExpense['amount_total'],
        'amount_paid' => $validatedExpense['amount_paid'],
        'amount_pending' => $validatedExpense['amount_pending'],
        'status' => $validatedExpense['status'],
        'due_date' => $validatedExpense['due_date'],
        'box_withdrawal' => $validatedExpense['box_withdrawal'] ?? false,
        'description' => $validatedExpense['description'],
      ]);

      return $expense;
    } catch (\Exception $e) {
      Notification::make()
        ->title($e->getMessage())
        ->danger()
        ->color('danger')
        ->send();
      throw $e;
    }
  }
}
