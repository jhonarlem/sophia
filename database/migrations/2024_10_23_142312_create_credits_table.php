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
    Schema::create('credits', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sale_id')->constrained('sales');
      $table->foreignId('user_id')->constrained('users');
      $table->decimal('total_credit', 10, 2);
      $table->decimal('total_paid', 10, 2)->default(0);
      $table->decimal('total_pending', 10, 2);
      $table->string('status')->default('Pendiente');
      $table->timestamp('credit_date');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('credits');
  }
};
