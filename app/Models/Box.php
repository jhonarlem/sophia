<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Box extends Model
{
  use HasFactory;


  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'resolution_id',
    'base_amount',
    'cash_payment',
    'transfer_payment',
    'credit_sales',
    'total_sales',
    'real_amount',
    'difference',
    'real_amount',
    'difference',
    'observations',
    'opened_by',
    'closed_by',
    'opened_at',
    'closed_at',
    'status',
  ];

  /**
   * Relations with other models.
   */

  public function resolution()
  {
    return $this->belongsTo(Resolution::class);
  }

  public function openedBy()
  {
    return $this->belongsTo(User::class, 'opened_by');
  }

  public function closedBy()
  {
    return $this->belongsTo(User::class, 'closed_by');
  }
}
