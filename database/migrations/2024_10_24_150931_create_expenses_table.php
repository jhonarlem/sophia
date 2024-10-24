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
    Schema::create('expenses', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users');
      $table->string('payee_name');
      $table->string('service_type');
      $table->decimal('amount_total', 10, 2);
      $table->decimal('amount_paid', 10, 2);
      $table->decimal('amount_pending', 10, 2);
      $table->string('status');
      $table->dateTime('due_date');
      $table->dateTime('paid_at')->nullable();
      $table->string('payment_method')->nullable();
      $table->text('description')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('expenses');
  }
};
