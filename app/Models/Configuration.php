<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Configuration extends Model
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
    'legal_representative',
    'name',
    'email',
    'cellphone',
    'address',
    'image',
    'slogan',
  ];

  protected static function boot()
  {
    parent::boot();

    static::deleting(function ($model) {
      if ($model->image) {
        Storage::disk('public')->delete($model->image);
      }
    });
  }
}
