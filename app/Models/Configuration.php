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
    'dv',
    'company',
    'legal_representative',
    'email',
    'cellphone',
    'street',
    'city',
    'department',
    'regime',
    'slogan',
    'image',
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


  /**
   * Attributes that must be mutated before saving.
   *
   * @var array<string>
   */

  public function setCompanyAttribute($value)
  {
    $this->attributes['company'] = strtoupper($value);
  }

  public function setLegalRepresentativeAttribute($value)
  {
    $this->attributes['legal_representative'] = strtoupper($value);
  }

  public function setEmailAttribute($value)
  {
    $this->attributes['email'] = strtolower($value);
  }

  public function setStreetAttribute($value)
  {
    $this->attributes['street'] = strtoupper($value);
  }

  public function setCityAttribute($value)
  {
    $this->attributes['city'] = strtoupper($value);
  }

  public function setDepartmentAttribute($value)
  {
    $this->attributes['department'] = strtoupper($value);
  }

  /* public function setRegimeAttribute($value)
  {
    $this->attributes['regime'] = strtoupper($value);
  } */

  public function setSloganAttribute($value)
  {
    $this->attributes['slogan'] = strtoupper($value);
  }
}
