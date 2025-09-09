<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Configuration;
use App\Models\Credit;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceController extends Controller
{
  public function getDataInvoice($id)
  {
    // Verificar si hay usuario autenticado
    if (!Auth::check()) {
      return redirect()->route('filament.dashboard.auth.login');
    }
    // Verificar si es un id válido sino retornar 404
    if (!is_numeric($id)) {
      abort(404);
    }

    $data = $this->prepareInvoiceData($id);

    // Generar el PDF con los datos
    $pdf = app(DomPDFPDF::class)->loadView('vendor.filament.resources.sales.invoice', $data)
      ->setOption([
        'fontDir' => public_path('/fonts'),
        'fontCache' => public_path('/fonts'),
        'defaultFont' => 'Sora',
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true,
        'isPhpEnabled' => false,
      ])->stream('Factura-' . $data['resolution']->prefix . '-' . $data['sale']->invoiceNumber . '.pdf');

    return $pdf;
  }

  public function sendInvoiceEmail($id)
  {
    // Verificar si hay usuario autenticado
    if (!Auth::check()) {
      return redirect()->route('filament.dashboard.auth.login');
    }
    // Verificar si el id es numérico
    if (!is_numeric($id)) {
      abort(404);
    }

    $data = $this->prepareInvoiceData($id);

    // Verificar si el cliente tiene el id 1 (Cliente genérico) mandar error para que el try catch lo capture
    if ($data['customer']->id === 1) {
      throw ValidationException::withMessages([
        'message' => 'No es posible enviar la factura al cliente PÚBLICO GENERAL',
      ]);
    }

    // Verificar que el correo del cliente no sea no termine en @notiene.com
    if (strpos($data['customer']->email, '@notiene.com') !== false) {
      throw ValidationException::withMessages([
        'message' => 'El correo electrónico del cliente no es válido',
      ]);
    }

    // Generar el PDF con los datos
    $pdf = app(DomPDFPDF::class)->loadView('vendor.filament.resources.sales.invoice', $data)
      ->setOption([
        'fontDir' => public_path('/fonts'),
        'fontCache' => public_path('/fonts'),
        'defaultFont' => 'Sora',
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled' => true,
        'isPhpEnabled' => false,
      ])->output();

    // Enviar el correo adjuntando el PDF
    Mail::to($data['customer']->email)->send(new InvoiceMail($data, $pdf));
    return;
  }

  private function prepareInvoiceData($id)
  {
    $sale = Sale::findOrFail($id);
    $company = Configuration::first();
    $salesItems = SaleItem::where('sale_id', $id)->get();
    $credit = Credit::where('sale_id', $id)->first();

    // Obtener la ruta completa de la imagen
    $logoPath = storage_path('app/public/' . $company->image);

    // Convertir la imagen a Base64 si existe
    $logoBase64 = null;
    if ($company->image && file_exists($logoPath)) {
      $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

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
      'logo' => $logoBase64,
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
      'id' => $sale->customer->id,
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
        'product' => $item->product->category !== 'Hogar'
          ? "{$item->product->name} - SERIAL: {$item->product->serial_number} - MARCA: {$item->product->brand} - MODELO: {$item->product->model} - COLOR: {$item->product->color}"
          : $item->product->name,
        'quantity' => $item->quantity,
        'price' => number_format($item->unit_price, 0, ',', '.'),
        'discount' => number_format($item->discount, 0, ',', '.'),
        'iva' => $item->product->iva,
        'subtotal' => number_format($item->subtotal, 0, ',', '.'),
        'total' => number_format($item->unit_price * $item->quantity, 0, ',', '.'),
      ];
    });

    return [
      'company' => $infoCompany,
      'resolution' => $resolutionInfo,
      'customer' => $infoCustomer,
      'sale' => $infoSale,
      'items' => $infoItems,
    ];
  }
}
