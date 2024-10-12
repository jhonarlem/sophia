<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Box;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateSale extends CreateRecord
{
  protected static string $resource = SaleResource::class;

  protected function getRedirectUrl(): string
  {
    return route('filament.dashboard.resources.caja.index');
  }

  protected function getCreateFormAction(): Action
  {
    return parent::getCreateFormAction()->extraAttributes(['type' => 'button', 'wire:click' => 'create']);
  }

  public $defaultAction = 'statusBox';

  public function statusBoxAction(): Action
  {
    $statuxBox = Box::latest()->first()?->status;
    return Action::make('checkStatusBox')
      ->visible(fn() => $statuxBox != 'Abierta')
      ->requiresConfirmation()
      ->closeModalByClickingAway(false)
      ->closeModalByEscaping(false)
      ->modalCloseButton(false)
      ->color('warning')
      ->modalIcon('heroicon-o-lock-closed')
      ->modalHeading('Caja cerrada')
      ->modalDescription('Actualmente, no es posible registrar una venta ya que la caja se encuentra cerrada. Por favor, abre la caja para poder continuar con el proceso de venta.')
      ->modalSubmitActionLabel('Abrir caja')
      ->modalCancelAction(false)
      ->action(function () {
        return redirect()->route('filament.dashboard.resources.caja.index');
      });
  }
}
