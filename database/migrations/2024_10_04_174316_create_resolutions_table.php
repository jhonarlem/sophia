<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('resolutions', function (Blueprint $table) {
      $table->id();
      $table->string('number');
      $table->string('start_invoice_number');
      $table->string('end_invoice_number');
      $table->date('start_date');
      $table->date('end_date');
      $table->integer('valid_months');
      $table->string('prefix');
      $table->text('description')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('resolutions');
  }
};
