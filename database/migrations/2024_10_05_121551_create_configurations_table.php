<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('configurations', function (Blueprint $table) {
      $table->id();
      $table->string('document_type');
      $table->string('document_number');
      $table->string('legal_representative');
      $table->string('name');
      $table->string('email');
      $table->string('cellphone');
      $table->string('address');
      $table->string('image')->nullable();
      $table->text('slogan');
      $table->timestamps();
    });

    // Insert default configuration
    DB::table('configurations')->insert([
      'document_type' => 'NIT',
      'document_number' => '22222222222-2',
      'legal_representative' => 'NOMBRE DEL REPRESENTANTE LEGAL',
      'name' => 'NOMBRE DE LA EMPRESA',
      'email' => 'correo@ejemplo.com',
      'cellphone' => '0000000000',
      'address' => 'DIRECCIÓN DE LA EMPRESA',
      'slogan' => 'ESLOGAN DE LA EMPRESA',
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('configurations');
  }
};
