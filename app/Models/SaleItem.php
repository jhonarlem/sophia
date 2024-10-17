<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
  use HasFactory;



  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'sale_id',
    'product_id',
    'quantity',
    'unit_price',
    'discount',
    'iva',
    'subtotal',
    'total',
  ];


  /**
   * Relations with other models.
   */
  public function sale()
  {
    return $this->belongsTo(Sale::class);
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }
}
