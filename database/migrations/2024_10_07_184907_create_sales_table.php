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
    Schema::create('sales', function (Blueprint $table) {
      $table->id();
      $table->foreignId('box_id')->constrained('boxes');
      $table->foreignId('customer_id')->constrained('customers');
      $table->foreignId('user_id')->constrained('users');
      $table->string('invoice_number');
      $table->decimal('total_amount', 10, 2);
      $table->decimal('total_iva', 10, 2);
      $table->decimal('total_discount', 10, 2);
      $table->string('payment_method');
      $table->string('status');
      $table->timestamp('sale_date')->default(now());
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('sales');
  }
};
