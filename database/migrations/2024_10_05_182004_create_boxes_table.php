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
    Schema::create('boxes', function (Blueprint $table) {
      $table->id();
      $table->string('name')->default('CAJA PRINCIPAL');
      $table->foreignId('resolution_id')->constrained('resolutions');
      $table->decimal('base_amount', 10, 2);
      $table->decimal('cash_payment', 10, 2)->default(0);
      $table->decimal('transfer_payment', 10, 2)->default(0);
      $table->decimal('credit_sales', 10, 2)->default(0);
      $table->decimal('total_sales', 10, 2)->default(0);
      $table->decimal('real_amount', 10, 2)->nullable()->default(0);
      $table->decimal('difference', 10, 2)->nullable()->default(0);
      $table->string('status')->default('Abierta');
      $table->text('observations')->nullable();
      $table->foreignId('opened_by')->constrained('users');
      $table->foreignId('closed_by')->nullable()->constrained('users');
      $table->timestamp('opened_at');
      $table->timestamp('closed_at')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('boxes');
  }
};
