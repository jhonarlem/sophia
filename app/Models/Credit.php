<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'sale_id',
    'user_id',
    'total_credit',
    'total_paid',
    'total_pending',
    'status',
    'credit_date',
  ];

  /**
   * Relations with other models.
   */

  public function sale()
  {
    return $this->belongsTo(Sale::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  /* public function payments()
  {
    return $this->hasManyThrough(Payment::class, Sale::class, 'id', 'sale_id', 'id', 'payment_id');
  } */

  public function payments()
  {
    return $this->hasMany(Payment::class, 'sale_id', 'sale_id');
  }

  public function customer()
  {
    return $this->hasOneThrough(Customer::class, Sale::class, 'id', 'id', 'sale_id', 'customer_id');
  }
}
