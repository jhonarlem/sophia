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
   * Relations with other models.
   */
  public function sales()
  {
    return $this->hasMany(Sale::class);
  }
}
