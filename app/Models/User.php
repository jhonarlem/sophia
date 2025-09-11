<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
  use HasFactory, Notifiable, HasRoles;


  /**
   * Determine if the given user can access the Filament admin panel.
   *
   * @param  \Filament\Panel  $panel
   * @return bool
   */
  public function canAccessPanel(Panel $panel): bool
  {
    return true;
  }

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'cellphone',
    'status',
    'password',
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

  public function setEmailAttribute($value)
  {
    $this->attributes['email'] = strtolower($value);
  }

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * Get the attributes that should be cast.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  /**
   * Relations with other models.
   */

  public function boxes()
  {
    return $this->hasMany(Box::class);
  }

  public function sales()
  {
    return $this->hasMany(Sale::class);
  }

  public function credits()
  {
    return $this->hasMany(Credit::class);
  }

  public function losses()
  {
    return $this->hasMany(Loss::class);
  }

  public function expenses()
  {
    return $this->hasMany(Expense::class);
  }
}
