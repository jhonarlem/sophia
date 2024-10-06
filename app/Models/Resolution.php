<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resolution extends Model
{
  use HasFactory;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'number',
    'start_invoice_number',
    'end_invoice_number',
    'start_date',
    'end_date',
    'valid_months',
    'prefix',
    'description',
  ];

  /**
   * Relations with other models.
   */

  public function boxes()
  {
    return $this->hasMany(Box::class);
  }
}
