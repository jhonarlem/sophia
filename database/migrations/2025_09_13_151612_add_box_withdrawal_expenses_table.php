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
    Schema::table('expenses', function (Blueprint $table) {
      $table->boolean('box_withdrawal')->default(false)->after('payment_method');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('expenses', function (Blueprint $table) {
      $table->dropColumn('box_withdrawal');
    });
  }
};
