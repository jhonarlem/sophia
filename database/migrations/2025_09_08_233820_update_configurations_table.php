<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('configurations', function (Blueprint $table) {
      Schema::table('configurations', function (Blueprint $table) {
        // Renombrar la columna 'name' a 'company'.
        $table->renameColumn('name', 'company');

        // Renombrar la columna 'address' a 'street'.
        $table->renameColumn('address', 'street')->after('cellphone');

        // Agregar las nuevas columnas en la posición correcta.
        $table->integer('dv')->after('document_number');
        $table->string('city')->after('street');
        $table->string('department')->after('city');
        $table->string('regime');

        // Modificar la columna 'slogan' para que sea nullable.
        $table->text('slogan')->nullable()->change();

        // Hacer que la columna created_at vaya despues de regime
        $table->timestamp('created_at')->after('regime')->change();
      });

      // Insertar la configuración por defecto después de la modificación de la tabla.
      DB::table('configurations')->update([
        'dv' => 0,
        'city' => 'CIUDAD O MUNICIPIO',
        'department' => 'DEPARTAMENTO',
        'regime' => ' NO RESPONSABLE DE IVA PERSONA NATURAL',
        'updated_at' => now(),
      ]);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('configurations', function (Blueprint $table) {
      // Revertir la columna 'slogan' a no nullable.
      $table->text('slogan')->change();

      // Eliminar las columnas que agregamos en 'up()'.
      $table->dropColumn(['dv', 'street', 'city', 'department', 'regime']);

      // Renombrar la columna 'company' de nuevo a 'name'.
      $table->renameColumn('company', 'name');

      // Renombrar la columna 'street' de nuevo a 'address'.
      $table->renameColumn('street', 'address');
    });
  }
};
