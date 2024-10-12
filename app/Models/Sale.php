<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'box_id',
    'customer_id',
    'user_id',
    'invoice_number',
    'total_amount',
    'total_iva',
    'total_discount',
    'payment_method',
    'status',
    'sale_date',
  ];


  public static function getNextInvoiceNumber()
  {
    $latestSale = self::latest()->first();
    $resolution = Resolution::find($latestSale->resolution_id);

    if ($latestSale) {
      $nextInvoiceNumber = (int) $latestSale->invoice_number + 1;
    } else {
      $nextInvoiceNumber = (int) $resolution->start_invoice_number;
    }

    if ($nextInvoiceNumber > (int) $resolution->end_invoice_number) {
      // throw new \Exception('Se ha alcanzado el número máximo de facturas para esta resolución.');
      Notification::make()
        ->title('Número de factura máximo alcanzado')
        ->message('Se ha alcanzado el número máximo de facturas para esta resolución.')
        ->error()
        ->icon('heroicon-o-exclamation')
        ->send();
    }

    return $resolution->prefix . '-' . $nextInvoiceNumber;
  }

  public static function generateInvoiceNumber()
  {
    // 1. Verificar si hay una caja abierta
    $openBox = Box::where('status', 'Abierta')->first();

    if (!$openBox) {
      return false;
    }

    // 2. Obtener la resolución asociada a la caja abierta
    $resolution = $openBox->resolution;

    // 3. Verificar el rango de start_invoice_number y end_invoice_number
    $startInvoiceNumber = (int) $resolution->start_invoice_number;
    $endInvoiceNumber = (int) $resolution->end_invoice_number;

    // 4. Obtener el último número de factura asociado a esa resolución
    $lastInvoiceNumber = Sale::whereHas('box', function ($query) use ($openBox) {
      $query->where('resolution_id', $openBox->resolution_id);
    })->orderBy('invoice_number', 'desc')->value('invoice_number');

    if ($lastInvoiceNumber) {
      // Si hay facturas, usar el último número como base
      $lastInvoiceNumber = (int) $lastInvoiceNumber;
    } else {
      // Si no hay facturas, comenzar con el número de inicio
      $lastInvoiceNumber = $startInvoiceNumber - 1;
    }

    // 5. Verificar si el número de la nueva venta está dentro del rango permitido
    $newInvoiceNumber = $lastInvoiceNumber + 1;

    if ($newInvoiceNumber > $endInvoiceNumber) {
      return
        Notification::make()
        ->title('Número de factura máximo alcanzado')
        ->message('Se ha alcanzado el número máximo de facturas para esta resolución.')
        ->error()
        ->icon('heroicon-o-exclamation')
        ->send();
    }

    // 6. Retornar el prefijo concatenado con el nuevo número de factura
    $prefix = $resolution->prefix;
    return $prefix . '-' . $newInvoiceNumber;
  }


  /**
   * Relations with other models.
   */

  public function box()
  {
    return $this->belongsTo(Box::class);
  }

  public function customer()
  {
    return $this->belongsTo(Customer::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function products()
  {
    return $this->hasMany(Product::class);
  }
}
