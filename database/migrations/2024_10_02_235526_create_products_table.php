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
    Schema::create('products', function (Blueprint $table) {
      $table->id();
      $table->string('sku')->unique();
      $table->string('name');
      $table->string('category');
      $table->decimal('purchase_price', 10, 2);
      $table->decimal('sale_price', 10, 2);
      $table->decimal('iva', 10, 2);
      $table->integer('stock');
      $table->integer('min_stock');
      $table->string('status')->default('Disponible');
      // Campos comunes para appliances y housewares
      $table->string('brand')->nullable();
      $table->string('color')->nullable();
      $table->string('discount_applicable');
      $table->decimal('discount_amount', 10, 2);
      // Campos exclusivos de electrodomésticos
      $table->string('serial_number')->unique()->nullable();
      $table->string('model')->nullable();
      $table->integer('warranty')->nullable();
      $table->date('purchase_date')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('products');
  }
};
