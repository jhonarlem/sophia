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
    return [
      Action::make('Abrir caja')
        ->label('Abrir caja')
        ->requiresConfirmation()
        ->modalHeading('Abrir caja')
        ->modalIcon('heroicon-o-lock-open')
        ->modalDescription('Por favor ingresa los datos necesarios para abrir la caja.')
        ->modalSubmitActionLabel('Abrir')
        ->action(function ($data) {
          // Lógica para abrir la caja
          try {
            $openBox = Box::where('status', 'Abierta')->first();
            if ($openBox) {
              Notification::make()
                ->title('La caja ya está abierta')
                ->danger()
                ->send();
              return;
            }

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

            return $box;
          } catch (\Exception $e) {
            Notification::make()
              ->title('Error al abrir caja')
              ->body($e->getMessage())
              ->danger()
              ->send();
            // Retirar la excepción para manejo superior si es necesario
            throw $e;
          }

          Notification::make()
            ->title('Caja abierta')
            ->success()
            ->icon('heroicon-o-check-circle')
            ->send();
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
        ->visible(fn() => true), // Cambiado a visible con una función
    ];
  }
}
