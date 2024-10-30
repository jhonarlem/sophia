<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ReceiptBoxController;
use App\Http\Controllers\ReceiptController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return redirect('/dashboard');
});


Route::get('/dashboard/ventas/recibo/{id}', [ReceiptController::class, 'getDataReceipt'])->name('recibo');
Route::get('/dashboard/ventas/factura/{id}', [InvoiceController::class, 'getDataInvoice'])->name('factura');
Route::get('/dashboard/caja/recibo/{id}', [ReceiptBoxController::class, 'getDataReceipt'])->name('recibo-caja');
