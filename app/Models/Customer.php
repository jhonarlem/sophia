<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'document_type',
    'document_number',
    'name',
    'email',
    'cellphone',
    'street',
  ];


  public static function getInfoCustomer($customer_id)
  {
    $customer = self::find($customer_id);
    return [
      'document_type' => $customer->document_type,
      'document_number' => $customer->document_number,
      'name' => $customer->name,
      'cellphone' => $customer->cellphone,
    ];
  }


  /**
   * Relations with other models.
   */

  public function sales()
  {
    return $this->hasMany(Sale::class);
  }

  public function credits()
  {
    return $this->hasMany(Credit::class);
  }
}
