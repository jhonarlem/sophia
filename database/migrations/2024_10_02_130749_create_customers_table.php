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
    Schema::create('customers', function (Blueprint $table) {
      $table->id();
      $table->string('document_type');
      $table->string('document_number')->unique();
      $table->string('name');
      $table->string('email');
      $table->string('cellphone')->nullable();
      $table->string('street')->nullable();
      $table->timestamps();
    });

    // Insert default customer "PÚBLICO GENERAL"
    DB::table('customers')->insert([
      'document_type' => 'CC',
      'document_number' => '22222222222',
      'name' => 'PÚBLICO GENERAL',
      'email' => 'notiene@notiene.com',
      'cellphone' => null,
      'street' => null,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('customers');
  }
};
