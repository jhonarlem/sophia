<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Credit;
use App\Models\Payment;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptCreditController extends Controller
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

    $credit = Credit::findOrFail($id);
    $sale = Sale::findOrFail($credit->sale_id);
    $company = Configuration::first();
    $payments = Payment::where('sale_id', $credit->sale_id)->get();

    // Información de la empresa
    $infoCompany = (object) [
      'document_type' => $company->document_type,
      'document_number' => $company->document_number,
      'name' => $company->name,
      'legal_representative' => $company->legal_representative,
      'email' => $company->email,
      'cellphone' => $company->cellphone,
      'address' => $company->address,
      'logo' => $company->image ? asset('storage/' . $company->image) : null,
      'slogan' => $company->slogan,
    ];

    // Información de la resolución
    $resolutionInfo = (object) [
      'number' => $sale->box->resolution->number,
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
      'invoiceNumber' => $sale->invoice_number,
    ];

    // Información del crédito
    $infoCredit = (object)[
      'id' => $credit->id,
      'totalCredit' => number_format($credit->total_credit, 2, ',', '.'),
      'totalPaid' => number_format($credit->total_paid, 2, ',', '.'),
      'totalPending' => number_format($credit->total_pending, 2, ',', '.'),
      'status' => $credit->status,
      'creditDate' => Carbon::parse($credit->credit_date)->format('d/m/Y h:i A'),
    ];

    // Informacion de los payments
    $infoPayments = collect($payments)->map(function ($payment) {
      return (object) [
        'amount' => number_format($payment->amount, 0, ',', '.'),
        'paymentMethod' => $payment->payment_method,
        'reference' => $payment->reference,
        'paymentDate' => Carbon::parse($payment->payment_date)->format('d/m/Y '),
      ];
    });

    // Retornar el ultimo pago
    $lastPayment = (object) [
      'amount' => number_format($payments->last()->amount, 2, ',', '.'),
      'paymentMethod' => $payments->last()->payment_method,
      'reference' => $payments->last()->reference,
      'paymentDate' => Carbon::parse($payments->last()->payment_date)->format('d/m/Y'),
    ];

    // Retornar la vista con los datos
    return view('vendor.filament.resources.sales.receiptcredit', [
      'company' => $infoCompany,
      'resolution' => $resolutionInfo,
      'customer' => $infoCustomer,
      'sale' => $infoSale,
      'credit' => $infoCredit,
      'payments' => $infoPayments,
      'lastPayment' => $lastPayment,
    ]);
  }
}
