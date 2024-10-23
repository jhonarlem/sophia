<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'sale_id',
    'amount',
    'payment_method',
    'reference',
    'payment_date',
  ];

  /**
   * Relations with other models.
   */

  public function sale()
  {
    return $this->belongsTo(Sale::class);
  }

  public function credit()
  {
    return $this->belongsTo(Credit::class, 'sale_id', 'sale_id');
  }
}
