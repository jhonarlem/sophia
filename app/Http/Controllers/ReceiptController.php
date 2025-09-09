<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Credit;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptController extends Controller
{
  public function getDataReceipt($id)
  {
    // Verificar si hay usuario autenticado
    if (!Auth::check()) {
      return redirect()->route('filament.dashboard.auth.login');
    }
    // Verificar si es un id válido sino retornar 404
    if (!is_numeric($id)) {
      abort(404);
    }

    $sale = Sale::findOrFail($id);
    $company = Configuration::first();
    $salesItems = SaleItem::where('sale_id', $id)->get();
    $credit = Credit::where('sale_id', $id)->first();

    // Información de la empresa
    $infoCompany = (object) [
      'document_type' => $company->document_type,
      'document_number' => $company->document_number,
      'dv' => $company->dv,
      'name' => $company->company,
      'legal_representative' => $company->legal_representative,
      'email' => $company->email,
      'cellphone' => $company->cellphone,
      'address' => $company->address,
      'logo' => $company->image ? asset('storage/' . $company->image) : null,
      'slogan' => $company->slogan,
      'regime' => $company->regime,
    ];

    // Información de la resolución
    $resolutionInfo = (object) [
      'number' => $sale->box->resolution->number,
      'startInvoice' => number_format($sale->box->resolution->start_invoice_number, 0, ',', '.'),
      'endInvoice' => number_format($sale->box->resolution->end_invoice_number, 0, ',', '.'),
      'startDate' => Carbon::parse($sale->box->resolution->start_date)->format('d/m/Y'),
      'validMonths' => $sale->box->resolution->valid_months,
      'prefix' => $sale->box->resolution->prefix,
    ];

    // Información del cliente
    $infoCustomer = (object) [
      'documentType' => $sale->customer->document_type,
      'documentNumber' => $sale->customer->document_number,
      'name' => $sale->customer->name,
      'email' => $sale->customer->email,
      'cellphone' => $sale->customer->cellphone,
    ];

    // Información de la venta
    $infoSale = (object) [
      'id' => $sale->id,
      'box' => $sale->box->name,
      'user' => $sale->user->name,
      'invoiceNumber' => $sale->invoice_number,
      'totalAmount' => number_format($sale->total_amount, 2, ',', '.'),
      'subtotal' => number_format($sale->total_amount + $sale->total_discount - $sale->total_iva, 2, ',', '.'),
      'totalIva' => number_format($sale->total_iva, 2, ',', '.'),
      'totalDiscount' => number_format($sale->total_discount, 2, ',', '.'),
      'totalPending' => number_format($credit->total_pending ?? 0, 2, ',', '.'),
      'paymentMethod' => $sale->payment_method,
      'received' => number_format($sale->received, 2, ',', '.'),
      'exchange' => number_format($sale->exchange, 2, ',', '.'),
      'status' => $sale->status,
      'saleDate' => Carbon::parse($sale->sale_date)->format('d/m/Y h:i A'),
    ];

    // Información de los items de la venta
    $infoItems = collect($salesItems)->map(function ($item) {
      return (object) [
        'product' => $item->product->name,
        'quantity' => $item->quantity,
        'price' => number_format($item->unit_price, 0, ',', '.'),
        'discount' => number_format($item->discount, 0, ',', '.'),
        'iva' => $item->product->iva,
        'subtotal' => number_format($item->subtotal, 0, ',', '.'),
        'total' => number_format($item->unit_price * $item->quantity, 0, ',', '.'),
      ];
    });

    // Retornar la vista con los datos
    return view('vendor.filament.resources.sales.receipt', [
      'company' => $infoCompany,
      'resolution' => $resolutionInfo,
      'customer' => $infoCustomer,
      'sale' => $infoSale,
      'items' => $infoItems,
    ]);
  }
}
