<?php

namespace App\Http\Controllers;

use App\Models\Box;
use App\Models\Configuration;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReceiptBoxController extends Controller
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

    $company = Configuration::first();
    $box = Box::where('id', $id)->first();

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

    // Información de la caja
    $infoBox = (object) [
      'id' => $box->id,
      'name' => $box->name,
      'resolution' => $box->resolution->number,
      'baseAmount' => number_format($box->base_amount, 2, ',', '.'),
      'cashPayment' => number_format($box->cash_payment, 2, ',', '.'),
      'transferPayment' => number_format($box->transfer_payment, 2, ',', '.'),
      'creditSales' => number_format($box->credit_sales, 2, ',', '.'),
      'totalSales' => number_format($box->total_sales + $box->transfer_payment + $box->credit_sales, 2, ',', '.'),
      'realAmount' => number_format($box->real_amount, 2, ',', '.'),
      'difference' => number_format($box->difference, 2, ',', '.'),
      'status' => $box->status,
      'observations' => $box->observations,
      'openedBy' => $box->openedBy->name,
      'closedBy' => $box->closedBy->name ?? null,
      'openedAt' => Carbon::parse($box->opened_at)->format('d/m/Y h:i A'),
      'closedAt' => $box->closed_at ? Carbon::parse($box->closed_at)->format('d/m/Y h:i A') : null,

    ];

    // Retornar la vista con los datos
    return view('vendor.filament.resources.sales.receiptbox', [
      'company' => $infoCompany,
      'box' => $infoBox,
    ]);
  }
}
