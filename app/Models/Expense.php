<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'user_id',
    'payee_name',
    'service_type',
    'amount_total',
    'amount_paid',
    'amount_pending',
    'status',
    'due_date',
    'paid_at',
    'payment_method',
    'description',
  ];

  /**
   * Relations with other models.
   */

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
