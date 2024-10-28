<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
  return redirect('/dashboard');
});


Route::get('/dashboard/ventas/recibo/{record}', function () {
  return view('vendor.filament.resources.sales.receipt');
})->name('recibo');

Route::get('/dashboard/ventas/factura/{record}', function () {
  return view('vendor.filament.resources.sales.invoice');
})->name('factura');
