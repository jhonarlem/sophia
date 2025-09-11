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

  /**
   * Attributes that must be mutated before saving.
   *
   * @var array<string>
   */

  public function setNameAttribute($value)
  {
    $this->attributes['name'] = strtoupper($value);
  }

  public function setEmailAttribute($value)
  {
    $this->attributes['email'] = strtolower($value);
  }

  public function setStreetAttribute($value)
  {
    $this->attributes['street'] = strtoupper($value);
  }

  // Borrar los espacios en blanco
  public function setDocumentNumberAttribute($value)
  {
    $this->attributes['document_number'] = preg_replace('/\s+/', '', $value);
  }

  public static function getInfoCustomer($customer_id)
  {
    $customer = self::find($customer_id);
    return [
      'document_type' => $customer->document_type,
      'document_number' => $customer->document_number,
      'name' => $customer->name,
      'cellphone' => $customer->cellphone,
      'email' => $customer->email,
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
    return $this->hasManyThrough(Credit::class, Sale::class);
  }
}
