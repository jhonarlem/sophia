<?php

namespace App\Filament\Resources\BoxResource\Pages;

use App\Filament\Resources\BoxResource;
use App\Models\Box;
use App\Models\Resolution;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ListBoxes extends ListRecords
{
  protected static string $resource = BoxResource::class;

  protected function getHeaderActions(): array
  {
    $status = Box::latest()->first()?->status;

    return [
      Action::make('openBox')
        ->label('Abrir caja')
        ->hidden(fn() => $status === 'Abierta')
        ->requiresConfirmation()
        ->modalHeading('Apertura de caja')
        ->modalIcon('heroicon-o-lock-open')
        ->modalDescription('Ingresa el dinero inicial y selecciona la resolución para abrir la caja.')
        ->modalSubmitActionLabel('Abrir caja')
        ->action(function ($data) {
          // Lógica para abrir la caja
          try {
            $validatorBox = Validator::make($data, [
              'base_amount' => 'required|numeric',
              'resolution_id' => 'required|exists:resolutions,id',
            ]);

            $validatedBox = $validatorBox->validate();

            $box = Box::create([
              'resolution_id' => $validatedBox['resolution_id'],
              'base_amount' => $validatedBox['base_amount'],
              'status' => 'Abierta',
              'opened_by' => Auth::user()->id,
              'opened_at' => now(),
            ]);

            Notification::make()
              ->title('Caja abierta')
              ->success()
              ->icon('heroicon-o-check-circle')
              ->send();

            // return $box;
            return redirect()->route('filament.dashboard.resources.caja.index');
          } catch (\Exception $e) {
            Notification::make()
              ->title('Error al abrir caja')
              ->body($e->getMessage())
              ->danger()
              ->send();
            // Retirar la excepción para manejo superior si es necesario
            throw $e;
          }
        })
        ->form([
          TextInput::make('base_amount')
            ->label('Dinero base')
            ->required()
            ->numeric()
            ->default(0),
          Select::make('resolution_id')
            ->label('Resolución')
            ->relationship('resolution', 'number', fn(Builder $query) => $query->where('end_date', '>=', now()))
            ->placeholder('Selecciona una resolución')
            ->getOptionLabelFromRecordUsing(fn($record) => "{$record->number} - Prefijo: {$record->prefix} - Vence: " . Carbon::parse($record->end_date)->locale('es')->isoFormat('D MMM YYYY'))
            ->searchable()
            ->preload()
            ->required(),
        ])
        ->visible(fn() => true),
      Action::make('closeBox')
        ->label('Cerrar caja')
        ->color('warning')
        ->hidden(fn() => $status === 'Cerrada')
        ->requiresConfirmation()
        ->modalHeading('Cierre de caja')
        ->modalIcon('heroicon-o-lock-closed')
        ->modalDescription('Ingresa el dinero total, incluyendo la base, para completar el cierre de la caja.')
        ->modalSubmitActionLabel('Cerrar caja')
        ->action(function ($data) {
          // Lógica para cerrar la caja
          try {
            $validatorBox = Validator::make($data, [
              'close_amount' => 'required|numeric',
              'observations' => 'nullable',
            ]);

            $validatedBox = $validatorBox->validate();
            // Calcular la diferencia entre cash_payment y close_amount
            $box = Box::where('status', 'Abierta')->first();
            $diferencia = $validatedBox['close_amount'] - $box->base_amount;

            $box->update([
              'real_amount' => $validatedBox['close_amount'],
              'difference' => $diferencia,
              'closed_by' => Auth::user()->id,
              'status' => 'Cerrada',
              'observations' => $validatedBox['observations'],
              'closed_at' => now(),
            ]);

            Notification::make()
              ->title('Caja cerrada')
              ->success()
              ->icon('heroicon-o-check-circle')
              ->send();

            return $box;
          } catch (\Exception $e) {
            Notification::make()
              ->title('Error al cerrar caja')
              ->body($e->getMessage())
              ->danger()
              ->send();
            // Retirar la excepción para manejo superior si es necesario
            throw $e;
          }
        })
        ->form([
          TextInput::make('close_amount')
            ->label('Dinero de cierre')
            ->required()
            ->autocomplete('off')
            ->numeric()
            ->default(0),
          TextInput::make('observations')
            ->label('Observaciones')
            ->autocomplete('off')

        ])
        ->visible(fn() => true),
    ];
  }
}
