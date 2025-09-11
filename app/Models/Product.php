<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
  use HasFactory;


  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'sku',
    'name',
    'category',
    'purchase_price',
    'sale_price',
    'iva',
    'stock',
    'min_stock',
    'status',
    'brand',
    'color',
    'discount_applicable',
    'discount_amount',
    'serial_number',
    'model',
    'warranty',
    'purchase_date',
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

  // Borrar los espacios en blanco
  public function setSkuAttribute($value)
  {
    $this->attributes['sku'] = preg_replace('/\s+/', '', $value);
  }

  /**
   * Attributes that must be mutated after retrieving.
   */
  public function getPurchasePriceAttribute($value)
  {
    return (int) $value;
  }

  public function getSalePriceAttribute($value)
  {
    return (int) $value;
  }

  public function getDiscountAmountAttribute($value)
  {
    return (int) $value;
  }

  /**
   * Relations with other models.
   */
  public function sales()
  {
    return $this->hasMany(Sale::class);
  }
}
